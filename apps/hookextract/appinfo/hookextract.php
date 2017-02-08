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
        $pregBrackets = "/\[(.*?)\]/";
        $apprSуmbols = '/[^a-zA-Z0-9\-\._]/';

        $maxConf = 20;
        $counter = 1;



        $recurr = $iniMapper->findByNameWithDefault("conf" . $counter . "_recurrency", "");
//echo "recurr=" . $recurr ."\n";
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

            $lastrun = false;
            $lastrun = $iniMapper->findByNameWithDefault("conf" . $counter . "_lastrun", false);
            $active = $iniMapper->findByNameWithDefault("conf" . $counter . "_active", "-");
            $reqUsers = $iniMapper->findManyByName("conf" . $counter . "_user");
//echo "counter=".$counter." $recurr=".$recurr."  lastrun=".$lastrun." \n";
            if (($this->checkRecurrency($recurr, $lastrun) || !$lastrun) && $active != "-") {
                echo "test \n";
                $app = $this; //new \OCA\Hookextract\AppInfo\Hookextract();
                $storage = $this->userfolder;
                if (!$storage) {
                    $storage = $this->root;
                }

                $label = $iniMapper->findByNameWithDefault("conf" . $counter . "_label", "*");



                $today = date_create();
                $today_str = $today->format('Ymd');
                $fileName = $iniMapper->findByNameWithDefault("conf" . $counter . "_saveFilename", $today_str . '.xlsx');

                $foundedMatches = preg_match($pregBrackets, $fileName, $timestamp);
                // 1. replace the forbidden symbols with a gap
                $model = preg_replace($apprSуmbols, ' ', $timestamp[1]);
                if ($foundedMatches > 0) {
                    $time = $today->format($model);
                    if (date_create_from_format($model, $time) !== FALSE) {
                        // it's a date
                        $anyDate = $time;
                    } else
                        $anyDate = $model;
                    // 2. change fileName according to [timestamp]
                    $fileName = preg_replace($pregBrackets, '_' . $anyDate, $fileName);
                }
                else {   // replace forbidden symbols with a gap
                    $fileName = preg_replace($pregBrackets, '_', $fileName);
                }

                $formtype = $iniMapper->findByNameWithDefault("conf" . $counter . "_formtype", "*");

                $predelete = $iniMapper->findByNameWithDefault("conf" . $counter . "_filepredelete", "-");

                //	echo "label = " . $label. "\n";



                $fileName = str_replace("[timestamp]", $today_str, $fileName);

                $app->exportToServer($formtype, $begin_selection, $end_selection, $this->getContainer()->getServer()->getDb(), $storage, $reqUsers, $fileName, $predelete);

                $today = date_create();
                $today_str = $today->format('Y-m-d H:i:s');
                $iniMapper->setByName("conf" . $counter . "_lastrun", $today_str);
            }

            $counter++;
//echo "counter=" . $counter ."\n";
            $recurr = $iniMapper->findByNameWithDefault("conf" . $counter . "_recurrency", "");

