<?php

namespace OCA\HookExtract\Cron;

use \OCA\HookExtract\AppInfo\Hookextract;

class ExtractTask {

	public static function run() {
		$app = new \OCA\Hookextract\AppInfo\Hookextract();
		$app->runJob();
		//$container = $app->getContainer();
		//$container->query('SomeService')->run();
	}

}