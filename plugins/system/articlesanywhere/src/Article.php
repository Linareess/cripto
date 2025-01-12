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

namespace RegularLabs\Plugin\System\ArticlesAnywhere;

defined('_JEXEC') or die;

use RegularLabs\Plugin\System\ArticlesAnywhere\DataGroups\DataGroup;
use RegularLabs\Plugin\System\ArticlesAnywhere\DataTags\DataTags as DataTags;
use RegularLabs\Plugin\System\ArticlesAnywhere\ForeachTags\Tags as ForeachTags;
use RegularLabs\Plugin\System\ArticlesAnywhere\IfStatements\IfStatements as IfStatements;

class Article
{
    private Articles     $articles;
    private int          $count;
    private DataTags     $data_tags;
    private ForeachTags  $foreach_tags;
    private string       $html;
    private IfStatements $if_statements;
    private int          $total;

    /**
     * Article constructor.
     *
     * @param DataTags     $data_tags
     * @param IfStatements $if_statements
     * @param ForeachTag   $foreach_tags
     * @param Articles     $articles
     * @param string       $html
     * @param int          $count
     * @param int          $total
     */
    public function __construct(
        DataTags     $data_tags,
        IfStatements $if_statements,
        ForeachTags  $foreach_tags,
        Articles     $articles,
                     $html,
                     $count,
                     $total
    )
    {
        $this->data_tags     = $data_tags;
        $this->if_statements = $if_statements;
        $this->articles = $articles;

        $this->html  = $html;
        $this->count = $count;
        $this->total = $total;

        $this->setValues();
    }

    /**
     * @return string
     */
    public function render()
    {
        $this->if_statements->replace($this->html);
        $this->data_tags->replace($this->html);

        return $this->html;
    }

    /**
     * @param mixed $article_selector
     *
     * @return int|string|null
     */
    private function getCountFromArticleSelector($article_selector)
    {
        if (empty($article_selector))
        {
            return $this->count;
        }

        if (is_numeric($article_selector))
        {
            return $article_selector;
        }

        return match ($article_selector)
        {
            'previous' => $this->count - 1,
            'next'     => $this->count + 1,
            'first'    => 1,
            'last'     => $this->total,
            'this'     => 'current',
            'row'      => $this->count,
            default    => null,
        };
    }

    /**
     * @param $type
     */
    private function setValues()
    {
        /* @var DataGroup[] $data_groups */
        $data_groups = [
            ...$this->if_statements->getDataGroups(),
            ...$this->data_tags->getDataGroups(),
        ];

        foreach ($data_groups as $data_group)
        {
            $count = $this->getCountFromArticleSelector($data_group->getArticleSelector());

            if (is_null($count))
            {
                continue;
            }

            $values  = $this->articles->getArticleValues($count);
            $numbers = $this->articles->getNumbers($count);

            $data_group->setValues($values, $numbers);
        }

        $values = $this->articles->getCurrentArticleValues();

        foreach ($this->data_tags->getCurrentDataTags() as $data_tag)
        {
            $data_tag->setValues($values, null);
        }
    }
}
