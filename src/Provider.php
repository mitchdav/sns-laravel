<?php

namespace Mitchdav\SNS;

use Aws\Sns\SnsClient;
use Illuminate\Broadcasting\BroadcastManager;
use Illuminate\Contracts\Broadcasting\Factory;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use Mitchdav\SNS\Commands\Create;
use Mitchdav\SNS\Commands\Delete;
use Mitchdav\SNS\Commands\Subscribe;
use Mitchdav\SNS\Commands\Unsubscribe;
use Mitchdav\SNS\Contracts\NameFormer;

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
		$this->app->singleton(NameFormer::class, function () {
			return new SimpleNameFormer();
		});

		$this->app->singleton(SNS::class, function ($app, $config) {
			return new SNS();
		});

		/** @var BroadcastManager $broadcastManager */
		$broadcastManager = $this->app[Factory::class];

		$broadcastManager->extend('sns', function ($app, $config) {
			return new SnsBroadcaster($app[SNS::class]);
		});

		//		$this->bootWithRouter($this->app[Router::class]);
	}

	/**
	 * @param object $router
	 *
	 * @return $this
	 */
	protected function bootWithRouter($router)
	{
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