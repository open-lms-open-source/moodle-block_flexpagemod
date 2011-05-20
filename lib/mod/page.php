<?php
/**
 * Display mod/page
 */
class block_flexpagemod_lib_mod_page extends block_flexpagemod_lib_mod {
    /**
     * Pretty much copied everything from mod/page/view.php
     *
     * @return void
     */
    public function module_block_setup() {
        global $CFG, $COURSE, $DB, $OUTPUT;

        $cm      = $this->get_cm();
        $page    = $DB->get_record('page', array('id' => $cm->instance));
        $context = get_context_instance(CONTEXT_MODULE, $cm->id);
        if ($page and has_capability('mod/page:view', $context)) {
            add_to_log($cm->course, 'page', 'view', 'view.php?id='.$cm->id, $page->id, $cm->id);

            // Update 'viewed' state if required by completion system
            require_once($CFG->libdir . '/completionlib.php');
            $completion = new completion_info($COURSE);
            $completion->set_module_viewed($cm);

            $options = empty($page->displayoptions) ? array() : unserialize($page->displayoptions);

            if (!empty($options['printheading'])) {
                $this->append_content($OUTPUT->heading(format_string($page->name), 2, 'main', 'pageheading'));
            }
            if (!empty($options['printintro'])) {
                if (trim(strip_tags($page->intro))) {
                    $this->append_content($OUTPUT->box(
                        format_module_intro('page', $page, $cm->id),
                        'mod_introbox', 'pageintro'
                    ));
                }
            }
            $content       = file_rewrite_pluginfile_urls($page->content, 'pluginfile.php', $context->id, 'mod_page', 'content', $page->revision);
            $formatoptions = array('noclean'=>true, 'overflowdiv'=>true);

            $this->append_content(format_text($content, $page->contentformat, $formatoptions, $COURSE->id))
                 ->append_content(html_writer::tag('div', get_string("lastmodified").': '.userdate($page->timemodified), array('class' => 'modified')));
        }
    }
}