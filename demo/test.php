<?php
require(__DIR__ . '/../vendor/autoload.php');
require(__DIR__ . '/../vendor/yiisoft/yii2/Yii.php');
require(__DIR__ . '/../CurlHttp.php');
$curl = new \lspbupt\yii2\CurlHttp([
    'host' => "www.baidu.com",
    'beforeRequest' => function($params, $curlhttp) {
        $params['q'] = "ldi";
        var_dump($curlhttp);
    }, 
    'afterRequest' => function($output, $curlhttp) {
        var_dump($curlhttp);
    }
]);
$data = $curl->setPost()->httpExec("/", ['q' => 'test']);
