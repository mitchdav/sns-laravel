<?php

namespace Tests\Unit\Parsers;

use Mitchdav\SNS\Models\Account;
use Mitchdav\SNS\Models\Topic;
use Mitchdav\SNS\ServiceBasedNameFormer;
use Tests\TestCase;

class TopicTest extends TestCase
{
	/** @test */
	public function parses_correctly_for_single()
	{
		$accounts = collect([
			$account = new Account('account-1', 123456789, 'arn:aws:iam::123456789:role/role-1'),
		]);

		$defaults = [];

		$topic = Topic::parse($accounts, $defaults, 'service-1', 'topic-1', [
			'account'    => 'account-1',
			'region'     => 'ap-southeast-2',
			'nameFormer' => ServiceBasedNameFormer::class,
		]);

		$this->assertNotNull($topic);

		$this->assertEquals($account, $topic->getAccount());

		$this->assertStringStartsWith('arn:aws:sns:ap-southeast-2:123456789:', $topic->getArn());
	}

	/** @test */
	public function parses_correctly_for_multiple()
	{
		$accounts = collect([
			$account1 = new Account('account-1', 123456789, 'arn:aws:iam::123456789:role/role-1'),
			$account2 = new Account('account-2', 987654321, 'arn:aws:iam::987654321:role/role-2'),
		]);

		$defaults = [];

		/** @var Topic[] $topics */
		$topics = Topic::parseTopics($accounts, $defaults, 'service-1', [
			'topic-1' => [
				'account'    => 'account-1',
				'region'     => 'ap-southeast-2',
				'nameFormer' => ServiceBasedNameFormer::class,
			],
			'topic-2' => [
				'account'    => 'account-2',
				'region'     => 'us-west-2',
				'nameFormer' => ServiceBasedNameFormer::class,
			],
		]);

		$topic1 = $topics[0];
		$topic2 = $topics[1];

		$this->assertNotNull($topic1);
		$this->assertNotNull($topic2);

		$this->assertEquals($account1, $topic1->getAccount());
		$this->assertEquals($account2, $topic2->getAccount());

		$this->assertStringStartsWith('arn:aws:sns:ap-southeast-2:123456789:', $topic1->getArn());
		$this->assertStringStartsWith('arn:aws:sns:us-west-2:987654321:', $topic2->getArn());
	}

	/** @test */
	public function throws_exception_for_missing_account()
	{
		$this->expectExceptionMessage('account');

		$accounts = collect([
			new Account('account-1', 123456789, 'arn:aws:iam::123456789:role/role-1'),
		]);

		$defaults = [];

		Topic::parse($accounts, $defaults, 'service-1', 'topic-1', [
			'region' => 'ap-southeast-2',
		]);
	}

	/** @test */
	public function throws_exception_for_missing_region()
	{
		$this->expectExceptionMessage('region');

		$accounts = collect([
			new Account('account-1', 123456789, 'arn:aws:iam::123456789:role/role-1'),
		]);

		$defaults = [];

		Topic::parse($accounts, $defaults, 'service-1', 'topic-1', [
			'account' => 'account-1',
		]);
	}

	/** @test */
	public function throws_exception_for_missing_name_former()
	{
		$this->expectExceptionMessage('former');

		$accounts = collect([
			new Account('account-1', 123456789, 'arn:aws:iam::123456789:role/role-1'),
		]);

		$defaults = [];

		Topic::parse($accounts, $defaults, 'service-1', 'topic-1', [
			'account' => 'account-2',
			'region'  => 'us-west-2',
		]);
	}
}
