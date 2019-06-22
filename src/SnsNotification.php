<?php

namespace Mitchdav\SNS;

use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class SnsNotification implements ShouldBroadcast
{
	/** @var string */
	protected $service;

	/** @var string[] */
	protected $topics;

	/**
	 * Get the channel or channels to broadcast on.
	 *
	 * @return string
	 */
	public function broadcastOn()
	{
		if (is_string($this->topics)) {
			return $this->service . '.' . $this->topics;
		} else {
			return array_map(function ($topic) {
				return $this->service . '.' . $topic;
			}, $this->topics);
		}
	}

	/**
	 * @return string
	 */
	public function getService()
	{
		return $this->service;
	}

	/**
	 * @param string $service
	 *
	 * @return SnsNotification
	 */
	public function setService($service)
	{
		$this->service = $service;

		return $this;
	}

	/**
	 * @return string[]
	 */
	public function getTopics()
	{
		return $this->topics;
	}

	/**
	 * @param string[] $topics
	 *
	 * @return SnsNotification
	 */
	public function setTopics($topics)
	{
		$this->topics = $topics;

		return $this;
	}
}