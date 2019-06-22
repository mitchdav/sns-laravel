<?php

namespace Mitchdav\SNS\Models\SubscriptionMethods;

use Aws\Exception\AwsException;
use Aws\Sqs\SqsClient;
use Illuminate\Foundation\Testing\Assert as PHPUnit;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Mitchdav\SNS\Contracts\SubscriptionMethod;
use Mitchdav\SNS\Models\Queue;
use Mitchdav\SNS\Models\Topic;

class SQS implements SubscriptionMethod
{
	const PROTOCOL = 'sqs';

	public function subscribe(Topic $topic, $subscriber)
	{
		/** @var \Mitchdav\SNS\Models\Queue $queue */
		$queue = $subscriber;

		$snsClient = $topic->snsClient();
		$sqsClient = $queue->sqsClient();

		$result = $sqsClient->getQueueAttributes([
			'QueueUrl'       => $queue->getUrl(),
			'AttributeNames' => [
				'Policy',
			],
		]);

		$attributes = $result->get('Attributes');

		$policy = $attributes['Policy'];

		$statementId = 'Sid' . round(microtime(TRUE) * 1000);

		$statement = [
			'Effect'    => 'Allow',
			'Principal' => [
				'AWS' => '*',
			],
			'Action'    => 'SQS:SendMessage',
			'Resource'  => $queue->getArn(),
			'Condition' => [
				'ArnEquals' => [
					'aws:SourceArn' => $topic->getArn(),
				],
			],
		];

		$statementWithId = array_merge_recursive($statement, [
			'Sid' => $statementId,
		]);

		if (empty($policy)) {
			$policy = [
				'Version'   => '2012-10-17',
				'Id'        => Str::uuid(),
				'Statement' => [
					$statementWithId,
				],
			];

			$sqsClient->setQueueAttributes([
				'QueueUrl'   => $queue->getUrl(),
				'Attributes' => [
					'Policy' => json_encode($policy),
				],
			]);
		} else {
			$policy = json_decode($policy, JSON_OBJECT_AS_ARRAY);

			$candidates = $policy['Statement'];

			$foundInPolicy = FALSE;

			foreach ($candidates as $candidate) {
				$foundInCandidate = $this->containsJson($statement, $candidate);

				if ($foundInCandidate) {
					$foundInPolicy = TRUE;

					break;
				}
			}

			if (!$foundInPolicy) {
				$policy['Statement'][] = $statementWithId;

				$sqsClient->setQueueAttributes([
					'QueueUrl'   => $queue->getUrl(),
					'Attributes' => [
						'Policy' => json_encode($policy),
					],
				]);
			}
		}

		$snsClient->subscribe([
			'Protocol'              => self::PROTOCOL,
			'Endpoint'              => $queue->getArn(),
			'ReturnSubscriptionArn' => TRUE,
			'TopicArn'              => $topic->getArn(),
		]);

		$subscriptionArn = $this->getSubscriptionArn($topic, $queue);

		if ($subscriptionArn) {
			$snsClient->setSubscriptionAttributes([
				'SubscriptionArn' => $subscriptionArn,
				'AttributeName'   => 'RawMessageDelivery',
				'AttributeValue'  => 'true',
			]);
		} else {
			// TODO: Log (shouldn't be possible)
		}
	}

	public function unsubscribe(Topic $topic, $subscriber)
	{
		/** @var \Mitchdav\SNS\Models\Queue $queue */
		$queue = $subscriber;

		$snsClient = $topic->snsClient();
		$sqsClient = $queue->sqsClient();

		$subscriptionArn = $this->getSubscriptionArn($topic, $queue);

		if ($subscriptionArn) {
			$snsClient->unsubscribe([
				'SubscriptionArn' => $subscriptionArn,
			]);
		}

		$result = $sqsClient->getQueueAttributes([
			'QueueUrl'       => $queue->getUrl(),
			'AttributeNames' => [
				'Policy',
			],
		]);

		$attributes = $result->get('Attributes');

		$policy = $attributes['Policy'];

		$statement = [
			'Effect'    => 'Allow',
			'Principal' => [
				'AWS' => '*',
			],
			'Action'    => 'SQS:SendMessage',
			'Resource'  => $queue->getArn(),
			'Condition' => [
				'ArnEquals' => [
					'aws:SourceArn' => $topic->getArn(),
				],
			],
		];

		if (!empty($policy)) {
			$policy = json_decode($policy, JSON_OBJECT_AS_ARRAY);

			$candidates = $policy['Statement'];
			$statements = [];

			//			dump($candidates);

			$foundInPolicy = FALSE;

			foreach ($candidates as $candidate) {
				$foundInCandidate = $this->containsJson($statement, $candidate);

				if ($foundInCandidate) {
					$foundInPolicy = TRUE;
				} else {
					$statements[] = $candidate;
				}
			}

			if ($foundInPolicy) {
				$policy['Statement'] = $statements;

				//				dump($policy);

				$sqsClient->setQueueAttributes([
					'QueueUrl'   => $queue->getUrl(),
					'Attributes' => [
						'Policy' => ((count($statements) > 0) ? (json_encode($policy)) : (NULL)),
					],
				]);
			}
		}
	}

	public function transformForJob($data)
	{

	}

	private function containsJson($needle, $haystack)
	{
		$diff = $this->array_diff_recursive($needle, $haystack);

		return (count(Arr::flatten($diff)) === 0);
	}

	/**
	 * @param $arr1
	 * @param $arr2
	 *
	 * @return array
	 *
	 * @see https://stackoverflow.com/a/29526501/7503569
	 */
	private function array_diff_recursive($arr1, $arr2)
	{
		$outputDiff = [];

		foreach ($arr1 as $key => $value) {
			//if the key exists in the second array, recursively call this function
			//if it is an array, otherwise check if the value is in arr2
			if (array_key_exists($key, $arr2)) {
				if (is_array($value)) {
					$recursiveDiff = $this->array_diff_recursive($value, $arr2[$key]);

					if (count($recursiveDiff)) {
						$outputDiff[$key] = $recursiveDiff;
					}
				} else if (!in_array($value, $arr2)) {
					$outputDiff[$key] = $value;
				}
			}
			//if the key is not in the second array, check if the value is in
			//the second array (this is a quirk of how array_diff works)
			else if (!in_array($value, $arr2)) {
				$outputDiff[$key] = $value;
			}
		}

		return $outputDiff;
	}

	private function getSubscriptionArn(Topic $topic, Queue $queue)
	{
		$snsClient = $topic->snsClient();

		$paginatorArguments = [
			'TopicArn' => $topic->getArn(),
		];

		foreach ($snsClient->getPaginator('ListSubscriptionsByTopic', $paginatorArguments) as $page) {
			foreach ($page->get('Subscriptions') as $subscription) {
				if ($subscription['Protocol'] === self::PROTOCOL && $subscription['Endpoint'] === $queue->getArn()) {
					return $subscription['SubscriptionArn'];
				}
			}
		}

		return NULL;
	}
}