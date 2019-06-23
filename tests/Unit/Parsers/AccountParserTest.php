<?php

namespace Tests\Unit\Parsers;

use Mitchdav\SNS\Parsers\ConfigParser;
use Tests\TestCase;

class AccountParserTest extends TestCase
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
				'account-2' => [
					'id'   => 987654321,
					'role' => 'role-2',
				],
			],
		]);

		$account1 = $config->getAccount('account-1');
		$account2 = $config->getAccount('account-2');

		$this->assertNotNull($account1);
		$this->assertNotNull($account2);

		$this->assertEquals('account-1', $account1->getLabel());
		$this->assertEquals('account-2', $account2->getLabel());

		$this->assertEquals(123456789, $account1->getId());
		$this->assertEquals(987654321, $account2->getId());

		$this->assertEquals('arn:aws:iam::123456789:role/role-1', $account1->getRole());
		$this->assertEquals('arn:aws:iam::987654321:role/role-2', $account2->getRole());
	}

	/** @test */
	public function parses_correctly_for_missing_role()
	{
		$config = ConfigParser::parse([
			'accounts' => [
				'account-1' => [
					'id' => 123456789,
				],
			],
		]);

		$account1 = $config->getAccount('account-1');

		$this->assertNotNull($account1);

		$this->assertNull($account1->getRole());
	}

	/** @test */
	public function parses_correctly_for_shorthand_role()
	{
		$config = ConfigParser::parse([
			'accounts' => [
				'account-1' => [
					'id'   => 123456789,
					'role' => 'role-1',
				],
			],
		]);

		$account1 = $config->getAccount('account-1');

		$this->assertNotNull($account1);

		$this->assertEquals('arn:aws:iam::123456789:role/role-1', $account1->getRole());
	}

	/** @test */
	public function throws_exception_for_missing_id()
	{
		$this->customExpectExceptionMessage('account id');

		ConfigParser::parse([
			'accounts' => [
				'account-1' => [
					'role' => 'role-1',
				],
			],
		]);
	}

	/** @test */
	public function throws_exception_for_non_numeric_id()
	{
		$this->customExpectExceptionMessage('account id');
		$this->customExpectExceptionMessage('numeric');

		ConfigParser::parse([
			'accounts' => [
				'account-1' => [
					'id' => 'abc123',
				],
			],
		]);
	}

	/** @test */
	public function throws_exception_for_non_string_role()
	{
		$this->customExpectExceptionMessage('account role');
		$this->customExpectExceptionMessage('string');

		ConfigParser::parse([
			'accounts' => [
				'account-1' => [
					'id'   => 123456789,
					'role' => 123,
				],
			],
		]);
	}

	/** @test */
	public function throws_exception_for_non_matching_role()
	{
		$this->customExpectExceptionMessage('account role');
		$this->customExpectExceptionMessage('start with');

		ConfigParser::parse([
			'accounts' => [
				'account-1' => [
					'id'   => 123456789,
					'role' => 'arn:aws:iam::987654321:role/role-2',
				],
			],
		]);
	}
}
