<?php

namespace OCA\DeductToDB\Commands;

use OCA\DeductToDB\Db\ObservationNodeMapper;
use OCA\DeductToDB\Db\ObservationNode;
use OCA\DeductToDB\Db\Forms;
use OCA\DeductToDB\Db\FormsMapper;
use OCA\DeductToDB\Db\Entry;
use OCA\DeductToDB\Db\EntryMapper;
use Doctrine\DBAL\Types\Type;

class ObservationFormCommand extends BaseCommand {

    public function __construct($fileName, $xml, $db) {
        parent::__construct($fileName, $xml, $db);
    }

    
        function executeForDeleted() {
            //delete entries of current form
            $path = substr($this->fileName, strrpos($this->fileName, '/') + 1);
            // get onodeid
            $formsMapper = new FormsMapper($this->db);
            $formsLine = $formsMapper->findByPath($path);

            if (!empty($formsLine)) {
                $onodeid = $formsLine->getOnodeid();
                $formsMapper->deleteByOnodeId($onodeid);
                // check entry
                $entryMapper = new EntryMapper($this->db);
                $entryLine = $entryMapper->findByFormId($onodeid);
                // delete entries
                if (!empty($entryLine)) {
                    $entryMapper->deleteEntriesByFormid($onodeid);
                }
            }
            return;
        }
    
    
    function execute($app, $mode, $versionFlag) {

        if ($mode == "predelete") {
            //delete entries of current form
            $path = substr($this->fileName, strrpos($this->fileName, '/') + 1);
            // get onodeid
            $formsMapper = new FormsMapper($this->db);
            $formsLine = $formsMapper->findByPath($path);

            if (!empty($formsLine)) {
                $onodeid = $formsLine->getOnodeid();
                $formsMapper->deleteByOnodeId($onodeid);
                // check entry
                $entryMapper = new EntryMapper($this->db);
                $entryLine = $entryMapper->findByFormId($onodeid);
                // delete entries
                if (!empty($entryLine)) {
                    $entryMapper->deleteEntriesByFormid($onodeid);
                }
            }
            
           // $onodeid = $formsLine->getOnodeid();
            // check entry
           /* $entryMapper = new EntryMapper($this->db);
            $entryLine = $entryMapper->findByFormId($onodeid);
            // delete entries
            if (!empty($entryLine)) {
                $rowCount = $entryMapper->deleteEntriesByFormid($onodeid);
            }*/

            return;
        }

        if ($versionFlag) {
            return;
        }

        //$node = new Forms();

        $root = $app->getContainer()->query('ServerContainer')->getRootFolder();

        $finfo = \OC\Files\Filesystem::getFileInfo($this->fileName);

        $owner = \OC\Files\Filesystem::getOwner($this->fileName);

        $stat = \OC\Files\Filesystem::stat($this->fileName);

        //$xml  = $app->getContainer()->query('XmlFactory')->makeXml($finfo->getId());
        $mainTag = (string) $this->xml->getName();
        $localFileName = substr(strrchr($this->fileName, "/"), 1);

        $uuid = "";
        if (strlen($localFileName) >= 36) {
            $uuid = substr($localFileName, 0, 36);
        }
        $newRecord = false;

        if ($mainTag == 'form') {
            $formsMapper = new FormsMapper($this->db);
            $node = $formsMapper->findByPath($localFileName); //$this->fileName

            if (!$node) {
                $node = new Forms();
                $newRecord = true;
            }

            $node->setUuid($uuid);
            $node->setCreatedat((string) $this->xml->created_at);

            $localTitle = (string) $this->xml->title;
            $localType = (string) $this->xml->type;

            $observation = new ObservationNodeMapper($this->db);
            $observationNode = $observation->findByUuid($uuid);
            if ($observationNode) {
                $node->setOnodeid($observationNode->getId());
                $node->setOwner($observationNode->getCreatedby());
            } else {
                $node->setOwner($owner);
            }

            $node->setType($localType);
            $node->setTitle($localTitle);
            $node->setPath($localFileName);


            $folderName = split("/", $this->fileName);
            if (count($folderName) >= 2) {
                $node->setFoldername($folderName[1]);
            }


            if ($newRecord) {
                $formsMapper->insert($node);
            } else {
                $formsMapper->update($node);
            }

            $tagsCount = (string) $this->xml->count();

            for ($i = 0; $i < $tagsCount; $i++) {
                $curTag = $this->xml->children()[$i];

                $entryMapper = new EntryMapper($this->db);
                $newEntry = false;
                if ($curTag->getName() == "entry") {
                    // check if the entry is already in the table
                    $entryLine = $entryMapper->findByFormIdAndKey($node->getId(), (string) $curTag->key);

                    if ($entryLine) {
                        // if line exists - we put a change document with CHANGE
                    } else {
                        // if line not exists  - we put a change document with NEW
                        $newEntry = true;
                        $entryLine = new Entry();
                    }
                    $entryLine->setFormid($node->getId());
                    $entryLine->setType((string) $curTag->type);
                    $entryLine->setValue((string) $curTag->value);
                    $entryLine->setKey((string) $curTag->key);
                    $entryLine->setValuedefault((string) $curTag->value->attributes()->default);
                    $entryLine->setOrder((string) $curTag->order);
                    $entryLine->setModified((string) $curTag->modified);

                    if ($newEntry) {
                        $entryMapper->insert($entryLine);
                    } else {
                        $entryMapper->update($entryLine);
                    }
                }
            }



// 			if(preg_match ( '/Evidence/' , $localTitle ) && preg_match ("/Evidence Form/", $localType)){
// 				$node->setType("1");
// 			}
// 			$locationCnt = (string)$this->xml->locations->loc->count();
// 			$mapper = new ObservationNodeMapper($this->db);
// 			$newNode = $mapper->insert($node);
// 			if($locationCnt > 0){
// 				for($i = 0; $i < $locationCnt; $i++){
// 				$location = new Locations();
// 			    $location->setLatitude((string)$this->xml->locations->loc[$i]->attributes()->lat);
// 				$location->setLongtitude((string)$this->xml->locations->loc[$i]->attributes()->lon);
// 				$location->setOnode_id($newNode->getId());
// 				$locationMapper = new LocationsMapper($this->db);
// 				$locationMapper->insert($location);
// 				}
// 			}
// 			$rscCnt = (string)$this->xml->resources->children()->count();
// 			if($rscCnt > 0){
// 				for($i = 0; $i < $rscCnt; $i++){
// 					$childType = (string)$this->xml->resources->children()[$i]->getName();
// // 					if($childType == "photo"){
// // 						$photo = new Photos();
// // 						$photo->setOnode_id($newNode->getId());
// // 						$photo->setPath((string)$this->xml->resources->children()[$i]->path);
// // 						$photo->setLatitude((string)$this->xml->resources->children()[$i]->loc->attributes()->lat);
// // 						$photo->setLongtitude((string)$this->xml->resources->children()[$i]->loc->attributes()->lon);
// // 						$photo->setGpsaccuracy((string)$this->xml->resources->children()[$i]->gps_accuracy);
// // 						$photoMapper = new PhotosMapper($this->db);
// // 						$photoMapper->insert($photo);
// // 					}
// // 					if($childType == "form"){
// // 						$form = new Forms();
// // 						$form->setPath((string)$this->xml->resources->children()[$i]);
// // 						$form->setOnode_id($newNode->getId());
// // 						$formMapper = new FormsMapper($this->db);
// // 						$formMapper->insert($form);
// // 					}
// 				}
// 			}
        }




        //$node->setTitle((string)$this->xml->attributes()->version);
    }

    public static function deleteEntry($data, $db) {

        foreach ($data as $file) {

            $formsMapper = new FormsMapper($db);
            $formsLine = $formsMapper->findByPath($file['name']);
            $onodeid = $formsLine->getOnodeid();

            $entryMapper = new EntryMapper($db);
            $entryLine = $entryMapper->findByFormId($onodeid);
            if (!empty($entryLine)) {
                $rowCount = $entryMapper->deleteEntriesByFormid($onodeid);
            }
        }
    }

}

?>