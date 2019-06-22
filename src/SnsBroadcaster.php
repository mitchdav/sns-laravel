<?php

namespace Mitchdav\SNS;

use Illuminate\Contracts\Broadcasting\Broadcaster as Broadcaster;
use Illuminate\Support\Str;

/**
 * Class SnsBroadcaster
 * @package Mitchdav\SNS
 */
class SnsBroadcaster implements Broadcaster
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
	 * @param array  $channels
	 * @param string $event
	 * @param array  $payload
	 */
	public function broadcast(array $channels, $event, array $payload = [])
	{
		foreach ($channels as $channel) {
			if (is_string($channel)) {
				if (($delimiterPosition = strpos($channel, '.')) !== FALSE) {
					$service = substr($channel, 0, $delimiterPosition);
					$label   = substr($channel, $delimiterPosition + 1);
				} else {
					$service = Str::slug(config('app.name'));
					$label   = $channel;
				}

				if (!$service) {
					throw new \Exception('You must specify the service to publish to for topic "' . $label . '" (try checking you have set APP_NAME or adjust the channel name to <service>.<topic label>).');
				}

				$topic = $this->sns->config()
				                   ->getTopic($service, $label);

				if ($topic) {
					$topic->publish(json_encode($payload));
				} else {
					// TODO: Log attempt (without payload for security, maybe config flag?)
					throw new \Exception('Unable to publish on channel ' . $channel);
				}
			} else {
				// TODO: Log?
			}
		}
	}

	/**
	 * Authenticate the incoming request for a given channel.
	 *
	 * @param \Illuminate\Http\Request $request
	 *
	 * @return void
	 */
	public function auth($request)
	{
		// SNS is for server-to-server communication, so there is no need to authenticate the request.
	}

	/**
	 * Return the valid authentication response.
	 *
	 * @param \Illuminate\Http\Request $request
	 * @param mixed                    $result
	 *
	 * @return void
	 */
	public function validAuthenticationResponse($request, $result)
	{
		// SNS is for server-to-server communication, so there is no need to authenticate the request.
	}
}