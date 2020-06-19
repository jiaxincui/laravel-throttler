<?php


namespace Jiaxincui\Throttler;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Container\Container;
use Jiaxincui\Throttler\Contracts\RateLimiter as RateLimitContract;
use Jiaxincui\Throttler\Exceptions\ThrottlerException;

class Throttler
{
    /**
     * @var
     */
    protected $taps = [];

    /**
     * @var
     */
    protected $key;

    /**
     * @var Container
     */
    protected $app;

    /**
     * @var string
     */
    protected $guard = 'default';


    protected $config;

    /**
     * ThrottlerManager constructor.
     * @param Container $app
     * @throws BindingResolutionException
     */
    public function __construct(Container $app)
    {
        $this->app = $app;
        $this->config = $app->make('config')->get('throttler');
    }

    /**
     * @return RateLimitContract|mixed|object
     * @throws ThrottlerException
     * @throws BindingResolutionException
     */
    protected function limiter()
    {
        $configLimiter = $this->config['limiter'] ?? null;

        if (! $configLimiter) {
            return $this->app->make(\Illuminate\Cache\RateLimiter::class);
        }

        if (class_exists($configLimiter) && $configLimiter instanceof RateLimitContract) {
            return $this->app->make($configLimiter);
        }

        throw new ThrottlerException("Class {$configLimiter} must be an instance of Jiaxincui\\Throttler\\Contracts\\RateLimiter");
    }

    /**
     * @param $key
     * @return $this
     */
    public function for($key)
    {
        $this->key = $key;
        return $this;
    }

    /**
     * @param array $taps
     * @return $this
     */
    public function tap(array $taps)
    {
        if (is_array(current($taps))) {
            $this->taps = array_merge($this->taps, $taps);
        } else {
            $this->taps[] = $taps;
        }
        return $this;
    }

    /**
     * @param $guard
     * @return $this
     */
    public function guard($guard)
    {
        $this->guard = $guard;
        return $this;
    }

    /**
     * @param callable $callback
     * @param callable|null $failure
     * @throws ThrottlerException
     * @throws BindingResolutionException
     */
    public function then(callable $callback, callable $failure = null)
    {
        $limiter = $this->limiter();

        $flag = true;

        $taps = $this->mergerTapsFromConfig();

        foreach ($taps as $key => $tap) {
            if ($limiter->tooManyAttempts($this->composeKey($key), (int) $tap[0])) {
                $flag = false;
                if ($failure) {
                    $failure($limiter->availableIn($this->composeKey($key)));
                }
                break;
            }
        }

        if ($flag) {
            foreach ($taps as $key => $tap) {
                $limiter->hit($this->composeKey($key), (int) $tap[1]);
            }
            $callback();
        }
    }

    /**
     * @return array
     */
    protected function mergerTapsFromConfig()
    {
        return array_merge($this->config['guards'][$this->guard] ?? [], $this->taps);
    }

    /**
     * @param $tapKey
     * @return string
     */
    protected function composeKey($tapKey)
    {
        $keyPrefix = $this->config['prefix'] ?? '';
        return $keyPrefix . $this->guard . $tapKey . $this->key;
    }

}
