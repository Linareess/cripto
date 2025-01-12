<?php

/**
 * @package         Regular Labs Library
 * @version         24.10.15076
 * 
 * @author          Peter van Westen <info@regularlabs.com>
 * @link            https://regularlabs.com
 * @copyright       Copyright © 2024 Regular Labs All Rights Reserved
 * @license         GNU General Public License version 2 or later
 */
namespace RegularLabs\Library;

use Joomla\CMS\Factory as JFactory;
use Joomla\CMS\User\User as JUser;
use Joomla\CMS\User\UserFactoryInterface;
defined('_JEXEC') or die;
class User
{
    public static function get(string $key = ''): mixed
    {
        $user = JFactory::getApplication()->getIdentity() ?: JFactory::getUser();
        if (empty($key)) {
            return $user;
        }
        return $user->{$key} ?? null;
    }
    public static function getId(): int
    {
        return (int) self::get('id');
    }
    public static function getEmail(): string
    {
        return (string) self::get('email');
    }
    public static function getName(): string
    {
        return (string) self::get('name');
    }
    public static function getUsername(): string
    {
        return (string) self::get('username');
    }
    public static function getByEmail(string $email): ?JUser
    {
        return self::getByKey('email', $email);
    }
    public static function getById(int $id): ?JUser
    {
        return JFactory::getContainer()->get(UserFactoryInterface::class)->loadUserById($id);
    }
    public static function getByKey(string $key, string $value): ?JUser
    {
        $id = self::getIdByKey($key, $value);
        if (!$id) {
            return null;
        }
        return self::getById($id);
    }
    public static function getByUsername(string $username): ?JUser
    {
        return self::getByKey('username', $username);
    }
    public static function isAdministrator($id = null): bool
    {
        return self::get($id)->authorise('core.admin');
    }
    public static function isGuest($id = null): bool
    {
        return self::get($id)->guest;
    }
    private static function getIdByKey(string $key, string $value): int
    {
        $db = JFactory::getDbo();
        $query = $db->getQuery(\true)->select($db->quoteName('id'))->from($db->quoteName('#__users'))->where($db->quoteName($key) . ' = :value')->bind(':value', $value)->setLimit(1);
        $db->setQuery($query);
        return (int) $db->loadResult();
    }
}
