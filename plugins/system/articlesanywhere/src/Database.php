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

namespace RegularLabs\Plugin\System\ArticlesAnywhere;

defined('_JEXEC') or die;

use Joomla\CMS\Factory as JFactory;
use Joomla\Database\DatabaseDriver as JDatabaseDriver;
use Joomla\Database\QueryInterface as JQueryInterface;
use RegularLabs\Library\Cache as RL_Cache;
use RegularLabs\Plugin\System\ArticlesAnywhere\Helpers\DB;
use RegularLabs\Plugin\System\ArticlesAnywhere\Helpers\Params;

class Database
{
    public $name;
    public $settings;
    /**
     * @var JDatabaseDriver
     */
    private $db;

    public function __construct($name = '')
    {
        $this->name = $name;
        $this->db = $this->getDbo();
    }

    /**
     * @return JDatabaseDriver|null
     */
    public function getDbo()
    {
        if ( ! is_null($this->db))
        {
            return $this->db;
        }

            $this->db = JFactory::getDbo();

            return $this->db;
    }

    /**
     * @param string|JQueryInterface $query
     * @param string                 $return_type
     * @param bool                   $allow_caching
     *
     * @return mixed
     */
    public function getResults($query, $return_type = 'column', $allow_caching = true)
    {
        $query_cache_id = '';
        $params         = Params::get();

        if ($allow_caching)
        {
            $force_caching = (int) $params->use_query_cache === 2;

            $query_cache_id = [
                __METHOD__, $this->name, $return_type, self::getQueryCacheString($query)
            ];

            $cache = (new RL_Cache($query_cache_id));

            if ($params->use_query_cache)
            {
                $cache->useFiles(
                    DB::getQueryTime(),
                    $force_caching
                );
            }
        }

        if ($allow_caching && $cache->exists())
        {
            return $cache->get();
        }

        $method = 'load' . ucfirst($return_type);

        $use_query_log_cache = $allow_caching && $params->use_query_comments && $params->use_query_log_cache;

        if (JDEBUG || $params->use_query_comments)
        {
            $backtrace = DB::getQueryComment();
        }

        if ($use_query_log_cache)
        {
            $query_cache = ''
                . "\n\n" . 'QUERY:' . "\n==========\n" . trim((string) $query)
                . "\n\n" . 'METHOD: ' . "\n==========\n" . $method
                . "\n\n" . 'BACKTRACE:' . "\n==========\n" . str_replace(' => ', "\n", $backtrace)
                . "\n\n";
        }

        if (JDEBUG || $params->use_query_comments)
        {
            $query->select(
                $this->db->quote($backtrace) . ' as ' . $this->db->quote('query_comment')
            );
        }


        $result = $this->db->setQuery($query)->$method();

        if ( ! $allow_caching)
        {
            return $result;
        }

        if ($use_query_log_cache)
        {
            (new RL_Cache($query_cache_id, 'regularlabs_query'))
                ->useFiles(
                    DB::getQueryTime() * 60,
                    true
                )
                ->set($query_cache);
        }

        return $cache->set($result);
    }

    public function getSetting($name)
    {
    }

    public function getSettings()
    {
    }

    private static function getQueryCacheString($query)
    {
        $nowDate = DB::getNowDate();
        $query   = (string) $query;

        $query = str_replace($nowDate, '??', $query);

        return $query;
    }
}
