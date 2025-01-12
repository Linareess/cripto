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

namespace RegularLabs\Plugin\System\ArticlesAnywhere\IfStatements;

defined('_JEXEC') or die;

use RegularLabs\Library\StringHelper as RL_String;
use RegularLabs\Plugin\System\ArticlesAnywhere\DataGroups\DataGroup;
use RegularLabs\Plugin\System\ArticlesAnywhere\Numbers\Numbers;

class IfStatement
{
    private array $match;
    private Tags  $tags;

    /**
     * @param array $match
     */
    public function __construct($match, $include_rows = false)
    {
        $this->match = $match;
        $this->tags  = new Tags($match[0], $include_rows);
    }

    //    public function getTags()
    //    {
    //        return $this->tags;
    //    }

    /**
     * @return DataGroup[]
     */
    public function getDataGroups()
    {
        $data_groups = [];

        foreach ($this->tags->getTags() as $tag)
        {
            $data_groups = [...$data_groups, ...$tag->getDataGroups()];
        }

        return $data_groups;
    }

    /**
     * @return string
     */
    public function getOutput()
    {
        foreach ($this->tags->getTags() as $tag)
        {
            if ($tag->pass())
            {
                return $tag->getOutput();
            }
        }

        return '';
    }

    /**
     * @return Tag[]
     */
    public function getTags()
    {
        return $this->tags->getTags();
    }

    /**
     * @param string $html
     */
    public function replace(&$html)
    {
        if (empty($this->getTags()))
        {
            return;
        }

        $output = $this->getOutput();

        $html = RL_String::replaceOnce($this->match[0], $output, $html);
    }

    /**
     * @param array   $values
     * @param Numbers $numbers
     */
    public function setValues($values, Numbers $numbers)
    {
        foreach ($this->tags->getTags() as $tag)
        {
            $tag->setValues($values, $numbers);
        }
    }
}
