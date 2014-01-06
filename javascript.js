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
 * @namespace M.format_flexpage
 */
M.format_flexpage = M.format_flexpage || {};

M.format_flexpage.mod_folder_init_tree = function(Y, expand_all) {
    Y.use('yui2-treeview', function(Y) {
        Y.all('.block_flexpagemod_folder div.block_flexpagemod_folder_tree').each(function(node) {
            var tree = new Y.YUI2.widget.TreeView(node.get('id'));
            tree.subscribe("clickEvent", function(node, event) {
                // we want normal clicking which redirects to url
                return false;
            });

            if (expand_all) {
                tree.expandAll();
            }

            tree.render();
        });
    });
};
