<?php

namespace Tests\Unit\Parsers;

use Mitchdav\SNS\Models\Account;
use Mitchdav\SNS\Models\Queue;
use Mitchdav\SNS\ServiceBasedNameFormer;
use Tests\TestCase;

class QueueTest extends TestCase
{
	/** @test */
	public function parses_correctly_for_single()
	{
		$accounts = collect([
			$account = new Account('account-1', 123456789, 'arn:aws:iam::123456789:role/role-1'),
		]);

		$defaults = [];

		$queue = Queue::parse($accounts, $defaults, 'service-1', 'queue-1', [
			'account'    => 'account-1',
			'region'     => 'ap-southeast-2',
			'nameFormer' => ServiceBasedNameFormer::class,
		]);

		$this->assertNotNull($queue);

		$this->assertEquals($account, $queue->getAccount());

		$this->assertStringStartsWith('arn:aws:sqs:ap-southeast-2:123456789:', $queue->getArn());
	}

	/** @test */
	public function parses_correctly_for_multiple()
	{
		$accounts = collect([
			$account1 = new Account('account-1', 123456789, 'arn:aws:iam::123456789:role/role-1'),
			$account2 = new Account('account-2', 987654321, 'arn:aws:iam::987654321:role/role-2'),
		]);

		$defaults = [];

		/** @var Queue[] $queues */
		$queues = Queue::parseQueues($accounts, $defaults, 'service-1', [
			'queue-1' => [
				'account'    => 'account-1',
				'region'     => 'ap-southeast-2',
				'nameFormer' => ServiceBasedNameFormer::class,
			],
			'queue-2' => [
				'account'    => 'account-2',
				'region'     => 'us-west-2',
				'nameFormer' => ServiceBasedNameFormer::class,
			],
		]);

		$queue1 = $queues[0];
		$queue2 = $queues[1];

		$this->assertNotNull($queue1);
		$this->assertNotNull($queue2);

		$this->assertEquals($account1, $queue1->getAccount());
		$this->assertEquals($account2, $queue2->getAccount());

		$this->assertStringStartsWith('arn:aws:sqs:ap-southeast-2:123456789:', $queue1->getArn());
		$this->assertStringStartsWith('arn:aws:sqs:us-west-2:987654321:', $queue2->getArn());
	}

	/** @test */
	public function throws_exception_for_missing_account()
	{
		$this->customExpectExceptionMessage('account');

		$accounts = collect([
			new Account('account-1', 123456789, 'arn:aws:iam::123456789:role/role-1'),
		]);

		$defaults = [];

		Queue::parse($accounts, $defaults, 'service-1', 'queue-1', [
			'region' => 'ap-southeast-2',
		]);
	}

	/** @test */
	public function throws_exception_for_missing_region()
	{
		$this->customExpectExceptionMessage('region');

		$accounts = collect([
			new Account('account-1', 123456789, 'arn:aws:iam::123456789:role/role-1'),
		]);

		$defaults = [];

		Queue::parse($accounts, $defaults, 'service-1', 'queue-1', [
			'account' => 'account-1',
		]);
	}

	/** @test */
	public function throws_exception_for_missing_name_former()
	{
		$this->customExpectExceptionMessage('former');

		$accounts = collect([
			new Account('account-1', 123456789, 'arn:aws:iam::123456789:role/role-1'),
		]);

		$defaults = [];

		Queue::parse($accounts, $defaults, 'service-1', 'queue-1', [
			'account' => 'account-2',
			'region'  => 'us-west-2',
		]);
	}
}
