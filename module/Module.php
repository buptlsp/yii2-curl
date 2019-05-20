<?php

namespace lspbupt\curl\module;

use lspbupt\curl\behaviors\BeforeActionBehavior;
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
    /**
     * @var array 存储了绑定的自定义参数key的默认值/当前值
     * @see BeforeActionBehavior
     */
    public static $beforeActionBehaviors = [];

    public function bootstrap($app)
    {
    }

    public function attachBehavior($name, $behavior)
    {
        if ($behavior instanceof BeforeActionBehavior) {
            self::$beforeActionBehaviors[$name] = $behavior->default();
        }
        return parent::attachBehavior($name, $behavior);
    }
}
