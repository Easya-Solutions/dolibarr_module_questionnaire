/* Copyright (C) 2021      Open-DSI             <support@open-dsi.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * \file       htdocs/core/js/questionnaire.js
 * \brief      File that include javascript functions for Questionnaire
 */

/**
 * Fallback copy text to clipboard
 * @see by Dean Taylor (see: https://stackoverflow.com/questions/400212/how-do-i-copy-to-the-clipboard-in-javascript)
 *
 * @param   text      string      Text to copy
 */
function questionnaire_fallbackCopyTextToClipboard(text) {
    var textArea = document.createElement("textarea");
    textArea.value = text;

    // Avoid scrolling to bottom
    textArea.style.top = "0";
    textArea.style.left = "0";
    textArea.style.position = "fixed";

    document.body.appendChild(textArea);
    textArea.focus();
    textArea.select();

    try {
        var successful = document.execCommand('copy');
        var msg = successful ? 'successful' : 'unsuccessful';
        console.log('Fallback: Copying text command was ' + msg);
    } catch (err) {
        console.error('Fallback: Oops, unable to copy', err);
    }

    document.body.removeChild(textArea);
}

/**
 * Copy text to clipboard
 * @see by Dean Taylor (see: https://stackoverflow.com/questions/400212/how-do-i-copy-to-the-clipboard-in-javascript)
 *
 * @param   text      string      Text to copy
 */
function questionnaire_copyTextToClipboard(text) {
    if (!navigator.clipboard) {
        questionnaire_fallbackCopyTextToClipboard(text);
        return;
    }
    navigator.clipboard.writeText(text).then(function() {
        console.log('Async: Copying to clipboard was successful!');
    }, function(err) {
        console.error('Async: Could not copy text: ', err);
    });
}

/**
 * Initialization when the page is loaded
 */
jQuery(document).ready(function () {
    // Event for copy text to clipboard
    jQuery('.questionnaire_copy_text').click(function () {
        var _this = jQuery(this);
        var _text = _this.attr('text_to_copy');

        if (_text.length > 0) {
            questionnaire_copyTextToClipboard(_text);
        }
    });
});