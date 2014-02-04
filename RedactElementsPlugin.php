<?php
class RedactElementsPlugin extends Omeka_Plugin_AbstractPlugin
{
    /**
     * @see http://www.regular-expressions.info/email.html
     */
    const PATTERN_EMAIL = '\b[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,4}\b';

    /**
     * @see http://stackoverflow.com/questions/833469/regular-expression-for-url
     */
    const PATTERN_URL = '\b((([A-Za-z]{3,9}:(?:\/\/)?)(?:[\-;:&=\+\$,\w]+@)?[A-Za-z0-9\.\-]+|(?:www\.|[\-;:&=\+\$,\w]+@)[A-Za-z0-9\.\-]+)((?:\/[\+~%\/\.\w\-_]*)?\??(?:[\-\+=&;%@\.\w_]*)#?(?:[\.\!\/\\\w]*))?)\b';

    /**
     * @see http://stackoverflow.com/questions/106179/regular-expression-to-match-hostname-or-ip-address
     */
    const PATTERN_IP = '\b(([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\.){3}([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\b';

    protected $_hooks = array(
        'install',
        'uninstall',
        'initialize',
        'config_form',
        'config',
        'after_delete_element',
    );

    protected $_filters = array('admin_navigation_main');

    protected $_defaultSettings = array(
        'overrides' => array(),
        'replacement' => '[REDACTED]',
        'patterns' => array(
            self::PATTERN_EMAIL => 'Email Address',
            self::PATTERN_URL => 'URL',
            self::PATTERN_IP => 'IP Address',
        ),
        'elements' => array(),
    );

    protected $_settings = array();

    public function hookInstall()
    {
        set_option('redact_elements_settings', json_encode($this->_defaultSettings));
    }

    public function hookUninstall()
    {
        delete_option('redact_elements_settings');
    }

    public function hookInitialize()
    {
        $this->_settings = json_decode(get_option('redact_elements_settings'), true);

        // Override redactions for configured user roles.
        if (in_array(current_user()->role, $this->_settings['overrides'])) {
            return;
        }

        foreach ($this->_settings['elements'] as $elementId => $patterns) {
            $sql = "
            SELECT elements.name AS element_name, element_sets.name AS element_set_name
            FROM {$this->_db->Element} AS elements
            JOIN {$this->_db->ElementSet} AS element_sets
            WHERE elements.id = ?";
            $names = $this->_db->query($sql, array($elementId))->fetch();

            // Don't add a filter for an invalid element.
            if (!$names) {
                continue;
            }

            foreach (array('Item', 'File', 'Collection') as $record) {
                add_filter(
                    array('Display', $record, $names['element_set_name'], $names['element_name']),
                    array($this, 'redactCallback')
                );
            }
        }
    }

    public function hookConfigForm()
    {
        $settings = $this->_settings;
        $view = get_view();
        include 'config-form.php';
    }

    public function hookConfig($args)
    {
        $post = $args['post'];

        // Set the role overrides.
        $this->_settings['overrides'] = isset($post['overrides'])
            ? $post['overrides'] : array();

        // Set the replacement text.
        $this->_settings['replacement'] = $post['replacement'];

        // Set the patterns.
        $patterns = array();
        foreach ($post['regexs'] as $key => $regex) {
            if ('' == $regex) {
                // Delete the pattern.
                continue;
            }
            $patterns[$regex] = $post['labels'][$key];
        }
        $this->_settings['patterns'] = $patterns;

        // Prepare the redacted elements.
        foreach ($this->_settings['elements'] as $elementId => $patterns) {
            foreach ($patterns as $patternKey => $pattern) {
                if (!array_key_exists($pattern, $this->_settings['patterns'])) {
                    // Remove deleted patterns from all redacted elements.
                    unset($this->_settings['elements'][$elementId][$patternKey]);
                }
            }
            if (empty($this->_settings['elements'][$elementId])) {
                // Remove all elements that have no redactions.
                unset($this->_settings['elements'][$elementId]);
            }
        }

        set_option('redact_elements_settings', json_encode($this->_settings));
    }

    public function hookAfterDeleteElement($args)
    {
        $elementId = $args['record']->id;
        if (isset($this->_settings['elements'][$elementId])) {
            // Remove the element from the settings.
            unset($this->_settings['elements'][$elementId]);
            set_option('redact_elements_settings', json_encode($this->_settings));
        }
    }

    public function filterAdminNavigationMain($nav)
    {
        $nav[] = array(
            'label' => 'Redact Elements',
            'uri' => url('redact-elements')
        );
        return $nav;
    }

    public function redactCallback($text, $args)
    {
        if (!$args['element_text']) {
            // An element may not have text.
            return;
        }
        return get_view()->redact(
            $text,
            $this->_settings['elements'][$args['element_text']->element_id],
            $this->_settings['replacement']
        );
    }
}
