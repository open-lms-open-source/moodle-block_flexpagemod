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
 * Display mod/url
 *
 * @author Mark Nielsen
 * @package block_flexpagemod
 */
class block_flexpagemod_lib_mod_url extends block_flexpagemod_lib_mod {
    /**
     * Pretty much copied everything from mod/url/view.php and url_display_embed()
     *
     * @return void
     */
    public function module_block_setup() {
        global $CFG, $COURSE, $DB, $PAGE;

        $cm      = $this->get_cm();
        $url     = $DB->get_record('url', array('id' => $cm->instance));
        $context = get_context_instance(CONTEXT_MODULE, $cm->id);
        $course  = $COURSE;
        if ($url and has_capability('mod/url:view', $context)) {
            require_once($CFG->dirroot.'/mod/url/locallib.php');
            require_once($CFG->libdir . '/completionlib.php');

            add_to_log($course->id, 'url', 'view', 'view.php?id='.$cm->id, $url->id, $cm->id);

            // Update 'viewed' state if required by completion system
            $completion = new completion_info($course);
            $completion->set_module_viewed($cm);

            $mimetype = resourcelib_guess_url_mimetype($url->externalurl);
            $fullurl  = url_get_full_url($url, $cm, $course);
            $title    = $url->name;

            $link        = html_writer::tag('a', $fullurl, array('href'=> str_replace('&amp;', '&', $fullurl)));
            $clicktoopen = get_string('clicktoopen', 'url', $link);
            $moodleurl   = new moodle_url($fullurl);

            $extension = resourcelib_get_extension($url->externalurl);

            $mediarenderer = $PAGE->get_renderer('core', 'media');
            $embedoptions  = array(
                core_media::OPTION_TRUSTED => true,
                core_media::OPTION_BLOCK   => true
            );

            if (in_array($mimetype, array('image/gif', 'image/jpeg', 'image/png'))) { // It's an image
                $code = resourcelib_embed_image($fullurl, $title);

            } else if ($mediarenderer->can_embed_url($moodleurl, $embedoptions)) {
                // Media (audio/video) file.
                $code = $mediarenderer->embed_url($moodleurl, $title, 0, 0, $embedoptions);

            } else {
                // anything else - just try object tag enlarged as much as possible
                $code = resourcelib_embed_general($fullurl, $title, $clicktoopen, $mimetype);
            }

            ob_start();
            url_print_heading($url, $cm, $course);
            echo $code;
            url_print_intro($url, $cm, $course);
            $this->append_content(ob_get_contents());
            ob_end_clean();
        }
    }
}