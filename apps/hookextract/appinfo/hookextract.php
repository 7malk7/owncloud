<?php

namespace OCA\Hookextract\AppInfo;

use \OCP\AppFramework\App;
use \OC\Files\Node\File;
use \OC\Files\Node\Folder;
use \OC\Files\Filesystem;
use OCA\DeductToDB\Db\paramsMapper;
use OCA\DeductToDB\Db\EntryArchiveMapper;
use OCA\DeductToDB\Db\EntryMapper;
use Box\Spout\Writer\WriterFactory;
use Box\Spout\Reader\ReaderFactory;
use Box\Spout\Common\Type;
use Box\Spout\Writer\Style\StyleBuilder;
use OCA\DeductToDB\Storage\StorageException;

require_once "phpexcel/Classes/PHPExcel.php";
require_once "spout/Autoloader/autoload.php";

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

        $this->runJob();
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
            $reqUsers = $iniMapper->findManyByName("conf" . $counter . "_user");

            $today = date_create();
            $lastrun_time = date_create($lastrun);

            if (!empty($lastrun)) {
                $interval = date_diff($today, $lastrun_time);
                $ddiff = $interval->format("%a");
            }

            if (($ddiff >= 1 || empty($lastrun)) && $active != "-") {
                $app = $this; //new \OCA\Hookextract\AppInfo\Hookextract();
                $storage = $this->userfolder;
                if (!$storage) {
                    $storage = $this->root;
                }

                $today = date_create();
                $today_str = $today->format('Ymd');
                $fileName = $iniMapper->findByNameWithDefault("conf" . $counter . "_saveFilename", $today_str . '.xlsx');
                $fileName = str_replace("[timestamp]", $today_str, $fileName);
                $formtype = $iniMapper->findByNameWithDefault("conf" . $counter . "_formtype", "*");

                $app->exportToServer($formtype, $begin_selection, $end_selection, $this->getContainer()->getServer()->getDb(), $storage, $reqUsers, $fileName);

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
    public function dbGetXls($formtype, $datefrom, $dateto, $db, $user) {
        $headers = [];
        $output = [];
        $keys = [];

        $users[] = $user;
        $this->buildOutput($formtype, $datefrom, $dateto, $db, $keys, $output, $users);

        $content = $this->exportFile($output, $keys);

        return $content;
    }

    /**
     * Parse data to output format
     * @param array $data
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

    /**
     * Export data to an Excel file on server
     * @param type $formtype
     * @param type $datefrom
     * @param type $dateto
     * @param type $db
     * @param type $folder
     * @param type $user
     * @throws StorageException
     */
    private function exportToServer($formtype, $datefrom, $dateto, $db, $folder, $reqUsers, $fileName) {
        $output = [];
        $keys = [];
        $data = [];
        $dataArchive = [];
        
        foreach ($reqUsers as $reqUser) {
            $users[] = $reqUser->getValue();
        }
        
        $this->buildOutput($formtype, $datefrom, $dateto, $db, $keys, $output, $users);

        // check if file exists and write to it if possible
        try {
            try {
                $file = $folder->get($fileName);
                $path = $this->getPath($file);
                $this->exportToExistingFile($path, $output, $keys);
            } catch (\OCP\Files\NotFoundException $e) {
                $file = $folder->newFile($fileName);
                $path = $this->getPath($file);
                $this->exportToNewFile($output, $keys, $path);
            }
        } catch (\OCP\Files\NotPermittedException $e) {
            // you have to create this exception by yourself ;)
            throw new StorageException("Can't write to file");
        }
    }

    /**
     * Build output data
     * @param type $formtype
     * @param type $datefrom
     * @param type $dateto
     * @param type $db
     * @param type $keys
     * @param type $output
     * @param type $users
     */
    private function buildOutput($formtype, $datefrom, $dateto, $db, &$keys, &$output, $users) {
        $headers = [];
        $data = [];
        $dataArchive = [];
        $mapper = new EntryMapper($db);
        $archiveMapper = new EntryArchiveMapper($db);
        foreach ($users as $user) {
            $dataForUser = $mapper->findByFormType($formtype, $datefrom, $dateto, $user);
            $data = array_merge($data, $dataForUser);

            $dataArchiveForUser = $archiveMapper->findByDate($datefrom, $dateto, $user);
            $dataArchive = array_merge($dataArchive, $dataArchiveForUser);
        }
        if (!empty($data)) {
            $this->parseData($data, $headers, $output);
        }
        if (!empty($dataArchive)) {
            $this->parseData($data_arch, $headers, $output);
        }
        if (!empty($headers)) {
            $keys = array_keys($headers);
        }
    }

    /**
     * Export data to a new Excel file on server
     * @param array $output
     * @param array $keys
     * @return type $content
     */
    private function exportToNewFile($output, $keys, $path) {
        $writer = WriterFactory::create(Type::XLSX);
        $style = (new StyleBuilder())->setFontBold()->build();
        $writer->openToFile($path);

        if (empty($output)) {
            $message = [];
            $message[] = "No data available";
            $writer->addRow($message);
        } else {
            $writer->addRowWithStyle($keys, $style)
                    ->addRows($output);
        }

        $writer->close();
    }

    /**
     * Export data to an existing Excel file on server
     * @param string $fileName
     * @param array $output
     * @return type $content
     */
    private function exportToExistingFile($path, $newData, $newKeys) {
        $reader = ReaderFactory::create(Type::XLSX);
        $reader->setShouldFormatDates(true);
        $reader->open($path);
        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $oldData[] = $row;
            }
        }
        $reader->close();

        $writer = WriterFactory::create(Type::XLSX);
        $style = (new StyleBuilder())->setFontBold()->build();
        $writer->openToFile($path);
        if (count($oldData) === 1) {
            if (empty($newData)) {
                $writer->addRows($oldData);
            } else {
                $writer->addRowWithStyle($newKeys, $style)
                        ->addRows($newData);
            }
        } else {
            $oldKeys = array_shift($oldData);
            $writer->addRowWithStyle($oldKeys, $style)
                    ->addRows($oldData)
                    ->addRows($newData);
        }
        $writer->close();
    }

    /**
     * Export data to a new Excel file for downloading
     * @param array $output
     * @param array $keys
     * @return type
     */
    private function exportFile($output, $keys) {
        $writer = WriterFactory::create(Type::XLSX);
        $style = (new StyleBuilder())->setFontBold()->build();
        ob_start();
        $writer->openToFile("php://output");

        if (empty($output)) {
            $message = [];
            $message[] = "No data available";
            $writer->addRow($message);
        } else {
            $writer->addRowWithStyle($keys, $style)
                    ->addRows($output);
        }

        $writer->close();
        $content = ob_get_clean();
        return $content;
    }

    /**
     * Get file path on server
     * @param type $file
     * @return type
     */
    private function getPath($file) {
        $storage = $file->getStorage();
        $filepath = $file->getInternalPath();
        $fullPath = $storage->getLocalFile($filepath);
        $fullPath = str_replace('\\', '/', $fullPath);

        return $fullPath;
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
