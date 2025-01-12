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

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper as JHtml;
use Joomla\CMS\Language\Text as JText;

?>

<div class="container-fluid container-main">
    <div class="row">
        <div class="fixed-top d-lg-none">
            <button type="button" class="btn btn-success mb-4 w-100"
                    onclick="RegularLabs.ArticlesAnywherePopup.insertText();window.parent.Joomla.Modal.getCurrent().close();">
                <span class="icon-file-import" aria-hidden="true"></span>
                <?php echo JText::_('RL_INSERT'); ?>
            </button>
        </div>

        <div class="pt-5 d-lg-none"></div>

        <div class="col-lg-6 border-end">
            <form action="index.php" id="adminForm" name="articlesAnywhereForm" method="post"
                  class="rl-form labels-sm">
                <input type="hidden" name="type" id="type" value="url">
                <?php echo JHtml::_('uitab.startTabSet', 'main', ['active' => 'filters']); ?>

                <?php
                $tabs = [
                    'filters'          => 'RL_FILTERS',
                    'data_tags'        => 'AA_DATA_TAGS',
                    'extra_attributes' => 'RL_OTHER_SETTINGS',
                ];

                foreach ($tabs as $id => $title)
                {
                    echo JHtml::_('uitab.addTab', 'main', $id, JText::_($title));
                    echo $this->form->renderFieldset($id);
                    echo JHtml::_('uitab.endTab');
                }
                ?>

                <?php echo JHtml::_('uitab.endTabSet'); ?>
            </form>
        </div>
        <div class="col-lg-6">
            <div class="position-sticky" style="top:1.25rem;">
                <button type="button" class="btn btn-success mb-4 w-100 hidden d-lg-block"
                        onclick="RegularLabs.ArticlesAnywherePopup.insertText();window.parent.Joomla.Modal.getCurrent().close();">
                    <span class="icon-file-import" aria-hidden="true"></span>
                    <?php echo JText::_('RL_INSERT'); ?>
                </button>
                <fieldset class="options-form mt-2 position-relative">
                    <legend class="mb-1"><?php echo JText::_('JGLOBAL_PREVIEW'); ?></legend>
                    <span id="preview_spinner" class="rl-spinner hidden"></span>
                    <span id="preview_message" class="text-muted fst-italic">
                        <span class="icon-info-circle text-info" aria-hidden="true"></span>
                        <?php echo JText::_('AA_MESSAGE_NO_PREVIEW'); ?>
                    </span>
                    <div id="preview_code" class="hidden"></div>
                </fieldset>
                <div class="alert alert-info">
                    <?php echo JText::sprintf(
                        'AA_POPUP_MORE_INFO',
                        '<a href="https://docs4.regularlabs.com/articlesanywhere" target="_blank">',
                        '</a>'
                    ); ?>
                </div>
            </div>
        </div>
    </div>
</div>
