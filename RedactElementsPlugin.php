<?php
/**
 * Redact Elements
 * 
 * @copyright Copyright 2007-2014 Roy Rosenzweig Center for History and New Media
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GNU GPLv3
 */

/**
 * Redact Elements plugin class
 * 
 * @package Omeka\Plugins\RedactElements
 */
class RedactElementsPlugin extends Omeka_Plugin_AbstractPlugin
{
    /**
     * Regular expression for an email address.
     *
     * @see http://www.regular-expressions.info/email.html
     */
    const REGEX_EMAIL = '\b[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,4}\b';

    /**
     * Regular expression for a URL.
     *
     * @see http://stackoverflow.com/questions/833469/regular-expression-for-url
     */
    const REGEX_URL = '\b((([A-Za-z]{3,9}:(?:\/\/)?)(?:[\-;:&=\+\$,\w]+@)?[A-Za-z0-9\.\-]+|(?:www\.|[\-;:&=\+\$,\w]+@)[A-Za-z0-9\.\-]+)((?:\/[\+~%\/\.\w\-_]*)?\??(?:[\-\+=&;%@\.\w_]*)#?(?:[\.\!\/\\\w]*))?)\b';

    /**
     * Regular expression for an IP address.
     *
     * @see http://stackoverflow.com/questions/106179/regular-expression-to-match-hostname-or-ip-address
     */
    const REGEX_IP = '\b(([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\.){3}([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\b';

    /**
     * @var array Plugin hooks
     */
    protected $_hooks = array(
        'install',
        'uninstall',
        'initialize',
        'config_form',
        'config',
        'after_delete_element',
        'define_acl',
    );

    /**
     * @var array Plugin filters
     */
    protected $_filters = array('admin_navigation_main');

    /**
     * Settings that are saved during plugin installation.
     *
     * Note that the patterns array is keyed with persistent identifiers. This
     * allows duplicate labels and regular expressions, and allows regular
     * expressions to be modified without unlinking an element from a pattern.
     *
     * @var array
     */
    protected $_defaultSettings = array(
        'overrides' => array('super'),
        'replacement' => '[REDACTED]',
        'patterns' => array(
            0 => array(
                'label' => 'Email Address',
                'regex' => self::REGEX_EMAIL,
            ),
            1 => array(
                'label' => 'URL',
                'regex' => self::REGEX_URL,
            ),
            2 => array(
                'label' => 'IP Address',
                'regex' => self::REGEX_IP,
            ),
        ),
        'elements' => array(),
    );

    /**
     * @var array Plugin settings cache
     */
    protected $_settings = array();

    /**
     * Install this plugin.
     */
    public function hookInstall()
    {
        set_option('redact_elements_settings', json_encode($this->_defaultSettings));
    }

    /**
     * Uninstall this plugin.
     */
    public function hookUninstall()
    {
        delete_option('redact_elements_settings');
    }

    /**
     * Initialize this plugin.
     */
    public function hookInitialize()
    {
        // Cache the plugin settings.
        $this->_settings = json_decode(get_option('redact_elements_settings'), true);

        // Override redactions for configured user roles.
        $currentUser = current_user();
        if ($currentUser instanceof User
            && in_array($currentUser->role, $this->_settings['overrides'])
        ) {
            return;
        }

        // Add element display filters.
        foreach ($this->_settings['elements'] as $elementId => $patternIds) {
            // Get the element name and element set name. A direct query on the
            // database is less intensive than getting the element record, which
            // is a good idea for a hook that runs on every request.
            $sql = "
            SELECT elements.name AS element_name, element_sets.name AS element_set_name
            FROM {$this->_db->Element} AS elements
            JOIN {$this->_db->ElementSet} AS element_sets
            ON elements.element_set_id = element_sets.id
            WHERE elements.id = ?";
            $names = $this->_db->query($sql, array($elementId))->fetch();

            // Don't add a filter for an nonexistent element.
            if (!$names) {
                continue;
            }

            // Add a filter for every native record type.
            foreach (array('Item', 'File', 'Collection') as $record) {
                add_filter(
                    array('Display', $record, $names['element_set_name'], $names['element_name']),
                    array($this, 'redactCallback')
                );
            }
        }
    }

