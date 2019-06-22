<?php

namespace Mitchdav\SNS\Models;

use Illuminate\Support\Collection;

class Service
{
	/**
	 * @var string
	 */
	private $label;

	/**
	 * @var Collection $topics
	 */
	private $topics;

	/**
	 * @var Collection $queues
	 */
	private $queues;

	/**
	 * @var Collection $endpoints
	 */
	private $endpoints;

	/**
	 * @var Collection $subscriptions
	 */
	private $subscriptions;

	/**
	 * Account constructor.
	 *
	 * @param string     $label
	 * @param Collection $topics
	 * @param Collection $queues
	 * @param Collection $endpoints
	 */
	public function __construct($label, $topics, $queues, $endpoints)
	{
		$this->label     = $label;
		$this->topics    = $topics;
		$this->queues    = $queues;
		$this->endpoints = $endpoints;

		$this->subscriptions = collect();
	}

	/**
	 * @param \Illuminate\Support\Collection $subscriptions
	 *
	 * @return Service
	 */
	public function setSubscriptions($subscriptions)
	{
		$this->subscriptions = $subscriptions;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getLabel()
	{
		return $this->label;
	}
}