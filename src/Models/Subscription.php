<?php

namespace Mitchdav\SNS\Models;

class Subscription
{
	/** @var Topic */
	private $topic;

	/** @var \Illuminate\Support\Collection */
	private $sqsSubscribers;

	/** @var \Illuminate\Support\Collection */
	private $httpSubscribers;

	/** @var \Illuminate\Support\Collection */
	private $httpsSubscribers;

	/** @var \Illuminate\Support\Collection */
	private $handlers;

	/**
	 * Subscription constructor.
	 *
	 * @param \Mitchdav\SNS\Models\Topic $topic
	 */
	public function __construct(\Mitchdav\SNS\Models\Topic $topic)
	{
		$this->topic = $topic;

		$this->sqsSubscribers   = collect();
		$this->httpSubscribers  = collect();
		$this->httpsSubscribers = collect();
		$this->handlers         = collect();
	}

	/**
	 * @return \Mitchdav\SNS\Models\Topic
	 */
	public function getTopic()
	{
		return $this->topic;
	}

	/**
	 * @param \Mitchdav\SNS\Models\Topic $topic
	 *
	 * @return Subscription
	 */
	public function setTopic($topic)
	{
		$this->topic = $topic;

		return $this;
	}

	/**
	 * @return \Illuminate\Support\Collection
	 */
	public function getSqsSubscribers()
	{
		return $this->sqsSubscribers;
	}

	/**
	 * @param \Illuminate\Support\Collection $sqsSubscribers
	 *
	 * @return Subscription
	 */
	public function setSqsSubscribers($sqsSubscribers)
	{
		$this->sqsSubscribers = $sqsSubscribers;

		return $this;
	}

	/**
	 * @return \Illuminate\Support\Collection
	 */
	public function getHttpSubscribers()
	{
		return $this->httpSubscribers;
	}

	/**
	 * @param \Illuminate\Support\Collection $httpSubscribers
	 *
	 * @return Subscription
	 */
	public function setHttpSubscribers($httpSubscribers)
	{
		$this->httpSubscribers = $httpSubscribers;

		return $this;
	}

	/**
	 * @return \Illuminate\Support\Collection
	 */
	public function getHttpsSubscribers()
	{
		return $this->httpsSubscribers;
	}

	/**
	 * @param \Illuminate\Support\Collection $httpsSubscribers
	 *
	 * @return Subscription
	 */
	public function setHttpsSubscribers($httpsSubscribers)
	{
		$this->httpsSubscribers = $httpsSubscribers;

		return $this;
	}

	/**
	 * @return \Illuminate\Support\Collection
	 */
	public function getHandlers()
	{
		return $this->handlers;
	}

	/**
	 * @param \Illuminate\Support\Collection $handlers
	 *
	 * @return Subscription
	 */
	public function setHandlers($handlers)
	{
		$this->handlers = $handlers;

		return $this;
	}

	public function handle($data)
	{
		$this->handlers->each(function ($handler) use ($data) {
			/** @var \Illuminate\Foundation\Bus\PendingChain $handler */

			$handler::dispatch($data);
		});
	}
}