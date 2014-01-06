<?php
/**
 * Flexpage Activity Block
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see http://opensource.org/licenses/gpl-3.0.html.
 *
 * @copyright Copyright (c) 2009 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license http://opensource.org/licenses/gpl-3.0.html GNU Public License
 * @package block_flexpagemod
 * @author Mark Nielsen
 */

/**
 * Block instance editing form
 *
 * @author Mark Nielsen
 * @package block_flexpagemod
 */
class block_flexpagemod_edit_form extends block_edit_form {
    /**
     * Add block specific configuration elements
     * @param MoodleQuickForm $mform
     */
    protected function specific_definition($mform) {
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