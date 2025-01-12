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

use Joomla\CMS\Application\CMSApplication as JApplication;
use Joomla\CMS\Factory as JFactory;
use Joomla\CMS\Language\Text as JText;
use Joomla\Component\Finder\Administrator\Indexer\Helper as JIndexerHelper;
use Joomla\Component\Finder\Administrator\Indexer\Result as JIndexerResult;
use Joomla\Database\DatabaseDriver as JDatabaseDriver;
use RegularLabs\Library\Document as RL_Document;
use RegularLabs\Library\Extension as RL_Extension;
use RegularLabs\Library\Html as RL_Html;
use RegularLabs\Library\StringHelper as RL_String;
use RegularLabs\Library\SystemPlugin as RL_SystemPlugin;
use RegularLabs\Plugin\System\ArticlesAnywhere\Helpers\CurrentArticle;
use RegularLabs\Plugin\System\ArticlesAnywhere\Helpers\Params;
use RegularLabs\Plugin\System\ArticlesAnywhere\Replace;

if ( ! is_file(JPATH_LIBRARIES . '/regularlabs/regularlabs.xml')
    || ! class_exists('RegularLabs\Library\Parameters')
    || ! class_exists('RegularLabs\Library\DownloadKey')
    || ! class_exists('RegularLabs\Library\SystemPlugin')
)
{
    JFactory::getApplication()->getLanguage()->load('plg_system_articlesanywhere', __DIR__);
    JFactory::getApplication()->enqueueMessage(
        JText::sprintf('AA_EXTENSION_CAN_NOT_FUNCTION', JText::_('ARTICLESANYWHERE'))
        . ' ' . JText::_('AA_REGULAR_LABS_LIBRARY_NOT_INSTALLED'),
        'error'
    );

    return;
}

if ( ! RL_Document::isJoomlaVersion(4, 'ARTICLESANYWHERE'))
{
    RL_Extension::disable('articlesanywhere', 'plugin');

    RL_Document::adminError(
        JText::sprintf('RL_PLUGIN_HAS_BEEN_DISABLED', JText::_('ARTICLESANYWHERE'))
    );

    return;
}

if (true)
{
    class PlgSystemArticlesAnywhere extends RL_SystemPlugin
    {
        public    $_lang_prefix = 'AA';
        protected $_jversion    = 4;
        protected $_page_types  = ['html', 'feed', 'pdf', 'xml', 'ajax', 'raw'];

        /**
         * @var    JApplication
         */
        protected $app;

        /**
         * @var    JDatabaseDriver
         */
        protected $db;

        /**
         * @param string &$string
         * @param string  $area
         * @param string  $context The context of the content being passed to the plugin.
         * @param mixed   $article An object with a "text" property
         * @param int     $page    Optional page number. Unused. Defaults to zero.
         *
         * @return  void
         */
        public function processArticle(&$string, $area = 'article', $context = '', $article = null, $page = 0)
        {
            if ($context == 'com_finder.indexer')
            {
                return;
            }

            CurrentArticle::set($article);

            if ( ! RL_String::contains($string, Params::getTags(true)))
            {
                return;
            }

            $string = Replace::render($string, $area, $context, $article);
        }

        /**
         * @param object $module
         * @param array  $params
         */
        protected function handleOnAfterRenderModule(&$module, &$params): void
        {
            if ( ! isset($module->content))
            {
                return;
            }

            Replace::render($module->content, 'module');
        }

        /**
         * @param string $buffer
         *
         * @return  bool
         */
        protected function changeDocumentBuffer(&$buffer)
        {
            $buffer = Replace::render($buffer, 'component');

            return true;
        }


        /**
         * @param string $html
         *
         * @return  bool
         */
        protected function changeFinalHtmlOutput(&$html)
        {
            if ( ! RL_String::contains($html, Params::getTags(true)))
            {
                return true;
            }

            if (RL_Document::isFeed())
            {
                $html = Replace::render($html);

                return true;
            }

            $params = Params::get();

            // only do stuff in body
            [$pre, $body, $post] = RL_Html::getBody($html);

            if ($params->handle_html_head)
            {
                $pre = Replace::render($pre, 'head');
            }

            $body = Replace::render($body, 'body');
            $html = $pre . $body . $post;

            return true;
        }
    }
}
