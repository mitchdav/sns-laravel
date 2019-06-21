<?php

namespace Mitchdav\SNS;

use Illuminate\Support\Arr;
use Mitchdav\SNS\Contracts\NameFormer;
use Mitchdav\SNS\Models\Account;
use Mitchdav\SNS\Models\Config;
use Mitchdav\SNS\Models\Queue;
use Mitchdav\SNS\Models\Service;
use Mitchdav\SNS\Models\Subscription;
use Mitchdav\SNS\Models\SubscriptionMethods\SQS;
use Mitchdav\SNS\Models\Topic;

class ConfigParser
{
	const ARN_FORMER_DEFAULT      = '$DEFAULT$';
	const ARN_PREFIX_SERVICE_NAME = '$SERVICE_NAME$';

	public static function parse($config)
	{
		$defaults = Arr::get($config, 'defaults', []);

		$accounts = self::parseAccounts(Arr::get($config, 'accounts', []));
		$services = self::parseServices(Arr::get($config, 'services', []), $accounts, $defaults);

		return new Config($accounts, $services);
	}

	private static function parseAccounts($config)
	{
		$accounts = collect();

		foreach ($config as $label => $attributes) {
			$accounts->push(new Account($label, $attributes['id'], $attributes['role']));
		}

		return $accounts;
	}

	private static function parseServices($services, $accounts, $defaults)
	{
		$collection = collect();

		$serviceMapping = [];

		foreach ($services as $label => $attributes) {
			$topics    = self::parseTopics($label, $accounts, Arr::get($attributes, 'topics', []), $defaults);
			$queues    = self::parseQueues($label, $accounts, Arr::get($attributes, 'queues', []), $defaults);
			$endpoints = self::parseEndpoints(Arr::get($attributes, 'endpoints', []), $defaults);

			$collection->push(new Service($label, $topics, $queues, $endpoints));

			$topicsMapping = [];

			$topics->each(function ($topic) use (&$topicsMapping) {
				/** @var Topic $topic */

				$topicsMapping[$topic->getLabel()] = $topic;
			});

			$queuesMapping = [];

			$queues->each(function ($queue) use (&$queuesMapping) {
				/** @var Queue $queue */

				$queuesMapping[$queue->getLabel()] = $queue;
			});

			$endpointsMapping = [];

			// TODO: Implement Endpoint model

			//			$endpoints->each(function ($endpoint) use (&$endpointsMapping) {
			//				/** @var Endpoint $endpoint */
			//
			//				$endpointsMapping[$endpoint->getLabel()] = $endpoint;
			//			});

			$serviceMapping[$label] = [
				'topics'    => $topicsMapping,
				'queues'    => $queuesMapping,
				'endpoints' => $endpointsMapping,
			];
		}

		$serviceMapping = Arr::dot($serviceMapping);

		foreach ($services as $label => $attributes) {
			/** @var Service $service */
			$service = $collection->firstWhere('label', $label);

			$subscriptions = self::parseSubscriptions($label, Arr::get($attributes, 'subscriptions', []),
				$serviceMapping);

			$service->setSubscriptions($subscriptions);
		}

		return $collection;
	}


	/**
	 * @param string                         $service
	 * @param \Illuminate\Support\Collection $accounts
	 * @param array                          $topics
	 * @param array                          $defaults
	 *
	 * @return \Illuminate\Support\Collection
	 * @throws \Exception
	 */
	private static function parseTopics($service, $accounts, $topics, $defaults)
	{
		$collection = collect();

		$defaults = array_replace_recursive(Arr::get($defaults, 'all', []), Arr::get($defaults, 'topic', []));

		foreach ($topics as $label => $attributes) {
			if (is_string($attributes)) {
				// Topic config just has the topic label

				$label      = $attributes;
				$attributes = [];
			}

			$mergedAttributes = array_replace_recursive($defaults, $attributes);

			if (!isset($mergedAttributes['account'], $mergedAttributes['region'])) {
				throw new \Exception('You must provide the account and region for the "' . $label . '" topic.');
			}

			$accountName = $mergedAttributes['account'];
			$region      = $mergedAttributes['region'];

			/** @var Account $account */
			$account = $accounts->firstWhere('label', $accountName);

			if (!isset($account)) {
				throw new \Exception('The account "' . $accountName . '" was not found for the "' . $label . '" topic.');
			}

			/** @var NameFormer $nameFormer */
			$nameFormer = app(NameFormer::class);

			$name = $nameFormer->formName($service, $label, $mergedAttributes);

			$collection->push(new Topic($label, $name, $account, $region));
		}

		return $collection;
	}

