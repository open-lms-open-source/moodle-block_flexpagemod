<?php
/**
 * Display mod/resource
 *
 * @author Mark Nielsen
 * @package format_flexpage
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
        $context  = get_context_instance(CONTEXT_MODULE, $cm->id);
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

                // This is copied from resource_display_embed() function
                $clicktoopen = resource_get_clicktoopen($file, $resource->revision);

                $path = '/'.$context->id.'/mod_resource/content/'.$resource->revision.$file->get_filepath().$file->get_filename();
                $fullurl = file_encode_url($CFG->wwwroot.'/pluginfile.php', $path, false);

                $mimetype = $file->get_mimetype();
                $title    = $resource->name;

                $extension = resourcelib_get_extension($file->get_filename());

                if (in_array($mimetype, array('image/gif','image/jpeg','image/png'))) {  // It's an image
                    $code = resourcelib_embed_image($fullurl, $title);

                } else if ($mimetype === 'application/pdf') {
                    // PDF document
                    $code = resourcelib_embed_pdf($fullurl, $title, $clicktoopen);

                } else if ($mimetype === 'audio/mp3') {
                    // MP3 audio file
                    $code = resourcelib_embed_mp3($fullurl, $title, $clicktoopen);

                } else if ($mimetype === 'video/x-flv' or $extension === 'f4v') {
                    // Flash video file
                    $code = resourcelib_embed_flashvideo($fullurl, $title, $clicktoopen);

                } else if ($mimetype === 'application/x-shockwave-flash') {
                    // Flash file
                    $code = resourcelib_embed_flash($fullurl, $title, $clicktoopen);

                } else if (substr($mimetype, 0, 10) === 'video/x-ms') {
                    // Windows Media Player file
                    $code = resourcelib_embed_mediaplayer($fullurl, $title, $clicktoopen);

                } else if ($mimetype === 'video/quicktime') {
                    // Quicktime file
                    $code = resourcelib_embed_quicktime($fullurl, $title, $clicktoopen);

                } else if ($mimetype === 'video/mpeg') {
                    // Mpeg file
                    $code = resourcelib_embed_mpeg($fullurl, $title, $clicktoopen);

                } else if ($mimetype === 'audio/x-pn-realaudio') {
                    // RealMedia file
                    $code = resourcelib_embed_real($fullurl, $title, $clicktoopen);

                } else {
                    // anything else - just try object tag enlarged as much as possible
                    $code = resourcelib_embed_general($fullurl, $title, $clicktoopen, $mimetype);
                }

                ob_start();
                resource_print_heading($resource, $cm, $COURSE);
                echo $code;
                resource_print_intro($resource, $cm, $COURSE);
                $this->append_content(ob_get_contents());
                ob_end_clean();
            }
        }
    }
}