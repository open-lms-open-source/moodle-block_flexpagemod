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
 * Display mod/forum
 *
 * @author Mark Nielsen
 * @package format_flexpage
 */
class block_flexpagemod_lib_mod_forum extends block_flexpagemod_lib_mod {
    public function module_block_setup() {
        global $CFG, $COURSE, $DB, $OUTPUT;

        $cm      = $this->get_cm();
        $forum   = $DB->get_record('forum', array('id' => $cm->instance));
        $context = get_context_instance(CONTEXT_MODULE, $cm->id);

        if ($forum and has_capability('mod/forum:viewdiscussion', $context)) {
            if (trim($forum->intro) != '') {
                $options = new stdClass();
                $options->para = false;
                $introcontent = format_module_intro('forum', $forum, $cm->id);

                $this->append_content($OUTPUT->box($introcontent, 'generalbox', 'intro'));
            }
            ob_start();
            forum_print_latest_discussions($COURSE, $forum);
            $this->append_content(ob_get_contents());
            ob_end_clean();

            add_to_log($COURSE->id, "forum", "view forum", "view.php?id=$cm->id", "$forum->id", $cm->id);

            require_once($CFG->libdir . '/completionlib.php');
            $completion = new completion_info($COURSE);
            $completion->set_module_viewed($cm);
        }
    }
}