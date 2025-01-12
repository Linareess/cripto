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

namespace RegularLabs\Plugin\System\ArticlesAnywhere\ForeachTags;

defined('_JEXEC') or die;

use RegularLabs\Library\RegEx as RL_RegEx;
use RegularLabs\Plugin\System\ArticlesAnywhere\Helpers\Params;
use RegularLabs\Plugin\System\ArticlesAnywhere\IfStatements\IfStatements;
use RegularLabs\Plugin\System\ArticlesAnywhere\Numbers\Numbers;

class Tags
{
    private $database_name;
    /**
     * @var IfStatements
     */
    private $if_statements;
    /**
     * @var Tag[]
     */
    private array $tags = [];

    /**
     * @param string $string
     */
    public function __construct($string, $database_name = '')
    {
    }

    /**
     * @return array
     */
    public function getDataGroups()
    {
    }

    /**
     * @return Tag[]
     */
    public function getTags()
    {
    }

    /**
     * @param $html
     */
    public function replace(&$html)
    {
    }

    /**
     * @param array   $values
     * @param Numbers $numbers
     */
    public function setValues($values, Numbers $numbers)
    {
    }

    /**
     * @param string $string
     */
    private function setTags($string)
    {
    }
}
