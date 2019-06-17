<?php

namespace Mitchdav\SNS\Actions;

use Aws\Sdk;
use Aws\Sns\SnsClient;

class CreateTopic
{
	/**
	 * @var \Aws\Sdk
	 */
	private $sdk;

	public function __construct(Sdk $sdk)
	{
		$this->sdk = $sdk;
	}

	public function __invoke($topic)
	{
		$snsClient = $this->sdk->createSns([
			'region' => 'ap-southeast-2',
		]);

		$result = $this->snsClient->createTopic([
			'Name' => $topic,
		]);

		var_dump($result);
	}
}
