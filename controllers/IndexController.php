<?php
class RedactElements_IndexController extends Omeka_Controller_AbstractActionController
{
    public function indexAction()
    {
        $settings = json_decode(get_option('redact_elements_settings'), true);

        if ($this->getRequest()->isPost() && is_array($_POST['elements'])) {
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
