<?php

namespace Mitchdav\SNS;

use Illuminate\Contracts\Broadcasting\Broadcaster as BaseBroadcaster;

/**
 * Class Broadcaster
 * @package Mitchdav\SNS
 */
class Broadcaster implements BaseBroadcaster
{
	/**
	 * @var \Mitchdav\SNS\SNS
	 */
	private $sns;

	/**
	 * Broadcaster constructor.
	 *
	 * @param \Mitchdav\SNS\SNS $sns
	 */
	public function __construct(SNS $sns)
	{
		$this->sns = $sns;
	}

	/**
	 * @param $channelName
	 * @param $callback
	 *
	 * @return bool
	 */
	public function channel($channelName, $callback)
	{
		return TRUE;
	}

	/**
	 * @param array  $channels
	 * @param string $event
	 * @param array  $payload
	 */
	public function broadcast(array $channels, $event, array $payload = [])
	{
		$payload = json_encode([
			'event' => $event,
			'data'  => $payload,
		]);

		foreach ($channels as $channel) {
			if (is_string($channel)) {
				$topic = $this->sns->getTopic($channel);

				$this->sns->getClient()->publish([
					'Message'  => $payload,
					'TopicArn' => $topic['arn'],
				]);
			}
		}
	}

	/**
	 * Authenticate the incoming request for a given channel.
	 *
	 * @param  \Illuminate\Http\Request $request
	 *
	 * @return mixed
	 */
	public function auth($request)
	{
		// SNS is for server-to-server communication, so there is no need to validate authenticate.
	}

	/**
	 * Return the valid authentication response.
	 *
	 * @param  \Illuminate\Http\Request $request
	 * @param  mixed                    $result
	 *
	 * @return mixed
	 */
	public function validAuthenticationResponse($request, $result)
	{
		// SNS is for server-to-server communication, so there is no need to validate authenticate.
	}
}