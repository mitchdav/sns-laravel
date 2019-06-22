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
	 */
	public function __construct($label, $name, Account $account, $region)
	{
		$this->label   = $label;
		$this->name    = $name;
		$this->account = $account;
		$this->region  = $region;

		$this->arn = $this->generateArn();
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