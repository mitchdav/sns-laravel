<?php

namespace Mitchdav\SNS\Models;

use Aws\Sdk;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;

class Account
{
	/** @var string */
	private $label;

	/** @var int */
	private $id;

	/** @var NULL|string */
	private $role;

	/** @var Sdk */
	private $sdk;

	/**
	 * Account constructor.
	 *
	 * @param string      $label
	 * @param int         $id
	 * @param NULL|string $role
	 */
	public function __construct($label, $id, $role)
	{
		$this->label = $label;
		$this->id    = $id;
		$this->role  = $role;

		$this->sdk = NULL;
	}

	public static function parseAccounts($config)
	{
		$accounts = [];

		foreach ($config as $label => $attributes) {
			$accounts[] = self::parse($label, $attributes);
		}

		return $accounts;
	}

	public static function parse($label, $attributes)
	{
		if (!isset($attributes['id'])) {
			throw new \Exception('You must provide the account id using the "id" key for account "' . $label . '".');
		}

		$id = $attributes['id'];

		if (!is_numeric($id)) {
			throw new \Exception('The account id for account "' . $label . '" must be numeric.');
		}

		$role = Arr::get($attributes, 'role', NULL);

		if ($role !== NULL) {
			if (!is_string($role)) {
				throw new \Exception('The account role for account "' . $label . '" must be a string.');
			}

			if (Str::startsWith($role, 'arn:aws:iam::')) {
				if (!Str::startsWith($role, 'arn:aws:iam::' . $id . ':role/')) {
					throw new \Exception('The account role for account "' . $label . '" must start with "arn:aws:iam::' . $id . ':role/" (check that you have the correct account id).');
				}
			} else {
				$role = 'arn:aws:iam::' . $id . ':role/' . $role;
			}
		}

		return new Account($label, $id, $role);
	}

	/**
	 * @param \Aws\Sdk $sdk
	 * @param          $role
	 *
	 * @return array
	 */
	private static function getCredentialsForRole(Sdk $sdk, $role)
	{
		$stsClient = $sdk->createSts();

		$assumeRoleResponse = $stsClient->assumeRole([
			'RoleArn'         => $role,
			'RoleSessionName' => 'AssumeSNSRole' . time(),
		]);

		$credentials = $assumeRoleResponse->get('Credentials');

		return [
			'key'        => $credentials['AccessKeyId'],
			'secret'     => $credentials['SecretAccessKey'],
			'token'      => $credentials['SessionToken'],
			'expiration' => $credentials['Expiration'],
		];
	}

	/**
	 * @return \Aws\Sdk
	 */
	public function sdk()
	{
		if ($this->sdk === NULL) {
			/** @var Sdk $sdk */
			$defaultSdk = App::make('aws');

			if ($this->role !== NULL) {
				$config = array_replace_recursive(config('aws'), [
					'credentials' => static::getCredentialsForRole($defaultSdk, $this->role),
				]);

				// Profile takes precedence over credentials, so we unset it here
				if (isset($config['profile'])) {
					unset($config['profile']);
				}

				$this->sdk = new Sdk($config);
			} else {
				$this->sdk = $defaultSdk;
			}
		}

		return $this->sdk;
	}

	/**
	 * @return string
	 */
	public function getLabel()
	{
		return $this->label;
	}

	/**
	 * @return int
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * @return NULL|string
	 */
	public function getRole()
	{
		return $this->role;
	}
}