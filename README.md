Opensearch的yii2扩展
===============================
阿里云的Opensearch的yii2扩展

## 使用方式
#### 1 Raw方式
就是直接调用OpenSearch类的search/suggest方法 和opensearch通信返回结果
#### 2 ActiveRecord类实现
仿照yii2的db ActiveRecord的设计和调用方式
代码放在db里面 使用的时候继承和配置SearchModel

SeachModel
===============================
- 1 index --> 定义表名
- 2 mapping --> 定义字段的类型 int / string / int_array / string_array
- 3 search / suggest -->  search/suggest 对应的instance名字 (默认就叫search/suggest)

SearchQuery
===============================
```
基本调用
$modelArr = SearchModel::search()->filter()->andFilter()->orderBy()->limit()->offset()->all();

SearchModel::search() / suggest()
->select([]) // fields
->query() // query
->filter(['type' => new SearchInt(1)]) // filter //'type'=>'1' 'type'=>1 bigint '1234'
->andFilter(['status' => 10]) // andWhere
->orFilter(['like', 'catgy', 'bc']) // orWhere
// andFilter and tag in ('8','9','10') // int string
->andWhere(['in', 'tag', [8,9,10]])
->all();
```

filter的特殊之处
===============================
```
本质原因是 search和sql还是有些不同的
    int型的是通用的
    string类型search有3中: text/shortText/literal
        text: 用于通用的搜索分词
        shortText: 用于实现类似like的搜索
        literal：也就是exactly match 精准匹配
    array类型:
        literal_array: 用or操作来实现了 in/not in
        int和其他类型的array: opensearch有原生的in/not in支持
```

代码结构
-------------------

```
OpenSearch              对CloudsearchClient的原始封装
CloudsearchClient       对Opensearch的web调用封装
db
    filter/              条件过滤器的实现(and/or/in/not in)
    BaseSearchQuery      Base的实现
    SearchQuery          SearchQuery
    SearchModel          SearchModel
    TestSearchModel      示例SearchModel
```
