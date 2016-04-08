<?php
namespace lspbupt\curl;
use Yii;
use \yii\base\Component;
use \yii\base\InvalidParamException;
use Closure;
/*encapsulate normal Http Request*/
class CurlHttp extends Component
{
    const METHOD_GET = 0;
    const METHOD_POST = 1;
    const METHOD_POSTJSON = 2;

    public $timeout = 30;
    public $connectTimeout = 30;
    public $protocol = "http";
    public $port = 80;
    public $host;
    public $method = self::METHOD_GET;
    public $headers = array(
        'User-Agent' => 'Mozilla/5.0 (X11; Linux i686) AppleWebKit/537.22 (KHTML, like Gecko) Ubuntu Chromium/25.0.1',
        'Accept-Charset' => 'GBK,utf-8' ,
    );

    private static $methodDesc = [
        self::METHOD_GET => "GET",
        self::METHOD_POST => "POST",
        self::METHOD_POSTJSON => "POST",
    ];

    // closure
    public $beforeRequest;
    public $afterRequest;

    private $_curl;
    private $_action;

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
        return $this->_action;
    }

    public function setAction($action)
    {
        $this->_action = $action;
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

    public function setHeader($key, $value)
    {
        $this->headers[$key] = $value;
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

    public function httpExec($action = "/", $params = array())
    {
        $this->setAction($action);
        if($this->beforeRequest instanceof Closure) {
            $params = call_user_func($this->beforeRequest, $params, $this);
            empty($params) && $params = [];
        }
        $ch = $this->getCurl();
        $url = $this->getUrl();
        if ($this->method == self::METHOD_POST) {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        } elseif ($this->method == self::METHOD_POSTJSON) {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
        } else {
            if(!empty($params)) {
                $temp = explode("?", $url);
                if(count($temp) > 1) {
                    $url = $temp[0]."?".$temp[1].'&'.http_build_query($params);
                } else {
                    $url = $url."?".http_build_query($params);
                }
            }
        }
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->connectTimeout);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER , $this->getHeads());
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        $data = curl_exec($ch);
        if($this->afterRequest instanceof Closure) {
            $data = call_user_func($this->afterRequest, $data, $this);
        }
        curl_close($ch);
        $this->_curl = null;
        return $data;
    }
}
