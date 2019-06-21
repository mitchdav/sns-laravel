<?php

namespace Mitchdav\SNS\Models\SubscriptionMethods;

use Mitchdav\SNS\Contracts\SubscriptionMethod;
use Mitchdav\SNS\Models\Topic;

class SQS implements SubscriptionMethod
{
	const METHOD = 'sqs';

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