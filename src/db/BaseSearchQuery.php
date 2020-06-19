<?php

namespace ethercap\opensearch\db;
use lspbupt\common\db\search\filter\InOperatorFilterCondition;
use lspbupt\common\db\search\filter\OneOperatorFilterCondition;
use lspbupt\common\db\search\filter\TwoOperatorFilterCondition;
use Yii;
use yii\base\Model;
use lspbupt\common\helpers\ArrayHelper;
use lspbupt\common\helpers\StringHelper;
use yii\helpers\Json;

/**
 * BaseSearchQuery: 支持搜索客户端的搜索基类
 *
 * @property string      $originQuery         原始查询词
 * @property SearchModel $searchModelInstance 搜索的model
 */
class BaseSearchQuery extends Model
{
    /** 真实的搜索用到的字段 */
    public $summary;
    public $format;
    public $indexes;
    public $query;
    public $fields;
    public $limit;
    public $offset;
    /* sort: -status,-fundId,source,friendFundName */
    public $sort;
    public $filter;
    /* 精排表达式 */
    public $formulaName;
    /* 参与精排计算的文档数 范围[0-2000] 默认200, 意味着最多只有前2000个进入精排, 所以粗排也同样重要 */
    public $rerankSize;

    /** 扩展的字段, 如飘红字段 */
    public $markFields;
    public $markCommonConfig;
    public $page;

    /* 原始的查询词 */
    public $_originQueryWord;
    /* query的索引 */
    public $queryKey;
    /* 是否切分query词 变成多词查询 */
    public $multiQuery;
    /* 切分query词的分隔字符 默认为',' */
    public $multiQuerySeparator;
    // 是否使用原始的query, 有些query比较复杂, 由子类自己加工
    public $useOriginQuery = false;
    /* @var array 过滤器集合 */
    public $_filterArr = [];
    /* 用来存放外部的统计参数等 如userId/utm */
    public $extraArr = [];

    private static $searchModel;
    public $modelClass;
    public $modelInstance;
    public $searchModelInstance;

    const CONDITION_AND = 'AND';
    const CONDITION_OR = 'OR';

    /* 默认飘红设置 */
    const SUMMARY_SNIPPED = 3;
    const SUMMARY_LEN = 300;
    const SUMMARY_ELEMENT = 'em';
    const SUMMARY_ELLIPSIS = '...';
    const SUMMARY_ELEMENT_PREFIX = '<em>';
    const SUMMARY_ELEMENT_POSTFIX = '</em>';

    private static $_commonConfigs = [
        'default' => [
            'summary_len' => self::SUMMARY_LEN,
            'summary_element' => self::SUMMARY_ELEMENT,
            'summary_ellipsis' => self::SUMMARY_ELLIPSIS,
            'summary_snipped' => self::SUMMARY_SNIPPED,
            'summary_element_prefix' => self::SUMMARY_ELEMENT_PREFIX,
            'summary_element_postfix' => self::SUMMARY_ELEMENT_POSTFIX,
        ],
        'app' => [
            'summary_len' => self::SUMMARY_LEN,
            'summary_element' => self::SUMMARY_ELEMENT,
            'summary_ellipsis' => self::SUMMARY_ELLIPSIS,
            'summary_snipped' => self::SUMMARY_SNIPPED,
            'summary_element_prefix' => '<font color="#ff5559">',
            'summary_element_postfix' => '</font>',
        ],
    ];

    const DEFAULT_LIMIT = 10;
    const DEFAULT_RERANK_SIZE = 2000;

    public function rules()
    {
        return [
            ['_originQueryWord', 'filter', 'filter' => function () {
                return $this->query;
            }],
            ['queryKey', 'default', 'value' => 'default'],
            ['queryKey', 'filter', 'filter' => [$this, 'formatQueryKey']],
            ['format', 'default', 'value' => 'json'],
            [['offset', 'page', ], 'default', 'value' => 0],
            [['sort', ], 'default', 'value' => ['RANK' => '-']],
            ['offset', 'filter', 'filter' => [$this, 'computeOffset'], 'when' => function ($model) {
                return empty($model->offset);
            }],
            ['formulaName', 'default', 'value' => ''],
            ['limit', 'default', 'value' => self::DEFAULT_LIMIT],
            ['rerankSize', 'default', 'value' => self::DEFAULT_RERANK_SIZE],
            ['sort', 'default', 'value' => ['RANK' => '-']],
            [['multiQuery'], 'default', 'value' => false],
            [['multiQuerySeparator'], 'default', 'value' => ','],
            ['markCommonConfig', 'default', 'value' => 'default'],
            ['summary', 'filter', 'filter' => [$this, 'formatSummary']],
            ['markFields', 'filter', 'filter' => [$this, 'buildMarkFields'], 'when' => function ($model) {
                return empty($model->markFields);
            }],
            [['markFields', 'format', 'indexes', 'queryKey', 'query', 'fields', 'limit', 'offset', 'sort', 'filter', ], 'safe'],
        ];
    }

