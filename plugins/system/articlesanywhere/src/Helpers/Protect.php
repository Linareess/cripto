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

use RegularLabs\Library\Protect as RL_Protect;

class Protect
{
    static $name = 'Articles Anywhere';

    public static function _(&$string)
    {
        RL_Protect::protectHtmlCommentTags($string);
        RL_Protect::protectFields($string, Params::getTags(true));

        if (Params::get()->disable_in_sourcerer)
        {
            RL_Protect::protectSourcerer($string);
        }
    }

    /**
     * Get the html end comment tags
     *
     * @return string
     */
    public static function getCommentEndTag()
    {
        return RL_Protect::getCommentEndTag(self::$name);
    }

    /**
     * Get the html start comment tags
     *
     * @return string
     */
    public static function getCommentStartTag()
    {
        return RL_Protect::getCommentStartTag(self::$name);
    }

    /**
     * Wrap the comment in comment tags
     *
     * @param string $comment
     *
     * @return string
     */
    public static function getMessageCommentTag($comment)
    {
        return RL_Protect::getMessageCommentTag(self::$name, $comment);
    }

    public static function protectTags(&$string)
    {
        RL_Protect::protectTags($string, Params::getTags(true));
    }

    public static function unprotectTags(&$string)
    {
        RL_Protect::unprotectTags($string, Params::getTags(true));
    }

    /**
     * Wrap the comment in comment tags
     *
     * @param string $comment
     *
     * @return string
     */
    public static function wrapInCommentTags($comment)
    {
        return RL_Protect::wrapInCommentTags(self::$name, $comment);
    }
}
