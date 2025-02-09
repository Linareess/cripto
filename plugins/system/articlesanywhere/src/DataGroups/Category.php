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

use Joomla\CMS\Layout\LayoutHelper as JLayout;
use Joomla\CMS\Router\Route as JRoute;
use Joomla\Component\Content\Site\Helper\RouteHelper as JContentHelperRoute;
use Joomla\Registry\Registry as JRegistry;
use RegularLabs\Library\RegEx as RL_RegEx;
use RegularLabs\Plugin\System\ArticlesAnywhere\Database;
use RegularLabs\Plugin\System\ArticlesAnywhere\DataGroups\Helpers\Image;
use RegularLabs\Plugin\System\ArticlesAnywhere\DataGroups\Helpers\Layout;
use RegularLabs\Plugin\System\ArticlesAnywhere\Helpers\Data;
use RegularLabs\Plugin\System\ArticlesAnywhere\Helpers\DB;
use RegularLabs\Plugin\System\ArticlesAnywhere\Helpers\Params;

class Category extends DataGroup
{
    protected static $data_key_aliases = [
        'name'           => 'title',
        'image-category' => 'image',
        'ordering'       => 'lft',
    ];
    protected static $default_data_key = 'category';
    protected static $ignore_group     = 'categories';
    protected static $layout_name      = 'category';
    protected static $main_table       = 'categories';
    protected static $prefix           = 'category';
    protected static $db_prefix        = 'category';

    public function getDatabaseKey($key = '', $add_prefix = true, $case = 'underscore')
    {
        $key = $key ?: $this->key;

        if ($key === 'category')
        {
            $key = 'title';
        }

        return parent::getDatabaseKey($key, $add_prefix, $case);
    }

    /**
     * @return array [table => field]
     */
    public function getGroupBys()
    {
        $group_bys = parent::getGroupBys();

        if ($this->getAttribute('one-per-category'))
        {
            $group_bys['article'] = 'catid';
        }

        return $group_bys;
    }

    /**
     * @return array [table => condition]
     */
    public function getJoins()
    {
        return [
            DB::quoteName('#__categories', 'category')
            => DB::quoteName('category.id') . ' = ' . DB::quoteName('article.catid'),
        ];
    }

    public function getQueryKeys()
    {
        switch ($this->key)
        {
            case 'category':
                return $this->hasAttribute('layout')
                    ? [
                        self::getDBPrefix() . '.title',
                        self::getDBPrefix() . '.id',
                        self::getDBPrefix() . '.language',
                        'article.attribs',
                    ]
                    : [self::getDBPrefix() . '.title'];

            case 'is-published':
                return [
                    self::getDBPrefix() . '.published',
                ];


            default:
                break;
        }


        return parent::getQueryKeys();
    }

    public function getRequiredQueryKeys()
    {
        return [self::getDBPrefix() . '.id'];
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        switch ($this->key)
        {
            case 'category':
                return $this->getValueCategory();

            case 'has-access':
                return $this->hasAccess();

            case 'is-published':
                return $this->isPublished();


            case '/link':
                return '</a>';

            default:
                break;
        }

        if (RL_RegEx::match('^image-(?<id>(random|[0-9]+))(?:-(?<type>.*?))?$', $this->key, $match))
        {
            return $this->getContentImageByMatch($match, $this->get('description'));
        }

        if (RL_RegEx::match('^image(?:-(?<type>.*?))?$', $this->key, $match))
        {
            return $this->getCategoryImageByMatch($match);
        }

        return parent::getValue();
    }

    /**
     * @return string
     */
    public function getValueCategory()
    {
        $layout = $this->getAttribute('layout', false);

        if ( ! $layout)
        {
            return $this->get(self::getDBPrefix() . '.title', '');
        }

        $layout_id = Layout::getId($layout, 'joomla.content.info_block.' . static::$layout_name);

        $displayData = [
            'item'   => (object) [
                'category_title'    => $this->get(self::getDBPrefix() . '.title'),
                'catid'             => $this->get(self::getDBPrefix() . '.id'),
                'category_language' => $this->get(self::getDBPrefix() . 'language'),
            ],
            'params' => new JRegistry($this->get('article.attribs')),
        ];

        return JLayout::render($layout_id, $displayData);
    }

    /**
     * @param array|object|string $values
     * @param string              $glue
     *
     * @return string
     */
    public function getWhere($values, $glue = 'OR')
    {
        if (empty($values))
        {
            return parent::getWhere($values, $glue);
        }

        return match ($this->filter_key)
        {
            'category'             => $this->getWhereFromCategoryComboKey($values, $glue),
            'id', 'alias', 'title' => $this->getWhereFromCategorySearchTypes($values, $glue),
            default                => parent::getWhere($values, $glue),
        };
    }

