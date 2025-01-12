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

use RegularLabs\Library\PluginTag as RL_PluginTag;
use RegularLabs\Library\StringHelper as RL_String;
use RegularLabs\Plugin\System\ArticlesAnywhere\DataGroups\DataGroup;
use RegularLabs\Plugin\System\ArticlesAnywhere\Helpers\Data as DataHelper;

class DataTag
{
    /* @var DataGroup */
    private $data_group;
    private $match;

    /**
     * @param array  $match
     * @param string $match
     */
    public function __construct($match, $data_group = '', $database_name = '')
    {
        $this->match = $match;

        $data_key = RL_String::toDashCase($match['data_key']);

        if ( ! empty($match['is_closing_tag']))
        {
            $data_key = '/' . $data_key;
        }

        $selectors = (object) [
            'article_selector' => $match['article_selector'] ?? '',
            'full_key'         => RL_String::toDashCase($match['full_key']),
            'data_group'       => $match['data_group'] ?? '',
            'data_key'         => $data_key,
            'data_subkey'      => $match['data_subkey'] ?? '',
        ];

        if ($selectors->data_group !== 'input')
        {
            $selectors->data_subkey = RL_String::toDashCase($selectors->data_subkey);
        }

        $attributes = RL_PluginTag::getAttributesFromString(
            $match['attributes'] ?? '',
            'articles',
            [],
            'underscore'
        );

        $this->data_group = DataHelper::getDataGroup(
            $selectors,
            $attributes,
            $data_group,
            $database_name
        );
    }

    public function getDataGroup()
    {
        return $this->data_group;
    }

    public function setDataGroup($data_group)
    {
        $this->data_group = $data_group;
    }

    public function getMatchData()
    {
        return $this->match;
    }

    public function getOutput()
    {
        if ( ! ($this->data_group instanceof DataGroup))
        {
            return '';
        }

        $output = $this->data_group->getOutput();

        return $output;
    }

    public function replace(&$html, $replace_once = true)
    {
        if ( ! str_contains($html, $this->match[0]))
        {
            return;
        }

        $output = $this->getOutput();

        if ($replace_once)
        {
            $html = RL_String::replaceOnce($this->match[0], $output, $html);

            return;
        }

        $html = str_replace($this->match[0], $output, $html);
    }

    public function replaceInAttributes($tags)
    {
        if (empty($this->match['attributes']))
        {
            return;
        }

        $orig_attributes = $this->match['attributes'];
        $new_attributes  = $this->match['attributes'];

        foreach ($tags as $tag)
        {
            $tag->replace($new_attributes);
        }

        if ($orig_attributes === $new_attributes)
        {
            return;
        }

        $attributes = RL_PluginTag::getAttributesFromString(
            $new_attributes,
            'articles',
            [],
            'underscore'
        );

        $this->data_group->setAttributes($attributes);

        $this->match['attributes'] = $new_attributes;
        $this->match[0]            = str_replace($orig_attributes, $new_attributes, $this->match[0]);
    }

    public function setParentKey($key)
    {
        $this->data_group->setParentKey($key);
    }

    public function setRow($row)
    {
        $this->data_group->setRow($row);
    }

    public function setValues($values, $numbers)
    {
        $this->data_group->setValues($values, $numbers);
    }
}
