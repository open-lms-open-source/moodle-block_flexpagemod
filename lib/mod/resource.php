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
 * Display mod/resource
 *
 * @author Mark Nielsen
 * @package block_flexpagemod
 */
class block_flexpagemod_lib_mod_resource extends block_flexpagemod_lib_mod {
    /**
     * Pretty much copied everything from mod/resource/view.php and resource_display_embed()
     *
     * @return void
     */
    public function module_block_setup() {
        global $CFG, $COURSE, $DB;

        $cm       = $this->get_cm();
        $resource = $DB->get_record('resource', array('id' => $cm->instance));
        $context  = context_module::instance($cm->id);
        if ($resource and has_capability('mod/resource:view', $context) and !$resource->tobemigrated) {
            $files = get_file_storage()->get_area_files($context->id, 'mod_resource', 'content', 0, 'sortorder DESC, id ASC', false);
            if (count($files) >= 1) {
                require_once($CFG->dirroot.'/mod/resource/locallib.php');
                require_once($CFG->libdir . '/completionlib.php');

                add_to_log($COURSE->id, 'resource', 'view', 'view.php?id='.$cm->id, $resource->id, $cm->id);

                // Update 'viewed' state if required by completion system
                $completion = new completion_info($COURSE);
                $completion->set_module_viewed($cm);

                $file = reset($files);
                unset($files);

                $resource->mainfile = $file->get_filename();
                $displaytype = resource_get_final_display_type($resource);

                ob_start();
                if ($displaytype == RESOURCELIB_DISPLAY_EMBED ) {
                    resource_display_embed($resource, $cm, $COURSE, $file, false);
                } else {
                    resource_print_workaround($resource, $cm, $COURSE, $file, false);
                }
                $this->append_content(ob_get_contents());
                ob_end_clean();
            }
        }
    }
}