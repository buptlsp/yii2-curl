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
php composer.phar require --dev --prefer-dist buptlsp/yii2-curl
```

or add   
或加入

```
"buptlsp/yii2-gii": "~2.0.0"
```

to the require-dev section of your `composer.json` file.  
到你的`composer.json`文件中的require-dev段。  

Usage
-----

Once the extension is installed, simply modify your application configuration as follows:  
一旦你安装了这个插件，你就可以直接在配置文件中加入如下的代码：  

```php
return [
    'curl' => [
        'host' => 'www.baidu.com',
        'beforeRequest' => function($ch, $req) {
            //Yii::info("begin Bequest");
            //you can add any aditional code before http request
        },
        'afterRequest' => function($ch, $req, $response)
        {
            //Yii::error("error");
            // you can add any aditional code after request
        }
        //'protocol' => 'http',
        //'port' => 80,
    ],
    // ...
];
```

