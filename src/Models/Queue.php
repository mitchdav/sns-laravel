<?php

namespace Mitchdav\SNS\Models;

use Illuminate\Support\Arr;
use Mitchdav\SNS\Contracts\NameFormer;
use Mitchdav\SNS\SNS;

class Queue
{
	/**
	 * @var string
	 */
	private $label;

	/**
	 * @var string
	 */
	private $name;

	/**
	 * @var \Mitchdav\SNS\Models\Account
	 */
	private $account;

	/**
	 * @var string
	 */
	private $region;

	/**
	 * @var string
	 */
	private $arn;

	/**
	 * @var string
	 */
	private $url;

	/**
	 * Queue constructor.
	 *
	 * @param string                       $label
	 * @param string                       $name
	 * @param \Mitchdav\SNS\Models\Account $account
	 * @param string                       $region
	 */
	public function __construct($label, $name, Account $account, $region)
	{
		$this->label   = $label;
		$this->name    = $name;
		$this->account = $account;
		$this->region  = $region;

		$this->arn = $this->generateArn();
		$this->url = $this->generateUrl();
	}

	public static function parseQueues($accounts, $defaults, $service, $config)
	{
		$queues = [];

		foreach ($config as $label => $attributes) {
			if (is_string($attributes)) {
				// Queue config just has the queue label

				$label      = $attributes;
				$attributes = [];
			}

			$queues[] = self::parse($accounts, $defaults, $service, $label, $attributes);
		}

		return $queues;
	}

	public static function parse($accounts, $defaults, $service, $label, $attributes)
	{
		$defaults = array_replace_recursive(Arr::get($defaults, 'all', []), Arr::get($defaults, 'queue', []));

		$mergedAttributes = array_replace_recursive($defaults, $attributes);

		if (!isset($mergedAttributes['account'])) {
			throw new \Exception('You must provide the account for the "' . $label . '" queue.');
		}

		if (!isset($mergedAttributes['region'])) {
			throw new \Exception('You must provide the region for the "' . $label . '" queue.');
		}

		if (!isset($mergedAttributes['nameFormer'])) {
			throw new \Exception('You must provide the name former for the "' . $label . '" queue.');
		}

		$accountName = $mergedAttributes['account'];
		$region      = $mergedAttributes['region'];
		$nameFormer  = app($mergedAttributes['nameFormer']);

		if (!$nameFormer instanceof NameFormer) {
			throw new \Exception('The name former for the "' . $label . '" queue must implement ' . NameFormer::class . '.');
		}

		/** @var Account $account */
		$account = $accounts->first(function ($account) use ($accountName) {
			/** @var Account $account */

			return $account->getLabel() === $accountName;
		});

		if (!isset($account)) {
			throw new \Exception('The account "' . $accountName . '" was not found for the "' . $label . '" queue.');
		}

		$name = $nameFormer->formName($service, $label, $mergedAttributes);

		return new Queue($label, $name, $account, $region);
	}

	public function create()
	{
		$this->sqsClient()
		     ->createQueue([
			     'QueueName' => $this->name,
		     ]);
	}

	public function delete()
	{
		$this->sqsClient()
		     ->deleteQueue([
			     'QueueUrl' => $this->url,
		     ]);
	}

	public function subscribe(Topic $topic)
	{
		/** @var SNS $sns */
		$sns = app(SNS::class);

		/** @var \Mitchdav\SNS\Contracts\SubscriptionMethod $subscriptionMethod */
		$subscriptionMethod = $sns->driver('sqs');

		$subscriptionMethod->subscribe($topic, $this);
	}

	public function unsubscribe(Topic $topic)
	{
		/** @var SNS $sns */
		$sns = app(SNS::class);

		/** @var \Mitchdav\SNS\Contracts\SubscriptionMethod $subscriptionMethod */
		$subscriptionMethod = $sns->driver('sqs');

		$subscriptionMethod->unsubscribe($topic, $this);
	}

	/**
	 * @return string
	 */
	public function getLabel()
	{
		return $this->label;
	}

	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * @return \Mitchdav\SNS\Models\Account
	 */
	public function getAccount()
	{
		return $this->account;
	}

	/**
	 * @return string
	 */
	public function getRegion()
	{
		return $this->region;
	}

	/**
	 * @return string
	 */
	public function getArn()
	{
		return $this->arn;
	}

	/**
	 * @return string
	 */
	public function getUrl()
	{
		return $this->url;
	}

	/**
	 * @return \Aws\Sqs\SqsClient
	 */
	public function sqsClient()
	{
		return $this->account->sdk()
		                     ->createSqs([
			                     'region' => $this->region,
		                     ]);
	}

	private function generateArn()
	{
		return join(':', [
			'arn',
			'aws',
			'sqs',
			$this->region,
			$this->account->getId(),
			$this->name,
		]);
	}

	private function generateUrl()
	{
		return 'https://sqs.' . $this->region . '.amazonaws.com/' . $this->account->getId() . '/' . $this->name;
	}
}