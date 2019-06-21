<?php

namespace Mitchdav\SNS\Models;

use Illuminate\Support\Collection;

class Config
{
	/**
	 * @var Collection
	 */
	private $accounts;

	/**
	 * @var Collection
	 */
	private $services;

	/**
	 * Config constructor.
	 *
	 * @param Collection $accounts
	 * @param Collection $services
	 */
	public function __construct($accounts, $services)
	{
		$this->accounts = $accounts;
		$this->services = $services;
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
}