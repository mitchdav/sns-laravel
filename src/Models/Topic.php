<?php

namespace Mitchdav\SNS\Models;

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
	 * @param string                       $arn
	 */
	public function __construct($label, $name, Account $account, $region, $arn)
	{
		$this->label   = $label;
		$this->name    = $name;
		$this->account = $account;
		$this->region  = $region;
		$this->arn     = $arn;
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
	 * @return \Aws\Sns\SnsClient
	 */
	private function snsClient()
	{
		return $this->account->sdk()
		                     ->createSns([
			                     'region' => $this->region,
		                     ]);
	}
}