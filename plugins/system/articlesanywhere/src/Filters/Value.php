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
use RegularLabs\Plugin\System\ArticlesAnywhere\DataGroups\Field;
use RegularLabs\Plugin\System\ArticlesAnywhere\Helpers\Data as DataHelper;
use RegularLabs\Plugin\System\ArticlesAnywhere\Helpers\DB;

class Value
{
    /* @var DataGroup */
    private $data_group;
    private $database_name;
    /* @var DataGroup */
    private $key_data_group;
    private $value;

    /**
     * @param string    $value
     * @param DataGroup $key_data_group
     */
    public function __construct($value, $key_data_group, $database_name = '')
    {
        $this->value          = $value;
        $this->key_data_group = $key_data_group;
        $this->database_name  = $database_name;

        $this->setDataGroup();
    }

    /**
     * @return ValuesObject
     */
    public function get()
    {
        if ($this->data_group)
        {
            return $this->getByDataGroup();
        }

        $value = DataHelper::getRangeObject($this->value);

        if ($value instanceof ValuesObject)
        {
            return $value;
        }

        $format   = '';
        $is_field = $this->key_data_group instanceof Field;

        if ($is_field)
        {
            $field = $this->key_data_group->getFieldType();

            if ( ! empty($field) && $field->type == 'calendar' && ! ($field->fieldparams->showtime ?? 1))
            {
                $format = 'Y-m-d';
            }
        }

        $value = DataHelper::getDateObject($this->value, ! $is_field, $format);

        if ($value instanceof ValuesObject)
        {
            return $value;
        }

        $operator = DB::getOperator($value);
        $value    = DB::removeOperator($value);

        if (in_array($operator, ['<=', '<', '<>'], true) && $value !== 'NULL')
        {
            return new ValuesObject(
                [
                    $operator . $value,
                    'NULL',
                ], 'OR'
            );
        }

        return new ValuesObject($operator . $value, 'OR');
    }

    public function getDataGroup()
    {
        return $this->data_group;
    }

    public function setDataGroup()
    {
        if ( ! str_contains($this->value, ':'))
        {
            return;
        }

        [$prefix] = RL_Array::toArray($this->value, ':');

        $no_operator = DB::removeOperator($prefix);

        if ( ! in_array($no_operator, ['this', 'input', 'user'], true))
        {
            return;
        }

        $this->data_group = DataHelper::getDataGroup($this->value, [], '', $this->database_name);
    }

    private function getByDataGroup()
    {
        $operator = DB::getOperator($this->value);
        $value    = $this->data_group->getOutputRaw();

        if (is_array($value) && ! empty($value))
        {
            $value[0] = $operator . $value[0];
        }

        if ( ! is_string($value) && ! is_numeric($value))
        {
            return new ValuesObject($value);
        }

        if (is_numeric($value) || empty($value))
        {
            return new ValuesObject($operator . $value);
        }

        $value = DataHelper::getDateObject($operator . $value, false, 'Y-m-d');

        if ($value instanceof ValuesObject)
        {
            return $value;
        }

        return new ValuesObject($value);
    }
}
