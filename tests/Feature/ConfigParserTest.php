<?php

namespace Tests\Feature;

use Mitchdav\SNS\ConfigParser;
use Tests\TestCase;

class ConfigParserTest extends TestCase
{
	/** @test */
	public function new_sns_creates_sns_object_from_config_file()
	{
		$config = ConfigParser::parse(config('sns'));

		// TODO:

		$this->assertTrue(TRUE);
	}
}
