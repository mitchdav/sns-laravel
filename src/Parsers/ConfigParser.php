<?php

namespace Mitchdav\SNS\Parsers;

use Illuminate\Support\Arr;
use Mitchdav\SNS\Models\Account;
use Mitchdav\SNS\Models\Config;
use Mitchdav\SNS\Models\Queue;
use Mitchdav\SNS\Models\Service;
use Mitchdav\SNS\Models\Subscription;
use Mitchdav\SNS\Models\SubscriptionMethods\SQS;
use Mitchdav\SNS\Models\Topic;

class ConfigParser
{
	/** @var array */
	private static $defaults;

	/** @var \Illuminate\Support\Collection */
	private static $accounts;

	/** @var \Illuminate\Support\Collection */
	private static $services;

	/** @var array */
	private static $serviceMapping;

	public static function parse($config)
	{
		self::$defaults = Arr::get($config, 'defaults', []);
		self::$accounts = collect(Account::parseAccounts(Arr::get($config, 'accounts', [])));
		self::parseServices(Arr::get($config, 'services', []));

		return new Config(self::$accounts, self::$services, collect(self::$serviceMapping));
	}

	private static function parseServices($services)
	{
		self::$services = collect();

		$serviceMapping = [];

		foreach ($services as $label => $attributes) {
			$topics = collect(Topic::parseTopics(self::$accounts, self::$defaults, $label,
				Arr::get($attributes, 'topics', [])));

			$queues = collect(Queue::parseQueues(self::$accounts, self::$defaults, $label,
				Arr::get($attributes, 'queues', [])));

			$endpoints = collect([]);//self::parseEndpoints(Arr::get($attributes, 'endpoints', []));

			self::$services->push(new Service($label, $topics, $queues, $endpoints));

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

		self::$serviceMapping = Arr::dot($serviceMapping);

		foreach ($services as $label => $attributes) {
			/** @var Service $service */
			$service = self::$services->first(function ($service) use ($label) {
				/** @var Service $service */

				return $service->getLabel() === $label;
			});

			$subscriptions = self::parseSubscriptions($label, Arr::get($attributes, 'subscriptions', []));

			$service->setSubscriptions($subscriptions);
		}
	}

	private static function parseSubscriptions($subscribingService, $subscriptions)
	{
		$collection = collect();

		foreach ($subscriptions as $publishingService => $topicLabels) {
			foreach ($topicLabels as $topicLabel => $subscriptionAttributes) {
				$topic = Arr::get(self::$serviceMapping, join('.', [
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
							case SQS::PROTOCOL:
								{
									$queue = Arr::get(self::$serviceMapping, join('.', [
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