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

namespace RegularLabs\Plugin\System\ArticlesAnywhere\DataGroups\Helpers;

defined('_JEXEC') or die;

use RegularLabs\Plugin\System\ArticlesAnywhere\Helpers\Data as DataHelper;

class Calculation
{
    /**
     * @param Text $string
     */
    public static function process($string, $key, $attributes)
    {
        if (empty($attributes->calc) || ! is_numeric($string))
        {
            return $string;
        }

        return DataHelper::calculate($string, $attributes->calc);
    }
}
