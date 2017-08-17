<?php

namespace Mitchdav\SNS\Commands;

use Illuminate\Console\Command;
use Mitchdav\SNS\SNS;

/**
 * Class Unsubscribe
 * @package Mitchdav\SNS\Commands
 */
class Unsubscribe extends Command
{
	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'sns:unsubscribe {topic?} {--delete}';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Unsubscribes to the SNS topics listed in the "sns" config file.';

	/**
	 * @var \Mitchdav\SNS\SNS
	 */
	private $sns;

	/**
	 * Unsubscribe constructor.
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
			$topics = array_keys($this->sns->getSubscriptions());
		}

		$delete = $this->option('delete');

		foreach ($topics as $topic) {
			$this->sns->unsubscribe($topic);

			if ($delete) {
				$this->sns->deleteTopic($topic);
			}
		}
	}
}