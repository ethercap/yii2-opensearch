<?php

namespace ethercap\opensearch\db;

/**
 * SearchQuery: 支持搜索客户端的搜索基类
 */
class SearchQuery extends BaseSearchQuery
{
    /**
     * query string格式
     *
     * @param $query
     *
     * @return $this
     */
    public function query($query)
    {
        $this->query = $query;
        return $this;
    }

    /**
     * query的索引
     *
     * @param array|string $queryKey
     *
     * @return $this
     */
    public function queryKey($queryKey)
    {
        $this->queryKey = $queryKey;
        return $this;
    }

    /**
     * @param bool $useOriginQuery
     *
     * @return $this
     */
    public function useOriginQuery($useOriginQuery = true)
    {
        $this->useOriginQuery = $useOriginQuery;
        return $this;
    }

    /**
     * @param bool $useMultiQuery
     *
     * @return $this
     */
    public function useMultiQuery($useMultiQuery = true)
    {
        $this->multiQuery = $useMultiQuery;
        return $this;
    }

    /**
     * @param string $multiQuerySeparator
     *
     * @return $this
     */
    public function setMultiQuerySeparator($multiQuerySeparator = ',')
    {
        $this->multiQuerySeparator = $multiQuerySeparator;
        return $this;
    }

    /**
     * @param int $rerankSize
     *
     * @return $this
     */
    public function setRerankSize($rerankSize = self::DEFAULT_RERANK_SIZE)
    {
        $this->rerankSize = $rerankSize;
        return $this;
    }

    /**
     * @param string $markCommonConfig
     *
     * @return $this
     */
    public function setMarkCommonConfig($markCommonConfig = 'default')
    {
        $this->markCommonConfig = $markCommonConfig;
        return $this;
    }

    /**
     * @param string|array $markFields
     *
     * @return $this
     */
    public function setMarkFields($markFields = '')
    {
        $this->markFields = $markFields;
        return $this;
    }

    /**
     * @param string $formulaName
     *
     * @return $this
     */
    public function setFormulaName($formulaName = '')
    {
        $this->formulaName = $formulaName;
        return $this;
    }

    /**
     * TODO getResults的时候做下toArray操作
     *
     * @param $columns
     *
     * @return $this
     */
    public function select($columns)
    {
        if (!is_array($columns)) {
            $columns = preg_split('/\s*,\s*/', trim($columns), -1, PREG_SPLIT_NO_EMPTY);
        }
        $select = $this->getUniqueColumns($columns);
        $this->fields = $this->formatFields($select);
        return $this;
    }

    /**
     * 两种写法 和 AR保持一致 hash / string
     * ['field1' => SORT_DESC, 'field2' => SORT_ASC] / 'field1 desc, field2 asc'
     *
     * @param $columns
     *
     * @return $this
     */
    public function orderBy($columns)
    {
        $result = $this->normalizeOrderBy($columns);
        $sort = array_map([$this, 'sortMap'], $result);
        // 默认按照精排分排序
        $sort['RANK'] = '-';
        $this->sort = $sort;
        return $this;
    }

    /**
     * @param int $limit
     *
     * @return $this
     */
    public function limit($limit = self::DEFAULT_LIMIT)
    {
        $this->limit = $limit;
        return $this;
    }

    /**
     * @param int $offset
     *
     * @return $this
     */
    public function offset($offset = 0)
    {
        $this->offset = $offset;
        return $this;
    }

    /**
     * @param int $page
     *
     * @return $this
     */
    public function page($page = 0)
    {
        $this->page = $page;
        return $this;
    }

    /**
     * @param $condition
     *
     * @return $this
     */
    public function where($condition)
    {
        return $this->filter($condition);
    }

    /**
     * @param $condition
     *
     * @return $this
     */
    public function andWhere($condition)
    {
        return $this->andFilter($condition);
    }

    /**
     * @param $condition
     *
     * @return $this
     */
    public function orWhere($condition)
    {
        return $this->orFilter($condition);
    }

    /**
     * @param $condition
     *
     * @return $this
     */
    public function filter($condition)
    {
        $this->_filterArr = $condition;
        return $this;
    }

    /**
     * @param $condition
     *
     * @return $this
     */
    public function andFilter($condition)
    {
        if ($this->_filterArr === null) {
            $this->_filterArr = $condition;
        } elseif (is_array($this->_filterArr) && isset($this->_filterArr[0]) && strcasecmp($this->_filterArr[0], self::CONDITION_AND) === 0) {
            $this->_filterArr[] = $condition;
        } else {
            $this->_filterArr = [self::CONDITION_AND, $this->_filterArr, $condition];
        }
        return $this;
    }

    /**
     * @param $condition
     *
     * @return $this
     */
    public function orFilter($condition)
    {
        if ($this->_filterArr === null) {
            $this->_filterArr = $condition;
        } else {
            $this->_filterArr = [self::CONDITION_OR, $this->_filterArr, $condition];
        }
        return $this;
    }

    public function setExtraArr($extraArr=[])
    {
        is_array($extraArr) && $this->extraArr = $extraArr;
        return $this;
    }
}
