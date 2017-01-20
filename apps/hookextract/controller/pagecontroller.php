<?php

/**
 * ownCloud - hookextract
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Alexy Yurchanka <maly@abat.de>
 * @author Aleh Kalenchanka <malk@abat.de>
 * @copyright Alexy Yurchanka, Aleh Kalenchanka 2016
 */

namespace OCA\HookExtract\Controller;

use OCP\IRequest;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\DownloadResponse;
use OCP\AppFramework\Controller;
use OCA\Deducttodb\Db\EntryMapper;
use OCA\Deducttodb\Db\FormsMapper;
use OCA\Deducttodb\Db\entryArchiveMapper;
use OCA\Deducttodb\Db\paramsMapper;
use OCA\HookExtract\AppInfo\Hookextract;
use OCA\DeductToDB\Storage\StorageException;
use OCA\HookExtract\Http\FileResponse;
use Box\Spout\Reader\ReaderFactory;
use Box\Spout\Common\Type;

require_once "phpexcel/Classes/PHPExcel.php";
require_once "spout/Autoloader/autoload.php";

class PageController extends Controller {

    private $userId;
    private $connection;
    private $db;
    private $app;
    private $container;

    public function __construct($AppName, IRequest $request, $UserId) {
        parent::__construct($AppName, $request);
        $this->userId = $UserId;

        $this->app = new Hookextract($_REQUEST);
        $this->container = $this->app->getContainer();


        $this->connection = $this->container->getServer()->getDatabaseConnection();
        $this->db = $this->container->getServer()->getDb();
    }

    /**
     * CAUTION: the @Stuff turns off security checks; for this page no admin is
     *          required and no CSRF check. If you don't know what CSRF is, read
     *          it up in the docs or you might create a security hole. This is
     *          basically the only required method to add this exemption, don't
     *          add it to any other method if you don't exactly know what it does
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function index() {
        $imgurl = \OC::$server->getURLGenerator()->imagePath('hookextract', 'image002.jpg');

        $iniMapper = new \OCA\DeductToDB\Db\paramsMapper($this->db);

        $recurr = $iniMapper->findByNameWithDefault("conf1_recurrency", "");
        if ($recurr) {
            $params = [
                label => $iniMapper->findByNameWithDefault("conf1_label", ""),
                active => $iniMapper->findByNameWithDefault("conf1_active", "-"),
                everyday => $recurr,
                begin => $iniMapper->findByNameWithDefault("conf1_begin", ""),
                begin_selection => $iniMapper->findByNameWithDefault("conf1_begin_selection", ""),
                end_selection => $iniMapper->findByNameWithDefault("conf1_end_selection", ""),
                plushour => $iniMapper->findByNameWithDefault("conf1_plus1h", ""),
                week1 => $iniMapper->findByNameWithDefault("conf1_week1", ""),
                week2 => $iniMapper->findByNameWithDefault("conf1_week2", ""),
                week3 => $iniMapper->findByNameWithDefault("conf1_week3", ""),
                week4 => $iniMapper->findByNameWithDefault("conf1_week4", ""),
                week5 => $iniMapper->findByNameWithDefault("conf1_week5", ""),
                week6 => $iniMapper->findByNameWithDefault("conf1_week6", ""),
                week7 => $iniMapper->findByNameWithDefault("conf1_week7", ""),
                user => $this->userId,
                imgurl => $imgurl
            ];
        }

        return new TemplateResponse('hookextract', 'main', $params);  // templates/main.php
    }

    /**
     * Simply method that posts back the payload of the request
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function xlsdwnl($formtype, $datefrom, $dateto) {
        $app = new \OCA\Hookextract\AppInfo\Hookextract();
        $content = $app->dbGetXls($formtype, $datefrom, $dateto, $this->db);

        $today = date_create();
        $today_str = $today->format('YmdHis');

        return new FileResponse($today_str . '.xlsx', 'application/xml', $content);
    }

    /**
     * Simply method that posts back the payload of the request
     * @NoAdminRequired
     */
    public function preselect($from, $to) {
        $mapper = new FormsMapper($this->db);
        $arch_mapper = new \OCA\DeductToDB\Db\entryArchiveMapper($this->db);

        $strout = "";
        $strout .= "<table><tr><td>";

        $data = $mapper->selectFormsbyInterval($from, $to);
        $archive = [];
        $data_uniq = [];
        foreach ($data as $line_u) {
            $data_uniq[] = $line_u->getType();
        }

        $data_uniq = array_unique($data_uniq);
        $strout .= "<p>From:" . $from . " To: " . $to . "</p>";
        $strout .= "<br><p><select name='presel' id='presel' multiple='yes' size='6' style='height:100px'>";
        sort($data_uniq);
        foreach ($data_uniq as $line) {
            $strout .= "<option value='" . $line . "'>" . $line . "</option>";
        }
        $strout .= "</select></p>";

        $archive = $arch_mapper->findByDate($from, $to);
        if (count($archive) >= 0) {
            $strout .= '<br/><p>Corresponding archive records found. Select?';
            $strout .= '<input type="checkbox" name="archive" id="archive" /></p>';
        }

        $strout .= '<br/><p><button id="select">Select</button></p>';
        $strout .= "</td><td>";

        $strout .= "<p>Fields control</p>";
        $strout .= "<table>";
        $strout .= "		<tr><td>";
        $strout .= "		</td>";
        $strout .= "		<td></td>";
        $strout .= "</tr>";
        $strout .= "		<tr><td>";
        $strout .= "		</td>";
        $strout .= "		<td></td>";
        $strout .= "</tr>";
        $strout .= "</table>";

        $strout .= "</td></tr></table>";
        return new DataResponse($strout);
    }

