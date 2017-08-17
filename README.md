# Laravel SNS Broadcast Driver

This library provides a Laravel 5.4 broadcast driver for AWS SNS platform. Using this library, you can send and receive broadcasts to and from SNS topics.

## Installation

    composer require mitchdav/sns-laravel

Once the library is installed, add the following service provider to your ```config/app.php``` file:

```php
Mitchdav\SNS\Provider::class,
```

You can also add the facade if required:

```php
'SNS' => Mitchdav\SNS\Facades\SNS::class,
```

You can then export the config file with the following command:

    php artisan vendor:publish --provider="Mitchdav\SNS\Provider"

## Configuration

You first need to define the following environment variables in your ```.env``` file:

    AWS_ACCOUNT_ID=
    AWS_ACCESS_KEY=
    AWS_SECRET_KEY=
    AWS_REGION=

While you're in the ```.env``` file, you can change the ```BROADCAST_DRIVER``` variable as follows (optional, you will need to configure this on your broadcasts elsewhere otherwise):

    BROADCAST_DRIVER=sns

Then edit the ```config/broadcasting.php``` file, and add the following in the ```connections``` section.

```php
'sns' => [
    'driver' => 'sns',
],
```

Then edit the ```config/sns.php``` file, which is where you can define your **topics** and **subscriptions**.

### Topics

Topics are the SNS topics you will interact with, through sending, receiving, or both. Each topic needs to have a name and some way to resolve to an ARN.

There are many configuration options to provide these, as follows:

```php
'topics' => [
    // The ARN will be formed using environment variables
    'test-broadcast',

    // You can also define how the ARN should be formed (this is how the first example works)
    'test-broadcast-array' => [
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

    // You can also define the ARN directly
    'test-broadcast-arn'   => 'arn:aws:sns:us-east-2:1234567890:test-broadcast-arn',
],
```

### Subscriptions

Subscriptions allow you to subscribe and receive broadcasts from the SNS topics specified in the topics configuration section. **All subscriptions must first be defined in the topics section.**

You can choose which topics to subscribe to (if any), and which actions to perform when a broadcast is received.

```php
'subscriptions' => [
    'test-broadcast' => [
        // 'controller' => 'App\Http\Controllers\BroadcastController@testBroadcast',
        // 'job'        => 'App\Jobs\TestBroadcastJob',
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
    ],*/
],
```

In each action, the action will be called with a single ```Message``` parameter, which contains the raw message sent by SNS.

An example controller might look like this:

```php
<?php

namespace App\Http\Controllers;

use Aws\Sns\Message;
use Log;

class BroadcastController extends Controller
{
    public function testBroadcast(Message $message)
    {
        ob_start();
        print_r($message);
        Log::info(ob_get_clean());
    }
}
```

An example job might look like this:

```php
<?php

namespace App\Jobs;

use Aws\Sns\Message;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Log;

class TestBroadcastJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var \Aws\Sns\Message
     */
    private $message;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Message $message)
    {
        $this->message = $message;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        ob_start();
        print_r($this->message);
        Log::info(ob_get_clean());
    }
}
```

#### Subscription Routes

The service provider will register routes to receive subscription broadcasts which can be configured using a combination of the ```url``` and ```subscriptions.route``` options.

```php
// The base URL for each of the routes, which is used to give SNS the right subscription endpoints
'url' => rtrim(env('APP_URL'), '/'),

'defaults' => [
    'subscriptions' => [
        // This will be the default route for all subscriptions
        'route' => '/sns',
    ],
],

'subscriptions' => [
    'test-broadcast' => [
        // You can override the route on a per-subscription basis
        'route' => '/sns/test-broadcast',
    ],
],
```

You can then list all of your routes with Laravel's ```route:list``` command:

    php artisan route:list

## Broadcasting

Follow the [Laravel instructions](https://laravel.com/docs/5.4/broadcasting), specifically the [Defining Broadcast Events](https://laravel.com/docs/5.4/broadcasting#defining-broadcast-events) section, and you will be able to broadcast to SNS.

An example notification class might look like this:

```php
<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class RequestReceived extends Notification implements ShouldQueue, ShouldBroadcast
{
    use Queueable;

    /**
     * @var string $requestId
     */
    private $requestId;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($requestId)
    {
        $this->requestId = $requestId;
    }

    /**
     * Get the channel or channels to broadcast on.
     *
     * @return string
     */
    public function broadcastOn()
    {
        return 'request-received';
    }

    /**
     * Get the broadcastable representation of the notification.
     *
     * @return array
     */
    public function broadcastWith()
    {
        return [
            'id' => $this->requestId,
        ];
    }
}
```

To broadcast, just fire it as an event as you would with any broadcast:

```php
event(new \App\Notifications\RequestReceived(1));
```

You will also need to have a queue listener running, which you can do with the following command:

    php artisan queue:work

## Commands

### sns:create {topic?}

This command allows you to create all of the topics specified in the ```topics``` section. If the topics already exist, they will not be effected, so it is beneficial to run this command when your system first boots up to make sure that the expected topics exist.

    php artisan sns:create

You can also specify to create only a single topic by providing the name of the topic as specified in the ```topics``` section.

    php artisan sns:create test-broadcast


### sns:delete {topic?}

This command allows you to delete all of the topics specified in the ```topics``` section. This is useful for testing the system, but it is hard to see where this would be used often in production.

    php artisan sns:delete

You can also specify to create only a single topic by providing the name of the topic as specified in the ```topics``` section.

    php artisan sns:delete test-broadcast

### sns:subscribe {topic?} {--create}

This command allows you to subscribe to all of the topics specified in the ```subscriptions``` section. If the specified endpoint is already subscribed to the topic, it will not be effected nor duplicated, so it is beneficial to run this command when your system first boots up to make sure that the expected subscription exist.

    php artisan sns:subscribe

You can also specify to subscribe to a single topic by providing the name of the topic as specified in the ```topics``` section.

    php artisan sns:subscribe test-broadcast

You can also specify to create the topics first if they do not exist, using the ```--create``` option.

    php artisan sns:subscribe --create

    # or

    php artisan sns:subscribe test-broadcast --create

### sns:unsubscribe {topic?} {--delete}

This command allows you to unsubscribe to all of the topics specified in the ```subscriptions``` section. This is useful for testing the system, but it is hard to see where this would be used often in production.

    php artisan sns:unsubscribe

You can also specify to unsubscribe to a single topic by providing the name of the topic as specified in the ```topics``` section.

    php artisan sns:unsubscribe test-broadcast

You can also specify to delete the topics after unsubscribing, using the ```--delete``` option.

    php artisan sns:unsubscribe --delete

    # or

    php artisan sns:unsubscribe test-broadcast --delete

## Testing

The library is currently not formally tested, however it is known to work with Laravel 5.4 projects. I aim to implement testing soon, however if you discover any problems please submit an issue and I will get back to you as soon as possible.