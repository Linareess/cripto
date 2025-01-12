<?php
/**
 * @package         Articles Anywhere
 * @version         16.1.0
 * 
 * @author          Peter van Westen <info@regularlabs.com>
 * @link            https://regularlabs.com
 * @copyright       Copyright © 2024 Regular Labs All Rights Reserved
 * @license         GNU General Public License version 2 or later
 */

namespace RegularLabs\Plugin\EditorButton\ArticlesAnywhere;

defined('_JEXEC') or die;

use Joomla\CMS\Form\Form as JForm;
use RegularLabs\Library\Document as RL_Document;
use RegularLabs\Library\EditorButtonPopup as RL_EditorButtonPopup;
use RegularLabs\Library\Input as RL_Input;
use RegularLabs\Library\RegEx as RL_RegEx;

class Popup extends RL_EditorButtonPopup
{
    protected $extension         = 'articlesanywhere';
    protected $require_core_auth = false;

    protected function loadScripts()
    {
        $params = $this->getParams();

        $this->editor_name = RL_Input::getString('editor', 'text');
        // Remove any dangerous character to prevent cross site scripting
        $this->editor_name = RL_RegEx::replace('[\'\";\s]', '', $this->editor_name);

        RL_Document::scriptOptions([
            'article_tag'         => $params->article_tag,
            'articles_tag'        => $params->articles_tag ?? '',
            'tag_characters'      => explode('.', $params->tag_characters),
            'tag_characters_data' => explode('.', $params->tag_characters_data),
            'editor_name'         => $this->editor_name,
        ], 'articlesanywhere_button');

        RL_Document::script('regularlabs.regular');
        RL_Document::script('regularlabs.admin-form');
        RL_Document::script('regularlabs.admin-form-descriptions');
        RL_Document::script('articlesanywhere.popup');

        $xmlfile = dirname(__FILE__, 2) . '/forms/popup.xml';

        $this->form = new JForm('articlesanywhere');
        $this->form->loadFile($xmlfile, 1, '//config');

        $script = "document.addEventListener('DOMContentLoaded', function(){RegularLabs.ArticlesAnywherePopup.init()});";
        RL_Document::scriptDeclaration($script, 'Articles Anywhere Button', true, 'after');
    }
}
