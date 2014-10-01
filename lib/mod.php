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
 * Display code for block's content
 *
 * @throws coding_exception
 * @author Mark Nielsen
 * @package block_flexpagemod
 */
class block_flexpagemod_lib_mod {
    /**
     * Determine if the default rendering was used or not
     *
     * @var bool
     */
    protected $defaultused = false;

    /**
     * @var cm_info
     */
    protected $cm;

    /**
     * @var block_flexpagemod
     */
    protected $block;

    /**
     * @param cm_info $cm The course module being displayed
     * @param block_flexpagemod $block The block instance being used for displayed
     */
    public function __construct(cm_info &$cm, block_flexpagemod &$block) {
        $this->cm    = $cm;
        $this->block = $block;
    }

    /**
     * Factory: create the most relevant instance
     *
     * @static
     * @throws coding_exception
     * @param cm_info $cm The course module being displayed
     * @param block_flexpagemod $block The block instance being used for displayed
     * @return block_flexpagemod_lib_mod
     */
    public static function factory(cm_info &$cm, block_flexpagemod &$block) {
        global $CFG;

        $paths = array(
            "$CFG->dirroot/mod/$cm->modname/flexpage.php" => "mod_{$cm->modname}_flexpage",
            "$CFG->dirroot/blocks/flexpagemod/lib/mod/$cm->modname.php" => "block_flexpagemod_lib_mod_$cm->modname",
        );

        foreach ($paths as $path => $class) {
            if (!class_exists($class) and file_exists($path)) {
                require_once($path);

                // Try to help out
                if (!class_exists($class)) {
                    throw new coding_exception("Failed to find $class");
                }
            }
            if (class_exists($class)) {
                $instance = new $class($cm, $block);

                // Try to help out
                if (!$instance instanceof block_flexpagemod_lib_mod) {
                    throw new coding_exception("The class $class must extend block_flexpagemod_lib_mod");
                }
                return $instance;
            }
        }
        return new block_flexpagemod_lib_mod($cm, $block);
    }

    /**
     * @return cm_info
     */
    public function get_cm() {
        return $this->cm;
    }

    /**
     * @return block_flexpagemod
     */
    public function get_block() {
        return $this->block;
    }

    /**
     * Setup $this->block for display
     *
     * @return void
     */
    public function setup_block() {
        // Change title, not always displayed though.
        $this->get_block()->title = format_string($this->get_cm()->name);

        // Check if we are not visible to the user
        if (!$this->get_cm()->uservisible) {
            // If we have availability information, we do default display
            if (!empty($this->get_cm()->availableinfo)) {
                $this->default_block_setup();
            }
        } else {
            // Allow module custom display
            $this->module_block_setup();
            $this->dim_content();
            $this->add_mod_commands();
        }
    }

    /**
     * Append text to the block's main content area
     *
     * @param string $content
     * @return block_flexpagemod_lib_mod
     */
    public function append_content($content) {
        $this->get_block()->content->text .= $content;
        return $this;
    }

    /**
     * Append text to the block's footer area
     *
     * @param string $content
     * @return block_flexpagemod_lib_mod
     */
    public function append_footer($content) {
        $this->get_block()->content->footer .= $content;
        return $this;
    }

    /**
     * Add module commands when not using default rendering
     *
     * Must be called after block text has been completely filled.
     *
     * @return void
     */
    public function add_mod_commands() {
        global $PAGE;

        if (!$this->defaultused and $PAGE->user_is_editing()) {
            $mod = $this->get_cm();

            /** @var core_course_renderer $renderer */
            $renderer   = $this->get_block()->page->get_renderer('core', 'course');
            $editactions = course_get_cm_edit_actions($mod);

            // Don't allow these actions.
            unset($editactions['move'], $editactions['title']);

            $buttons = $renderer->course_section_cm_edit_actions($editactions, $mod, array('constraintselector' => '.block_flexpagemod')).$mod->afterediticons;
            $buttons = html_writer::tag('div', $buttons, array('class' => 'block_flexpagemod_commands'));

            $this->get_block()->content->text = html_writer::tag(
                'div',
                $buttons.$this->get_block()->content->text,
                array('class' => 'block_flexpagemod_commands_wrapper')
            );
        }
    }

    /**
     * Customized block setup for a particular module
     *
     * @return void
     */
    public function module_block_setup() {
        $this->default_block_setup();
    }

    /**
     * Dim the content of the block
     */
    public function dim_content() {
        if (!$this->defaultused && !$this->get_cm()->visible) {
            $this->get_block()->content->text = html_writer::div($this->get_block()->content->text, 'dimmed_text');
        }
    }

    /**
     * Default block setup, make it look like a link from topics/weeks format
     *
     * @return void
     */
    public function default_block_setup() {
        global $CFG;

        require_once($CFG->libdir.'/completionlib.php');

        // Mark our flag
        $this->defaultused = true;

        $course = $this->get_block()->page->course;
        $completioninfo = new completion_info($course);

        /** @var core_course_renderer $renderer */
        $renderer = $this->get_block()->page->get_renderer('core', 'course');
        $output   = $renderer->course_section_cm($course, $completioninfo, $this->get_cm(), null);

        // We need these for CSS rules, use role to have screen readers ignore list structure.
        $output = html_writer::tag('li', $output, array('role' => 'presentation', 'class' => 'activity', 'id' => 'module-'.$this->get_cm()->id));
        $output = html_writer::tag('ul', $output, array('role' => 'presentation', 'class' => 'section'));

        $this->append_content(
            html_writer::tag('div', $output, array('class' => 'block_flexpagemod_default course-content'))
        );
    }
}