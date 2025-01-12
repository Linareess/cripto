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

class ValuesObject
{
    private $glue;
    private $values;

    /**
     * @param array|string $value
     * @param string       $glue
     */
    public function __construct($values, $glue = 'OR')
    {
        $this->values = is_array($values) ? $values : [$values];
        $this->glue   = $glue;
    }

    /**
     * @return string
     */
    public function getGlue()
    {
        return $this->glue;
    }

    /**
     * @return array
     */
    public function getValues()
    {
        return $this->values;
    }
}
