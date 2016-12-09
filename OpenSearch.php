<?php
namespace koenigseggposche\yii2opensearch;

use yii\base\Component;
use Aliyun\OpenSearch\CloudsearchClient;
use Aliyun\OpenSearch\CloudsearchSearch;

class OpenSearch extends Component
{
    public $AccessKeyId = '';
    public $AccessKeySecret = '';
    public $version = 'v2';
    public $host = 'http://opensearch-cn-beijing.aliyuncs.com';
    public $gzip = false;
    public $debug = false;
    public $signatureMethod = 'HMAC-SHA1';
    public $signatureVersion = '1.0';
    public $key_type = 'aliyun';

    private $client;
    public function init()
    {
        $options = [
            'version' => $this->version,
            'host' => $this->host,
            'gzip' => $this->gzip,
            'debug' => $this->debug,
            'signatureMethod' => $this->signatureMethod,
            'signatureVersion' => $this->signatureVersion,
        ];
        $this->client = new CloudsearchClient($this->AccessKeyId, $this->AccessKeySecret, $options, $this->key_type);
    }

    /**
     * 执行搜索
     *
     * 执行向API提出搜索请求。
     * 更多说明请参见 [API 配置config子句]({{!api-reference/query-clause&config-clause!}})
     * @param array $opts 此参数如果被复制，则会把此参数的内容分别赋给相应的变量。此参数的值可能有以下内容：
     * @subparam string query 指定的搜索查询串，可以为query=>"索引名:'鲜花'"。
     * @subparam array indexes 指定的搜索应用，可以为一个应用，也可以多个应用查询。
     * @subparam array fetch_fields 设定返回的字段列表，如果只返回url和title，则为 array('url', 'title')。
     * @subparam string format 指定返回的数据格式，有json,xml和protobuf三种格式可选。默认值为：'xml'
     * @subparam string formula_name 指定的表达式名称，此名称需在网站中设定。
     * @subparam array summary 指定summary字段一些标红、省略、截断等规则。
     * @subparam int start 指定搜索结果集的偏移量。默认为0。
     * @subparam int hits 指定返回结果集的数量。默认为20。
     * @subparam array sort 指定排序规则。默认值为：'self::SORT_DECREASE' (降序)
     * @subparam string filter 指定通过某些条件过滤结果集。
     * @subparam array aggregate 指定统计类的信息。
     * @subparam array distinct 指定distinct排序。
     * @subparam string kvpair 指定的kvpair。
     *
     * @return string 返回搜索结果。
     *
     */
    public function search($params = [])
    {
        $search = new CloudsearchSearch($this->client);
        return $search->search($params);
    }
}