	private static function parseQueues($service, $accounts, $queues, $defaults)
	{
		$collection = collect();

		$defaults = array_replace_recursive(Arr::get($defaults, 'all', []), Arr::get($defaults, 'queue', []));

		foreach ($queues as $label => $attributes) {
			if (is_string($attributes)) {
				// Queue config just has the topic label

				$label      = $attributes;
				$attributes = [];
			}

			$mergedAttributes = array_replace_recursive($defaults, $attributes);

			if (!isset($mergedAttributes['account'], $mergedAttributes['region'])) {
				throw new \Exception('You must provide the account and region for the "' . $label . '" queue.');
			}

			$accountName     = $mergedAttributes['account'];
			$region          = $mergedAttributes['region'];
			$queueAttributes = Arr::get($mergedAttributes, 'attributes', []);

			/** @var Account $account */
			$account = $accounts->firstWhere('label', $accountName);

			if (!isset($account)) {
				throw new \Exception('The account "' . $accountName . '" was not found for the "' . $label . '" queue.');
			}

			/** @var NameFormer $nameFormer */
			$nameFormer = app(NameFormer::class);

			$name = $nameFormer->formName($service, $label, $mergedAttributes);

			$collection->push(new Queue($label, $name, $account, $region, $queueAttributes));
		}

		return $collection;
	}

	private static function parseEndpoints($config, $defaults)
	{
		$endpoints = collect();

		foreach ($config as $label => $attributes) {


			//			$endpoints->push(new Endpoint());
		}

		return $endpoints;
	}

	private static function parseSubscriptions($subscribingService, $subscriptions, $serviceMapping)
	{
		$collection = collect();

		foreach ($subscriptions as $publishingService => $topicLabels) {
			foreach ($topicLabels as $topicLabel => $subscriptionAttributes) {
				$topic = Arr::get($serviceMapping, join('.', [
					$publishingService,
					'topics',
					$topicLabel,
				]));

				if (!$topic) {
					throw new \Exception('Topic "' . $topicLabel . '" not found in subscriptions for ' . join('.', [
							$subscribingService,
							$publishingService,
							$topicLabel,
						]) . '.');
				}

				$subscription = new Subscription($topic);

				$protocols = Arr::get($subscriptionAttributes, 'protocols', []);

				foreach ($protocols as $protocol => $resources) {
					foreach ($resources as $resource) {
						switch ($protocol) {
							case SQS::METHOD:
								{
									$queue = Arr::get($serviceMapping, join('.', [
										$subscribingService,
										'queues',
										$resource,
									]));

									if (!$queue) {
										throw new \Exception('Queue "' . $resource . '" not found in subscriptions for ' . join('.',
												[
													$subscribingService,
													$publishingService,
													$topicLabel,
													'sqs',
													$resource,
												]) . '.');
									}

									break;
								}

							case 'http':
							case 'https':
								{


									break;
								}

							default:
								{
									throw new \Exception('Unsupported protocol "' . $protocol . '" found in subscriptions for ' . join('.',
											[
												$subscribingService,
												$publishingService,
												$topicLabel,
											]) . '.');
								}
						}
					}
				}

				$handlers = Arr::get($subscriptionAttributes, 'handlers', []);

				$subscription->setHandlers(collect($handlers));
			}
		}

		return $collection;
	}
}