<?php

namespace ethercap\opensearch\db\filter;

use ethercap\opensearch\db\SearchModel;

/**
 * OneOperatorFilterCondition : 一元运算符构造
 * = / != / > / < / >= / <=
 */
class OneOperatorFilterCondition extends BaseOperatorFilterCondition
{
    public $field;
    public $value;
    public $valueType;
    public $operator;

    public function rules()
    {
        return [
            ['valueType', 'default', 'value' => SearchModel::SEARCH_INT],
            ['valueType', 'required'],
            ['operator', 'default', 'value' => '='],
            [['field', 'value', ], 'safe'],
        ];
    }

    public function build()
    {
        $filter = '';
        $padding = ($this->valueType == SearchModel::SEARCH_INT) ? '' : '"';
        if (!self::checkEmpty($this->value, $this->valueType)) {
            $filter = $this->field . $this->operator . $padding . $this->formatValue($this->value) . $padding;
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
        if ($this->valueType == SearchModel::SEARCH_INT) {
            $value = intval($value);
        }
        return $value;
    }
}
