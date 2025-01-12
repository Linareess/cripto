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
use RegularLabs\Library\ObjectHelper as RL_Object;
use RegularLabs\Library\RegEx as RL_RegEx;
use RegularLabs\Library\StringHelper as RL_String;
use RegularLabs\Plugin\System\ArticlesAnywhere\DataGroups\DataGroup;
use RegularLabs\Plugin\System\ArticlesAnywhere\Helpers\Data as DataHelper;
use RegularLabs\Plugin\System\ArticlesAnywhere\Numbers\Numbers;

class Condition
{
    private string $operator;
    private        $value1;
    private        $value2;

    /**
     * @param array $match
     */
    public function __construct($condition)
    {
        $condition = $this->getConditionParts(trim($condition));

        if (is_null($condition))
        {
            return;
        }

        $this->value1   = $condition->value1;
        $this->value2   = $condition->value2;
        $this->operator = $this->getOperator($condition->operator);
    }

    /**
     * @return DataGroup[]
     */
    public function getDataGroups()
    {
        return [
            ...$this->getDataGroupsFromValue($this->value1),
            ...$this->getDataGroupsFromValue($this->value2),
        ];
    }

    /**
     * @param $value
     *
     * @return array
     */
    public function getDataGroupsFromValue($value)
    {
        $data_groups = [];

        if (is_array($value))
        {
            foreach ($value as $sub_value)
            {
                $data_groups = [...$data_groups, ...$this->getDataGroupsFromValue($sub_value)];
            }

            return $data_groups;
        }

        if ($value instanceof DataGroup)
        {
            $data_groups[] = $value;
        }

        return $data_groups;
    }

    /**
     * @return mixed
     */
    public function pass()
    {
        $value1 = DataHelper::getValueFromValue($this->value1);
        $value2 = DataHelper::getValueFromValue($this->value2);

        if ( ! in_array($this->operator, ['IN', '!IN'], true))
        {
            $value1 = RL_Array::implode($value1);
            $value2 = RL_Array::implode($value2);
        }

        if (in_array($this->operator, ['IN', '!IN'], true))
        {
            $value2 = RL_Array::toArray($value2);
        }

        switch ($this->operator)
        {
            case '!=' :
                if ($this->isRegexValue($value2))
                {
                    return ! RL_RegEx::match(
                        $this->prepareRegexValue($value2),
                        $value1
                    );
                }

                return $value1 != $value2;

            case '<' :
                return $value1 < $value2;

            case '>' :
                return $value1 > $value2;

            case '<=' :
                return $value1 <= $value2;

            case '>=' :
                return $value1 >= $value2;

            case '<=>' :
                return $value1 <=> $value2;

            case 'TRUE' :
                return $value1;

            case 'FALSE' :
                return ! $value1;

            case 'IN' :
                return RL_Array::find($value1, $value2, false);

            case '!IN' :
                return ! RL_Array::find($value1, $value2, false);

            default:
            case '==' :
                if ($this->isRegexValue($value2))
                {
                    return RL_RegEx::match(
                        $this->prepareRegexValue($value2),
                        $value1
                    );
                }

                return $value1 == $value2;
        }
    }

    /**
     * @param         $values
     * @param Numbers $numbers
     */
    public function setValues($values, Numbers $numbers)
    {
        if ( ! is_array($values))
        {
            $this->value1 = $values;

            return;
        }

        // @TODO: Get values from (grand)parent Articles and Article classes
        // So we need to inject them down the line!
        if ($this->value1 instanceof DataGroup)
        {
            $key = $this->value1->getKey();

            $value_values = (array) $values[$key] ?? [];

            $this->value1->setValues($value_values, $numbers);
        }

        if ($this->value2 instanceof DataGroup)
        {
            $key = $this->value2->getKey();

            $value_values = (array) $values[$key] ?? [];

            $this->value2->setValues($value_values, $numbers);
        }
    }

    /**
     * @param $condition
     *
     * @return null|object
     */
    private function getConditionParts($condition)
    {
        if (empty($condition))
        {
            return null;
        }

        // start with most unique
        $operators = [
            '!===', '!==', '!=',
            '<=>', '&lt;=&gt;',
            '>=', '&gt;=',
            '<=', '&lt;=',
            '>', '&gt;',
            '<', '&lt;',
            '===', '==', '=',
        ];

        $spaced_operators = [
            '!IN', 'NOT IN',
            'IN',
        ];

        RL_RegEx::match(
            '^(?<value1>.*?)\s*' . RL_RegEx::quote($operators, 'operator') . '\s*(?<value2>.*?)$',
            $condition,
            $match
        );

        if ( ! empty($match))
        {
            return self::getConditionPartsFromMatch($match);
        }

        RL_RegEx::match(
            '^(?<value1>.*?)\s+' . RL_RegEx::quote($spaced_operators, 'operator') . '\s+(?<value2>.*?)$',
            $condition,
            $match
        );

        if ( ! empty($match))
        {
            return self::getConditionPartsFromMatch($match);
        }

        return self::getConditionPartsObject($condition);
    }

    /**
     * @param $match
     *
     * @return object
     */
    private function getConditionPartsFromMatch($match)
    {
        return self::getConditionPartsObject($match['value1'], $match['value2'] ?? '', $match['operator'] ?? '');
    }

    /**
     * @param        $value1
     * @param null   $value2
     * @param string $operator
     *
     * @return object
     */
    private function getConditionPartsObject($value1, $value2 = null, $operator = '')
    {
        $operator = self::getOperator($operator);

        if (empty($operator))
        {
            $negative = $value1[0] === '!';
            $value1   = RL_String::ltrim($value1, '!');

            $operator = $negative ? 'FALSE' : 'TRUE';
        }

        $value1 = DataHelper::getValue($value1);
        $value2 = DataHelper::getValue($value2);

        if ($value1 === '[:current:]' && $value2 instanceof DataGroup)
        {
            /* @var DataGroup $value1 */
            $value1 = RL_Object::clone($value2);
            $value1->setArticleSelector('this');
        }

        if ($value2 === '[:current:]' && $value1 instanceof DataGroup)
        {
            /* @var DataGroup $value2 */
            $value2 = RL_Object::clone($value1);
            $value2->setArticleSelector('this');
        }

        return (object) compact('value1', 'value2', 'operator');
    }

    /**
     * @param $operator
     *
     * @return string|string[]
     */
    private function getOperator($operator)
    {
        $operator = str_replace(['===', '=='], '=', $operator);
        $operator = str_replace(['&lt;', '&gt;'], ['<', '>'], $operator);
        $operator = strtoupper($operator);
        $operator = str_replace('NOT ', '!', $operator);

        if ($operator === '=')
        {
            return '==';
        }

        return $operator;
    }

    /**
     * @param string $string
     *
     * @return bool
     */
    private function isRegexValue($string)
    {
        return str_contains($string, '*');
    }

    /**
     * @param string $string
     *
     * @return string
     */
    private function prepareRegexValue($string)
    {
        return '^' . str_replace('\*', '.*?', RL_RegEx::quote($string)) . '$';
    }
}
