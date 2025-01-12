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

namespace RegularLabs\Plugin\System\ArticlesAnywhere\DataGroups;

defined('_JEXEC') or die;

use RegularLabs\Plugin\System\ArticlesAnywhere\Helpers\DB;

class Modifier extends DataGroup
{
    protected static $database_column_case = 'camel';
    protected static $default_data_key     = 'name';
    protected static $main_table           = 'users';
    protected static $prefix               = 'modifier';
    protected static $db_prefix            = 'modifier';

    /**
     * @return array [table => condition]
     */
    public function getJoins()
    {
        return [
            DB::quoteName('#__users', 'modifier')
            => DB::quoteName('modifier.id') . ' = ' . DB::quoteName('article.modified_by'),
        ];
    }

    public function getRequiredQueryKeys()
    {
        return ['modifier.id'];
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        if ($this->key === 'password')
        {
            return '***';
        }

        return parent::getValue();
    }

    protected static function getJsonKeys()
    {
        return [
            'params' => [
                'admin_style',
                'admin_language',
                'language',
                'editor',
                'timezone',
                'a11y_mono',
                'a11y_contrast',
                'a11y_highlight',
                'a11y_font',
            ],
        ];
    }
}
