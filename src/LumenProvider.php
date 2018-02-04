<?php

namespace Mitchdav\SNS;

/**
 * Class LumenProvider
 * @package Mitchdav\SNS
 */
class LumenProvider extends Provider
{
	public function boot()
	{
		$broadcastManager = new \Illuminate\Broadcasting\BroadcastManager($this->app);

		$this->app->singleton(\Illuminate\Broadcasting\BroadcastManager::class, function ($app, $config) use ($broadcastManager) {
			return $broadcastManager;
		});

		$this->app->bind(\Illuminate\Contracts\Broadcasting\Factory::class, function ($app, $config) use ($broadcastManager) {
			return $broadcastManager;
		});

		$this->bootWithRouter($this->app);
	}
}