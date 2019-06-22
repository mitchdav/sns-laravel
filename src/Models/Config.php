<?php

namespace Mitchdav\SNS\Models;

use Illuminate\Support\Collection;

class Config
{
	/**
	 * @var \Illuminate\Support\Collection
	 */
	private $accounts;

	/**
	 * @var \Illuminate\Support\Collection
	 */
	private $services;

	/**
	 * @var \Illuminate\Support\Collection
	 */
	private $serviceMapping;

	/**
	 * Config constructor.
	 *
	 * @param Collection $accounts
	 * @param Collection $services
	 * @param Collection $serviceMapping
	 */
	public function __construct($accounts, $services, $serviceMapping)
	{
		$this->accounts       = $accounts;
		$this->services       = $services;
		$this->serviceMapping = $serviceMapping;
	}

	/**
	 * @return \Illuminate\Support\Collection
	 */
	public function getAccounts()
	{
		return $this->accounts;
	}

	/**
	 * @return \Illuminate\Support\Collection
	 */
	public function getServices()
	{
		return $this->services;
	}

	/**
	 * @return \Illuminate\Support\Collection
	 */
	public function getServiceMapping()
	{
		return $this->serviceMapping;
	}

	/**
	 * @param string $service
	 *
	 * @return Service|null
	 */
	public function getService($service)
	{
		return $this->services->first(function ($candidate) use ($service) {
			/** @var Service $candidate */

			return $candidate->getLabel() === $service;
		});
	}

	/**
	 * @param string $service
	 * @param string $label
	 *
	 * @return Topic|null
	 */
	public function getTopic($service, $label)
	{
		return $this->serviceMapping->get(join('.', [
			$service,
			'topics',
			$label,
		]));
	}

	/**
	 * @param string $service
	 * @param string $label
	 *
	 * @return Queue|null
	 */
	public function getQueue($service, $label)
	{
		return $this->serviceMapping->get(join('.', [
			$service,
			'queues',
			$label,
		]));
	}

	/**
	 * @param string $service
	 * @param string $label
	 *
	 * @return Endpoint|null
	 */
	public function getEndpoint($service, $label)
	{
		return $this->serviceMapping->get(join('.', [
			$service,
			'endpoints',
			$label,
		]));
	}
}