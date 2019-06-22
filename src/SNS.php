<?php

namespace Mitchdav\SNS;

use Illuminate\Broadcasting\BroadcastManager;
use Mitchdav\SNS\Contracts\SubscriptionMethod;
use Mitchdav\SNS\Models\SubscriptionMethods\HTTP;
use Mitchdav\SNS\Models\SubscriptionMethods\HTTPS;
use Mitchdav\SNS\Models\SubscriptionMethods\SQS;

class SNS
{
	const ACCOUNT_DEFAULT    = 'default';
	const ARN_FORMER_DEFAULT = '$DEFAULT$';

	/**
	 * @var \Mitchdav\SNS\Models\Config
	 */
	private $config;

	public function __construct($configFileName = 'sns')
	{
		$this->config = ConfigParser::parse(config($configFileName));

		$this->drivers = [];

		$this->extend(HTTP::METHOD, $this->createHttpDriver($this->config));
		$this->extend(HTTPS::METHOD, $this->createHttpsDriver($this->config));
		$this->extend(SQS::PROTOCOL, $this->createSqsDriver($this->config));
	}

	/**
	 * @return \Mitchdav\SNS\Models\Config
	 */
	public function config()
	{
		return $this->config;
	}

	public function publish($notification)
	{
		/** @var BroadcastManager $broadcastManager */
		$broadcastManager = app(BroadcastManager::class);

		$defaultDriver = $broadcastManager->getDefaultDriver();

		$broadcastManager->setDefaultDriver('sns');

		event($notification);

		$broadcastManager->setDefaultDriver($defaultDriver);
	}

	public function extend($protocol, SubscriptionMethod $driver)
	{
		$this->drivers[$protocol] = $driver;
	}

	public function drivers()
	{
		return $this->drivers;
	}

	public function driver($protocol)
	{
		return $this->drivers[$protocol];
	}

	/**
	 * Dynamically call the default driver instance.
	 *
	 * @param string $method
	 * @param array  $parameters
	 *
	 * @return mixed
	 */
	public function __call($method, $parameters)
	{
		return $this->config()
		            ->$method(...$parameters);
	}

	protected function createHttpDriver($config)
	{
		return new HTTP($config);
	}

	protected function createHttpsDriver($config)
	{
		return new HTTPS($config);
	}

	protected function createSqsDriver($config)
	{
		return new SQS($config);
	}
}