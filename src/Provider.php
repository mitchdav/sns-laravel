<?php

namespace Mitchdav\SNS;

use Aws\Sns\SnsClient;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use Mitchdav\SNS\Commands\Create;
use Mitchdav\SNS\Commands\Delete;
use Mitchdav\SNS\Commands\Subscribe;
use Mitchdav\SNS\Commands\Unsubscribe;

/**
 * Class Provider
 * @package Mitchdav\SNS
 */
class Provider extends ServiceProvider
{
	public function register()
	{
		$this->mergeConfigFrom(__DIR__ . '/../config/sns.php', 'sns');
	}

	public function boot()
	{
		$this->bootWithRouter(Router::class);
	}

	/**
	 * @param string $router
	 *
	 * @return $this
	 */
	protected function bootWithRouter($router)
	{
		$this->app->singleton(SnsClient::class, function ($app, $config) {
			return new SnsClient(config('sns.client'));
		});

		$this->app->singleton(SNS::class, function ($app, $config) use ($router) {
			return new SNS($app[SnsClient::class], $app[$router]);
		});

		$this->app[\Illuminate\Broadcasting\BroadcastManager::class]->extend('sns', function ($app, $config) {
			return new Broadcaster($app[SNS::class]);
		});

		$this->publishes([
			__DIR__ . '/../config/sns.php' => $this->getConfigPath('sns.php'),
		], 'config');

		if ($this->app->runningInConsole()) {
			$this->commands([
				Create::class,
				Delete::class,
				Subscribe::class,
				Unsubscribe::class,
			]);
		}

		$this->app[SNS::class]->registerRoutes();

		return $this;
	}

	/**
	 * @param string $path
	 *
	 * @return string
	 */
	private function getConfigPath($path = '')
	{
		if (!function_exists('config_path')) {
			/**
			 * @see https://gist.github.com/mabasic/21d13eab12462e596120
			 */
			return app()->basePath() . '/config' . ($path ? '/' . $path : $path);
		} else {
			return config_path($path);
		}
	}
}