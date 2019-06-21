<?php

namespace Tests\Unit;

use Mitchdav\SNS\ConfigParser;
use Mitchdav\SNS\SNS;
use Tests\TestCase;

class ConfigParserTest extends TestCase
{
	/** @test */
	public function parseAccounts_returns_data_matching_test_config_file()
	{
		$sns = new SNS('snsTest');

		$account = $sns->getConfig()->getAccounts()->firstWhere('label','=','account-1');
		$this->assertEquals('7777777777', $account->getId());
	}
}
