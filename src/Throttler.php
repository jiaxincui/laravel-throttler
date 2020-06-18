<?php


namespace Jiaxincui\Throttler;

use Illuminate\Contracts\Container\BindingResolutionException;
use jiaxincui\Throttler\Contracts\RateLimiter as RateLimitContract;
use Illuminate\Container\Container;
use Jiaxincui\Throttler\Exceptions\ThrottlerException;

class Throttler
{
    /**
     * @var
     */
    protected $policies = [];

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

    /**
     * ThrottlerManager constructor.
     * @param Container $app
     */
    public function __construct(Container $app)
    {
        $this->app = $app;
    }

    /**
     * @return RateLimitContract|mixed|object
     * @throws ThrottlerException
     * @throws BindingResolutionException
     */
    protected function limiter()
    {
        $configLimiter = $this->app->make('config')->get('throttler.limiter');

        if ($configLimiter && class_exists($configLimiter)) {
            $limiter = $this->app->make($configLimiter);
            if (!$limiter instanceof RateLimitContract) {
                throw new ThrottlerException("Class {$configLimiter} must be an instance of Jiaxincui\\Throttler\\Contracts\\RateLimiter");
            }
            return $limiter;
        }
        return $this->app->make(\Illuminate\Cache\RateLimiter::class);
    }

    /**
     * @param $key
     * @return $this
     */
    public function throttle($key)
    {
        $this->key = $key;
        return $this;
    }

    /**
     * @param int $maxAttempts
     * @param int $decaySeconds
     * @return $this
     */
    public function addPolicy($maxAttempts, $decaySeconds)
    {
        $this->policies[] = [$maxAttempts, $decaySeconds];
        return $this;
    }

    /**
     * @param array $policies
     * @return $this
     */
    public function addPolicies(array $policies)
    {
        $this->policies = array_merge($this->policies, $policies);
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

        $policies = $this->mergerPoliciesFromConfig();

        foreach ($policies as $key => $policy) {
            if ($limiter->tooManyAttempts($this->composeKey($key), (int) $policy[0])) {
                $flag = false;
                if ($failure) {
                    $failure($policy[0], $limiter->availableIn($this->composeKey($key)));
                }
                break;
            }
        }

        if ($flag) {
            foreach ($policies as $key => $policy) {
                $limiter->hit($this->composeKey($key), (int) $policy[1]);
            }
            $callback();
        }
    }

    /**
     * @return array
     * @throws BindingResolutionException
     */
    protected function mergerPoliciesFromConfig()
    {
        return array_merge($this->app->make('config')->get("throttler.guards.{$this->guard}", []), $this->policies);
    }

    /**
     * @param $policyKey
     * @return string
     * @throws BindingResolutionException
     */
    protected function composeKey($policyKey)
    {
        $keyPrefix = $this->app->make('config')->get('throttler.key_prefix', '');
        return $keyPrefix . $this->guard . ':' . $policyKey . ':' . $this->key;
    }

}
