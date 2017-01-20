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

            if ($lastrun_time) {
                $interval = date_diff($today, $lastrun_time);
                $ddiff = $interval->format("%a");
            }

            if (($ddiff >= 0 || !$lastrun_time) && $active != "-") {
                $app = $this; //new \OCA\Hookextract\AppInfo\Hookextract();
                $storage = $this->userfolder;
                if (!$storage) {
                    $storage = $this->root;
                }


                $today = date_create();
                $today_str = $today->format('Ymd');
                $fileName = $iniMapper->findByNameWithDefault("conf" . $counter . "_saveFilename", $today_str . '.xlsx');

                $formtype = $iniMapper->findByNameWithDefault("conf" . $counter . "_formtype", "*");



                $fileName = str_replace("[timestamp]", $today_str, $fileName);

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

        $mapper = new EntryMapper($db);
        $data = $mapper->findByFormType($formtype, $datefrom, $dateto, $user);
        $this->parseData($data, $headers, $output);

        $archiveMapper = new EntryArchiveMapper($db);
        $data_arch = $archiveMapper->findByDate($datefrom, $dateto, $user);
        $this->parseData($data_arch, $headers, $output);
        $keys = array_keys($headers);

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
     * Export data to an Excel file on server
     * @param type $formtype
     * @param type $datefrom
     * @param type $dateto
     * @param type $db
     * @param type $folder
     * @param type $user
     * @throws StorageException
     */
    private function exportToServer($formtype, $datefrom, $dateto, $db, $folder, $users, $fileName) {
        $headers = [];
        $output = [];

        foreach ($users as $user) {

            $mapper = new EntryMapper($db);
            $data1 = $mapper->findByFormType($formtype, $datefrom, $dateto, $user->getValue());
            foreach ($data1 as $line1) {
                $data[] = $line1;
            }
            //$data = array_merge($data, $data1);

            $archiveMapper = new EntryArchiveMapper($db);
            $data_arch1 = $archiveMapper->findByDate($datefrom, $dateto, $user->getValue());
            foreach ($data_arch1 as $arch_line1) {
                $data_arch[] = $arch_line1;
            }
            //$data_arch = array_merge($data_arch, $data_arch1);
        }

        $this->parseData($data, $headers, $output);
        $this->parseData($data_arch, $headers, $output);

        // server part
        $iniMapper = new paramsMapper($db);


        // check if file exists and write to it if possible
        try {
            try {
                $file = $folder->get($fileName);
                $storage = $file->getStorage();
                $filepath = $file->getInternalPath();
                $contents = $storage->file_get_contents($filepath);

                if (strlen($contents) <= 0) {
                    throw new \OCP\Files\NotFoundException("File not found");
                }

                chdir("temp");

                file_put_contents($fileName, $contents);

                $content = $this->exportToExistingFile($fileName, $output);
                unlink($fileName);
                chdir("..");
            } catch (\OCP\Files\NotFoundException $e) {
                chdir("temp");
                unlink($fileName);
                $file = $folder->newFile($fileName);
                $keys = array_keys($headers);
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
//        $objPHPExcel = new \PHPExcel();
//
//        $objPHPExcel->getProperties()->setTitle("Extraction")
//                ->setSubject("Extraction")
//                ->setDescription("Extraction")
//                ->setKeywords("Extraction");
//        $objPHPExcel->setActiveSheetIndex(0);
//        $objPHPExcel->getActiveSheet()->fromArray($keys, null, 'A1');
//        $objPHPExcel->getActiveSheet()->getStyle('A1:AZ1')->getFont()->setBold(true);
//        $objPHPExcel->getActiveSheet()->fromArray($output, null, 'A2');
//
//        foreach (range('A', 'Z') as $column) {
//            $aColumn = 'A' . $column;
//            if ($objPHPExcel->getActiveSheet()->getCell($column . '1')->getValue()) {
//                $objPHPExcel->getActiveSheet()->getColumnDimension($column)->setAutoSize(true);
//            }
//            if ($objPHPExcel->getActiveSheet()->getCell($aColumn . '1')->getValue()) {
//                $objPHPExcel->getActiveSheet()->getColumnDimension($aColumn)->setAutoSize(true);
//            }
//        }
//
//        $objPHPExcel->getActiveSheet()->setTitle('Extraction');
//
//        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
//        ob_start();
//        $objWriter->save('php://output');
//        $content = ob_get_clean();

        $writer = WriterFactory::create(Type::XLSX);
        $style = (new StyleBuilder())->setFontBold()->build();
        ob_start();
        $writer->openToFile('php://output')
                ->addRowWithStyle($keys, $style)
                ->addRows($output)
                ->close();
        $content = ob_get_clean();

        return $content;
    }

    /**
     * Export data to an existing Excel file
     * @param string $fileName
     * @param array $output
     * @return type $content
     */
    private function exportToExistingFile($fileName, $newData) {
//        try {
//            $objReader = \PHPExcel_IOFactory::createReader('Excel2007');
//            $objPHPExcel = $objReader->load($fileName);
//        } catch (Exception $ex) {
//            echo $ex->getMessage();
//        }
//
//        $objWorksheet = $objPHPExcel->setActiveSheetIndex(0);
//        $startRow = (int) $objWorksheet->getHighestRow() + 1;
//        $objWorksheet->fromArray($output, null, 'A' . $startRow);
//
//        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
//        ob_start();
//        $objWriter->save('php://output');
//        $content = ob_get_clean();

        $reader = ReaderFactory::create(Type::XLSX);
        $reader->setShouldFormatDates(true);
        $reader->open($fileName);
    
        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $oldData[] = $row;
            }
        }
        
        $reader->close();
        $keys = array_shift($oldData);
        
        $writer = WriterFactory::create(Type::XLSX);
        $style = (new StyleBuilder())->setFontBold()->build();
        ob_start();
        $writer->openToFile('php://output')
                ->addRowWithStyle($keys, $style)
                ->addRows($oldData)
                ->addRows($newData)
                ->close();
        
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
