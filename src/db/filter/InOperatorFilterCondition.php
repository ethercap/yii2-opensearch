<?php

namespace ethercap\opensearch\db\filter;

use ethercap\opensearch\db\SearchModel;

/**
 * InOperatorFilterCondition : in运算符构造
 * 1 其实opensearch只支持int的in 构造, 但是可以使用OR来实现 literal的in支持
 * sample:
 * ['in', 'id', [1,2,3]] ==> (id=1 OR id=2 OR id=3)
 * ['in', 'from', ['36kr','xiniu']] ==> (from="36kr" OR from="xiniu")
 */
class InOperatorFilterCondition extends BaseOperatorFilterCondition
{
    public $field;
    public $value;
    public $valueType;
    public $operator;
    /* filter内部的连接类型 交集或并集 OR或者AND */
    public $innerJoinType;

    public function rules()
    {
        return [
            ['valueType', 'default', 'value' => SearchModel::SEARCH_INT_ARRAY],
            ['valueType', 'required'],
            ['innerJoinType', 'default', 'value' => SearchModel::INNER_JOIN_TYPE_OR],
            ['innerJoinType', 'filter', 'filter' => [$this, 'parseInnerJoinType']],
            ['operator', 'default', 'value' => '='],
            [['field', 'value', 'innerJoinType'], 'safe'],
        ];
    }

    /**
     * 获取正确的innerJoinType
     *
     * @param $value
     *
     * @return int
     */
    public function parseInnerJoinType()
    {
        $innerJoinType = strtoupper($this->innerJoinType);
        if (!in_array($innerJoinType, [SearchModel::INNER_JOIN_TYPE_OR, SearchModel::INNER_JOIN_TYPE_AND])) {
            $innerJoinType = SearchModel::INNER_JOIN_TYPE_OR;
        }
        return $innerJoinType;
    }

    public function build()
    {
        $filter = '';
        $padding = $this->isIntValue() ? '' : '"';
        $operator = strtolower($this->operator);
        if (strncmp($operator, 'not ', 4) === 0) {
            $operator = '!=';
        } else {
            $operator = '=';
        }
        $prefix = $this->field . $operator;
        if (!is_array($this->value) || empty($this->value)) {
            return $filter;
        }

        $valueArr = $this->value;
        if ($valueArr) {
            $tempFilterArr = [];
            foreach ($valueArr as $value) {
                if (!self::checkEmpty($value, $this->valueType)) {
                    $tempFilterArr[] = $prefix . $padding . $this->formatValue($value) . $padding;
                }
            }
            if ($tempFilterArr) {
                $filter = implode(' ' . $this->innerJoinType . ' ', $tempFilterArr);
                $filter = "($filter)";
            }
        }
        return $filter;
    }

    /**
     * NOTE 这个不能预先用filter 因为需要先判断是否empty
     *
     * @param $value
     *
     * @return int
     */
    public function formatValue($value)
    {
        if ($this->isIntValue()) {
            $value = intval($value);
        }
        return $value;
    }
}