    /**
     * @return string
     */
    public function getWhereFromCategoryComboKey($values, $glue = 'OR')
    {
        $type_values = [
            'id'    => [],
            'alias' => [],
            'title' => [],
        ];

        $values = Data::valuesToSimpleArray($values);

        foreach ($values as $value)
        {
            $negative = ($value[0] ?? '') === '!';

            $no_operator = DB::removeOperator($value);

            if (is_numeric($no_operator))
            {
                $type_values['id'][] = $value;
            }

            if (strtolower($no_operator) === $no_operator)
            {
                $type_values['alias'][] = $value;
            }

            $type_values['title'][] = $value;

            if ($negative)
            {
                $glue = 'AND';
            }
        }

        $wheres = [];

        if ( ! empty($type_values['id']))
        {
            $wheres[] = DB::is($this->getDatabaseKey('id'), $type_values['id'], [
                'handle_wildcards' => false, 'glue' => $glue
            ]);
        }

        if ( ! empty($type_values['alias']))
        {
            $wheres[] = DB::is($this->getDatabaseKey('alias'), $type_values['alias'], [
                'glue' => $glue, 'quote' => true
            ]);
        }

        if ( ! empty($type_values['title']))
        {
            $wheres[] = DB::is($this->getDatabaseKey('title'), $type_values['title'], [
                'glue' => $glue, 'quote' => true
            ]);
        }

        $wheres = DB::combine($wheres, $glue);

        if ( ! empty($this->getAttribute('include-child-categories')))
        {
            return $this->getWhereWithChildren($wheres);
        }

        return $wheres;
    }

    /**
     * @return string
     */
    public function getWhereFromCategorySearchTypes($values, $glue = 'OR')
    {
        $values = Data::valuesToSimpleArray($values);

        $where = DB::is($this->getDatabaseKey(), $values, compact('glue'));

        if ( ! empty($this->getAttribute('include-child-categories')))
        {
            return $this->getWhereWithChildren([$where]);
        }

        return $where;
    }

    public function isPublished()
    {
        return $this->get('state') == 1;
    }

    protected static function getExtraFields()
    {
        return [
            'has-access', 'is-published',
            'url', 'sefurl', 'link', '/link', 'readmore',
            'image', 'image-url',
        ];
    }

    protected static function getJsonKeys()
    {
        return [
            'params'   => [
                'category_layout',
                'image',
                'workflow_id',
            ],
            'metadata' => [
                'author',
                'robots',
            ],
        ];
    }

    protected function getCategoryImageByMatch($match)
    {
        $type = $this->subkey ?: $match['type'] ?? 'tag';

        if ($type === 'tag' && $this->getAttribute('layout'))
        {
            $type = 'layout';
        }

        $image_data = (object) [
            'type' => 'full_image',
            'src'  => $this->get(self::getDBPrefix() . '.params.image'),
            'alt'  => $this->get(self::getDBPrefix() . '.params.image_alt'),
        ];

        return Image::getOutputByKey($type, $image_data, $this->attributes, 'category');
    }

    private function getChildIds($parent_ids = [], $level = 1)
    {
        if (empty($parent_ids))
        {
            return [];
        }

        $include_child_categories = $this->getAttribute('include-child-categories');

        if (is_numeric($include_child_categories) && $include_child_categories < $level)
        {
            return [];
        }

        $query = DB::getQuery()
            ->select('category.id')
            ->from(DB::quoteName('#__categories', 'category'))
            ->where(DB::is('category.parent_id', $parent_ids, ['handle_wildcards' => false]));

        $database = new Database($this->database_name);
        $children = $database->getResults($query);

        if (empty($children))
        {
            return [];
        }

        return [...$children, ...$this->getChildIds($children, $level++)];
    }

    private function getIdsByWhere($where)
    {
        $query = DB::getQuery()
            ->select('category.id')
            ->from(DB::quoteName('#__categories', 'category'))
            ->where($where);

        $database = new Database($this->database_name);

        return $database->getResults($query);
    }

    private function getLink()
    {
    }

    private function getSefUrl()
    {
    }

    private function getUrl()
    {
    }

    private function getWhereWithChildren($where)
    {
        $parent_ids = $this->getIdsByWhere($where);

        if (empty($parent_ids))
        {
            return [];
        }

        $child_ids = $this->getChildIds($parent_ids);

        $all_ids = array_unique([...$parent_ids, ...$child_ids]);

        return DB::is('article.catid', $all_ids, ['handle_wildcards' => false]);
    }

    private function hasAccess()
    {
        return in_array($this->get('access'), Params::getAuthorisedViewLevels());
    }
}
