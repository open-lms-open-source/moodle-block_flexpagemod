<?php
/**
 * Display mod/folder
 *
 * @author Mark Nielsen
 * @package format_flexpage
 */
class block_flexpagemod_lib_mod_folder extends block_flexpagemod_lib_mod {
    /**
     * Pretty much copied everything from mod/folder/view.php
     *
     * @return void
     */
    public function module_block_setup() {
        global $CFG, $COURSE, $DB, $PAGE;

        $cm      = $this->get_cm();
        $folder  = $DB->get_record('folder', array('id' => $cm->instance));
        $context = get_context_instance(CONTEXT_MODULE, $cm->id);
        $course  = $COURSE;
        if ($folder and has_capability('mod/folder:view', $context)) {
            add_to_log($course->id, 'folder', 'view', 'view.php?id='.$cm->id, $folder->id, $cm->id);

            // Update 'viewed' state if required by completion system
            require_once($CFG->libdir . '/completionlib.php');
            $completion = new completion_info($course);
            $completion->set_module_viewed($cm);

            $output = $PAGE->get_renderer('mod_folder');

            ob_start();
            echo $output->heading(format_string($folder->name), 2);

            if (trim(strip_tags($folder->intro))) {
                echo $output->box_start('mod_introbox', 'pageintro');
                echo format_module_intro('folder', $folder, $cm->id);
                echo $output->box_end();
            }

            echo $output->box_start('generalbox foldertree');
            echo $output->folder_tree($folder, $cm, $course);
            echo $output->box_end();

            if (has_capability('mod/folder:managefiles', $context)) {
                echo $output->container_start('mdl-align');
                echo $output->single_button(new moodle_url('/mod/folder/edit.php', array('id'=>$cm->id)), get_string('edit'));
                echo $output->container_end();
            }
            $this->append_content(ob_get_contents());
            ob_end_clean();
        }
    }
}