<?php

namespace Tests\Feature;

use Mitchdav\SNS\ConfigParser;
use Tests\TestCase;
use Mitchdav\SNS\SNS;

class ConfigParserTest extends TestCase
{
	/** @test */
	public function new_sns_creates_sns_object_from_config_file()
	{
		$config = ConfigParser::parse(config('sns'));

		// TODO:

		$this->assertTrue(TRUE);
	}


	/** @test */
	public function parse_services_creates_a_list_of_services()
	{
		$configData = ConfigParser::parse(config('snsTest'));
//		$config = ConfigParser::parseServices($configData);

		// TODO:

		$this->assertTrue(TRUE);
	}
}
