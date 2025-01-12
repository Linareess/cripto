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

use Joomla\CMS\Layout\FileLayout as JFileLayout;

class Layout
{
    public static function getId($layout, $default = '', $prefix = '')
    {
        $prefix = $prefix ? $prefix . '.' : '';

        if ( ! $layout || $layout === true || $layout === 'default' || $layout === $default)
        {
            return $prefix . $default;
        }

        $layout = self::getDottedPath($layout, $prefix);

        return $layout;
    }

    public static function render($layoutId, $displayData = [], $options = [])
    {
        $layout = new JFileLayout(
            $layoutId,
            null,
            $options
        );

        return $layout->addIncludePath(JPATH_SITE)->render($displayData);
    }

    private static function getDottedPath($path, $prefix = '')
    {
        $prefix = $prefix ? $prefix . '.' : '';

        $path = str_replace('.php', '', $path);
        $path = str_replace('/', '.', trim($path, '/'));

        if ( ! str_contains($path, '.'))
        {
            $path = $prefix . $path;
        }

        return $path;
    }
}
