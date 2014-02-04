<?php
/**
 * Redact Elements
 * 
 * @copyright Copyright 2007-2014 Roy Rosenzweig Center for History and New Media
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GNU GPLv3
 */

/**
 * Redact Elements controller.
 * 
 * @package Omeka\Plugins\RedactElements
 */
class RedactElements_IndexController extends Omeka_Controller_AbstractActionController
{
    /**
     * Add and edit elements to redact.
     */
    public function indexAction()
    {
        $settings = json_decode(get_option('redact_elements_settings'), true);

        // Handle a form submission.
        if ($this->getRequest()->isPost()
            && isset($_POST['elements'])
            && is_array($_POST['elements'])
        ) {
            $elements = $_POST['elements'];
            foreach ($elements as $elementId => $patterns) {
                if (!is_array($patterns)) {
                    // Remove all elements that have no redactions.
                    unset($elements[$elementId]);
                }
            }
            $settings['elements'] = $elements;
            set_option('redact_elements_settings', json_encode($settings));
        }

        $this->view->settings = $settings;
        $this->view->select_elements = get_table_options('Element', null, array(
            'record_types' => array('All', 'Item', 'File', 'Collection'),
            'sort' => 'alphaBySet',
        ));
    }
}
