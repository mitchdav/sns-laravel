#!/bin/bash

cp composer.json original-composer.json

composer require "aws/aws-php-sns-message-validator:^1.2" --no-update
composer require "aws/aws-sdk-php-laravel:3.0.*" --no-update
composer require "illuminate/broadcasting:5.7.*" --no-update
composer require "illuminate/config:5.7.*" --no-update
composer require "illuminate/console:5.7.*" --no-update
composer require "illuminate/routing:5.7.*" --no-update
composer require "illuminate/support:5.7.*" --no-update
composer require "phpunit/phpunit:7.*" --no-update --dev
composer require "orchestra/testbench:3.7.*" --no-update --dev
composer update --prefer-source --no-interaction

rm composer.json
mv original-composer.json composer.json