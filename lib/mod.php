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
                    throw new coding_exception("Expected to find $class in '$path' - please rename the declared class");
                }
            }
            if (class_exists($class)) {
                $instance = new $class($cm, $block);

                // Try to help out
                if (!$instance instanceof block_flexpagemod_lib_mod) {
                    throw new coding_exception("The class $class in file '$path' must extend block_flexpagemod_lib_mod");
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
        // Check if we are not visible to the user
        if (!$this->get_cm()->uservisible) {
            // If we have availability information, we do default display
            if ($this->get_cm()->showavailability and !empty($this->get_cm()->availableinfo)) {
                $this->default_block_setup();
            }
        } else {
            // Allow module custom display
            $this->module_block_setup();
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
            $course = $this->get_block()->page->course;
            $groupbuttons = ($course->groupmode or (!$course->groupmodeforce));
            $groupbuttonslink = (!$course->groupmodeforce);

            if ($groupbuttons and plugin_supports('mod', $mod->modname, FEATURE_GROUPS, 0)) {
                if (!$mod->groupmodelink = $groupbuttonslink) {
                    $mod->groupmode = $course->groupmode;
                }
            } else {
                $mod->groupmode = false;
            }
            $buttons = make_editing_buttons($mod, true, true, $mod->indent, $mod->sectionnum);
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
     * Default block setup, make it look like a link from topics/weeks format
     *
     * @return void
     */
    public function default_block_setup() {
        global $CFG, $PAGE, $USER, $OUTPUT;

        // Mark our flag
        $this->defaultused = true;

        // Fake a bunch of variables for the copied code
        $course = $this->get_block()->page->course;
        $completioninfo = new completion_info($course);
        $modinfo = get_fast_modinfo($course);
        $mods = $modinfo->get_cms();
        $strmovefull = '';
        $strmovehere = '';
        $ismoving = false;
        $hidecompletion = false;
        $absolute = true;
        $isediting = $PAGE->user_is_editing();
        $customicon = false;
        $groupbuttons = ($course->groupmode or (!$course->groupmodeforce));
        $groupbuttonslink = (!$course->groupmodeforce);
        $section = get_course_section($this->get_cm()->sectionnum, $course->id);
        $this->get_cm()->modfullname = get_string('modulename', $this->get_cm()->modname);

        ob_start();
        foreach (array($this->get_cm()->id) as $modnumber) {

        /// FOLLOWING COPIED FROM print_section
        /// LOOK FOR "FLEXPAGE" FOR CHANGES

            /** @var $mod cm_info */
            $mod = $mods[$modnumber];

            if ($ismoving and $mod->id == $USER->activitycopy) {
                // do not display moving mod
                continue;
            }

            if (isset($modinfo->cms[$modnumber])) {
                // We can continue (because it will not be displayed at all)
                // if:
                // 1) The activity is not visible to users
                // and
                // 2a) The 'showavailability' option is not set (if that is set,
                //     we need to display the activity so we can show
                //     availability info)
                // or
                // 2b) The 'availableinfo' is empty, i.e. the activity was
                //     hidden in a way that leaves no info, such as using the
                //     eye icon.
                if (!$modinfo->cms[$modnumber]->uservisible &&
                    (empty($modinfo->cms[$modnumber]->showavailability) ||
                      empty($modinfo->cms[$modnumber]->availableinfo))) {
                    // visibility shortcut
                    continue;
                }
            } else {
                if (!file_exists("$CFG->dirroot/mod/$mod->modname/lib.php")) {
                    // module not installed
                    continue;
                }
                if (!coursemodule_visible_for_user($mod) &&
                    empty($mod->showavailability)) {
                    // full visibility check
                    continue;
                }
            }

            if (!isset($modulenames[$mod->modname])) {
                $modulenames[$mod->modname] = get_string('modulename', $mod->modname);
            }
            $modulename = $modulenames[$mod->modname];

            // In some cases the activity is visible to user, but it is
            // dimmed. This is done if viewhiddenactivities is true and if:
            // 1. the activity is not visible, or
            // 2. the activity has dates set which do not include current, or
            // 3. the activity has any other conditions set (regardless of whether
            //    current user meets them)
            $canviewhidden = has_capability(
                'moodle/course:viewhiddenactivities',
                get_context_instance(CONTEXT_MODULE, $mod->id));
            $accessiblebutdim = false;
            if ($canviewhidden) {
                $accessiblebutdim = !$mod->visible;
                if (!empty($CFG->enableavailability)) {
                    $accessiblebutdim = $accessiblebutdim ||
                        $mod->availablefrom > time() ||
                        ($mod->availableuntil && $mod->availableuntil < time()) ||
                        count($mod->conditionsgrade) > 0 ||
                        count($mod->conditionscompletion) > 0;
                }
            }

            $liclasses = array();
            $liclasses[] = 'activity';
            $liclasses[] = $mod->modname;
            $liclasses[] = 'modtype_'.$mod->modname;
            $extraclasses = $mod->get_extra_classes();
            if ($extraclasses) {
                $liclasses = array_merge($liclasses, explode(' ', $extraclasses));
            }
            echo html_writer::start_tag('li', array('class'=>join(' ', $liclasses), 'id'=>'module-'.$modnumber));
            if ($ismoving) {
                echo '<a title="'.$strmovefull.'"'.
                     ' href="'.$CFG->wwwroot.'/course/mod.php?moveto='.$mod->id.'&amp;sesskey='.sesskey().'">'.
                     '<img class="movetarget" src="'.$OUTPUT->pix_url('movehere') . '" '.
                     ' alt="'.$strmovehere.'" /></a><br />
                     ';
            }

            $classes = array('mod-indent');
            if (!empty($mod->indent)) {
                $classes[] = 'mod-indent-'.$mod->indent;
                if ($mod->indent > 15) {
                    $classes[] = 'mod-indent-huge';
                }
            }
            echo html_writer::start_tag('div', array('class'=>join(' ', $classes)));

            // Get data about this course-module
            list($content, $instancename) =
                    get_print_section_cm_text($modinfo->cms[$modnumber], $course);

            //Accessibility: for files get description via icon, this is very ugly hack!
            $altname = '';
            $altname = $mod->modfullname;
            if (!empty($customicon)) {
                $archetype = plugin_supports('mod', $mod->modname, FEATURE_MOD_ARCHETYPE, MOD_ARCHETYPE_OTHER);
                if ($archetype == MOD_ARCHETYPE_RESOURCE) {
                    $mimetype = mimeinfo_from_icon('type', $customicon);
                    $altname = get_mimetype_description($mimetype);
                }
            }
            // Avoid unnecessary duplication: if e.g. a forum name already
            // includes the word forum (or Forum, etc) then it is unhelpful
            // to include that in the accessible description that is added.
            if (false !== strpos(textlib::strtolower($instancename),
                    textlib::strtolower($altname))) {
                $altname = '';
            }
            // File type after name, for alphabetic lists (screen reader).
            if ($altname) {
                $altname = get_accesshide(' '.$altname);
            }

            // We may be displaying this just in order to show information
            // about visibility, without the actual link
            $contentpart = '';
            if ($mod->uservisible) {
                // Nope - in this case the link is fully working for user
                $linkclasses = '';
                $textclasses = '';
                if ($accessiblebutdim) {
                    $linkclasses .= ' dimmed';
                    $textclasses .= ' dimmed_text';
                    $accesstext = '<span class="accesshide">'.
                        get_string('hiddenfromstudents').': </span>';
                } else {
                    $accesstext = '';
                }
                if ($linkclasses) {
                    $linkcss = 'class="' . trim($linkclasses) . '" ';
                } else {
                    $linkcss = '';
                }
                if ($textclasses) {
                    $textcss = 'class="' . trim($textclasses) . '" ';
                } else {
                    $textcss = '';
                }

                // Get on-click attribute value if specified
                $onclick = $mod->get_on_click();
                if ($onclick) {
                    $onclick = ' onclick="' . $onclick . '"';
                }

                if ($url = $mod->get_url()) {
                    // Display link itself
                    echo '<a ' . $linkcss . $mod->extra . $onclick .
                            ' href="' . $url . '"><img src="' . $mod->get_icon_url() .
                            '" class="activityicon" alt="' .
                            $modulename . '" /> ' .
                            $accesstext . '<span class="instancename">' .
                            $instancename . $altname . '</span></a>';

                    // If specified, display extra content after link
                    if ($content) {
                        $contentpart = '<div class="contentafterlink' .
                                trim($textclasses) . '">' . $content . '</div>';
                    }
                } else {
                    // No link, so display only content
                    $contentpart = '<div ' . $textcss . $mod->extra . '>' .
                            $accesstext . $content . '</div>';
                }

                if (!empty($mod->groupingid) && has_capability('moodle/course:managegroups', get_context_instance(CONTEXT_COURSE, $course->id))) {
                    if (!isset($groupings)) {
                        $groupings = groups_get_all_groupings($course->id);
                    }
                    echo " <span class=\"groupinglabel\">(".format_string($groupings[$mod->groupingid]->name).')</span>';
                }
            } else {
                $textclasses = $extraclasses;
                $textclasses .= ' dimmed_text';
                if ($textclasses) {
                    $textcss = 'class="' . trim($textclasses) . '" ';
                } else {
                    $textcss = '';
                }
                $accesstext = '<span class="accesshide">' .
                        get_string('notavailableyet', 'condition') .
                        ': </span>';

                if ($url = $mod->get_url()) {
                    // Display greyed-out text of link
                    echo '<div ' . $textcss . $mod->extra .
                            ' >' . '<img src="' . $mod->get_icon_url() .
                            '" class="activityicon" alt="' .
                            $modulename .
                            '" /> <span>'. $instancename . $altname .
                            '</span></div>';

                    // Do not display content after link when it is greyed out like this.
                } else {
                    // No link, so display only content (also greyed)
                    $contentpart = '<div ' . $textcss . $mod->extra . '>' .
                            $accesstext . $content . '</div>';
                }
            }

            // Module can put text after the link (e.g. forum unread)
            echo $mod->get_after_link();

            if ($isediting) {
                if ($groupbuttons and plugin_supports('mod', $mod->modname, FEATURE_GROUPS, 0)) {
                    if (! $mod->groupmodelink = $groupbuttonslink) {
                        $mod->groupmode = $course->groupmode;
                    }

                } else {
                    $mod->groupmode = false;
                }
                echo '&nbsp;&nbsp;';
                echo make_editing_buttons($mod, $absolute, true, $mod->indent, $section->section);
                echo $mod->get_after_edit_icons();
            }

            // Completion
            $completion = $hidecompletion
                ? COMPLETION_TRACKING_NONE
                : $completioninfo->is_enabled($mod);
            if ($completion!=COMPLETION_TRACKING_NONE && isloggedin() &&
                !isguestuser() && $mod->uservisible) {
                $completiondata = $completioninfo->get_data($mod,true);
                $completionicon = '';
                if ($isediting) {
                    switch ($completion) {
                        case COMPLETION_TRACKING_MANUAL :
                            $completionicon = 'manual-enabled'; break;
                        case COMPLETION_TRACKING_AUTOMATIC :
                            $completionicon = 'auto-enabled'; break;
                        default: // wtf
                    }
                } else if ($completion==COMPLETION_TRACKING_MANUAL) {
                    switch($completiondata->completionstate) {
                        case COMPLETION_INCOMPLETE:
                            $completionicon = 'manual-n'; break;
                        case COMPLETION_COMPLETE:
                            $completionicon = 'manual-y'; break;
                    }
                } else { // Automatic
                    switch($completiondata->completionstate) {
                        case COMPLETION_INCOMPLETE:
                            $completionicon = 'auto-n'; break;
                        case COMPLETION_COMPLETE:
                            $completionicon = 'auto-y'; break;
                        case COMPLETION_COMPLETE_PASS:
                            $completionicon = 'auto-pass'; break;
                        case COMPLETION_COMPLETE_FAIL:
                            $completionicon = 'auto-fail'; break;
                    }
                }
                if ($completionicon) {
                    $imgsrc = $OUTPUT->pix_url('i/completion-'.$completionicon);
                    $imgalt = s(get_string('completion-alt-'.$completionicon, 'completion'));
                    if ($completion == COMPLETION_TRACKING_MANUAL && !$isediting) {
                        $imgtitle = s(get_string('completion-title-'.$completionicon, 'completion'));
                        $newstate =
                            $completiondata->completionstate==COMPLETION_COMPLETE
                            ? COMPLETION_INCOMPLETE
                            : COMPLETION_COMPLETE;
                        // In manual mode the icon is a toggle form...

                        // If this completion state is used by the
                        // conditional activities system, we need to turn
                        // off the JS.
                        if (!empty($CFG->enableavailability) &&
                            condition_info::completion_value_used_as_condition($course, $mod)) {
                            $extraclass = ' preventjs';
                        } else {
                            $extraclass = '';
                        }
                        echo "
<form class='togglecompletion$extraclass' method='post' action='".$CFG->wwwroot."/course/togglecompletion.php'><div>
<input type='hidden' name='id' value='{$mod->id}' />
<input type='hidden' name='sesskey' value='".sesskey()."' />
<input type='hidden' name='completionstate' value='$newstate' />
<input type='image' src='$imgsrc' alt='$imgalt' title='$imgtitle' />
</div></form>";
                    } else {
                        // In auto mode, or when editing, the icon is just an image
                        echo "<span class='autocompletion'>";
                        echo "<img src='$imgsrc' alt='$imgalt' title='$imgalt' /></span>";
                    }
                }
            }

            // Display the content (if any) at this part of the html
            echo $contentpart;

            // Show availability information (for someone who isn't allowed to
            // see the activity itself, or for staff)
            if (!$mod->uservisible) {
                echo '<div class="availabilityinfo">'.$mod->availableinfo.'</div>';
            } else if ($canviewhidden && !empty($CFG->enableavailability)) {
                $ci = new condition_info($mod);
                $fullinfo = $ci->get_full_information();
                if($fullinfo) {
                    echo '<div class="availabilityinfo">'.get_string($mod->showavailability
                        ? 'userrestriction_visible'
                        : 'userrestriction_hidden','condition',
                        $fullinfo).'</div>';
                }
            }

            echo html_writer::end_tag('div');
            echo html_writer::end_tag('li')."\n";
        }

        $output = ob_get_contents();
        ob_end_clean();

        if (!empty($output)) {
            $output = html_writer::tag('ul', $output, array('class' => 'section img-text'));
            $output = html_writer::tag('div', $output, array('class' => 'block_flexpagemod_default'));
            $this->append_content($output);
        }
    }
}