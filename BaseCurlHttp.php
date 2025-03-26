<?php

namespace lspbupt\curl;

use Yii;
use yii\base\Component;
use yii\base\InvalidConfigException;
use yii\base\ModelEvent;
use yii\helpers\ArrayHelper;
use yii\helpers\VarDumper;

/*encapsulate normal Http Request*/
class BaseCurlHttp extends Component
{
    const METHOD_GET = 0;
    const METHOD_POST = 1;
    /**
     * @deprecated 兼容老版本的兼容性方法，建议分开指定HTTP method和数据格式化方式。
     */
    const METHOD_POSTJSON = 2;
    const METHOD_PUT = 3;

    const EVENT_BEFORE_CURL = 'beforeCurl';
    const EVENT_AFTER_CURL = 'afterCurl';
    const EVENT_BEFORE_CURL_EXEC = 'beforeCurlExec';
    const EVENT_AFTER_CURL_EXEC = 'afterCurlExec';

    public $timeout = 10; //单位s，支持小数
    public $connectTimeout = 5; //单位s，支持小数
    public $returnTransfer = 1;
    public $followLocation = 1;
    public $protocol = 'http';
    public $port = 80;
    public $host;
    public $method = self::METHOD_GET;
    public $headers = [
        'User-Agent' => 'Mozilla/5.0 (X11; Linux i686) AppleWebKit/537.22 (KHTML, like Gecko) Ubuntu Chromium/25.0.1',
        'Accept-Charset' => 'GBK,utf-8',
    ];
    public $action;
    public $params;

    private $debug = false;
    //默认为非formData的模式,传文件时需要开启
    private $isFormData = false;
    //采用json的方式传递数据，postjson/putjson
    private $isJsonData = false;
    private static $methodDesc = [
        self::METHOD_GET => 'GET',
        self::METHOD_POST => 'POST',
        self::METHOD_POSTJSON => 'POST',
        self::METHOD_PUT => 'PUT',
    ];
    private $jsonEncodeOption = 0;

    private $_curl;

    public function getUrl()
    {
        $url = $this->protocol.'://'.$this->host;
        if ($this->port != 80) {
            $url .= ':'.$this->port;
        }
        return $url.$this->getAction();
    }

    public function getAction()
    {
        return $this->action;
    }

    public function setAction($action)
    {
        $this->action = $action;
    }

    public function setParams($params = [])
    {
        $this->params = $params;
    }

    public function getParams()
    {
        return $this->params;
    }

    public function setMethod($method)
    {
        $this->method = $method;
        return $this;
    }

    public function setGet()
    {
        if (!empty($this->headers['Content-Type'])) {
            unset($this->headers['Content-Type']);
        }
        return $this->setMethod(self::METHOD_GET);
    }

    public function getMethod()
    {
        if (isset(self::$methodDesc[$this->method])) {
            return self::$methodDesc[$this->method];
        }
        return 'GET';
    }

    public function setPost()
    {
        if (!empty($this->headers['Content-Type'])) {
            unset($this->headers['Content-Type']);
        }
        return $this->setMethod(self::METHOD_POST);
    }

    public function setPut()
    {
        return $this->setMethod(self::METHOD_PUT);
    }

    /**
     * @deprecated 建议使用 setPost()->setJsonData()
     * @var int JSON options
     * @return self
     */
    public function setPostJson($option = 0)
    {
        $this->setMethod(self::METHOD_POST);
        return $this->setJsonData(true, $option);
    }

    public function setProtocol($protocol)
    {
        $this->protocol = $protocol;
        return $this;
    }

    public function setHeaders($arr = [])
    {
        if (!ArrayHelper::isIndexed($arr)) {
            foreach ($arr as $key => $value) {
                $this->setHeader($key, $value);
            }
        }
        return $this;
    }

    public function setHeader($key, $value)
    {
        if ($value === null) {
            unset($this->headers[$key]);
        } else {
            $this->headers[$key] = $value;
        }
        return $this;
    }

    private function getHeads()
    {
        $heads = [];
        foreach ($this->headers as $key => $val) {
            $heads[] = $key.':'.$val;
        }
        return $heads;
    }

    public function getCurl()
    {
        if ($this->_curl) {
            return $this->_curl;
        }
        $this->_curl = curl_init();
        return $this->_curl;
    }

    public function setDebug($debug = true)
    {
        $this->debug = $debug;
        return $this;
    }

    public function setFormData($isFormData = true)
    {
        $this->isFormData = $isFormData;
        return $this;
    }

    public function setJsonData($isJsonData = true, $option = 0)
    {
        if ($this->isJsonData = $isJsonData) {
            $this->setHeader('Content-Type', 'application/json;charset=utf-8');
        }
        if ($option) {
            $this->jsonEncodeOption = $option;
        }
        return $this;
    }

    public function isDebug()
    {
        return $this->debug ? true : false;
    }

    public function debug($info, $level = 'info')
    {
        if ($this->isDebug()) {
            $info = $level.':'.VarDumper::dumpAsString($info);
            echo $info."\n";
        }
    }

