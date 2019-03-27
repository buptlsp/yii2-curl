<?php

namespace lspbupt\curl;

/**
 * Class BrowserCurl
 * 这个Curl主要设计用来更好的模拟浏览器的行为, 可以供简单爬虫使用
 * 支持 加载浏览器Cookie文件 和 保存
 *
 * @package lspbupt\curl
 */
class BrowserCurl extends CurlHttp
{
    /* 直接从File里加载cookie */
    public $cookieFile = '';

    /*
     * NOTE 这个不能和cookieFile同时使用, cookieFile适用于比 cookie适用于临时性、一次Session的情况了
     * 从数组里加载cookie
     * $cookie = ['test1'=> 'cookie1', 'test2' => 'cookie110'];
     */
    public $cookie = [];

    /* 是否存储新的cookie, 默认会记到cookieFile里面 */
    public $enableCookieFileSave = false;

    public function beforeCurlExec(&$ch)
    {
        $this->bulidCookie($ch);
        return parent::beforeCurlExec($ch);
    }

    public function bulidCookie(&$ch)
    {
        // sending manually set cookie
        if ($this->cookie) {
            $this->setHeader('Cookie', $this->genCookieStr());
            curl_setopt($ch, CURLOPT_HTTPHEADER, $this->getHeads());
        } elseif ($this->cookieFile) {
            // sending cookies from file
            curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookieFile);
        }
        // 使用临时cookie的时候也可以记录到file 供下次使用
        if ($this->enableCookieFileSave) {
            curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookieFile);
        }
    }

    private function genCookieStr()
    {
        $ret = array_map(
                function ($key, $value) {
                    return $key . '=' . $value;
                },
                array_keys($this->cookie),
                $this->cookie
            );
        return implode('; ', $ret);
    }
}
