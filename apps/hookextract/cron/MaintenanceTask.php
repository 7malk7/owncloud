<?php

namespace OCA\HookExtract\Cron;

use OCA\DeductToDB\AppInfo\DeductToDB;

/**
 *  Maintenance Concept
 */
class MaintenanceTask {

    public static function run() {
        $app = new \OCA\DeductToDB\AppInfo\Phphook();
        //$app->maintenanceJob();
    }

}
