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

use RegularLabs\Library\ArrayHelper as RL_Array;
use RegularLabs\Library\StringHelper as RL_String;

class Conditions
{
    private array  $conditions = [];
    private string $operator   = 'AND';

    /**
     * @param string $string
     */
    public function __construct($string)
    {
        $this->setConditions($string);
    }

    /**
     * @return Condition[]
     */
    public function getConditions()
    {
        return $this->conditions;
    }

    /**
     * @return string
     */
    public function getOperator()
    {
        return $this->operator;
    }

    /**
     * @param string $string
     */
    private function setConditions($string)
    {
        if (empty($string))
        {
            return;
        }

        $string = RL_String::html_entity_decoder($string);

        $string = str_replace(
            [' AND ', ' OR '],
            [' && ', ' || '],
            $string
        );

        $this->operator = str_contains($string, ' || ') ? 'OR' : 'AND';

        $string = str_replace(' && ', ' || ', $string);

        $parts = RL_Array::toArray($string, ' || ');

        foreach ($parts as $part)
        {
            $this->conditions[] = new Condition($part);
        }
    }
}
