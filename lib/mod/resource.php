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

                $params = array(
                    'context'  => $context,
                    'objectid' => $resource->id
                );
                $event  = \mod_resource\event\course_module_viewed::create($params);
                $event->add_record_snapshot('course_modules', $cm);
                $event->add_record_snapshot('course', $COURSE);
                $event->add_record_snapshot('resource', $resource);
                $event->trigger();

                // Update 'viewed' state if required by completion system
                $completion = new completion_info($COURSE);
                $completion->set_module_viewed($cm);

                $file = reset($files);
                unset($files);

                $resource->mainfile = $file->get_filename();
                $displaytype = resource_get_final_display_type($resource);

                ob_start();
                if ($displaytype == RESOURCELIB_DISPLAY_EMBED ) {
                    $this->resource_display_embed($resource, $cm, $COURSE, $file);
                } else {
                    resource_print_workaround($resource, $cm, $COURSE, $file, false);
                }
                $this->append_content(ob_get_contents());
                ob_end_clean();
            }
        }
    }

    /**
     * Copied from resource_display_embed and then modified
     * for Flexpage display.  See comments for edited parts.
     */
    protected function resource_display_embed($resource, $cm, $course, $file) {
        global $CFG, $PAGE, $OUTPUT;

        $clicktoopen = resource_get_clicktoopen($file, $resource->revision);

        $context   = context_module::instance($cm->id);
        $path      = '/'.$context->id.'/mod_resource/content/'.$resource->revision.$file->get_filepath().$file->get_filename();
        $fullurl   = file_encode_url($CFG->wwwroot.'/pluginfile.php', $path, false);
        $moodleurl = new moodle_url('/pluginfile.php'.$path);

        $mimetype = $file->get_mimetype();
        $title    = $resource->name;

        $extension = resourcelib_get_extension($file->get_filename());

        $mediarenderer = $PAGE->get_renderer('core', 'media');
        $embedoptions  = array(
            core_media::OPTION_TRUSTED => true,
            core_media::OPTION_BLOCK   => true,
        );

        if (file_mimetype_in_typegroup($mimetype, 'web_image')) { // It's an image
            $code = resourcelib_embed_image($fullurl, $title);

        } else if ($mimetype === 'application/pdf') {
            // PDF document
            // This doesn't work in flexpage.
            // $code = resourcelib_embed_pdf($fullurl, $title, $clicktoopen);

            $code = <<<EOT
<div class="resourcecontent resourcepdf">
  <object id="resourceobject" class="block_flexpagemod_object" data="$fullurl" type="application/pdf">
    <param name="wmode" value="opaque" />
    <param name="src" value="$fullurl" />
    $clicktoopen
  </object>
</div>
EOT;


        } else if ($mediarenderer->can_embed_url($moodleurl, $embedoptions)) {
            // Media (audio/video) file.
            $code = $mediarenderer->embed_url($moodleurl, $title, 0, 0, $embedoptions);

        } else {
            // This doesn't work in flexpage.
            // $code = resourcelib_embed_general($fullurl, $title, $clicktoopen, $mimetype);

            $code = html_writer::tag('iframe', $clicktoopen, array(
                'id'    => html_writer::random_id('modurl'),
                'src'   => $fullurl,
                'class' => 'block_flexpagemod_iframe',
            ));
            $code = html_writer::div($code, 'resourcecontent resourcegeneral');
        }

        // Not for Flexpage.
        // resource_print_header($resource, $cm, $course);
        resource_print_heading($resource, $cm, $course);

        echo $code;

        resource_print_intro($resource, $cm, $course);

        // Not for Flexpage.
        // echo $OUTPUT->footer();
        // die;
    }
}