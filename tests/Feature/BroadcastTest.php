<?php

namespace Tests\Feature;

use Mitchdav\SNS\Facades\SNS;
use Tests\Notifications\OrderCreated;
use Tests\Notifications\OrderShipped;
use Tests\TestCase;

class BroadcastTest extends TestCase
{
	/** @test */
	public function can_broadcast_to_topic()
	{
		$this->markTestSkipped();

		return;

		/** @var \Mitchdav\SNS\Models\Topic $topic1 */
		$topic1 = SNS::getTopic('orders', 'order-shipped');

		$topic1->create();

		/** @var \Mitchdav\SNS\Models\Topic $topic2 */
		$topic2 = SNS::getTopic('orders', 'order-created@region-1');

		$topic2->create();

		/** @var \Mitchdav\SNS\Models\Topic $topic3 */
		$topic3 = SNS::getTopic('orders', 'order-created@region-2');

		$topic3->create();

		/** @var \Mitchdav\SNS\Models\Queue $queue */
		$queue = SNS::getQueue('orders', 'payment-succeeded@region-1');

		$queue->create();

		$queue->subscribe($topic1);
		$queue->subscribe($topic2);
		$queue->subscribe($topic3);

		SNS::publish(new OrderShipped('123'));
		SNS::publish(new OrderCreated('456'));

		$queue->unsubscribe($topic1);
		$queue->unsubscribe($topic2);
		$queue->unsubscribe($topic3);

		// TODO: Add command that generates supervisor config flags for queue:work, including all queues and tries settings for dead letter queues

		$this->assertTrue(TRUE);
	}
}
