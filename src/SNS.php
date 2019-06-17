<?php

namespace Mitchdav\SNS;

use Mitchdav\SNS\Models\Account;

class SNS
{
	const ACCOUNT_DEFAULT         = 'default';
	const ARN_FORMER_DEFAULT      = '$DEFAULT$';
	const ARN_PREFIX_SERVICE_NAME = '$SERVICE_NAME$';

	private $accounts;

	private $services;

	public function __construct()
	{
		$this->accounts = [];
		$this->services = [];
	}

	private function parseConfig()
	{
		$accountsConfig = config('sns.accounts', []);
		$defaultsConfig = config('sns.defaults', []);
		$servicesConfig = config('sns.services', []);

		foreach ($accountsConfig as $label => $attributes) {
			$this->accounts[] = new Account($label, $attributes['id'], $attributes['role']);
		}

		foreach ($servicesConfig as $label => $attributes) {
			$this->accounts[] = new Account($label, $attributes['id'], $attributes['role']);
		}
	}
}