    public function setOpt($option, $value)
    {
        curl_setopt($this->getCurl(), $option, $value);
        return $this;
    }

    //请求之前的操作
    protected function beforeCurl($params)
    {
        $event = new ModelEvent();
        $this->trigger(self::EVENT_BEFORE_CURL, $event);
        return $event->isValid;
    }

    // 保持历史的兼容性
    protected function afterCurlNew($data, $error)
    {
        return $this->afterCurl($data);
    }

    //请求之后的操作
    protected function afterCurl($data)
    {
        $event = new ModelEvent();
        $this->trigger(self::EVENT_AFTER_CURL, $event);
        return $data;
    }

    /**
     * @deprecated 推荐使用send方法
     *
     * @param string $action
     * @param array  $params
     */
    public function httpExec($action = '/', $params = [])
    {
        return $this->send($action, $params);
    }

    /**
     * 设置CURLOPT_POSTFIELDS的参数的序列化方式
     * FormData：不序列化，保持数组
     * JsonData: json_encode
     * 默认：http_build_query
     * @var array body部分的数据
     * @return self
     */
    public function processPostData($data)
    {
        if ($this->isFormData) {
            return $data;
        } elseif ($this->isJsonData) {
            return json_encode($data, $this->jsonEncodeOption);
        }
        return http_build_query($data);
    }

    public function send($action = '/', $params = [])
    {
        if (empty($this->host)) {
            throw new InvalidConfigException('Host must be configured before sending.');
        }
        $this->setAction($action);
        $this->setParams($params);
        $ret = $this->beforeCurl($params);
        if (!$ret) {
            return '';
        }
        $ch = $this->getCurl();
        $url = $this->getUrl();
        if ($this->method == self::METHOD_POST) {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $this->processPostData($this->getParams()));
            $this->debug($params, 'POST');
        } elseif ($this->method == self::METHOD_POSTJSON) {
            // 这个分支仅做兼容性处理。
            // 除非使用->setMethod(CurlHttp::METHOD_POSTJSON)才会进入这个分支，历史代码极少这么用的
            $this->setPostJson();
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $this->processPostData($this->getParams()));
            $this->debug($params, 'POST');
        } elseif ($this->method == self::METHOD_PUT) {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            curl_setopt($ch, CURLOPT_POSTFIELDS, $this->processPostData($this->getParams()));
            $this->debug($params, 'PUT');
        } else {
            if (!empty($params)) {
                $temp = explode('?', $url);
                if (count($temp) > 1) {
                    $url = $temp[0].'?'.$temp[1].'&'.http_build_query($this->getParams());
                } else {
                    $url = $url.'?'.http_build_query($this->getParams());
                }
            }
        }
        curl_setopt($ch, CURLOPT_TIMEOUT_MS, intval($this->timeout * 1000));
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, intval($this->connectTimeout * 1000));
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, $this->returnTransfer);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->getHeads());
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, $this->followLocation);
        curl_setopt($ch, CURLOPT_VERBOSE, $this->isDebug());
        $this->beforeCurlExec($ch);
        $data = curl_exec($ch);

        $err = [
            'code' => 0,
            'message' => '',
        ];
        if (empty($data)) {
            $err['code'] = curl_errno($ch);
            $err['message'] = curl_error($ch);
        }
        $this->debug($data, 'RESPONSE');
        $this->afterCurlExec($ch);
        curl_close($ch);
        $data = $this->afterCurlNew($data, $err);
        $this->refreshCurl();
        return $data;
    }

    public function beforeCurlExec(&$ch)
    {
        $event = new ModelEvent();
        $this->trigger(self::EVENT_BEFORE_CURL_EXEC, $event);
        return $event->isValid;
    }

    public function afterCurlExec(&$ch)
    {
        $event = new ModelEvent();
        $this->trigger(self::EVENT_AFTER_CURL_EXEC, $event);
    }

    public function refreshCurl()
    {
        $this->_curl = null;
    }

    public static function getObjectByUrl($url, &$action = null, $method = self::METHOD_GET)
    {
        $data = parse_url($url);
        $config = [];
        $config['protocol'] = ArrayHelper::getValue($data, 'scheme', 'http');
        $config['host'] = ArrayHelper::getValue($data, 'host', '');
        $config['port'] = ArrayHelper::getValue($data, 'port', 80);
        $config['method'] = $method;
        $action = ArrayHelper::getValue($data, 'path', '');
        $queryStr = ArrayHelper::getValue($data, 'query', '');
        $fragment = ArrayHelper::getValue($data, 'fragment', '');
        if ($queryStr) {
            $action .= '?'.$queryStr;
        }
        if ($fragment) {
            $action .= '#'.$fragment;
        }
        $config['class'] = get_called_class();
        $obj = Yii::createObject($config);
        return $obj;
    }

    public static function requestByUrl($url, $params = [], $method = self::METHOD_GET)
    {
        $action = null;
        $obj = self::getObjectByUrl($url, $action, $method);
        return $obj->httpExec($action, $params);
    }
}