    /**
     * 主要为了保存原始的query参数
     *
     * @param array $data
     * @param null  $formName
     *
     * @return bool
     */
    public function load($data, $formName = null)
    {
        $ret = parent::load($data, $formName);
        return $ret;
    }

    /**
     * @return array
     */
    public function all()
    {
        // 读取searchModel里的配置 searchInstance / index / 各个字段的数据类型 filter要用
        if ($this->validate()) {
            return $this->getResults();
        }
        Yii::info(['errors' => $this->errors], 'baseSearchQuery.validate.error');
        return [];
    }

    public function one()
    {
        $this->page = 0;
        $this->offset = 0;
        $this->limit = 1;
        return $this->all();
    }

    /**
     * 返回发送给搜索系统的搜索参数
     * @return array the raw search sql Array
     */
    public function getRawSql()
    {
        // 读取searchModel里的配置 searchInstance / index / 各个字段的数据类型 filter要用
        if ($this->validate()) {
            return $this->_getResults(true);
        }
        return ['errors' => $this->errors];
    }

    /**
     * 发送给搜索系统 获取我们想要的数据格式的数组
     *
     * @return array
     */
    public function getResults()
    {
        return $this->_getResults();
    }

    /**
     * @return array
     */
    private function _getResults($dryRun=false)
    {
        $resultArr = [];
        $this->searchModelInstance = new $this->modelClass();
        $this->indexes = $this->searchModelInstance->getIndexes();
        if ($this->query && $this->indexes) {
            if (is_array($this->_originQueryWord)) {
                $this->_originQueryWord = implode(' ', array_values($this->_originQueryWord));
            }
            $params = [
                '_originQueryWord' => $this->_originQueryWord,
                'summary' => $this->summary,
                'format' => $this->format,
                'indexes' => $this->indexes,
                'query' => $this->buildQuery(),
                'fetch_field' => $this->fields,
                'start' => $this->offset,
                'hits' => $this->limit,
                'sort' => $this->sort,
                'filter' => $this->buildFilter($this->_filterArr),
                'rerankSize' => $this->rerankSize,
            ];
            $this->formulaName && $params['formula_name'] = $this->formulaName;
            $searchArr = ['query' => $this->_originQueryWord,  'indexes' => $this->indexes, 'params' => Json::encode($params)];
            $dryRun && $searchArr['params'] = $params;
            $this->extraArr && $searchArr = ArrayHelper::mergeIfEmpty($searchArr, $this->extraArr);
            if ($dryRun) {
                return $searchArr;
            }
            Yii::info($searchArr, 'baseSearchQuery.params');
            $resultArr = $this->searchModelInstance->search->setPost()->send('/base/search', $searchArr);
            Yii::debug($resultArr, 'baseSearchQuery.rawBaseSearch.all.result');
        } else {
            Yii::info(['query' => $this->query, 'indexes' => $this->indexes], 'baseSearchQuery.emptyQueryOrIndexes');
        }
        return $resultArr;
    }

    /**
     * 把fields转成数组形式, 默认必须显示返回null
     *
     * @return array
     */
    public function formatFields($fields)
    {
        if ($fields && is_array($fields)) {
            if (!ArrayHelper::isIndexed($fields)) {
                $fields = array_values($fields);
            }
        } else {
            $fields = null;
        }
        return $fields;
    }

    /**
     * 获取summary通用配置, 主要是飘红配置
     *
     * @return array
     */
    public function getCommonSummaryConfig()
    {
        return ArrayHelper::getValue(self::$_commonConfigs, $this->markCommonConfig, []);
    }

    /**
     * 将需要飘红的字段 配置 成summary的格式
     *
     * @return array
     */
    public function buildMarkFields()
    {
        $summary = [];
        if (!empty($this->markFields)) {
            $markFieldsArr = is_array($this->markFields) ? $this->markFields : explode(',', $this->markFields);
            foreach ($markFieldsArr as $field) {
                // NOTE: 一定要trim掉空格 调试时候折腾了一晚
                $field = trim($field);
                $summary[$field] = array_merge(
                    [
                        'summary_field' => $field,
                    ],
                    $this->getCommonSummaryConfig()
                );
            }
        }
        return $summary;
    }

