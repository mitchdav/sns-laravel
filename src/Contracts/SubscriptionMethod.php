<?php

namespace Mitchdav\SNS\Contracts;

use Mitchdav\SNS\Models\Topic;

interface SubscriptionMethod
{
	public function subscribe(Topic $topic, $subscriber);

	public function unsubscribe(Topic $topic, $subscriber);

	public function transformForJob($data);
}