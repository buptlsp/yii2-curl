<?php

namespace lspbupt\curl\module;

use yii\base\BootstrapInterface;

/**
 * To use Curl, include it as a module in the application configuration like the following:
 *
 * ~~~
 * return [
 *     'modules' => [
 *         'curl' => ['class' => 'yii\curl\Module'],
 *     ],
 * ]
 * ~~~
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class Module extends \yii\base\Module implements BootstrapInterface
{
    public $controllerNamespace = 'lspbupt\curl\module\controllers';

    public function bootstrap($app)
    {
    }
}
