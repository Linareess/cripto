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

namespace RegularLabs\Plugin\System\ArticlesAnywhere\DataTags;

defined('_JEXEC') or die;

use RegularLabs\Library\RegEx as RL_RegEx;
use RegularLabs\Plugin\System\ArticlesAnywhere\Helpers\Params;

class DataTags
{
    /* @var DataTag[] */
    private array $current_data_tags = [];
    /* @var DataTag[] */
    private array $data_tags = [];
    private       $database_name;

    /**
     * @param string $string
     * @param string $database_name
     */
    public function __construct($string, $database_name = '')
    {
        $this->database_name = $database_name;

        $this->initTags($string);
    }

    /**
     * @return array
     */
    public function getCurrentDataGroups()
    {
        $data_groups = [];

        foreach ($this->current_data_tags as $data_tag)
        {
            $data_groups[] = $data_tag->getDataGroup();
        }

        return $data_groups;
    }

    /**
     * @return DataTag[]
     */
    public function getCurrentDataTags()
    {
        return $this->current_data_tags;
    }

    /**
     * @return array
     */
    public function getDataGroups()
    {
        $data_groups = [];

        foreach ($this->data_tags as $data_tag)
        {
            $data_groups[] = $data_tag->getDataGroup();
        }

        return $data_groups;
    }

    /**
     * @return DataTag[]
     */
    public function getTags()
    {
        return $this->data_tags;
    }

    /**
     * @param string $html
     */
    public function replace(&$html)
    {
        $this->replaceInAttributes();

        foreach ($this->current_data_tags as $data_tag)
        {
            $data_tag->replace($html);
        }

        foreach ($this->data_tags as $data_tag)
        {
            $data_tag->replace($html);
        }
    }

    /**
     * @param array $data_tags
     */
    public function setTags($data_tags = [])
    {
        $this->data_tags = $data_tags;
    }

    /**
     * @param string $string
     */
    private function getDataTagsFromString($string)
    {
        $regex = Params::getRegex('datatag');

        RL_RegEx::matchAll($regex, $string, $matches);

        if (empty($matches))
        {
            return [[], []];
        }

        $data_tags         = [];
        $current_data_tags = [];

        foreach ($matches as $match)
        {
            if ( ! empty($match['attributes']))
            {
                [$nested_data_tags, $nested_current_data_tags] = $this->getDataTagsFromString($match['attributes']);
                $data_tags         = [...$data_tags, ...$nested_data_tags];
                $current_data_tags = [...$current_data_tags, ...$nested_current_data_tags];
            }

            $data_tag = new DataTag($match, '', $this->database_name);

            if ( ! $data_tag->getDataGroup())
            {
                continue;
            }

            if ($match['article_selector'] === 'this')
            {
                $current_data_tags[] = $data_tag;
                continue;
            }

            $data_tags[] = $data_tag;
        }

        return [$data_tags, $current_data_tags];
    }

    /**
     * @param string $string
     */
    private function initTags($string)
    {
        $this->data_tags         = [];
        $this->current_data_tags = [];

        [$data_tags, $current_data_tags] = $this->getDataTagsFromString($string);

        $this->setTags($data_tags);
        $this->setCurrentTags($current_data_tags);
    }

    private function replaceInAttributes()
    {
        foreach ($this->data_tags as $data_tag)
        {
            $data_tag->replaceInAttributes($this->current_data_tags);
            $data_tag->replaceInAttributes($this->data_tags);
        }
    }

    /**
     * @param array $data_tags
     */
    private function setCurrentTags($data_tags = [])
    {
        $this->current_data_tags = $data_tags;
    }
}
