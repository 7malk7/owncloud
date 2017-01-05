<?php

namespace OCA\Hookextract\AppInfo;

use \OCP\AppFramework\App;
use \OC\Files\Node\File;
use \OC\Files\Node\Folder;
use \OC\Files\Filesystem;
use OCA\DeductToDB\Db\paramsMapper;
use OCA\DeductToDB\Db\EntryArchiveMapper;
use OCA\DeductToDB\Db\EntryMapper;

require_once "phpexcel/Classes/PHPExcel.php";

class Hookextract extends App {

    private $root;
    private $userfolder;
    private $logger;
    private $db;

    public function __construct(array $urlParams = array()) {
        parent::__construct('hookextract', $urlParams);

        $container = $this->getContainer();
        $this->root = $container->query('ServerContainer')->getRootFolder();
        $this->userfolder = $container->query('ServerContainer')->getUserFolder();
        $this->logger = $container->getServer()->getLogger();
        $this->db = $container->getServer()->getDatabaseConnection();


        /**
         * Controllers
         */
        $container->registerService('FileHooks', function($c) {
            return new FileHooks(
                    $c->query('ServerContainer')->getRootFolder()
            );
        });


        $container->registerService('RootStorage', function ($c) {
            return $c->query('ServerContainer')->getRootFolder();
        });

        $container->registerService('XmlFactory', function ($c) {
            return new XmlFactory($c->query('RootStorage'));
        });
    }

    public function getRootFolder() {
        return $this->root;
    }

    public function getUserFolder() {
        return $this->userfolder;
    }

    public function runJob() {

        $iniMapper = new \OCA\DeductToDB\Db\paramsMapper($this->getContainer()->getServer()->getDb());
        $confParam = "conf";

        $maxConf = 10;
        $counter = 1;

        $recurr = $iniMapper->findByNameWithDefault("conf" . $counter . "_recurrency", "");
        while (!empty($recurr)) {

            $begin = $iniMapper->findByNameWithDefault("conf" . $counter . "_begin", "");
            $begin_selection = $iniMapper->findByNameWithDefault("conf" . $counter . "_begin_selection", "");
            $end_selection = $iniMapper->findByNameWithDefault("conf" . $counter . "_end_selection", "");

            if (!preg_match('/\d{4}-\d{2}-\d{2}/', $begin_selection)) {
                $startdate = strtotime("today");
                $begin_selection_date = strtotime($begin_selection, $startdate);
                $begin_selection = date("Y-m-d", $begin_selection_date);
            }

            if (!preg_match('/\d{4}-\d{2}-\d{2}/', $end_selection)) {
                $startdate = strtotime("today");
                $end_selection_date = strtotime($end_selection, $startdate);
                $end_selection = date("Y-m-d", $end_selection_date);
            }


            $lastrun = $iniMapper->findByNameWithDefault("conf" . $counter . "_lastrun", "");
            $active = $iniMapper->findByNameWithDefault("conf" . $counter . "_active", "-");
            $reqUser = $iniMapper->findByNameWithDefault("conf" . $counter . "_user", "");

            $today = date_create();

            $lastrun_time = date_create($lastrun);

            if ($lastrun_time) {
                $interval = date_diff($today, $lastrun_time);

                $ddiff = $interval->format("%a");
            }

            if (($ddiff >= 1 || !$lastrun_time) && $active != "-") {
                $app = $this; //new \OCA\Hookextract\AppInfo\Hookextract();
                $storage = $this->userfolder;
                if (!$storage) {
                    $storage = $this->root;
                }

                $app->dbGetXls("*", $begin_selection, $end_selection, $this->getContainer()->getServer()->getDb(), $storage, true, $reqUser);

                $today = date_create();
                $today_str = $today->format('Y-m-d H:i:s');
                $iniMapper->setByName("conf" . $counter . "_lastrun", $today_str);
            }

            $counter++;

            $recurr = $iniMapper->findByNameWithDefault("conf" . $counter . "_recurrency", "");
        }
    }

