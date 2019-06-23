<?php

namespace Mitchdav\SNS\Models\SubscriptionMethods;

use Mitchdav\SNS\Contracts\SubscriptionMethod;
use Mitchdav\SNS\Models\Topic;

class HTTPS implements SubscriptionMethod
{
	const PROTOCOL = 'https';

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