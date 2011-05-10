<?php
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
        return has_capability('moodle/course:manageactivities', $page->context);
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
        return true;
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

        try {
            $modinfo = get_fast_modinfo($this->page->course);
            $cm      = $modinfo->get_cm($this->config->cmid);
            $attributes['class'] .= ' block_flexpagemod_'.$cm->modname;
        } catch (Exception $e) {
            $attributes['class'] .= ' block_flexpagemod_unknown';
        }
        return $attributes;
    }


}