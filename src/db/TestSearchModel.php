<?php
namespace ethercap\opensearch\db;

/**
 * SearchModel 搜索类的基础类 和 类SearchQuery配合使用
 *
 * @see SearchQuery
 */
class TestSearchModel extends SearchModel
{
    public $search = 'search';

    /**
     *```
     * public function getIndexes() {
     *   return 'up_tags';
     * }
     *```
     * 获取搜索表的名字
     *
     * @return string
     */
    public function getIndexes()
    {
        return 'up_report';
    }

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
    public function getFields()
    {
        return [
            'id' => SearchModel::SEARCH_INT,
            'fromid' => SearchModel::SEARCH_INT,
            'fromtype' => SearchModel::SEARCH_INT,
            'name' => SearchModel::SEARCH_TEXT,
            'content' => SearchModel::SEARCH_TEXT,
            'contenttype' => SearchModel::SEARCH_LITERAL,
            'filetype' => SearchModel::SEARCH_LITERAL,
            'reportsize' => SearchModel::SEARCH_INT,
            'status' => SearchModel::SEARCH_INT,
            'tags' => SearchModel::SEARCH_TEXT,
            'downloadkeywords' => SearchModel::SEARCH_TEXT,
            'pages' => SearchModel::SEARCH_INT,
            'downloadtimes' => SearchModel::SEARCH_INT,
            'md5hash' => SearchModel::SEARCH_LITERAL,
            'uploaduserid' => SearchModel::SEARCH_INT,
            'uploadtime' => SearchModel::SEARCH_INT,
            'verifyuserid' => SearchModel::SEARCH_INT,
            'verifyusername' => SearchModel::SEARCH_TEXT,
            'verifytime' => SearchModel::SEARCH_INT,
            'url' => SearchModel::SEARCH_LITERAL,
        ];
    }
}
