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

	public function __construct(array $urlParams=array()){
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


		$container->registerService ( 'RootStorage', function ($c) {
			return $c->query ( 'ServerContainer' )->getRootFolder ();
		} );

		$container->registerService ( 'XmlFactory', function ($c) {
			return new XmlFactory( $c->query ( 'RootStorage' ) );
		} );
		
		$this->runJob();

	}
	
	public function getRootFolder(){
		return $this->root;
	}
	
	public function getUserFolder(){
		return $this->userfolder;
	}
	
	public function runJob(){
		
		$iniMapper = new \OCA\DeductToDB\Db\paramsMapper($this->getContainer()->getServer()->getDb());
		
		$recurr = $iniMapper->findByNameWithDefault("conf1_recurrency", "");
		
		$begin = $iniMapper->findByNameWithDefault("conf1_begin", "");
		$begin_selection = $iniMapper->findByNameWithDefault("conf1_begin_selection", "");
		$end_selection = $iniMapper->findByNameWithDefault("conf1_end_selection", "");
		$lastrun = $iniMapper->findByNameWithDefault("conf1_lastrun", "");
		$active = $iniMapper->findByNameWithDefault("conf1_active", "-");
		
		$today = strtotime('now');
		$today = date_create();
		
		$lastrun_time = date_create($lastrun);
		
		if($lastrun_time)
		{
			$interval = date_diff($today, $lastrun_time);
		
			$ddiff = $interval->format("%a");
		}
		
		if( ($ddiff >= 0 || !$lastrun_time) && $active != "-"){
			$app = $this;//new \OCA\Hookextract\AppInfo\Hookextract();
			$app->dbGetXls("*", $begin_selection, $end_selection, $this->getContainer()->getServer()->getDb(), $this->userfolder, true);

			$today = date_create();
			$today_str = $today->format('Y-m-d H:i:s');
			$iniMapper->setByName("conf1_lastrun", $today_str);
		}
		
	}
	
	/**
	 * Simply method that posts back the payload of the request
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function dbGetXls($formtype, $datefrom, $dateto, $db, $storage, $saveOnServer ) {
		$mapper = new EntryMapper($db);
	
		$objPHPExcel = new \PHPExcel();
	
		$objPHPExcel->getProperties()->setTitle("Extraction")
		->setSubject("Extraction")
		->setDescription("Extraction")
		->setKeywords("Extraction");
	
		$data = $mapper->findByFormType($formtype, $datefrom, $dateto);
	
		$headers = [];
		$output = [];
	
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
		
		$archiveMapper = new EntryArchiveMapper($db);
		$data_arch = $archiveMapper->findByDate($datefrom, $dateto);
		
		foreach ($data_arch as $line) {
			if (!$headers[$line->getKey()]) {
				$headers[$line->getKey()] = [];
			}
		
			if (!$output[$line->getFormid()]) {
				$output[$line->getFormid()] = [$line->getKey() => $line->getValue()];
			} else {
				$output[$line->getFormid()][$line->getKey()] = $line->getValue();
			}
		}		
	
		$keys = array_keys($headers);
		$objPHPExcel->setActiveSheetIndex(0);
		$objPHPExcel->getActiveSheet()->fromArray($keys, null, 'A1');
		$objPHPExcel->getActiveSheet()->getStyle('A1:AZ1')->getFont()->setBold(true);
		$objPHPExcel->getActiveSheet()->fromArray($output, null, 'A2');
	
		foreach (range('A', 'Z') as $column) {
			$aColumn = 'A'.$column;
			if ($objPHPExcel->getActiveSheet()->getCell($column.'1')->getValue()) {
				$objPHPExcel->getActiveSheet()->getColumnDimension($column)->setAutoSize(true);
			}
			if ($objPHPExcel->getActiveSheet()->getCell($aColumn.'1')->getValue()) {
				$objPHPExcel->getActiveSheet()->getColumnDimension($aColumn)->setAutoSize(true);
			}
		}
	
	
		$objPHPExcel->getActiveSheet()->setTitle('Extraction');
	
		$objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
		ob_start();
		$objWriter->save('php://output');
	
		$content = ob_get_clean();
		
		if($saveOnServer){
		
			$iniMapper = new paramsMapper($db);
		
			$today = date_create();
			$today_str = $today->format('YmdHis');
		
			$fileName = $iniMapper->findByNameWithDefault("saveFilename", $today_str . '.xlsx');
	
			// check if file exists and write to it if possible
			try {
				try {
					$file = $storage->get($fileName);
				} catch (\OCP\Files\NotFoundException $e) {
					$file = $storage->newFile($fileName);
				}
	
				// the id can be accessed by $file->getId();
				$file->putContent($content);
			} catch (\OCP\Files\NotPermittedException $e) {
				// you have to create this exception by yourself ;)
				throw new StorageException('Cant write to file');
			}
		}
	
		return $content;
	}

	public function initFiles($folder){
		
	}

	public function initialization(){
		 
		

	}
}