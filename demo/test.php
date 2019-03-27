<?php

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../vendor/yiisoft/yii2/Yii.php';
require __DIR__ . '/../CurlHttp.php';
$curl = new \lspbupt\curl\CurlHttp([
    'host' => 'apis.baidu.com',
    'beforeRequest' => function ($params, $curlHttp) {
        //you need put you baidu api key here
        $apikey = '';
        echo $curlHttp->getMethod();
        echo $curlHttp->getUrl();
        $curlHttp->setHeader('apikey', $apikey);
        return $params;
    },
    'afterRequest' => function ($response, $curlHttp) {
        // you may want process the request here, this is just a example
        $code = curl_getinfo($curlHttp->getCurl(), CURLINFO_HTTP_CODE);
        if ($code == 200) {
            $data = json_decode($response, true);
            if (empty($data) || empty($data['code'])) {
            }
            return $response;
        }
        return $response;
    },
]);
$data = $curl->setGet()
        ->httpExec('/apistore/weatherservice/recentweathers', ['cityname' => '北京', 'cityid' => '101010100']);
echo $data;
