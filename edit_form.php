<?php
/**
 * Block instance editing form
 *
 * @author Mark Nielsen
 * @package format_flexpage
 */
class block_flexpagemod_edit_form extends block_edit_form {
    /**
     * Add block specific configuration elements
     */
    protected function specific_definition($mform) {
        $mform     = $this->_form;
        $modinfo   = get_fast_modinfo($this->page->course);
        $optgroups = array();
        foreach ($modinfo->get_instances() as $module => $instances) {
            $options = array();
            foreach ($instances as $instance) {
                $options[$instance->id] = $instance->name;
            }
            natcasesort($options);

            $optgroups[get_string('modulenameplural', $module)] = $options;
        }
        ksort($optgroups);

        $mform->addElement('header', 'configheader', get_string('blocksettings', 'block'));
        $mform->addElement('selectgroups', 'config_cmid', get_string('displayactivity', 'block_flexpagemod'), $optgroups);
        $mform->addHelpButton('config_cmid', 'displayactivity', 'block_flexpagemod');
    }
}