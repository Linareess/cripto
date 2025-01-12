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

namespace RegularLabs\Plugin\System\ArticlesAnywhere\Helpers;

defined('_JEXEC') or die;

use Joomla\CMS\Factory as JFactory;
use Joomla\Database\DatabaseDriver as JDatabaseDriver;
use Joomla\Database\DatabaseQuery as JDatabaseQuery;
use Joomla\Database\QueryInterface;
use RegularLabs\Library\Cache as RL_Cache;
use RegularLabs\Library\DB as RL_DB;
use RegularLabs\Plugin\System\ArticlesAnywhere\Database;

class DB
{
    private static $query_cache_time;

    public static function __callStatic(string $name, array $arguments): mixed
    {
        return call_user_func_array([RL_DB::class, $name], $arguments);
    }

    public static function get($database_name = ''): Database
    {
        $cache = new RL_Cache([__METHOD__, $database_name]);

        if ($cache->exists())
        {
            return $cache->get();
        }

        $db = new Database($database_name);

        return $cache->set($db);
    }

    public static function getDbo(): JDatabaseDriver
    {
        return self::get()->getDbo();
    }

    public static function getNullDate(): string
    {
        return self::getDbo()->getNullDate();
    }

    public static function getQuery(): JDatabaseQuery
    {
        return self::getDbo()->getQuery(true);
    }

    public static function getQueryTime(): int
    {
        if ( ! is_null(self::$query_cache_time))
        {
            return self::$query_cache_time;
        }

        self::$query_cache_time = (int) Params::get()->query_cache_time ?: JFactory::getApplication()->get('cachetime');

        return self::$query_cache_time;
    }

    public static function getResults(
        string|QueryInterface $query,
        string                $return_type = 'column',
        bool                  $allow_caching = true
    ): mixed
    {
        return self::get()->getResults($query, $return_type, $allow_caching);
    }

    public static function quote(array|string $text, bool $escape = true): string
    {
        return self::getDbo()->quote($text, $escape);
    }

    public static function quoteName(array|string $name, array|string $as = null): array|string
    {
        return self::getDbo()->quoteName($name, $as);
    }
}
