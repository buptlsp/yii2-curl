<?php
namespace lspbupt\curl;
use Yii;
use \yii\helpers\ArrayHelper;
use \yii\base\Component;
use \yii\base\InvalidParamException;
use Closure;
/*encapsulate normal Http Request*/
class BaseCurlHttp extends Component
{
    const METHOD_GET = 0;
    const METHOD_POST = 1;
    const METHOD_POSTJSON = 2;

    public $timeout = 10;
    public $connectTimeout = 5;
    public $returnTransfer = 1;
    public $followLocation = 1;
    public $protocol = "http";
    public $port = 80;
    public $host;
    public $method = self::METHOD_GET;
    public $headers = array(
        'User-Agent' => 'Mozilla/5.0 (X11; Linux i686) AppleWebKit/537.22 (KHTML, like Gecko) Ubuntu Chromium/25.0.1',
        'Accept-Charset' => 'GBK,utf-8' ,
    );
    public $action;
    public $params;
    private $debug = false;
    //默认为非formData的模式,传文件时需要开启
    private $isFormData = false;
    private static $methodDesc = [
        self::METHOD_GET => "GET",
        self::METHOD_POST => "POST",
        self::METHOD_POSTJSON => "POST",
    ];

    private $_curl;

    public function init()
    {
        parent::init();
        if(empty($this->host)) {
            throw new InvalidParamException("Please config host.");
        }
    }

    public function getUrl()
    {
        $url = $this->protocol."://".$this->host;
        if($this->port != 80) {
            $url .= ":".$this->port;
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
        if(!empty($this->headers['Content-Type'])) {
            unset($this->headers["Content-Type"]);
        }
        return $this->setMethod(self::METHOD_GET);
    }

    public function getMethod()
    {
        if(isset(self::$methodDesc[$this->method])) {
            return self::$methodDesc[$this->method];
        }
        return "GET";
    }

    public function setPost()
    {
        if(!empty($this->headers['Content-Type'])) {
            unset($this->headers["Content-Type"]);
        }
        return $this->setMethod(self::METHOD_POST);
    }

    public function setPostJson()
    {
        $this->setHeader('Content-Type', 'application/json;charset=utf-8');
        return $this->setMethod(self::METHOD_POSTJSON);
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
        foreach($this->headers as $key => $val) {
            $heads[] = $key.":".$val;
        }
        return $heads;
    }

    public function getCurl()
    {
        if($this->_curl) {
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

    public function isDebug()
    {
        return $this->debug;
    } 


    //请求之前的操作
    protected function beforeCurl($params)
    {
        return true;
    }

    //请求之后的操作
    protected function afterCurl($data)
    {
        return $data;
    }

    /**
     * @deprecated 推荐使用send方法
     * @param string $action
     * @param array $params
     */
    public function httpExec($action = "/", $params = []) {
        return $this->send($action, $params);
    }

    public function send($action = "/", $params = [])
    {
        $this->setAction($action);
        $this->setParams($params);
        if($this->isDebug()) {
            echo "\n开始请求之前:\nurl:".$this->getUrl()."\n参数列表:".json_encode($this->getParams())."\n方法:".$this->getMethod()."\n";
        }
        $ret = $this->beforeCurl($params);
        if(!$ret) {
            return ""; 
        }
        $ch = $this->getCurl();
        $url = $this->getUrl();
        if ($this->method == self::METHOD_POST) {
            curl_setopt($ch, CURLOPT_POST, 1);
            if($this->isFormData) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $this->getParams());
            }else {
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($this->getParams()));
            }
        } elseif ($this->method == self::METHOD_POSTJSON) {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($this->getParams()));
        } else {
            if(!empty($params)) {
                $temp = explode("?", $url);
                if(count($temp) > 1) {
                    $url = $temp[0]."?".$temp[1].'&'.http_build_query($this->getParams());
                } else {
                    $url = $url."?".http_build_query($this->getParams());
                }
            }
        }
        if($this->isDebug()) {
            echo "\n开始请求:\nurl:${url}\n参数列表:".json_encode($this->getParams())."\n方法:".$this->getMethod()."\n";
        }
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->connectTimeout);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, $this->returnTransfer);
        curl_setopt($ch, CURLOPT_HTTPHEADER , $this->getHeads());
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, $this->followLocation);
        $this->beforeCurlExec($ch);
        $data = curl_exec($ch);
        $this->afterCurlExec($ch);
        if($this->isDebug()) {
            echo "\n请求结果:".$data."\n";
        }
        $data = $this->afterCurl($data);
        curl_close($ch);
        $this->refreshCurl();
        return $data;
    }

    public function beforeCurlExec(&$ch) {
    }

    public function afterCurlExec(&$ch) {
    }

    public function refreshCurl() {
        $this->_curl = null;
    }

    public static function requestByUrl($url, $params = [], $method=self::METHOD_GET)
    {
        $data = parse_url($url);
        $config = [];
        $config['protocol'] = ArrayHelper::getValue($data, "scheme", "http");
        $config['host'] = ArrayHelper::getValue($data, "host", "");
        $config['port'] = ArrayHelper::getValue($data, "port", 80);
        $config['method'] = $method;
        $action = ArrayHelper::getValue($data, "path", "");
        $queryStr = ArrayHelper::getValue($data, "query", "");
        $fragment = ArrayHelper::getValue($data, "fragment", "");
        if($queryStr) {
            $action .= "?".$queryStr;
        }
        if($fragment) {
            $action .= "#".$fragment;
        }
        $config['class'] = get_called_class();
        $obj = Yii::createObject($config);
        return $obj->httpExec($action, $params);
    }

}
