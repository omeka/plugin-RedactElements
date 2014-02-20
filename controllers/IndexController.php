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
        if ($this->getRequest()->isPost()) {
            if (!isset($_POST['elements'])) {
                $settings['elements'] = array();
            } else {
                $elements = $_POST['elements'];
                foreach ($elements as $elementId => $patternIds) {
                    if (!is_array($patternIds)) {
                        // Remove elements that have no redactions.
                        unset($elements[$elementId]);
                        continue;
                    }
                }
                $settings['elements'] = $elements;
            }
            set_option('redact_elements_settings', json_encode($settings));
        }

        $elementData = array();
        foreach ($settings['elements'] as $elementId => $patternIds) {
            $element = $this->_helper->db->getTable('Element')->find($elementId);
            $elementData[$elementId] = array(
                'element_name' => $element->name,
                'element_set_name' => $element->set_name,
            );
        }

        $this->view->settings = $settings;
        $this->view->element_data = $elementData;
        $this->view->select_elements = get_table_options('Element', null, array(
            'record_types' => array('All', 'Item', 'File', 'Collection'),
            'sort' => 'alphaBySet',
        ));
    }
}