    /**
     * Simply method that posts back the payload of the request
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function dbGetXls($formtype, $datefrom, $dateto, $db, $storage, $saveOnServer, $user) {
        $headers = [];
        $output = [];

        $mapper = new EntryMapper($db);
        $data = $mapper->findByFormType($formtype, $datefrom, $dateto, $user);
        $this->parseData($data, $headers, $output);

        $archiveMapper = new EntryArchiveMapper($db);
        $data_arch = $archiveMapper->findByDate($datefrom, $dateto, $user);
        $this->parseData($data_arch, $headers, $output);
        $keys = array_keys($headers);
        
        $content = $this->exportToNewFile($output, $keys);

//        if ($saveOnServer) {
//            $iniMapper = new paramsMapper($db);
//
//            $today = date_create();
//            $today_str = $today->format('YmdHis');
//
//            $fileName = $iniMapper->findByNameWithDefault("saveFilename", $today_str . '.xlsx');
//
//            // check if file exists and write to it if possible
//            try {
//                try {
//                    $file = $storage->get($fileName);
//                } catch (\OCP\Files\NotFoundException $e) {
//                    $file = $storage->newFile($fileName);
//                }
//                // the id can be accessed by $file->getId();
//                $file->putContent($content);
//            } catch (\OCP\Files\NotPermittedException $e) {
//                // you have to create this exception by yourself ;)
//                throw new StorageException("Can't write to file");
//            }
//        }

        return $content;
    }

    /**
     * Parse data to output format
     * @param type $data
     * @param array $headers
     * @param array $output
     */
    public function parseData($data, &$headers, &$output) {
        foreach ($data as $line) {
            if (!$headers[$line->getKey()]) {
                $headers[$line->getKey()] = [];
            }

            if (!$output[$line->getFormid()]) {
                $output[$line->getFormid()] = [$line->getKey() => $line->getValue()];
            } else {
                $output[$line->getFormid()][$line->getKey()] = $line->getValue();
            }
        }
    }

    public function exportToServer($formtype, $datefrom, $dateto, $db, $storage, $user) {
        $headers = [];
        $output = [];

        $mapper = new EntryMapper($db);
        $data = $mapper->findByFormType($formtype, $datefrom, $dateto, $user);
        $this->parseData($data, $headers, $output);

        $archiveMapper = new EntryArchiveMapper($db);
        $data_arch = $archiveMapper->findByDate($datefrom, $dateto, $user);
        $this->parseData($data_arch, $headers, $output);

        // server part
        $iniMapper = new paramsMapper($db);
        $today = date_create();
        $today_str = $today->format('YmdHis');
        $fileName = $iniMapper->findByNameWithDefault("saveFilename", $today_str . '.xlsx');

        // check if file exists and write to it if possible
        try {
            try {
                $file = $storage->get($fileName);
                $content = $this->writeToExistingFile($fileName, $output);
            } catch (\OCP\Files\NotFoundException $e) {
                $file = $storage->newFile($fileName);
                $keys = array_keys($headers);
                $content = $this->writeToNewFile($output, $keys);
            }
            // the id can be accessed by $file->getId();
            $file->putContent($content);
        } catch (\OCP\Files\NotPermittedException $e) {
            // you have to create this exception by yourself ;)
            throw new StorageException("Can't write to file");
        }
    }

    /**
     * Export data to a new file
     * @param array $output
     * @param array $keys
     * @return type $content
     */
    private function exportToNewFile($output, $keys) {
        $objPHPExcel = new \PHPExcel();

        $objPHPExcel->getProperties()->setTitle("Extraction")
                ->setSubject("Extraction")
                ->setDescription("Extraction")
                ->setKeywords("Extraction");
        $objPHPExcel->setActiveSheetIndex(0);
        $objPHPExcel->getActiveSheet()->fromArray($keys, null, 'A1');
        $objPHPExcel->getActiveSheet()->getStyle('A1:AZ1')->getFont()->setBold(true);
        $objPHPExcel->getActiveSheet()->fromArray($output, null, 'A2');

        foreach (range('A', 'Z') as $column) {
            $aColumn = 'A' . $column;
            if ($objPHPExcel->getActiveSheet()->getCell($column . '1')->getValue()) {
                $objPHPExcel->getActiveSheet()->getColumnDimension($column)->setAutoSize(true);
            }
            if ($objPHPExcel->getActiveSheet()->getCell($aColumn . '1')->getValue()) {
                $objPHPExcel->getActiveSheet()->getColumnDimension($aColumn)->setAutoSize(true);
            }
        }

        $objPHPExcel->getActiveSheet()->setTitle('Extraction');

        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        ob_start();
        $objWriter->save('php://output');
        $content = ob_get_clean();

        return $content;
    }

    /**
     * Export data to an existing excel file
     * @param string $fileName
     * @param array $output
     * @return type $content
     * @throws StorageException
     */
    private function exportToExistingFile($fileName, $output) {
        try {
            $fileType = \PHPExcel_IOFactory::identify($fileName);
            $objReader = \PHPExcel_IOFactory::createReader($fileType);
            $objPHPExcel = $objReader->load($fileName);
        } catch (Exception $ex) {
            echo $ex->getMessage();
        }

        $objWorksheet = $objPHPExcel->setActiveSheetIndex(0);
        $highestRow = $objWorksheet->getHighestRow();
        $objWorksheet->fromArray($output, null, 'A' . $highestRow);

        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, $fileType);
        ob_start();
        $objWriter->save('php://output');
        $content = ob_get_clean();

        return $content;
    }

    /**
     * Simply method that posts back the payload of the request
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function initFiles($folder) {
        
    }

    /**
     * Simply method that posts back the payload of the request
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function initialization() {
        
    }

}
