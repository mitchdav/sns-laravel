<?php

use Mitchdav\SNS\SimpleNameFormer;

return [

	'accounts' => [
		'account-1' => [
			'id'   => env('ACCOUNT_1_ID'),
			'role' => env('ACCOUNT_1_ROLE'),
		],

		'account-2' => [
			'id'   => env('ACCOUNT_2_ID'),
			'role' => env('ACCOUNT_2_ROLE'),
		],
	],

	'defaults' => [
		'all' => [
			'account' => 'account-1',
			'region'  => env('ACCOUNT_1_REGION'),
		],

		'topic' => [
			'prefix' => SimpleNameFormer::PREFIX_SERVICE_NAME,
			'joiner' => '_',
		],

		'queue' => [
			'prefix'     => SimpleNameFormer::PREFIX_SERVICE_NAME,
			'joiner'     => '_',
			'attributes' => [
				'MessageRetentionPeriod' => 14 * 24 * 60 * 60,
				'VisibilityTimeout'      => 2 * 60,
			],
		],

		'endpoint' => [
			'route'    => '/sns',
			'template' => 'http://api.example.com/{service}/sns',
		],
	],

	'services' => [
		'orders' => [
			// Topics that the orders service broadcasts to
			'topics' => [
				'order-created@region-1' => [
					'account' => 'account-1',
					'region'  => env('ACCOUNT_1_REGION'),
				],

				'order-created@region-2' => [
					'account' => 'account-2',
					'region'  => env('ACCOUNT_2_REGION'),
				],

				'order-shipped',
			],

			'queues' => [
				'payment-succeeded@region-1' => [
					'account' => 'account-1',
					'region'  => env('ACCOUNT_1_REGION'),
				],

				'payment-succeeded@region-2' => [
					'account' => 'account-2',
					'region'  => env('ACCOUNT_2_REGION'),
				],

				'payment-failed',
			],

			'endpoints'     => [
				'payment-succeeded' => [
					'url' => 'http://api.example.com/sns/whatever',
				],
			],

			// Topics that the orders service subscribes to
			'subscriptions' => [
				'payments' => [
					'payment-succeeded' => [
						'protocols' => [
							'sqs' => [
								'payment-succeeded@region-1',
								'payment-succeeded@region-2',
							],

							'http' => [
								'payment-succeeded'                   => 'resolves alias',
								'/payment-succeeded'                  => 'uses template but overwrites route',
								'http://api.example.com/sns/whatever' => 'overwrites template, is absolute',
								TRUE                                  => 'uses template as is',
							],

							'https' => [
								TRUE,
							],
						],

						'handlers' => [
							'PaymentSucceededJob',
						],
					],

					'payment-failed' => [
						'protocols' => [
							'sqs' => [
								'payment-failed',
							],
						],

						'handlers' => [
							'PaymentFailedJob',
						],
					],
				],
			],
		],

		'payments' => [
			// Topics that the payments service broadcasts to
			'topics' => [
				'payment-succeeded',
				'payment-failed',
			],

			'queues'        => [
				'risk-identified',
				'risk-mitigated',
			],

			// Topics that the payments service subscribes to
			'subscriptions' => [
				'risk' => [
					'risk-identified' => [
						'protocols' => [
							'sqs' => [
								'risk-identified',
							],
						],
					],

					'risk-mitigated' => [
						'protocols' => [
							'sqs' => [
								'risk-mitigated',
							],
						],
					],
				],
			],
		],

		'risk' => [
			// Topics that the risk service broadcasts to
			'topics' => [
				'risk-identified',
				'risk-mitigated',
			],
		],
	],

];