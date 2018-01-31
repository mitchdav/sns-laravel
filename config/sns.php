<?php

use Aws\Sns\Message;

// use Illuminate\Support\Facades\Log;

return [
	// The credentials needed for the AWS client
	'client'        => [
		'id'      => env('AWS_ACCOUNT_ID'),
		'key'     => env('AWS_ACCESS_KEY'),
		'secret'  => env('AWS_SECRET_KEY'),
		'region'  => env('AWS_REGION'),
		'version' => 'latest',
	],

	// The base URL for each of the routes, which is used to give SNS the right subscription endpoints
	'url'           => rtrim(env('APP_URL'), '/'),

	// Suitable defaults
	'defaults'      => [
		'topics' => [
			'region'  => env('AWS_REGION'),
			'id'      => env('AWS_ACCOUNT_ID'),
			'prefix'  => str_slug(env('APP_NAME')),
			'joiner'  => '_',
			'formARN' => function ($region, $id, $prefix, $joiner, $topic) {
				// This default joiner will form ARNs similar to arn:aws:sns:us-east-2:1234567890:app-name_test-broadcast
				$output = 'arn:aws:sns:' . $region . ':' . $id . ':';

				if (!empty($prefix)) {
					$output .= $prefix . $joiner;
				}

				return $output . $topic;
			},
		],

		'subscriptions' => [
			// This will be the default route for all subscriptions
			'route' => '/sns',
		],
	],

	// The topics and their matching ARNs, which can be created with
	// php artisan sns:create
	'topics'        => [
		// The ARN can be formed using the defaults above
		'test-broadcast',

		// You can also define the ARN directly
		//'test-broadcast-arn' => 'arn:aws:sns:us-east-2:1234567890:test-broadcast-arn',
	],

	// The topics to be subscribed to, and their matching actions
	'subscriptions' => [
		'test-broadcast' => [
			// 'controller' => 'BroadcastController@testBroadcast',
			// 'job'        => 'TestBroadcastJob',
			'callback' => function (Message $message) {
				// Log::info('Broadcast received from ARN "' . $message->offsetGet('TopicArn') . '" with Message "' . $message->offsetGet('Message') . '".');
			},
		],

		/*'test-broadcast-arn' => [
			// You can also dispatch an array of actions
			'controller' => [
				'BroadcastController@testBroadcastARN',
			],
			'job'        => [
				'TestBroadcastARNJob',
			],
			'callback'   => [
				function (\Aws\Sns\Message $message) {
					// Log::info('Broadcast received from ARN "' . $message->offsetGet('TopicArn') . '" with Message "' . $message->offsetGet('Message') . '".');
				},
			],
			// You can override the route on a per-subscription basis
			// 'route'      => '/sns/test-broadcast-arn',
		],*/
	],
];