//echo "recurr=" . $recurr ."\n";
        }
    }

    /**
     * Check the recurrency settings
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function checkRecurrency($recurrency, $lastrun) {
        if (!$lastrun) {
            return true;
        }

        $min_constant = 1;

        $today = date_create();
        $lastrun_time = date_create($lastrun);

        if ($lastrun_time) {
            $interval = date_diff($today, $lastrun_time);
            $ddiff = $interval->format("%a");
        }
        if ($recurrency === "daily") {
            if ($ddiff > $min_constant) {
                return true;
//echo "ddiff=".$ddiff." run_daily \n";
            }
        } elseif ($recurrency === "monthly") {
            $interval = date_diff($today, $lastrun_time);
            $mdiff = $interval->format("%m");
            if ($mdiff > $min_constant) {
                return true;
            }
        } elseif ($recurrency === "yearly") {
            $interval = date_diff($today, $lastrun_time);
            $mdiff = $interval->format("%y");
            if ($mdiff > $min_constant) {
                return true;
            }
        }
        return false;
    }

    /**
     * Simply method that posts back the payload of the request
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function dbGetXls($formtype, $datefrom, $dateto, $db, $user) {
        $headers = [];
        $output = [];
      
        $mapper = new EntryMapper($db);
        $data = $mapper->findByFormType($formtype, $datefrom, $dateto, $user);
        $this->parseData($data, $headers, $output);

        $archiveMapper = new EntryArchiveMapper($db);
        $data_arch = $archiveMapper->findByDate($datefrom, $dateto, $user);
        $this->parseData($data_arch, $headers, $output);
        $keys = array_keys($headers);
        $this->orderData($keys, $output);

        $content = $this->exportToNewFile($output, $keys);

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
    
    /**
     * Order data in the output array
     * @param array $keys
     * @param array $output
     */
    public function orderData($keys, &$output) {
        $outputSorted = [];
        $outputSortedLine = array_fill_keys($keys, "");
        foreach ($output as $outputLine) {
            $outputSorted[] = array_merge($outputSortedLine, $outputLine);
        }
        $output = $outputSorted;
    }
    
    /**
     * Update strings with symbol '|' - get last substring after '|'
     * @param array $output
     */
    public function checkStrings(&$output) {
        // get last substring after "|"
            foreach($output as &$line){
                foreach($line as &$value){
                    $newCellValue = str_replace("|","",substr($value, strrpos($value, "|") , strlen($value)));    
                    $value = $newCellValue;
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
    private function exportToServer($formtype, $datefrom, $dateto, $db, $folder, $users, $fileName, $predelete) {
        $headers = [];
        $output = [];
        $data = [];
        $data_arch = [];

        foreach ($users as $user) {
            // echo "counter data= " . count($data). "\n";
            $mapper = new EntryMapper($db);
            $data1 = [];

            $data1 = $mapper->findByFormType($formtype, $datefrom, $dateto, $user->getValue());
            //    echo "findformbytype=".count($data1) ."\n";
            foreach ($data1 as $line1) {
                $data[] = $line1;
            }
            //$data = array_merge($data, $data1);

            $archiveMapper = new EntryArchiveMapper($db);

            $data_arch1 = [];
            $data_arch1 = $archiveMapper->findByDate($datefrom, $dateto, $user->getValue());
            foreach ($data_arch1 as $arch_line1) {
                $data_arch[] = $arch_line1;
            }
            //$data_arch = array_merge($data_arch, $data_arch1);
        }

        $this->parseData($data, $headers, $output);
        $this->parseData($data_arch, $headers, $output);
        
         // change data with '|'
        $this->checkStrings($output);

        // server part
        $iniMapper = new paramsMapper($db);

        // check if file exists and write to it if possible
        try {
            try {
                $file = $folder->get($fileName);
                $storage = $file->getStorage();
                $filepath = $file->getInternalPath();

                if ($predelete == '+') {
                    echo "Predeletion is active.\n";
                    $result = $storage->unlink('' . $filepath);
                    echo "Delete result = " . $result . "\n";
                }
                $contents = $storage->file_get_contents($filepath);

                if (strlen($contents) <= 0) {
                    throw new \OCP\Files\NotFoundException("File not found");
                }

                chdir("temp");
//echo "filename=" . $fileName . "\n";
                $localFileName = substr(strrchr($fileName, "/"), 1);
                if (!$localFileName) {
                    $localFileName = $fileName;
                }
                file_put_contents($localFileName, $contents);
                $keys = array_keys($headers);
                $this->orderData($keys, $output);
                $content = $this->exportToExistingFile($localFileName, $output, $keys);
                unlink($localFileName);
                chdir("..");
            } catch (\OCP\Files\NotFoundException $e) {
                chdir("temp");
                unlink($fileName);
                $file = $folder->newFile($fileName);
                $keys = array_keys($headers);
                $this->orderData($keys, $output);
                $content = $this->exportToNewFile($output, $keys);
                unlink($fileName);
                chdir("..");
            }
            // the id can be accessed by $file->getId();
            $file->putContent($content);
        } catch (\OCP\Files\NotPermittedException $e) {
            // you have to create this exception by yourself ;)
            throw new StorageException("Can't write to file");
        }
    }

    /**
     * Export data to a new Excel file
     * @param array $output
     * @param array $keys
     * @return type $content
     */
    private function exportToNewFile($output, $keys) {

        $writer = WriterFactory::create(Type::XLSX);
        $style = (new StyleBuilder())->setFontBold()->build();
        ob_start();
        $writer->openToFile('php://output');
        if (empty($output)) {
            $message = [];
            $message[] = "No data available. Last run " . date("F j, Y H:i");
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
     * Export data to an existing Excel file
     * @param string $fileName
     * @param array $output
     * @return type $content
     */
    private function exportToExistingFile($fileName, $newData, $newKeys) {

        $reader = ReaderFactory::create(Type::XLSX);
        $reader->setShouldFormatDates(true);
        $reader->open($fileName);

        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $oldData[] = $row;
            }
        }

        $reader->close();

        $writer = WriterFactory::create(Type::XLSX);
        $style = (new StyleBuilder())->setFontBold()->build();
        ob_start();
        $writer->openToFile('php://output');
        if (count($oldData) == 1) {
            if (empty($newData)) {
                $message = [];
                $message[] = "No data available. Last run " . date("F j, Y");
                $writer->addRow($message);
            } else {
                $writer->addRowWithStyle($newKeys, $style)
                        ->addRows($newData);
            }
        } else {
            $oldKeys = array_shift($oldData);
            $this->orderData($oldKeys, $newData);
            $writer->addRowWithStyle($oldKeys, $style)
                    ->addRows($oldData)
                    ->addRows($newData);
        }
        $writer->close();

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
