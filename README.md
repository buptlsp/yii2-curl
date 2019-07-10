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

Usage
-----
1、Debug模式

打开debug时，会将请求的详细信息均打印出来，方便调试。

```php
    $data = Yii::$app->baiduApi->setDebug()->httpExec("/apistore/xxx", []);
```

2、http头设置
我们可以通过如下方法来设置我们想要的header，如下为发送postjson请求的示例。当然，我们也可以直接setPostJson()来设置为postjson请求。

```php
    $data = Yii::$app->baiduApi
        ->setHeader('Content-Type', 'application/json;charset=utf-8')
        ->httpExec("/apistore/xxx", []);
```

3、一些Tips

- 正常的请求，都建议明确带上setGet()还是setPost()。
- postjson请求时，我们可以加上setPostJson()即可。
- 如果需要传文件，我们需要formData方式时，可以加上setFormData()来实现。
- 正常如果beforeRequest和AfterRequest较长的话，不建议写配置中。建议通过类的继承来实现。

4、console模式
正常情况下，我们经常需要在命令行发出一些命令，来跟baidu通信，调试api接口。因此，我们可以使用代码提供的工具来调试：

首先，在`console/controllers/main-local.php`中加入如下的配置：

```php
return [
    // 其它配置
    'modules' => [
        // ...
        'curl' => ['class' => 'lspbupt\curl\module\Module'],
        'as foo' => ['class' => 'some_class_extends_BeforeActionBehavior']
        // ...
    ],
    // 其它配置
];
```

其次，我们就可以在命令行使用它进行调试了：

```bash
./yii curl baiduApi "/apistore/iplookupservice/iplookup?ip=1.1.1.1"
# 如果绑定一个自定义的前置处理器，此方法将能接受一个新的参数，同注入行为的名字。
# 详见behaviors/BeforeActionBehavior
./yii curl baiduApi "/apistore/iplookupservice/iplookup?ip=1.1.1.1" --foo bar
#更多帮助
./yii curl -h
```

5、利用yii2内置http服务器进行简易调试
只需要加入简单的参数`--serve xxxend/web`即可抛开nginx的限制，进行原地简易调试。

广告
--------------

我们是一群热爱技术，追求卓越的极客，我们乐于做一些对整个社会都有作用的事情，我们希望通过我们的努力来推动整个社会的创新，如果你也一样，欢迎加入我们（service@ethercap.com）！你也可以通过https://tech.ethercap.com 来了解更多！
