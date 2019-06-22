<?php

namespace Mitchdav\SNS\Models\SubscriptionMethods;

use Mitchdav\SNS\Contracts\SubscriptionMethod;
use Mitchdav\SNS\Models\Topic;

class HTTP implements SubscriptionMethod
{
	const METHOD = 'http';

	public function subscribe(Topic $topic, $subscriber)
	{

	}

	public function unsubscribe(Topic $topic, $subscriber)
	{

	}

	public function transformForJob($data)
	{

	}
}