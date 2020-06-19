<?php

namespace ethercap\opensearch\db\filter;

use ethercap\opensearch\db\SearchModel;

/**
 * TwoOperatorFilterCondition : 二元运算符构造
 * 1 主要支持位运算符 和 其他的简单加减乘除
 * 2 NOTE 原则上二元运算和特殊运算符只支持int
 * & / !& / | / !|
 * sample:
 * ['!+', field, 3, 9] ==> field + 3 != 9
 * ['&', field, 4, 0] ==> field & 4 = 0
 * ['!|', field, 8, 0] ==> field | 8 != 0
 */
class TwoOperatorFilterCondition extends BaseOperatorFilterCondition
{
    public $field;
    public $value;
    public $valueType;
    public $operator;
    public $resultValue = 0;

    public function rules()
    {
        return [
            ['valueType', 'default', 'value' => SearchModel::SEARCH_INT],
            ['valueType', 'compare', 'compareValue' => SearchModel::SEARCH_INT, 'operator' => '=='],
            ['valueType', 'required'],
            ['operator', 'default', 'value' => '='],
            [['field', 'value', 'resultValue'], 'safe'],
        ];
    }

    public function build()
    {
        $filter = '';
        $padding = ($this->valueType == SearchModel::SEARCH_INT) ? '' : '"';
        if (!self::checkEmpty($this->value, $this->valueType)) {
            if (strncmp($this->operator, '!', 1) === 0) {
                $firstOperator = substr($this->operator, 1);
                $secondOperator = '!=';
            } else {
                $firstOperator = $this->operator;
                $secondOperator = '=';
            }
            $filter = $this->field . $firstOperator . $padding . $this->formatValue($this->value) . $padding . $secondOperator .  $this->resultValue;
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
