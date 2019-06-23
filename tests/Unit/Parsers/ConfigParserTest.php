<?php

namespace Tests\Unit\Parsers;

use Mitchdav\SNS\Parsers\ConfigParser;
use Mitchdav\SNS\ServiceBasedNameFormer;
use Tests\TestCase;

class ConfigParserTest extends TestCase
{
	/** @test */
	public function parses_correctly()
	{
		$config = ConfigParser::parse([
			'accounts' => [
				'account-1' => [
					'id'   => 123456789,
					'role' => 'arn:aws:iam::123456789:role/role-1',
				],
			],
			'services' => [
				'service-1' => [
					'topics' => [
						'topic-1' => [
							'account'    => 'account-1',
							'region'     => 'ap-southeast-2',
							'nameFormer' => ServiceBasedNameFormer::class,
						],
					],
				],
			],
		]);

		$account = $config->getAccount('account-1');
		$service = $config->getService('service-1');
		$topic   = $config->getTopic('service-1', 'topic-1');

		$this->assertNotNull($account);
		$this->assertNotNull($service);
		$this->assertNotNull($topic);

		$this->assertEquals($account, $topic->getAccount());

		$this->assertStringStartsWith('arn:aws:sns:ap-southeast-2:123456789:', $topic->getArn());
	}
}
