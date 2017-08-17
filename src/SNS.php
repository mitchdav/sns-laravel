<?php

namespace Mitchdav\SNS;

use Aws\Sns\Message;
use Aws\Sns\MessageValidator;
use Aws\Sns\SnsClient;
use Log;
use Route;

/**
 * Class SNS
 * @package Mitchdav\SNS
 */
class SNS
{
	/**
	 * @var \Aws\Sns\SnsClient $client
	 */
	private $client;

	/**
	 * @var string $url
	 */
	private $url;

	/**
	 * @var array $defaults
	 */
	private $defaults;

	/**
	 * @var array $topics
	 */
	private $topics;

	/**
	 * @var array $subscriptions
	 */
	private $subscriptions;

	/**
	 * @var array $routes
	 */
	private $routes;

	/**
	 * SNS constructor.
	 *
	 * @param \Aws\Sns\SnsClient $client
	 */
	public function __construct(SnsClient $client)
	{
		$this->client = $client;

		$config = config('sns');

		$this->url      = $config['url'];
		$this->defaults = $config['defaults'];

		$this->topics        = $this->parseTopics($config['topics']);
		$this->subscriptions = $this->parseSubscriptions($config['subscriptions']);
	}

	/**
	 * @param array $topics
	 *
	 * @return array
	 */
	private function parseTopics($topics)
	{
		$output = [];

		foreach ($topics as $key => $value) {
			if (is_int($key)) {
				$key = $value;

				$value = [];
			}

			if (!is_string($value)) {
				$value = array_merge(
					$this->defaults['topics'],
					[
						'topic' => $key,
					],
					$value
				);

				$value = $value['formARN']($value['region'], $value['id'], $value['prefix'], $value['joiner'], $value['topic']);
			}

			$name = $this->getNameFromARN($value);

			$value = [
				'arn'  => $value,
				'name' => $name,
			];

			$output[$key] = $value;
		}

		return $output;
	}

	/**
	 * @param string $arn
	 *
	 * @return string
	 */
	private function getNameFromARN($arn)
	{
		$parts = explode(':', $arn);

		$name = end($parts);

		return $name;
	}

	/**
	 * @param array $subscriptions
	 *
	 * @return array
	 */
	private function parseSubscriptions($subscriptions)
	{
		$output = [];

		foreach ($subscriptions as $key => $value) {
			$value = array_merge(
				$this->defaults['subscriptions'],
				$value
			);

			$output[$key] = $value;
		}

		return $output;
	}

	/**
	 * @param $topic
	 *
	 * @return $this
	 */
	public function createTopic($topic)
	{
		if (!array_key_exists($topic, $this->getTopics())) {
			throw new \InvalidArgumentException('The topic must exist in the topics configuration.');
		}

		$result = $this->getClient()->createTopic([
			'Name' => $this->getTopic($topic)['name'],
		]);

		$arn = $result->get('TopicArn');

		$this->topics[$topic]['arn'] = $arn;

		return $this;
	}

	/**
	 * @return array
	 */
	public function getTopics()
	{
		return $this->topics;
	}

	/**
	 * @param string $topic
	 *
	 * @return array
	 */
	public function getTopic($topic)
	{
		if (!array_key_exists($topic, $this->getTopics())) {
			throw new \InvalidArgumentException('The topic must exist in the topics configuration.');
		}

		return $this->getTopics()[$topic];
	}

	/**
	 * @return \Aws\Sns\SnsClient
	 */
	public function getClient()
	{
		return $this->client;
	}

	/**
	 * @param $topic
	 *
	 * @return $this
	 */
	public function deleteTopic($topic)
	{
		if (!array_key_exists($topic, $this->getTopics())) {
			throw new \InvalidArgumentException('The topic must exist in the topics configuration.');
		}

		$this->getClient()->deleteTopic([
			'TopicArn' => $this->getTopic($topic)['arn'],
		]);

		unset($this->topics[$topic]);

		return $this;
	}

	/**
	 * @param $topic
	 *
	 * @return $this
	 */
	public function subscribe($topic)
	{
		if (!array_key_exists($topic, $this->getTopics())) {
			throw new \InvalidArgumentException('The topic must exist in the topics configuration.');
		}

		if (!array_key_exists($topic, $this->getSubscriptions())) {
			throw new \InvalidArgumentException('The topic must exist in the subscriptions configuration.');
		}

		$arn      = $this->getTopics()[$topic]['arn'];
		$protocol = substr($this->url, 0, strpos($this->url, ':'));
		$endpoint = $this->url . $this->getSubscription($topic)['route'];

		$this->getClient()->subscribe([
			'TopicArn' => $arn,
			'Protocol' => $protocol,
			'Endpoint' => $endpoint,
		]);

		return $this;
	}

