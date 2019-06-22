<?php

namespace Mitchdav\SNS\Models;

use Aws\Sqs\Exception\SqsException;
use Mitchdav\SNS\SNS;

class Queue
{
	/**
	 * @var string
	 */
	public $label;

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
	 * @var array
	 */
	private $attributes;

	/**
	 * Queue constructor.
	 *
	 * @param string                       $label
	 * @param string                       $name
	 * @param \Mitchdav\SNS\Models\Account $account
	 * @param string                       $region
	 * @param array                        $attributes
	 */
	public function __construct($label, $name, Account $account, $region, $attributes)
	{
		$this->label      = $label;
		$this->name       = $name;
		$this->account    = $account;
		$this->region     = $region;
		$this->attributes = $attributes;

		$this->arn = $this->generateArn();
		$this->url = $this->generateUrl();
	}

	public function create()
	{
		try {
			$this->sqsClient()
			     ->createQueue([
				     'QueueName'  => $this->name,
				     'Attributes' => $this->attributes,
			     ]);
		} catch (SqsException $exception) {
			if ($exception->getAwsErrorType() === 'QueueAlreadyExists') {
				// https://docs.aws.amazon.com/AWSSimpleQueueService/latest/APIReference/API_CreateQueue.html#API_CreateQueue_Errors
				// The queue already exists, and the attributes differ from those of the existing queue

				$result = $this->sqsClient()
				               ->getQueueAttributes([
					               'QueueUrl'       => $this->url,
					               'AttributeNames' => [
						               'All',
					               ],
				               ]);

				$attributes = array_merge_recursive($result->get('Attributes'), $this->attributes);

				$this->sqsClient()
				     ->setQueueAttributes([
					     'QueueUrl'  => $this->url,
					     'Attribute' => $attributes,
				     ]);
			} else {
				throw $exception;
			}
		}
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
	 * @return array
	 */
	public function getAttributes()
	{
		return $this->attributes;
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