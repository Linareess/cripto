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

namespace RegularLabs\Plugin\System\ArticlesAnywhere\Filters;

defined('_JEXEC') or die;

use RegularLabs\Library\ArrayHelper as RL_Array;
use RegularLabs\Plugin\System\ArticlesAnywhere\DataGroups\DataGroup;
use RegularLabs\Plugin\System\ArticlesAnywhere\DataGroups\Helpers\Date;
use RegularLabs\Plugin\System\ArticlesAnywhere\Helpers\Data;
use RegularLabs\Plugin\System\ArticlesAnywhere\Helpers\DB;

class Filter
{
    /* @var DataGroup */
    private        $data_group;
    private string $database_name;
    private string $glue = 'OR';
    private        $key;
    /* @var Value[] */
    private array $values = [];

    /**
     * @param string    $key
     * @param string    $values
     * @param DataGroup $data_group
     */
    public function __construct($key, $values, $data_group, $database_name = '')
    {
        $this->data_group    = $data_group;
        $this->key           = $key;
        $this->database_name = $database_name;

        $this->setValuesAndGlue($values);
    }

    public function getDataGroup()
    {
        return $this->data_group;
    }

    public function getGlue()
    {
        return $this->glue;
    }

    public function getKey()
    {
        return $this->key;
    }

    /**
     * @return array
     */
    public function getValueDataGroups()
    {
        $data_groups = [];

        foreach ($this->values as $value)
        {
            $data_group = $value->getDataGroup();

            if ( ! $data_group)
            {
                continue;
            }

            $data_groups[] = $data_group;
        }

        return $data_groups;
    }

    public function getValues()
    {
        $values = [];

        foreach ($this->values as $value)
        {
            $values[] = $value->get();
        }

        return $values;
    }

    private function setValues($values)
    {
        $key = $this->key;

        if ( ! str_contains($key, ':'))
        {
            $key .= ':id';
        }

        foreach ($values as $value)
        {
            if ($value === 'current')
            {
                $value = 'this:' . $key;
            }

            if ($value === '!current')
            {
                $value = '!this:' . $key;
            }

            $this->values[] = new Value($value, $this->data_group, $this->database_name);
        }
    }

    private function setValuesAndGlue($values)
    {
        if (is_numeric($values))
        {
            $this->setValues([$values]);

            return;
        }

        if (Data::isRange($values))
        {
            $this->setValues([$values]);

            return;
        }

        $clean_value = DB::removeOperator($values);
        $date_value  = Data::placeholderToDate($clean_value);
        $is_date     = ($date_value instanceof ValuesObject) || Date::valueIsDate($date_value);

        if ($values !== '+' && ! $is_date)
        {
            $values = str_replace(
                ['\\,', '\\+'],
                ['[:COMMA:]', '[:PLUS:]'],
                $values
            );

            $values = str_replace(
                [',', '+'],
                [' || ', ' && '],
                $values
            );

            $values = str_replace(
                ['[:COMMA:]', '[:PLUS:]'],
                [',', '+'],
                $values
            );
        }

        // no AND separator (&&) found. So handle everything as OR's
        if ( ! str_contains($values, ' && '))
        {
            $values = RL_Array::toArray($values, ' || ');

            if (empty($values))
            {
                $values = [''];
            }

            $this->setValues($values);

            return;
        }

        // AND separator (&&) found. So handle everything as AND's
        $this->glue = 'AND';

        $values = str_replace(' || ', ' && ', $values);
        $values = RL_Array::toArray($values, ' && ');

        $this->setValues($values);
    }
}