	/**
	 * @return array
	 */
	public function getSubscriptions()
	{
		return $this->subscriptions;
	}

	/**
	 * @param string $topic
	 *
	 * @return array
	 */
	public function getSubscription($topic)
	{
		if (!array_key_exists($topic, $this->getSubscriptions())) {
			throw new \InvalidArgumentException('The topic must exist in the subscriptions configuration.');
		}

		return $this->getSubscriptions()[$topic];
	}

	/**
	 * @param string $topic
	 *
	 * @return $this
	 */
	public function unsubscribe($topic)
	{
		if (!array_key_exists($topic, $this->getTopics())) {
			throw new \InvalidArgumentException('The topic must exist in the topics configuration.');
		}

		if (!array_key_exists($topic, $this->getSubscriptions())) {
			throw new \InvalidArgumentException('The topic must exist in the subscriptions configuration.');
		}

		$arn      = $this->getTopics()[$topic]['arn'];
		$protocol = substr($this->url, 0, strpos($this->url, ':'));
		$endpoint = $this->url . $this->getSubscription($topic)['route'];

		$nextToken = NULL;

		do {
			$args = [
				'TopicArn' => $arn,
			];

			if ($nextToken != NULL) {
				$args['NextToken'] = $nextToken;
			}

			$result = $this->getClient()->listSubscriptionsByTopic($args);

			$nextToken = $result->get('NextToken');

			$subscriptions = $result->get('Subscriptions');

			foreach ($subscriptions as $subscription) {
				if ($subscription['Protocol'] == $protocol && $subscription['Endpoint'] == $endpoint) {
					$subscriptionArn = $subscription['SubscriptionArn'];

					$this->getClient()->unsubscribe([
						'TopicArn'        => $arn,
						'SubscriptionArn' => $subscriptionArn,
					]);
				}
			}
		} while ($nextToken != NULL);

		return $this;
	}

	/**
	 * @return $this
	 */
	public function registerRoutes()
	{
		$this->routes = [];

		foreach ($this->getSubscriptions() as $topic => $subscription) {
			if (!array_key_exists($subscription['route'], $this->routes)) {
				$this->routes[$subscription['route']] = [];
			}

			$this->routes[$subscription['route']][] = [
				'topic'        => $this->getTopic($topic),
				'subscription' => $subscription,
			];
		}

		foreach ($this->routes as $route => $topics) {
			Route::post($route, function () use ($topics) {
				$message   = Message::fromRawPostData();
				$validator = new MessageValidator();

				// Validate the message and log errors if invalid.
				try {
					$validator->validate($message);
				} catch (\Exception $e) {
					throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException();
				}

				$arn   = $message['TopicArn'];
				$found = FALSE;

				foreach ($topics as $topic) {
					if ($arn == $topic['topic']['arn']) {
						$found = TRUE;

						switch ($message['Type']) {
							case 'SubscriptionConfirmation': {
								file_get_contents($message['SubscribeURL']);

								break;
							}

							case 'Notification': {
								$subscription = $topic['subscription'];

								if (array_key_exists('controller', $subscription)) {
									if (is_array($subscription['controller'])) {
										foreach ($subscription['controller'] as $controller) {
											$this->callController($controller, $message);
										}
									} else {
										$this->callController($subscription['controller'], $message);
									}
								}

								if (array_key_exists('job', $subscription)) {
									if (is_array($subscription['job'])) {
										foreach ($subscription['job'] as $job) {
											$this->callJob($job, $message);
										}
									} else {
										$this->callJob($subscription['job'], $message);
									}
								}

								if (array_key_exists('callback', $subscription)) {
									if (is_array($subscription['callback'])) {
										foreach ($subscription['callback'] as $callback) {
											$this->callCallback($callback, $message);
										}
									} else {
										$this->callCallback($subscription['callback'], $message);
									}
								}

								break;
							}
						}

						break;
					}
				}

				if (!$found) {
					throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException();
				}
			});
		}

		return $this;
	}

	/**
	 * @param string  $controller
	 * @param Message $message
	 */
	private function callController($controller, $message)
	{
		$split = explode('@', $controller);

		$controller = $split[0];
		$method     = $split[1];

		/** @var \Illuminate\Routing\Controller $controller */
		$controller = app()->make($controller);

		$controller->callAction($method, [
			'message' => $message,
		]);
	}

	/**
	 * @param string  $job
	 * @param Message $message
	 */
	private function callJob($job, Message $message)
	{
		dispatch(new $job($message));
	}

	/**
	 * @param callable $callback
	 * @param Message  $message
	 */
	private function callCallback($callback, Message $message)
	{
		if (is_callable($callback)) {
			$callback($message);
		}
	}
}