    /**
     * Simply method that posts back the payload of the request
     * @NoAdminRequired
     */
    public function select($formtype, $datefrom, $dateto) {
        $params = ['user' => $this->userId];
        $mapper = new EntryMapper($this->db);

        $in_array = split(";", $formtype);

        $strout = "<p>Data preview:</p>";

        $headers = [];
        $output = [];

        foreach ($in_array as $type) {
            $data = $mapper->findByFormType($type, $datefrom, $dateto);
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

        $strout .= '<p><table border="1">';
        foreach ($headers as $headerkey => $headerval) {
            $strout .= '<th>';
            $strout .= $headerkey;
            $strout .= '</th>';
        }
        foreach ($output as $outline) {
            $strout .= '<tr>';
            foreach ($headers as $headerkey => $headerval) {
                $strout .= '<td>';
                $strout .= $outline[$headerkey];
                $strout .= '</td>';
            }
            $strout .= '</tr>';
        }

        $strout .= '</table></p><br/>';

        $strout .= '<p><button id="exceldownl">Excel download</button></p>';
        return new DataResponse($strout);
    }

    /**
     * Simply method that posts back the payload of the request
     * @NoAdminRequired
     */
    public function doEcho($echo) {
        return new DataResponse(['echo' => $echo]);
    }

    /**
     * Simply method that posts back the payload of the request
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function timers($active, $label, $everyday, $begin, $plushour, $week1, $week2, $week3, $week4, $week5, $week6, $week7, $begin_selection, $end_selection) {

        $iniMapper = new \OCA\DeductToDB\Db\paramsMapper($this->db);
        $iniMapper->setByName("conf1_active", $active);

        if ($everyday == "1") {  //  every day recurrency
            $iniMapper->setByName("conf1_label", $label);
            $iniMapper->setByName("conf1_recurrency", "daily");
            $iniMapper->setByName("conf1_begin", $begin);
            $iniMapper->setByName("conf1_begin_selection", $begin_selection);
            $iniMapper->setByName("conf1_end_selection", $end_selection);
            $iniMapper->setByName("conf1_plus1h", $plushour);
        } else {
            $iniMapper->setByName("conf1_recurrency", "weekly");
        }
        $params = [];

        $imgurl = \OC::$server->getURLGenerator()->imagePath('hookextract', 'image002.jpg');

        $recurr = $iniMapper->findByNameWithDefault("conf1_recurrency", "");
        if ($recurr) {
            $params = [
                label => $iniMapper->findByNameWithDefault("conf1_label", ""),
                active => $iniMapper->findByNameWithDefault("conf1_active", "-"),
                everyday => $recurr,
                begin => $iniMapper->findByNameWithDefault("conf1_begin", ""),
                begin_selection => $iniMapper->findByNameWithDefault("conf1_begin_selection", ""),
                end_selection => $iniMapper->findByNameWithDefault("conf1_end_selection", ""),
                plushour => $iniMapper->findByNameWithDefault("conf1_plus1h", ""),
                week1 => $iniMapper->findByNameWithDefault("conf1_week1", ""),
                week2 => $iniMapper->findByNameWithDefault("conf1_week2", ""),
                week3 => $iniMapper->findByNameWithDefault("conf1_week3", ""),
                week4 => $iniMapper->findByNameWithDefault("conf1_week4", ""),
                week5 => $iniMapper->findByNameWithDefault("conf1_week5", ""),
                week6 => $iniMapper->findByNameWithDefault("conf1_week6", ""),
                week7 => $iniMapper->findByNameWithDefault("conf1_week7", ""),
                user => $this->userId,
                imgurl => $imgurl
            ];
        }

        return new TemplateResponse('hookextract', 'part.content', $params);
    }

    /**
     * Simply method that posts back the payload of the request
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function upload($filepath) {
        if (isset($_FILES['filepath'])) {
            if ($_FILES['filepath']['tmp_name'] && !$_FILES['filepath']['error']) {
                $inputFile = $_FILES['filepath']['tmp_name'];
//                try {
//                    $inputFileType = \PHPExcel_IOFactory::identify($inputFile);
//                    $objReader = \PHPExcel_IOFactory::createReader($inputFileType);
//                    $objPHPExcel = $objReader->load($inputFile);
//                } catch (Exception $ex) {
//                    echo $ex->getMessage();
//                }
//
//                $objWorksheet = $objPHPExcel->getActiveSheet();
//                $highestRow = $objWorksheet->getHighestRow();
//                $highestColumn = $objWorksheet->getHighestColumn();
//
//                $keys = $objWorksheet->rangeToArray('A1:' . $highestColumn . '1', NULL, TRUE, FALSE);
//                $data = $objWorksheet->rangeToArray('A2:' . $highestColumn . $highestRow, NULL, TRUE, FALSE);

                $reader = ReaderFactory::create(Type::XLSX);
                $reader->setShouldFormatDates(true);
                $reader->open($inputFile);
                foreach ($reader->getSheetIterator() as $sheet) {
                    foreach ($sheet->getRowIterator() as $row) {
                        $data[] = $row;
                    }
                }
                $keys = array_shift($data);

                // if the 1st row is empty then the whole document is empty
                $errors = array_filter($data[0]);
                if (empty($errors) == false) {
                    $mapper = new EntryArchiveMapper($this->db);
                    $inserted = $mapper->insertFromArray($keys, $data);
                    if ($inserted) {
                        $insertFlag = count($data);
                    } else {
                        $insertFlag = -1;
                    }
                } else {
                    $insertFlag = -1;
                }
            } else {
                // echo "An error occured during uploading";
                $insertFlag = -1;
            }
        } else {
            //echo "File hasn't been uploaded";
            $insertFlag = -1;
        }
        $imgurl = \OC::$server->getURLGenerator()->imagePath('hookextract', 'image002.jpg');
        $params = ['user' => $this->userId, 'imgurl' => $imgurl, 'upload' => $insertFlag];
        return new TemplateResponse('hookextract', 'main', $params);
    }

}
