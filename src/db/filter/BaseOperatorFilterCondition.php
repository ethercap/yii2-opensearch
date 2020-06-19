<?php

namespace ethercap\opensearch\db\filter;

use ethercap\opensearch\db\SearchModel;
use yii\base\Model;

/**
 * OneOperatorFilterCondition : 一元运算符构造
 * = / != / > / < / >= / <=
 */
abstract class BaseOperatorFilterCondition extends Model implements FilterConditionInterface
{
    // 判断是否是有效值，int型的用is_null 其他的用empty
    public static function checkEmpty($value, $valueType)
    {
        return (in_array($valueType, [SearchModel::SEARCH_INT, SearchModel::SEARCH_INT_ARRAY])) ? (is_null($value) || !is_numeric($value)) : empty($value);
    }

    public function isIntValue() {
        return in_array($this->valueType, [SearchModel::SEARCH_INT_ARRAY, SearchModel::SEARCH_INT]);
    }
}
