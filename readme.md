## About

Multiple policy rate limit for laravel

适用于 Laravel 的多策略速率限制包


## 安装

```terminal
composer require jiaxincui/laravel-throttler
```

## 配置文件

此命令将配置文件copy至`config/throttler.php`

```terminal
php artisan vendor:publish --provider="Jiaxincui\Throttler\ThrottlerServiceProvider"
```

`key_prefix` 限速器 key 前缀，如果有多个应用存在 key 重复的可能你可以设置一个适当的前缀，如果你确定没有重复的可能可忽略。

`limiter` 使用的限速器，如果为空，默认使用`Iluminate\Cache\RateLimiter`， 如果要自定义限速器实现，要确保其实现 `Jiaxincui\Throttler\Contracts\Limiter` 接口

`guard` 限速方案，在使用中没有明确指定 `guard` 将使用 `default` 作为限速方案。

每个方案可以设置多条策略，如 default 方案限制每秒钟5次，并且每分钟10次，每小时50次，每24小时100次，可以像下面这样设置:

```php
[
  'guards' => [
    'default' => [
      [5, 1],
      [10, 60],
      [50, 60*60],
      [100, 60*60*24]
    ],

 //   'custom1' => [
 //     [5, 1],
 //     [10, 60]
 //   ]
  ]
];

```


### 使用

一般情况下你只需要在配置文件 `config/throttler.php` 设置策略即可。


```php
use Jiaxincui\Throttler\Facades\Throttler;

Throttler::throttle($request->ip)
    ->then(function() {
        // 通过
        return 'Hello world!';
    }, function($maxAttempts, $decaySeconds) {
        // 未通过
        var_dump($decaySeconds);
    });

// 使用其他限速方案
Throttler::guard('custom')
    ->throttle($request->ip)
    ->then(function() {
        // 通过
        return 'Hello world!';
    }, function($maxAttempts, $decaySeconds) {
        // 未通过
        var_dump($decaySeconds);
    });
```

如果你要在运行时设置限制策略，可以像下面这样做：

```php
use Jiaxincui\Throttler\Facades\Throttler;

Throttler::throttle($user->id)
    ->addPolicy(5, 1)
    ->addPolicy(10, 60)
    ->addPolicy(50, 60*60)
    ->then(function() {
        // 通过
        return 'Hello world!';
    }, function($maxAttempts, $decaySeconds) {
        // 未通过
        var_dump($decaySeconds);
    });

// 或者
$policies = [
    [5, 1],
    [10, 60],
    [50, 60 * 60]
];

Throttler::throttle($user->id)
    ->addPolicies($policies)
    ->then(function() {
        // 通过
        return 'Hello world!';
    }, function($maxAttempts, $decaySeconds) {
        // 未通过
        var_dump($decaySeconds);
    });
```

你也可以在控制器的构造方法里通过依赖注入使用它

```php
<?php

namespace App\Http\Controllers;

use Jiaxincui\Throttler\Throttler;

class UserController extends Controller
{
    protected $throttler;

    /**
     * Create a new controller instance.
     *
     * @param Throttler $throttler
     */
    public function __construct(Throttler $throttler)
    {
        $this->throttler = $throttler;
    }
    public function index(Request $request)
    {
        $this->throttler->throttle($request->ip)
            ->then(function () {
              // 通过
            }, function ($maxAttempts, $decaySeconds) {
              // 未通过
}           );
    }

}
```

## Api

| 方法 | 返回 | 说明 |
| :-- | :-- | :-- |
| `throttle(string $key)` | `$this` | 传入`$key`创建一个限速器 |
| `guard(string $guradName)` | `$this` | 应用一个限速方案 |
| `addPolicy(int $maxAttempts, int $decaySeconds )` | `$this` | 添加一个限速策略 |
| `addPolicies(array $policies)` | `$this` | 批量添加策略 |
| `then(callable $callback, callable $failure)`| `null` | `$callback` 成功回调, `$failure`失败回调接收两个参数：第一个参数最大尝试次数`$maxAttempts`, 第二个参数距离下次可通过的秒数`$decaySeconds` |



## License

[MIT](https://github.com/jiaxincui/laravel-throttler/blob/master/LICENSE.md) © [JiaxinCui](https://github.com/jiaxincui)

