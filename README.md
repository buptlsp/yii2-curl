CURL Encapsulation for Yii 2
========================

This extension provides a curl encapsulation for yii2. you can use it for rapid develop;
这个扩展提供了一个基于yii2的curl封装，通过它你能快速的开发。

For license information check the [LICENSE](LICENSE.md)-file.
在此处可以查看本扩展的[许可](LICENSE.md)


Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).
推荐的方式是通过composer 进行下载安装[composer](http://getcomposer.org/download/)。

Either run
在命令行执行
```
php composer.phar require --prefer-dist "lspbupt/yii2-curl" "*"
```

or add
或加入

```
"lspbupt/yii2-curl": "*"
```

to the require-dev section of your `composer.json` file.
到你的`composer.json`文件中的require-dev段。

Usage
-----

Once the extension is installed, simply modify your application configuration as follows:
一旦你安装了这个插件，你就可以直接在配置文件中加入如下的代码：

Now we will use [baiduApistore](http://apistore.baidu.com) to show how can use it:
现在我们用[百度apistore](http://apistore.baidu.com)来举例说明你如何使用它:

```php
return [
    'components' => [
        'baiduApi' => [
            'class' => 'lspbupt\curl\CurlHttp',
            'host' => 'apis.baidu.com',
            'beforeRequest' => function($params, $curlHttp) {
                //you need put you baidu api key here
                $apikey = 'xxxxxx';
                $curlHttp->setHeader('apikey', $apikey);
                return $params;
            },
            'afterRequest' => function($response, $curlHttp) {
                // you may want process the request here, this is just a example
                $code = curl_getinfo($curlHttp->getCurl(), CURLINFO_HTTP_CODE);
                if($code == 200) {
                    $data = json_decode($response, true);
                    if(empty($data) || empty($data['code'])) {
                        Yii::warning("error!", "curl.baidu");
                    }
                    Yii::info("ok!", "curl.baidu");
                    return $response;
                }
                Yii::error("error", "curl.baidu");
                return $response;
            }
            //'protocol' => 'http',
            //'port' => 80,
            // ...
        ],
    ],   
    // .... 
];
```

After that, you can use it as follow:  
在配置好之后，你可以这么访问它： 
```php
// you can use this search beijin weather,  http://apistore.baidu.com/apiworks/servicedetail/112.html
$data = Yii::$app->baiduApi
                ->setGet()
                ->httpExec("/apistore/weatherservice/recentweathers", ['cityname' => '北京', 'cityid' => '101010100']);
// you can also use this search the real address of a ip address, http://apistore.baidu.com/apiworks/servicedetail/114.html
$data = Yii::$app->baiduApi
            ->setGet()
            ->httpExec("/apistore/iplookupservice/iplookup", ['ip' => '117.89.35.58']);
// any other apis
```

as you see, once you configed a api, you can use it anywhere, have fun!  
如上所见，一旦你配置好了对接的参数和处理，你就能在任何地方很方便的使用它了，祝您使用愉快！ 
