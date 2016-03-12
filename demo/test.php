<?php
require(__DIR__ . '/../vendor/autoload.php');
require(__DIR__ . '/../vendor/yiisoft/yii2/Yii.php');
require(__DIR__ . '/../Curl.php');
$curl = new \lspbupt\yii2\curl\CurlHttp([
    'host' => "www.baidu.com",
    'beforeRequest' => function($ch, $test, $params) {
        $params['q'] = "ldi";
        var_dump($ch);
    }, 
    'afterRequest' => function($ch, $test) {
        var_dump($test);
    }
]);
$data = $curl->setPost()->httpExec("/", ['q' => 'test']);
