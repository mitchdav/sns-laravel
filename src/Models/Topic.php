<?php

namespace Mitchdav\SNS\Models;

use Illuminate\Support\Arr;
use Mitchdav\SNS\Contracts\NameFormer;

class Topic
{
	/**
	 * @var string
	 */
	private $label;

	/**
	 * @var string
	 */
	private $name;

	/**
	 * @var \Mitchdav\SNS\Models\Account
	 */
	private $account;

	/**
	 * @var string
	 */
	private $region;

	/**
	 * @var string
	 */
	private $arn;

	/**
	 * Topic constructor.
	 *
	 * @param string                       $label
	 * @param string                       $name
	 * @param \Mitchdav\SNS\Models\Account $account
	 * @param string                       $region
	 */
	public function __construct($label, $name, Account $account, $region)
	{
		$this->label   = $label;
		$this->name    = $name;
		$this->account = $account;
		$this->region  = $region;

		$this->arn = $this->generateArn();
	}

	public static function parseTopics($accounts, $defaults, $service, $config)
	{
		$topics = [];

		foreach ($config as $label => $attributes) {
			if (is_string($attributes)) {
				// Topic config just has the topic label

				$label      = $attributes;
				$attributes = [];
			}

			$topics[] = self::parse($accounts, $defaults, $service, $label, $attributes);
		}

		return $topics;
	}

	public static function parse($accounts, $defaults, $service, $label, $attributes)
	{
		$defaults = array_replace_recursive(Arr::get($defaults, 'all', []), Arr::get($defaults, 'topic', []));

		$mergedAttributes = array_replace_recursive($defaults, $attributes);

		if (!isset($mergedAttributes['account'])) {
			throw new \Exception('You must provide the account for the "' . $label . '" topic.');
		}

		if (!isset($mergedAttributes['region'])) {
			throw new \Exception('You must provide the region for the "' . $label . '" topic.');
		}

		if (!isset($mergedAttributes['nameFormer'])) {
			throw new \Exception('You must provide the name former for the "' . $label . '" topic.');
		}

		$accountName = $mergedAttributes['account'];
		$region      = $mergedAttributes['region'];
		$nameFormer  = app($mergedAttributes['nameFormer']);

		if (!$nameFormer instanceof NameFormer) {
			throw new \Exception('The name former for the "' . $label . '" topic must implement ' . NameFormer::class . '.');
		}

		/** @var Account $account */
		$account = $accounts->first(function ($account) use ($accountName) {
			/** @var Account $account */

			return $account->getLabel() === $accountName;
		});

		if (!isset($account)) {
			throw new \Exception('The account "' . $accountName . '" was not found for the "' . $label . '" topic.');
		}

		$name = $nameFormer->formName($service, $label, $mergedAttributes);

		return new Topic($label, $name, $account, $region);
	}

	public function create()
	{
		$this->snsClient()
		     ->createTopic([
			     'Name' => $this->name,
		     ]);
	}

	public function delete()
	{
		$this->snsClient()
		     ->deleteTopic([
			     'TopicArn' => $this->arn,
		     ]);
	}

	/**
	 * @param $message
	 */
	public function publish($message)
	{
		$this->snsClient()
		     ->publish([
			     'TopicArn' => $this->arn,
			     'Message'  => $message,
		     ]);
	}

	/**
	 * @return string
	 */
	public function getLabel()
	{
		return $this->label;
	}

	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * @return \Mitchdav\SNS\Models\Account
	 */
	public function getAccount()
	{
		return $this->account;
	}

	/**
	 * @return string
	 */
	public function getRegion()
	{
		return $this->region;
	}

	/**
	 * @return string
	 */
	public function getArn()
	{
		return $this->arn;
	}

	/**
	 * @return \Aws\Sns\SnsClient
	 */
	public function snsClient()
	{
		return $this->account->sdk()
		                     ->createSns([
			                     'region' => $this->region,
		                     ]);
	}

	private function generateArn()
	{
		return join(':', [
			'arn',
			'aws',
			'sns',
			$this->region,
			$this->account->getId(),
			$this->name,
		]);
	}
}