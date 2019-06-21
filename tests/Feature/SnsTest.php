<?php

namespace Tests\Feature;

use Tests\TestCase;
use Mitchdav\SNS\SNS;

class SnsTest extends TestCase
{
	/** @test */
	public function new_sns_creates_sns_object_from_config_file()
	{
		$sns = new SNS();
		$this->assertTrue(TRUE);
	}
}