    /**
     * 支持多index搜索
     *
     * @return string
     */
    private function buildQuery()
    {
        $query = $this->query;
        if (!$this->useOriginQuery) {
            $queryArr = $this->splitQuerys();
            $queryArr = array_map([$this, 'buildQueryKeys'], $queryArr);
            $query = implode(' OR ', $queryArr);
        }
        return $query;
    }

    /**
     * 将query词按照分隔符切分出一个数组
     *
     * @return array
     */
    private function splitQuerys()
    {
        $query = is_array($this->query) ? $this->query : [$this->query];
        if ($this->multiQuery) {
            $query = explode($this->multiQuerySeparator, $this->query);
            $query = array_filter($query, function ($v, $k) {
                return !is_null($v) && ($v != '');
            }, ARRAY_FILTER_USE_BOTH);
        }
        return $query;
    }

    /**
     * 给单个query词 组装上queryKey
     *
     * @param $query
     *
     * @return string
     */
    private function buildQueryKeys($query)
    {
        $query = $this->escapeQuery($query);
        if (is_array($this->queryKey)) {
            $queryArr = [];
            foreach ($this->queryKey as $index) {
                $queryArr[] = $index . ":'" . $query . "'";
            }
            $query = implode(' OR ', $queryArr);
        } else {
            $query = $this->queryKey . ":'" . $query . "'";
        }
        return $query;
    }

    /*
     * 转义一下query 按照阿里云的要求 用'把query引起来 碰到'号就加上\
     * @return string
     */
    public function escapeQuery($query)
    {
        return mb_ereg_replace("'", "\\'", $query);
    }

    public function isOriginQueryAlpha()
    {
        return StringHelper::isAlpha($this->originQuery);
    }

    /**
     * 把queryKey中的,转成数组形式
     *
     * @return array
     */
    public function formatQueryKey()
    {
        $queryKey = $this->queryKey;
        if (is_string($queryKey) && (strpos($queryKey, ',') !== false)) {
            $queryKey = explode(',', $queryKey);
        }
        return $queryKey;
    }

    /*
     * 目前summary还只支持飘红设置
     */
    public function formatSummary()
    {
        empty($summary) && $summary = $this->buildMarkFields();
        return $summary;
    }

    /**
     * 根据page和limit计算出offset
     *
     * @return int
     */
    public function computeOffset()
    {
        return (int) $this->page * $this->limit;
    }

    /**
     * 通过filterArr 条件 构造最终的filter
     *
     * @return string
     */
    public function buildFilter($filterArrs = null)
    {
        $filter = '';
        is_null($filterArrs) && $filterArrs = $this->_filterArrs;
        if (empty($filterArrs) || !is_array($filterArrs)) {
            return $filter;
        }
        if (isset($filterArrs[0])) {
            if (is_string($filterArrs[0]) && (strncmp(strtolower($filterArrs[0]), 'and', 3) === 0)) {
                return $this->buildAndFilter($filterArrs);
            } elseif (is_string($filterArrs[0]) && (strncmp(strtolower($filterArrs[0]), 'or', 2) === 0)) {
                return $this->buildOrFilter($filterArrs);
            } else {
                @list($operator, $field, $value, $resultValue) = $filterArrs;
                $operator = strtolower($operator);
                if (in_array($operator, ['in', 'not in'])) {
                    return $this->buildInFilter($operator, $field, $value, $resultValue);
                } elseif ($resultValue) {
                    return $this->buildTwoOperatorFilter($operator, $field, $value, $resultValue);
                } else {
                    return $this->buildOneOperatorFilter($operator, $field, $value, $resultValue);
                }
            }
        } else {
            $filter = $this->buildHashFilter($filterArrs);
        }
        return $filter;
    }

    /**
     * 一元运算符
     *
     * @param $operator
     * @param $field
     * @param $value
     * @param $resultValue
     *
     * @return string
     */
    public function buildOneOperatorFilter($operator, $field, $value, $resultValue)
    {
        $filterCondition = new OneOperatorFilterCondition(
            [
                'field' => $field,
                'value' => $value,
                'operator' => $operator,
                'valueType' => $this->searchModelInstance->getFieldType($field),
            ]
        );
        $filterCondition->validate();
        return $filterCondition->build();
    }

