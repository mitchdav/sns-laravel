<?php

namespace Mitchdav\SNS\Models;

use Aws\Sdk;
use Carbon\Carbon;
use Illuminate\Support\Facades\App;

class Account
{
	/**
	 * @var string
	 */
	private $label;

	/**
	 * @var int
	 */
	private $id;

	/**
	 * @var string|NULL
	 */
	private $role;

	/**
	 * @var Sdk
	 */
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
			'RoleSessionName' => 'AssumeSNSRole' . now()->timestamp,
		]);

		$credentials = $assumeRoleResponse->get('Credentials');

		return [
			'key'        => $credentials['AccessKeyId'],
			'secret'     => $credentials['SecretAccessKey'],
			'token'      => $credentials['SessionToken'],
			'expiration' => Carbon::make($credentials['Expiration']),
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
				$config = array_merge(config('aws'), [
					'credentials' => static::getCredentialsForRole($defaultSdk, $this->role),
				]);

				$this->sdk = new Sdk($config);
			} else {
				$this->sdk = $defaultSdk;
			}
		}

		return $this->sdk;
	}
}