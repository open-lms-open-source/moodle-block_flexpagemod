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

global $CFG;

require_once($CFG->dirroot.'/mod/folder/renderer.php');

/**
 * Flexpage Activity Renderer
 *
 * Hack alert: extending folder renderer because
 * the JS for folder does not work when we add
 * multiple folders to Flexpage.
 *
 * @author Mark Nielsen
 * @package block_flexpagemod
 */
class block_flexpagemod_renderer extends mod_folder_renderer {

    public function render_folder_tree(folder_tree $tree) {
        static $requirejs = true;

        echo '<div id="'.html_writer::random_id('folder').'" class="filemanager block_flexpagemod_folder_tree">';
        echo $this->htmllize_tree($tree, array('files' => array(), 'subdirs' => array($tree->dir)));
        echo '</div>';

        if ($requirejs) {
            $this->page->requires->js_init_call('M.format_flexpage.mod_folder_init_tree', array(true), false, array(
                'name'     => 'block_flexpagemod',
                'fullpath' => '/blocks/flexpagemod/javascript.js',
            ));
            $requirejs = false;
        }
    }
}