    /**
     * Display the plugin configuration form.
     */
    public function hookConfigForm()
    {
        $settings = $this->_settings;
        $view = get_view();
        include 'config-form.php';
    }

    /**
     * Handle the plugin configuration form.
     *
     * @param array $args 
     */
    public function hookConfig($args)
    {
        $post = $args['post'];

        // Set the role overrides.
        $this->_settings['overrides'] = isset($post['overrides'])
            ? $post['overrides'] : array();

        // Set the replacement text.
        $this->_settings['replacement'] = $post['replacement'];

        // Process existing patterns.
        foreach ($post['regexs'] as $patternId => $regex) {
            if ('' == $regex) {
                // Remove deleted patterns.
                unset($this->_settings['patterns'][$patternId]);
                continue;
            }
            $this->_settings['patterns'][$patternId] = array(
                'label' => $post['labels'][$patternId],
                'regex' => $regex,
            );
        }

        // Add new patterns.
        foreach ($post['new-regexs'] as $key => $regex) {
            if ('' == $regex) {
                // Do not add a patterns without a regex.
                continue;
            }
            // Note how new patterns are appended to the patterns array. The
            // resulting key is n+1, where n is the highest existing key. This
            // becomes the new pattern's persistent identifier.
            $this->_settings['patterns'][] = array(
                'label' => $post['new-labels'][$key],
                'regex' => $regex,
            );
        }

        // Prepare the redacted elements.
        foreach ($this->_settings['elements'] as $elementId => $patternIds) {
            foreach ($patternIds as $key => $patternId) {
                if (!array_key_exists($patternId, $this->_settings['patterns'])) {
                    // Remove deleted patterns from all redacted elements.
                    unset($this->_settings['elements'][$elementId][$key]);
                }
                if (empty($this->_settings['elements'][$elementId])) {
                    // Remove elements that have no patterns.
                    unset($this->_settings['elements'][$elementId]);
                }
            }
        }

        set_option('redact_elements_settings', json_encode($this->_settings));
    }

    /**
     * Remove deleted elements from plugin settings.
     *
     * @param array $args
     */
    public function hookAfterDeleteElement($args)
    {
        $elementId = $args['record']->id;
        if (isset($this->_settings['elements'][$elementId])) {
            unset($this->_settings['elements'][$elementId]);
            set_option('redact_elements_settings', json_encode($this->_settings));
        }
    }

    /**
     * Define the RedactElements ACL resource.
     *
     * @param array $args
     */
    public function hookDefineAcl($args)
    {
        $acl = $args['acl'];
        $acl->addResource('RedactElements_Index');
    }

    /**
     * Add admin navigation.
     *
     * @param array $nav
     * @return array
     */
    public function filterAdminNavigationMain($nav)
    {
        $nav[] = array(
            'label' => 'Redact Elements',
            'uri' => url('redact-elements'),
            'resource' => 'RedactElements_Index',
        );
        return $nav;
    }

    /**
     * Redact element callback, called by the display element filter.
     *
     * @param string $text
     * @param array $args
     * @return string
     */
    public function redactCallback($text, $args)
    {
        $elementText = $args['element_text'];

        if (!$elementText) {
            // An element may not have text.
            return;
        }

        // Get the patterns by their pattern ID.
        $patterns = array();
        $patternIds = $this->_settings['elements'][$elementText->element_id];
        foreach ($patternIds as $patternId) {
            $patterns[] = $this->_settings['patterns'][$patternId]['regex'];
        }

        // Call the redact text view helper.
        return get_view()->redact($text, $patterns, $this->_settings['replacement']);
    }
}
