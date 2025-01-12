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

use RegularLabs\Plugin\System\ArticlesAnywhere\DataGroups\DataGroup;
use RegularLabs\Plugin\System\ArticlesAnywhere\Numbers\Numbers;

class Tag
{
    private Conditions $conditions;
    private array      $match;
    private string     $type;

    /**
     * @param array $match
     */
    public function __construct($match)
    {
        $this->match = $match;

        $this->type = str_replace(' ', '', $match['type']);

        $this->conditions = new Conditions($match['condition'] ?? '');
    }

    /**
     * @return Condition[]
     */
    public function getConditions()
    {
        return $this->conditions->getConditions();
    }

    /**
     * @return DataGroup[]
     */
    public function getDataGroups()
    {
        $data_groups = [];

        foreach ($this->conditions->getConditions() as $condition)
        {
            $data_groups = [...$data_groups, ...$condition->getDataGroups()];
        }

        return $data_groups;
    }

    /**
     * @return string
     */
    public function getOutput()
    {
        return $this->match['content'];
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @return bool
     */
    public function pass()
    {
        if ($this->type === 'else')
        {
            return true;
        }

        $pass     = false;
        $operator = $this->conditions->getOperator();

        foreach ($this->conditions->getConditions() as $condition)
        {
            $pass = $condition->pass();

            if ($pass && $operator === 'OR')
            {
                return true;
            }

            if ( ! $pass && $operator === 'AND')
            {
                return false;
            }
        }

        return $pass;
    }

    //    /**
    //     * @param string $html
    //     */
    //    public function replace(&$html)
    //    {
    //        $output = $this->getOutput();
    //
    //        $html = RL_String::replaceOnce($this->match[0], $output, $html);
    //    }

    /**
     * @param mixed $value
     */
    public function setValue($value)
    {
        $this->value = $value;
    }

    /**
     * @param array   $values
     * @param Numbers $numbers
     */
    public function setValues($values, Numbers $numbers)
    {
        foreach ($this->conditions->getConditions() as $condition)
        {
            $condition->setValues($values, $numbers);
        }
    }
}
