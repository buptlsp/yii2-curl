<?php
namespace lspbupt\curl;
use Yii;
use Closure;

class CurlHttp extends \lspbupt\curl\BaseCurlHttp
{
    // closure
    public $beforeRequest;
    public $afterRequest;

    //请求之前的操作
    protected function beforeCurl($params)
    {
        if($this->beforeRequest instanceof Closure) {
            $params = call_user_func($this->beforeRequest, $params, $this);
            empty($params) && $params = [];
            $this->setParams($params);
        }
        return parent::beforeCurl($params);
    }

    //请求之后的操作
    protected function afterCurl($data)
    {
        if($this->afterRequest instanceof Closure) {
            $data = call_user_func($this->afterRequest, $data, $this);
        }
        return parent::afterCurl($data);
    }
}
