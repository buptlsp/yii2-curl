<?php
namespace lspbupt\curl;
use Yii;
use Closure;
use yii\caching\Cache;
use yii\di\Instance;

/**
 * Class CacheCurl
 * 这个Curl主要设计用来缓存重复查询的curl请求结果
 * @package lspbupt\curl
 */
class CacheCurl extends CurlHttp
{
    public $defaultPrefix = 'CacheUrl_';

    public $cache = 'cache';

    /* 默认缓存时间 10min */
    public $cacheTime = 600;

    /* 是否打开cache缓存 默认关闭 */
    public $enableCache = false;

    public $cacheKey = '';

    /* 获取缓存的时候从params里要排除的参数, 比如 ['_ts', '_nonce', '_sign']  */
    public $excludes = [];

    public function init()
    {
        parent::init();
        $this->cache = Instance::ensure($this->cache, Cache::class);
    }

    /**
     * @deprecated 推荐使用send方法
     * @param string $action
     * @param array $params
     */
    public function httpExec($action = "/", $params = []) {
        return $this->send($action, $params);
    }

    public function send($action = "/", $params = []) {
        if ($this->enableCache) {
            $this->cacheKey = $this->getKey($action, $params);
            $data = $this->cache->get($this->cacheKey);
            if($this->isDebug()) {
                echo "\n注意cache开启中:" . "\n";
                echo "\n请求结果:".$data."\n";
            }
            if ($data) return json_decode($data, true);
            if($this->isDebug()) {
                echo "\ncache没有命中 走正常请求流程:" . "\n";
            }
        }
        return parent::send($action, $params);
    }

    //请求之后的操作
    protected function afterCurl($data)
    {
        if ($this->enableCache && $this->cacheKey) {
            $this->cache->set($this->cacheKey, $data, $this->cacheTime);
        }
        return parent::afterCurl($data);
    }

    public function setEnableCache($value=true) {
        $this->enableCache = $value;
        return $this;
    }

    /**
     * 根据action params获取redis key
     * @return string
     */
    private function getKey($action = "/", $params = []) {
        if ($params) {
            if ($this->excludes) {
                foreach ($this->excludes as $exclude) {
                    unset($params[$exclude]);
                }
            }
            sort($params);
        }
        $arr = [$this->getUrl(), $action, md5(json_encode($params))];
        $key = $this->defaultPrefix . implode('_', $arr);
        return $key;
    }
}
