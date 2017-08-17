<?php

namespace Mitchdav\SNS\Facades;

use Illuminate\Support\Facades\Facade;
use Mitchdav\SNS\SNS as BaseSNS;

class SNS extends Facade
{
	protected static function getFacadeAccessor()
	{
		return BaseSNS::class;
	}
}