<?php

namespace Tests\Feature;

use Mitchdav\SNS\ConfigParser;
use Mitchdav\SNS\Models\Config;
use Tests\TestCase;
use Mitchdav\SNS\SNS;

class SnsTest extends TestCase
{
	/** @test */
	public function new_sns_creates_sns_object_from_config_file()
	{
		$sns = new SNS();

		$this->assertInstanceOf(SNS::class,$sns);

		$this->assertNotInstanceOf(ConfigParser::class,$sns);
	}

	/** @test */
	public function getConfig_returns_instance_of_ConfigParser()
	{
		$sns = new SNS();

		$this->assertInstanceOf(Config::class,$sns->getConfig());

		$this->assertNotInstanceOf(SNS::class,$sns->getConfig());
	}

	/** @test */
	public function new_sns_created_from_config_file_contains_data()
	{
		$sns = new SNS();

		$this->assertNotEmpty($sns->getConfig()->getAccounts());
		$this->assertNotEmpty($sns->getConfig()->getServices());

		$this->assertNotCount(0,$sns->getConfig()->getAccounts());
		$this->assertNotCount(0,$sns->getConfig()->getServices());
	}

	/** @test */
	public function parsing_a_string_to_SNS_constructor_allows_a_different_config_file_to_be_set()
	{
		$snsTest = new SNS('snsTest');

		$sns = new SNS('sns');

		$this->assertNotEmpty($snsTest->getConfig()->getAccounts());
		$this->assertNotEmpty($snsTest->getConfig()->getServices());

		$this->assertNotCount(0,$snsTest->getConfig()->getAccounts());
		$this->assertNotCount(0,$snsTest->getConfig()->getServices());


		$snsTestAccountId = $snsTest->getConfig()->getAccounts()->firstWhere('label','=','account-1')->getId();
		$snsAccountId = $sns->getConfig()->getAccounts()->firstWhere('label','=','account-1')->getId();

		$this->assertNotEquals($snsTestAccountId,$snsAccountId);
	}


}
