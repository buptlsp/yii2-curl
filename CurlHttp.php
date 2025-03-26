<?php

namespace lspbupt\curl;

use Closure;

class CurlHttp extends \lspbupt\curl\BaseCurlHttp
{
    // closure
    public $beforeRequest;
    public $afterRequest;

    protected $enableAudit = false;

    //请求之前的操作
    protected function beforeCurl($params)
    {
        if ($this->beforeRequest instanceof Closure) {
            $params = call_user_func($this->beforeRequest, $params, $this);
            empty($params) && $params = [];
            $this->setParams($params);
        }
        return parent::beforeCurl($params);
    }

    //请求之后的操作
    protected function afterCurl($data)
    {
        if ($this->afterRequest instanceof Closure) {
            $data = call_user_func($this->afterRequest, $data, $this);
        }
        return parent::afterCurl($data);
    }

    public function setEnableAudit()
    {
        $this->enableAudit = true;
        return $this;
    }

    public function beforeCurlExec(&$ch)
    {
        if ($this->enableAudit) {
            $this->trigger('auditBeforeCurlExec');
        }
        return parent::beforeCurlExec($ch);
    }

    public function afterCurlExec(&$ch)
    {
        if ($this->enableAudit) {
            $this->trigger('auditAfterCurlExec');
        }
        return parent::afterCurlExec($ch);
    }

    public function refreshCurl()
    {
        $this->enableAudit = false;
        parent::refreshCurl();
    }
}
