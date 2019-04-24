<?php

namespace lspbupt\curl;

use lspbupt\common\helpers\ArrayHelper;
use yii\caching\Cache;
use yii\di\Instance;

/**
 * Class CacheCurl
 * 这个Curl主要设计用来缓存重复查询的curl请求结果
 * 两种cache策略 一种走redis 一种放内存里,随request结束而释放
 *
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

    public $useMemoryCache = false;

    /* 当启用内部数组缓存时候, 用来存放缓存数据的数组 */
    private $_cacheArr = [];

    public function init()
    {
        parent::init();
        $this->cache = Instance::ensure($this->cache, Cache::class);
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

    public function send($action = '/', $params = [])
    {
        if ($this->enableCache) {
            $this->cacheKey = $this->getKey($action, $params);
            $data = $this->getCacheData($this->cacheKey);
            if ($this->isDebug()) {
                echo "\n注意cache开启中:" . "\n";
                echo "\n请求结果:".$data."\n";
            }
            if ($data !== false) {
                return json_decode($data, true);
            }
            if ($this->isDebug()) {
                echo "\ncache没有命中 走正常请求流程:" . "\n";
            }
        }
        return parent::send($action, $params);
    }

    //请求之后的操作
    protected function afterCurl($data)
    {
        if ($this->enableCache && $this->cacheKey) {
            $this->setCacheData($this->cacheKey, $data, $this->cacheTime);
        }
        return parent::afterCurl($data);
    }

    private function getCacheData($cacheKey) {
        if ($this->useMemoryCache) {
            $data = ArrayHelper::getValue($this->_cacheArr, $cacheKey, false);
        } else {
            $data = $this->cache->get($cacheKey);
        }
        return $data;
    }

    private function setCacheData($cacheKey, $data, $cacheTime) {
        if ($this->useMemoryCache) {
            $this->_cacheArr[$cacheKey] = $data;
        } else {
            $this->cache->set($cacheKey, $data, $cacheTime);
        }
    }

    public function enableCache()
    {
        $this->enableCache = true;
        $this->useMemoryCache = false;
        return $this;
    }

    public function disableCache()
    {
        $this->enableCache = false;
        $this->useMemoryCache = false;
        return $this;
    }

    public function enableMemCache()
    {
        $this->enableCache = true;
        $this->useMemoryCache = true;
        return $this;
    }

    public function disableMemCache()
    {
        $this->enableCache = false;
        $this->useMemoryCache = false;
        return $this;
    }

    /**
     * 根据action params获取redis key
     *
     * @return string
     */
    private function getKey($action = '/', $params = [])
    {
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
