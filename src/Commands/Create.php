<?php

namespace Mitchdav\SNS\Commands;

use Illuminate\Console\Command;
use Mitchdav\SNS\SNS;

/**
 * Class Create
 * @package Mitchdav\SNS\Commands
 */
class Create extends Command
{
	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'sns:create {topic?}';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Creates the SNS topics listed in the "sns" config file.';

	/**
	 * @var \Mitchdav\SNS\SNS
	 */
	private $sns;

	/**
	 * Create constructor.
	 *
	 * @param \Mitchdav\SNS\SNS $sns
	 */
	public function __construct(SNS $sns)
	{
		parent::__construct();

		$this->sns = $sns;
	}

	/**
	 * Execute the console command.
	 */
	public function handle()
	{
		if ($this->argument('topic') !== NULL) {
			$topics = [
				$this->argument('topic'),
			];
		} else {
			$topics = array_keys($this->sns->getTopics());
		}

		foreach ($topics as $topic) {
			$this->sns->createTopic($topic);
		}
	}
}