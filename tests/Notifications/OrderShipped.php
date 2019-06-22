<?php

namespace Tests\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Queue\ShouldQueue;
use Mitchdav\SNS\SnsNotification;

class OrderShipped extends SnsNotification implements ShouldQueue, ShouldBroadcast
{
	use Queueable;

	protected $service = 'orders';

	protected $topics  = 'order-shipped';

	/**
	 * @var string $orderId
	 */
	private $orderId;

	/**
	 * Create a new notification instance.
	 */
	public function __construct($orderId)
	{
		$this->orderId = $orderId;
	}

	/**
	 * Get the broadcastable representation of the notification.
	 *
	 * @return array
	 */
	public function broadcastWith()
	{
		return [
			'id' => $this->orderId,
		];
	}
}