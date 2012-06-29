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
 * @package format_flexpage
 */
class block_flexpagemod_lib_mod_url extends block_flexpagemod_lib_mod {
    /**
     * Pretty much copied everything from mod/url/view.php and url_display_embed()
     *
     * @return void
     */
    public function module_block_setup() {
        global $CFG, $COURSE, $DB;

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

            $link = html_writer::tag('a', $fullurl, array('href'=>str_replace('&amp;', '&', $fullurl)));
            $clicktoopen = get_string('clicktoopen', 'url', $link);

            $extension = resourcelib_get_extension($url->externalurl);

            if (in_array($mimetype, array('image/gif','image/jpeg','image/png'))) {  // It's an image
                $code = resourcelib_embed_image($fullurl, $title);

            } else if ($mimetype == 'audio/mp3') {
                // MP3 audio file
                $code = resourcelib_embed_mp3($fullurl, $title, $clicktoopen);

            } else if ($mimetype == 'video/x-flv' or $extension === 'f4v') {
                // Flash video file
                $code = resourcelib_embed_flashvideo($fullurl, $title, $clicktoopen);

            } else if ($mimetype == 'application/x-shockwave-flash') {
                // Flash file
                $code = resourcelib_embed_flash($fullurl, $title, $clicktoopen);

            } else if (substr($mimetype, 0, 10) == 'video/x-ms') {
                // Windows Media Player file
                $code = resourcelib_embed_mediaplayer($fullurl, $title, $clicktoopen);

            } else if ($mimetype == 'video/quicktime') {
                // Quicktime file
                $code = resourcelib_embed_quicktime($fullurl, $title, $clicktoopen);

            } else if ($mimetype == 'video/mpeg') {
                // Mpeg file
                $code = resourcelib_embed_mpeg($fullurl, $title, $clicktoopen);

            } else if ($mimetype == 'audio/x-pn-realaudio-plugin') {
                // RealMedia file
                $code = resourcelib_embed_real($fullurl, $title, $clicktoopen);

            } else {
                // anything else - just try object tag enlarged as much as possible
                // $code = resourcelib_embed_general($fullurl, $title, $clicktoopen, $mimetype);
                $this->default_block_setup();
                return;
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