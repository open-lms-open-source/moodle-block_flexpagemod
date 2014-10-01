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
 * Display mod/folder
 *
 * @author Mark Nielsen
 * @package block_flexpagemod
 */
class block_flexpagemod_lib_mod_folder extends block_flexpagemod_lib_mod {
    /**
     * Pretty much copied everything from mod/folder/view.php
     *
     * @return void
     */
    public function module_block_setup() {
        global $CFG, $COURSE, $DB, $PAGE, $OUTPUT;

        $cm      = $this->get_cm();
        $folder  = $DB->get_record('folder', array('id' => $cm->instance));
        $context = context_module::instance($cm->id);
        $course  = $COURSE;
        if ($folder and has_capability('mod/folder:view', $context)) {
            $params = array(
                'context'  => $context,
                'objectid' => $folder->id
            );
            $event  = \mod_folder\event\course_module_viewed::create($params);
            $event->add_record_snapshot('course_modules', $cm);
            $event->add_record_snapshot('course', $course);
            $event->add_record_snapshot('folder', $folder);
            $event->trigger();

            // Update 'viewed' state if required by completion system
            require_once($CFG->libdir . '/completionlib.php');
            $completion = new completion_info($course);
            $completion->set_module_viewed($cm);

            /** @var $output mod_folder_renderer */
            $output = $PAGE->get_renderer('mod_folder');

            ob_start();
            echo $OUTPUT->heading(format_string($folder->name), 2);

            if (trim($folder->intro)) {
                echo $OUTPUT->box(format_module_intro('folder', $folder, $cm->id),
                    'generalbox', 'intro');
            }

            echo $OUTPUT->box_start('generalbox foldertree');
            echo $output->render(new folder_tree($folder, $cm));
            echo $OUTPUT->box_end();

            if (has_capability('mod/folder:managefiles', $context)) {
                echo $OUTPUT->container_start('mdl-align');
                echo $OUTPUT->single_button(new moodle_url('/mod/folder/edit.php', array('id' => $cm->id)), get_string('edit'), 'mdl-align folder-edit-button');
                echo $OUTPUT->container_end();
            }
            $this->append_content(ob_get_contents());
            ob_end_clean();
        }
    }
}