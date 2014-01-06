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
 * Flexpage Activity Block
 *
 * @author Mark Nielsen
 * @package block_flexpagemod
 */
class block_flexpagemod extends block_base {
    /**
     * Block init
     */
    function init() {
        $this->title = get_string('pluginname', 'block_flexpagemod');
    }

    /**
     * Block contents
     */
    function get_content() {
        global $CFG;

        if ($this->content !== NULL) {
            return $this->content;
        }

        require_once($CFG->dirroot.'/blocks/flexpagemod/lib/mod.php');

        $this->content = new stdClass;
        $this->content->text = '';
        $this->content->footer = '';

        if (empty($this->config->cmid)) {
            return $this->content;
        }

        try {
            $modinfo = get_fast_modinfo($this->page->course);
            $cm      = $modinfo->get_cm($this->config->cmid);

            $mod = block_flexpagemod_lib_mod::factory($cm, $this);
            $mod->setup_block();

        } catch (moodle_exception $e) {
            if (has_capability('moodle/course:manageactivities', $this->page->context)) {
                $this->content->text = html_writer::tag(
                    'div',
                    get_string('cmdisplayerror', 'block_flexpagemod', $e->getMessage()),
                    array('class' => 'block_flexpagemod_error')
                );
            }
        }
        return $this->content;
    }

    /**
     * Only if the user can manage activities
     *
     * @param moodle_page $page
     * @return bool
     */
    function user_can_addto($page) {
        if($page->course->format == 'flexpage'){
            return has_capability('moodle/course:manageactivities', $page->context);
        } else {
            return false;
        }
    }

    /**
     * Only if the user can manage activities
     *
     * @return bool
     */
    function user_can_edit() {
        return has_capability('moodle/course:manageactivities', $this->page->context);
    }

    /**
     * Prevent docking
     *
     * @return bool
     */
    public function instance_can_be_docked() {
        return false;
    }

    /**
     * No header display
     *
     * @return bool
     */
    function hide_header() {
        return $this->page->theme->name !== 'mymobile';
    }

    /**
     * Allow multiples
     *
     * @return bool
     */
    function instance_allow_multiple() {
        return true;
    }

    /**
     * Add another class to help distinguish between activities
     *
     * @return array
     */
    function html_attributes() {
        $attributes = parent::html_attributes();

        if (!empty($this->config->cmid)) {
            try {
                $modinfo = get_fast_modinfo($this->page->course);
                $cm      = $modinfo->get_cm($this->config->cmid);
                $attributes['class'] .= ' block_flexpagemod_'.$cm->modname;
            } catch (Exception $e) {
                $attributes['class'] .= ' block_flexpagemod_unknown';
            }
        }
        return $attributes;
    }

    /**
     * Have to override so the cmid can be saved to table
     */
    function instance_config_save($data, $nolongerused = false) {
        $this->save_cmid($data->cmid);
        parent::instance_config_save($data, $nolongerused);
    }

    /**
     * Save course module ID to table.  Needed for queries.
     *
     * @param int $cmid
     * @return void
     */
    function save_cmid($cmid) {
        global $DB;

        if ($id = $DB->get_field('block_flexpagemod', 'id', array('instanceid' => $this->instance->id))) {
            $DB->set_field('block_flexpagemod', 'cmid', $cmid, array('id' => $id));
        } else {
            $DB->insert_record('block_flexpagemod', (object) array(
                'instanceid' => $this->instance->id,
                'cmid' => $cmid,
            ));
        }
    }

    /**
     * A way to associate a new instance with a cmid via session
     *
     * @return boolean
     */
    function instance_create() {
        global $SESSION;

        if (!empty($SESSION->block_flexpagemod_create_cmids)) {
            $cmid = array_shift($SESSION->block_flexpagemod_create_cmids);
            if (!empty($cmid)) {
                $this->instance_config_save((object) array('cmid' => $cmid));
            }
            if (empty($SESSION->block_flexpagemod_create_cmids)) {
                unset($SESSION->block_flexpagemod_create_cmids);
            }
        }
        return true;
    }

    /**
     * Clean table
     *
     * @return bool
     */
    function instance_delete() {
        global $DB;

        $DB->delete_records('block_flexpagemod', array('instanceid' => $this->instance->id));

        return true;
    }
}