/**
 * @package         Articles Anywhere
 * @version         16.1.0
 * 
 * @author          Peter van Westen <info@regularlabs.com>
 * @link            https://regularlabs.com
 * @copyright       Copyright © 2024 Regular Labs All Rights Reserved
 * @license         GNU General Public License version 2 or later
 */

(function() {
    'use strict';

    window.RegularLabs = window.RegularLabs || {};

    window.RegularLabs.ArticlesAnywhereButton = window.RegularLabs.ArticlesAnywhereButton || {
        code: '',

        insertText: function(editor_name) {
            const editor = parent.Joomla.editors.instances[editor_name];

            const html = this.prepareOutputForEditor(this.code, editor);

            editor.replaceSelection(html);
        },

        setCode: function(code) {
            this.code = code;
        },

        prepareOutputForEditor: function(string, editor) {
            const editor_content   = editor.getValue();
            const editor_selection = editor.getSelection();

            // If the editor is CodeMirror
            if (editor_content === '' || editor_content[0] !== '<') {
                // remove all p tags
                return string.replace(/<\/?p>/g, '');
            }

            // If selection is empty or code is replacing a selection not starting with a html tag
            if (editor_selection.indexOf('<') !== 0) {
                // remove surrounding p tags
                return string.replace(/^<p>(.*)<\/p>$/g, '$1');
            }

            return string;
        },
    };
})();
