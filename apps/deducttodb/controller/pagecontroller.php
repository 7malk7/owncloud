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
use OCA\HookExtract\AppInfo\Hookextract;
use OCA\DeductToDB\Storage\StorageException;
use OCA\HookExtract\Http\FileResponse;

require_once "phpexcel/Classes/PHPExcel.php";

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
        $params = ['user' => $this->userId, 'imgurl' => $imgurl];
        return new TemplateResponse('hookextract', 'main', $params);  // templates/main.php
    }

    /**
     * Simply method that posts back the payload of the request
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function xlsdwnl($formtype, $datefrom, $dateto) {
        $mapper = new EntryMapper($this->db);

        $objPHPExcel = new \PHPExcel();

        $objPHPExcel->getProperties()->setTitle("Extraction")
                ->setSubject("Extraction")
                ->setDescription("Extraction")
                ->setKeywords("Extraction");

        $data = $mapper->findByFormType($formtype, $datefrom, $dateto);

        $newData = array();
        foreach ($data as $row) {
            $newRow = $row->jsonSerialize();
            array_push($newData, $newRow);
        }
        
        $keys = array_keys($newRow);
        $objPHPExcel->setActiveSheetIndex(0);
        $objPHPExcel->getActiveSheet()->fromArray($keys, null, 'A1');
        $objPHPExcel->getActiveSheet()->getStyle('A1:Z1')->getFont()->setBold(true);
        $objPHPExcel->getActiveSheet()->fromArray($newData, null, 'A2');
        $columns = array('C','D','E','G');
        foreach ($columns as $column) {
            $objPHPExcel->getActiveSheet()->getColumnDimension($column)->setAutoSize(true);
        }

        $objPHPExcel->getActiveSheet()->setTitle('Extraction');

        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        ob_end_clean();
        $objWriter->save('output.xls');

        $content = file_get_contents('output.xls');
        $storage = $this->app->getUserFolder();

        // check if file exists and write to it if possible
        try {
            try {
                $file = $storage->get('myfile.xls');
            } catch (\OCP\Files\NotFoundException $e) {
                $file = $storage->newFile('myfile.xls');
            }

            // the id can be accessed by $file->getId();
            $file->putContent($content);
        } catch (\OCP\Files\NotPermittedException $e) {
            // you have to create this exception by yourself ;)
            throw new StorageException('Cant write to file');
        }

        return new FileResponse('myfile.xls', 'application/xml', $content);
    }

    /**
     * Simply method that posts back the payload of the request
     * @NoAdminRequired
     */
    public function preselect($from, $to) {
        $mapper = new FormsMapper($this->db);

        $data = $mapper->selectFormsbyInterval($from, $to);
        //var_dump($data);
        $data_uniq = [];
        foreach ($data as $line_u) {
            $data_uniq[] = $line_u->getType();
        }

        $data_uniq = array_unique($data_uniq);
        $strout = "<p>From:" . $from . " To: " . $to . "</p>";
        $strout .= "<br><p><select name='presel' id='presel' multiple='yes' size='6' style='height:100px'>";
        sort($data_uniq);
        foreach ($data_uniq as $line) {
            $strout .= "<option value='" . $line . "'>" . $line . "</option>";
        }
        $strout .= "</select></p>" . '<br/><p><button id="select">Select</button></p>';
        return new DataResponse($strout);
    }

    /**
     * Simply method that posts back the payload of the request
     * @NoAdminRequired
     */
    public function select($formtype, $datefrom, $dateto) {
        $params = ['user' => $this->userId];
        $mapper = new EntryMapper($this->db);

        $data = $mapper->findByFormType($formtype, $datefrom, $dateto);
        $strout = "<p>Data preview:</p>";

        //var_dump($data);
        $headers = [];
        $output = [];
// 		for($i = 0; $i < count($data); $i++)
// 		{
// 			$output[$i] = [];
// 		}
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

        //var_dump($output);
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

}
