<?php

namespace ethercap\opensearch\db;

use Yii;
use lspbupt\common\helpers\ArrayHelper;
use lspbupt\curl\CurlHttp;
use yii\base\Model;
use yii\di\Instance;

/**
 * SearchModel 搜索类的基础类 和 类SearchQuery配合使用
 *
 * @see SearchQuery
 * @see TestSearchModel 这里有一个使用示例
 *
 * @property CurlHttp $search
 */
abstract class SearchModel extends Model
{
    const SEARCH_INT = 1;
    const SEARCH_TEXT = 2;
    const SEARCH_INT_ARRAY = 3;
    const SEARCH_LITERAL_ARRAY = 4;
    const SEARCH_SHORT_TEXT = 5;
    const SEARCH_LITERAL = 6;

    const INNER_JOIN_TYPE_AND = 'AND';
    const INNER_JOIN_TYPE_OR = 'OR';

    /**
     * @var CurlHttp
     *               获取search系统对应的component, 类似getDb
     */
    public $search = 'search';

    public function init()
    {
        $this->search = Instance::ensure($this->search, CurlHttp::class);
        parent::init();
    }

    /**
     * 获取搜索表的名字 类似tablename
     *```
     * public function getIndexes() {
     *   return 'up_tags';
     * }
     *```
     *
     * @return string
     */
    abstract public function getIndexes();

    /**
     *```
     * public function getFields() {
     *   return [
     *       'id' => SearchModel::SEARCH_INT,
     *       'flag' => SearchModel::SEARCH_INT,
     *       'tags' => SearchModel::SEARCH_LITERAL_ARRAY,
     *       'title' => SearchModel::SEARCH_TEXT,
     *       'short_title' => SearchModel::SEARCH_SHORT_TEXT,
     *       'outId' => SearchModel::SEARCH_LITERAL,
     *   ];
     * }
     *```
     * 字段格式 注意大小写不敏感, 实际上opensearch里面只有小写 这里会自己转一次
     *
     * @return array
     */
    abstract public function getFields();

    /**
     * @return SearchQuery
     */
    public static function openSearch()
    {
        return Yii::createObject(['class' => SearchQuery::class, 'modelClass' => get_called_class()]);
    }

    public function getFieldType($field)
    {
        return ArrayHelper::getRightValue($this->getFields(), $field, SearchModel::SEARCH_INT);
    }

    public static function openSuggest()
    {
    }
}
