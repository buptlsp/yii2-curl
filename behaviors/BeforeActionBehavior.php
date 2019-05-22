<?php

namespace lspbupt\curl\behaviors;

use lspbupt\curl\module\controllers\DefaultController;
use yii\base\Behavior;

/**
 * To use Curl, include it as a module in the application configuration like the following:
 *
 * ~~~
 * return [
 *     'modules' => [
 *         'curl' => ['class' => 'yii\curl\Module'],
 *         'as foo' => ClassExtendsThis::class,
 *     ],
 * ]
 * ~~~
 * php yii curl <component> [action] --foo bar
 */
abstract class BeforeActionBehavior extends Behavior
{
    /**
     * 在即将请求curl的时候，执行本方法
     * @var string 当前类的实例绑定到module的behavior的key对应的传入值，在上述例子是string(3) "bar"
     * @var DefaultController 实例，可以修改参数，比如headers等，实现自动登录
     */
    abstract public function run(string $params, DefaultController $controller): void;

    public function default(): string
    {
        return '';
    }
}