    /**
     * 二元运算符
     *
     * @param $operator
     * @param $field
     * @param $value
     * @param $resultValue
     *
     * @return string
     */
    public function buildTwoOperatorFilter($operator, $field, $value, $resultValue)
    {
        $filterCondition = new TwoOperatorFilterCondition(
            [
                'field' => $field,
                'value' => $value,
                'operator' => $operator,
                'resultValue' => $resultValue,
                'valueType' => $this->searchModelInstance->getFieldType($field),
            ]
        );
        $filterCondition->validate();
        return $filterCondition->build();
    }

    /**
     * in运算符
     *
     * @param $operator
     * @param $field
     * @param $value
     * @param $resultValue
     *
     * @return string
     */
    public function buildInFilter($operator, $field, $value, $resultValue)
    {
        $filterCondition = new InOperatorFilterCondition(
            [
                'field' => $field,
                'value' => $value,
                'operator' => $operator,
                'innerJoinType' => $resultValue,
                'valueType' => $this->searchModelInstance->getFieldType($field),
            ]
        );
        $filterCondition->validate();
        return $filterCondition->build();
    }

    private function buildHashFilter($filterArrs = null)
    {
        // hash format 这种情况只允许=/in
        $tempFilterArr = [];
        foreach ($filterArrs as $field => $value) {
            if (is_array($value)) {
                $tempFilter = $this->buildInFilter('in', $field, $value, SearchModel::INNER_JOIN_TYPE_OR);
                $tempFilter && $tempFilterArr[] = $tempFilter;
            } else {
                $filterCondition = new OneOperatorFilterCondition(
                    [
                        'field' => $field,
                        'value' => $value,
                        'operator' => '=',
                        'valueType' => $this->searchModelInstance->getFieldType($field),
                    ]
                );
                $filterCondition->validate();
                $tempFilter = $filterCondition->build();
                $tempFilter && $tempFilterArr[] = '(' . $tempFilter . ')';
            }
        }
        $tempFilterArr = array_filter($tempFilterArr);
        $filter = implode(' AND ', $tempFilterArr);
        Yii::info($filterArrs, 'temp.hashformat');
        return $filter;
    }

    private function buildOrFilter($filterArrs = null)
    {
        return $this->_buildConditionFilter('OR', $filterArrs);
    }

    private function buildAndFilter($filterArrs = null)
    {
        return $this->_buildConditionFilter('AND', $filterArrs);
    }

    private function _buildConditionFilter($conditon, $filterArrs = null)
    {
        $tempFilterArr = [];
        array_shift($filterArrs);
        // for loop
        foreach ($filterArrs as $filterArr) {
            $tempFilter = $this->buildFilter($filterArr);
            $tempFilter && $tempFilterArr[] = '(' . $tempFilter . ')';
        }
        return implode(' ' . $conditon . ' ', $tempFilterArr);
    }

    public function normalizeOrderBy($columns)
    {
        if (is_array($columns)) {
            return $columns;
        }
        $columns = preg_split('/\s*,\s*/', trim($columns), -1, PREG_SPLIT_NO_EMPTY);
        $result = [];
        foreach ($columns as $column) {
            if (preg_match('/^(.*?)\s+(asc|desc)$/i', $column, $matches)) {
                $result[$matches[1]] = strcasecmp($matches[2], 'desc') ? SORT_ASC : SORT_DESC;
            } else {
                $result[$column] = SORT_ASC;
            }
        }
        return $result;
    }

    public function sortMap($sort)
    {
        $map = [SORT_ASC => '+', SORT_DESC => '-'];
        return ArrayHelper::getValue($map, $sort, '+');
    }

    /**
     * 对select的字段进行去重 并且去除alias 最后得到结果的时候进行重命名
     *
     * @param array $columns the columns to be merged to the select.
     *
     * @return array
     */
    protected function getUniqueColumns($columns)
    {
        $result = [];
        foreach ($columns as $columnAlias => $columnDefinition) {
            if (is_string($columnAlias)) {
                if (isset($this->select[$columnAlias]) && $this->select[$columnAlias] === $columnDefinition) {
                    continue;
                }
            } elseif (is_int($columnAlias)) {
                $existsInResultSet = in_array($columnDefinition, $result, true);
                if ($existsInResultSet) {
                    continue;
                }
            }
            $result[$columnAlias] = $columnDefinition;
        }
        return $result;
    }
}
