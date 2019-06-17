<?php

namespace Mitchdav\SNS\Models;

use Aws\Sqs\Exception\SqsException;

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
	 * @param string                       $arn
	 * @param string                       $url
	 * @param array                        $attributes
	 */
	public function __construct($label, $name, Account $account, $region, $arn, $url, $attributes)
	{
		$this->label      = $label;
		$this->name       = $name;
		$this->account    = $account;
		$this->region     = $region;
		$this->arn        = $arn;
		$this->url        = $url;
		$this->attributes = $attributes;
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

	/**
	 * @return \Aws\Sqs\SqsClient
	 */
	private function sqsClient()
	{
		return $this->account->sdk()
		                     ->createSqs([
			                     'region' => $this->region,
		                     ]);
	}
}