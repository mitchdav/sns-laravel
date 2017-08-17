<?php

namespace Mitchdav\SNS\Commands;

use Illuminate\Console\Command;
use Mitchdav\SNS\SNS;

/**
 * Class Delete
 * @package Mitchdav\SNS\Commands
 */
class Delete extends Command
{
	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'sns:delete {topic?}';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Deletes the SNS topics listed in the "sns" config file.';

	/**
	 * @var \Mitchdav\SNS\SNS
	 */
	private $sns;

	/**
	 * Delete constructor.
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
			$this->sns->deleteTopic($topic);
		}
	}
}