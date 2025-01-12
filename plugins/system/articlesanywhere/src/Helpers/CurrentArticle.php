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

namespace RegularLabs\Plugin\System\ArticlesAnywhere\Helpers;

defined('_JEXEC') or die;

use Joomla\CMS\Factory as JFactory;
use RegularLabs\Library\Input as RL_Input;
use RegularLabs\Library\StringHelper as RL_String;
use RegularLabs\Plugin\System\ArticlesAnywhere\Articles;
use RegularLabs\Plugin\System\ArticlesAnywhere\Filters\Filters as Filters;

class CurrentArticle
{
    protected static $article          = null;
    protected static $category_article = null;

    public static function get()
    {
        if (is_null(self::$article))
        {
            self::setArticleByUrl();
        }

        return static::$article;
    }

    public static function getId()
    {
        $article = self::get();

        return (int) ($article->id ?? 0);
    }

    public static function getValue($key, $default = null)
    {
        $article = self::get();

        if ( ! $article)
        {
            return $default;
        }

        if (isset($article->$key))
        {
            return $article->$key;
        }

        $key_underscore = RL_String::toUnderscoreCase($key);

        if (isset($article->$key_underscore))
        {
            return $article->$key_underscore;
        }

        [$tag_start, $tag_end] = Params::getDataTagCharacters();

        $content = $tag_start . $key . $tag_end;
        $filters = new Filters(['id' => $article->id]);

        $data = new Articles(
            $tag_start . $key . $tag_end,
            [$filters]
        );

        $value = $data->render();

        $article->$key = ($value != $content) ? $value : $default;

        return $article->$key;
    }

    public static function set($article)
    {
        $is_article = $article && ! empty($article->id) && isset($article->fulltext);

        if ( ! $is_article
            && RL_Input::getCmd('option') === 'com_content'
            && RL_Input::getCmd('view') === 'category'
        )
        {
            self::setFromCategory();

            return;
        }

        if ( ! $is_article)
        {
            return;
        }

        static::$article = clone $article;
    }

    public static function setArticleByUrl()
    {
        $input = JFactory::getApplication()->getInput();

        if (
            $input->get('option', '') !== 'com_content'
            || $input->get('view', '') !== 'article'
        )
        {
            return;
        }

        static::$article = (object) [
            'id' => $input->getInt('id'),
        ];
    }

    public static function setFromCategory()
    {
        if ( ! is_null(self::$category_article))
        {
            static::$article = clone self::$category_article;

            return;
        }

        $category_id = RL_Input::getInt('id');

        if ( ! $category_id)
        {
            return;
        }

        $query = DB::getQuery()
            ->select('article.id')
            ->from(DB::quoteName('#__content', 'article'))
            ->where(DB::is('article.catid', $category_id))
            ->where(DB::is('article.state', 1));

        $article_id = DB::getResults($query, 'result');

        if ( ! $article_id)
        {
            return;
        }

        self::$category_article = (object) [
            'id' => $article_id,
        ];

        static::$article = clone self::$category_article;
    }
}
