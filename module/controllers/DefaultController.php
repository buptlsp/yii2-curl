<?php

namespace lspbupt\curl\module\controllers;

use lspbupt\curl\CurlHttp;
use lspbupt\curl\module\Module;
use Yii;
use yii\di\Instance;
use yii\validators\UrlValidator;

class DefaultController extends \yii\console\Controller
{
    /**
     * @var string 对应curl的--data-raw, -d选项。后面可以跟"a=b&c=d"也可以跟json数据
     */
    public $dataRaw = '';
    /**
     *  @var bool 对应curl -v, --verbose选项。如果设置，会打印出整个请求的过程
     */
    public $verbose = false;
    /**
     * @var string 对应curl的-H, --header选项。注意：不能多个-H使用，多个header请用|分隔，如："Content-type: xxx|User-Agent: xxx"
     */
    public $header = '';
    /**
     * @var string 对应curl的-x, --req选项。本软件只支付get, post, postjson, formdata。如果不设置，系统会自行判断
     */
    public $req = '';

    /**
     * @var string 对应./yii serve --docroot，表示自行调试的代码目录所在，此时会覆盖原始的host而用本地的host
     */
    public $serve = '';

    /**
     * @var string 为beforeAction能获取到action参数存了一份
     */
    public $_action;
    private $_server_pid;

    public static $allowMethods = ['get', 'post', 'postjson', 'formdata'];

    public $defaultScheme = 'http';

    /**
     * 通过系统的实例来发起http请求
     */
    public function actionIndex($instance, $action = '')
    {
        $this->_action = $action;
        $params = [];
        //规范化method
        $method = 'GET';
        $isFormData = false;
        if ($this->dataRaw !== '') {
            $method = 'POST';
            //判断dataRaw是否为json
            $ret = json_decode($this->dataRaw);
            if (!is_null($ret)) {
                $method = 'POSTJSON';
                $params = $ret;
            } else {
                parse_str($this->dataRaw, $params);
                if (is_null($params)) {
                    echo '-d 参数错误，请传输有意义的data数据';
                    return 1;
                }
                foreach ($params as $key => $value) {
                    if (is_string($value) && substr($value, 0, 1) === '@') {
                        $method = 'POST';
                        $isFormData = true;
                        $file = substr($value, 1);
                        $params[$key] = new \CURLFile(realpath($file));
                    }
                }
            }
        }

        //规范化method
        empty($this->req) && $this->req = $method;
        $this->req = strtolower($this->req);
        if (!in_array($this->req, self::$allowMethods)) {
            $this->req = 'get';
        }
        //规范化headers
        $this->header = $this->parseHeader($this->header);

        $obj = $this->getInstance($instance, $action);
        $this->startServe($obj);
        call_user_func([$obj, 'set'.$this->req]);
        foreach (Module::$beforeActionBehaviors as $name => $value) {
            Module::getInstance()->getBehavior($name)->run($value, $this);
        }
        $ret = $obj->setDebug((bool) $this->verbose)
            ->setHeaders($this->header)
            ->setFormData($isFormData)
            ->send($action, $params);
        $this->terminateServe();
        if (!$this->verbose) {
            if (is_string($ret)) {
                echo $ret;
            } else {
                echo json_encode($ret);
            }
        }
        return 0;
    }

    public function options($actionID)
    {
        return array_merge(parent::options($actionID), [
            'dataRaw',
            'verbose',
            'header',
            'req',
            'serve',
        ], array_keys(Module::$beforeActionBehaviors));
    }

    public function optionAliases()
    {
        return array_merge(parent::optionAliases(), [
            'd' => 'data-raw',
            'v' => 'verbose',
            'H' => 'header',
            'X' => 'req',
            's' => 'serve',
        ]);
    }

    private function parseHeader($headers)
    {
        $headers = explode('|', $headers);
        $arr = [];
        foreach ($headers as $str) {
            $index = strpos($str, ':');
            if ($index !== false) {
                $headKey = trim(substr($str, 0, $index));
                $headValue = trim(substr($str, $index + 1));
                $arr[$headKey] = $headValue;
            }
        }

        return $arr;
    }

    private function getInstance($key, &$action)
    {
        $key = trim($key);
        $obj = null;
        //先判断是否为Url
        $validator = new UrlValidator([
            'defaultScheme' => $this->defaultScheme,
        ]);
        if ($validator->validate($key)) {
            if (strpos($key, '://') === false) {
                $key = $this->defaultScheme . '://'. $key;
            }
            $obj = CurlHttp::getObjectByUrl($key, $action);
        } else {
            $obj = Instance::ensure($key);
        }
        return $obj;
    }

    public function startServe(CurlHttp $obj): void
    {
        if (!$this->serve) {
            return;
        }
        $port = mt_rand(13000, 15000);
        $yii = dirname(Yii::getAlias('@console')).'/yii';
        $pid = pcntl_fork();
        if ($pid == -1) {
            echo "fork failed.\n";
            die;
        } elseif ($pid) {
            sleep(1);
            $pidstatus = pcntl_waitpid($pid, $statsu, WNOHANG);
            if ($pidstatus != 0) {
                echo "server failed.\n";
                die;
            }
            $this->_server_pid = $pid;
            $obj->host = 'localhost';
            $obj->port = $port;
            $obj->protocol = 'http';
        } else {
            fclose(STDOUT);
            fclose(STDERR);
            pcntl_exec('/usr/bin/env', [
                'php',
                $yii, 'serve',
                '--docroot', $this->serve,
                '--port', $port,
            ]);
        }
    }

    public function terminateServe(): void
    {
        if (!$this->_server_pid) {
            return;
        }
        posix_kill($this->_server_pid, SIGKILL);
    }

    public function __get($name)
    {
        return Module::$beforeActionBehaviors[$name] ?? parent::__get($name);
    }

    public function __set($name, $value)
    {
        if (isset(Module::$beforeActionBehaviors[$name])) {
            Module::$beforeActionBehaviors[$name] = $value;
            return;
        }
        parent::__set($name, $value);
    }
}
