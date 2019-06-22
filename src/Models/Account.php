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
	public $label;

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