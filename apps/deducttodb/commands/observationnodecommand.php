<?php

namespace OCA\DeductToDB\Commands;

use OCA\DeductToDB\Db\ObservationNodeMapper;
use OCA\DeductToDB\Db\ObservationNode;
use OCA\DeductToDB\Db\Locations;
use OCA\DeductToDB\Db\LocationsMapper;
use OCA\DeductToDB\Db\Photos;
use OCA\DeductToDB\Db\PhotosMapper;
use OCA\DeductToDB\Db\Forms;
use OCA\DeductToDB\Db\FormsMapper;

class ObservationNodeCommand extends BaseCommand {

    public function __construct($fileName, $xml, $db) {
        parent::__construct($fileName, $xml, $db);
    }
    
     function executeForDeleted() {
         
            //$uuid = $this->xml->uuid;
            $file = substr($this->fileName,strrpos($this->fileName, '/') + 1);
            $uuid = substr($file,0,strpos($file, '_'));
            
            $mapper = new ObservationNodeMapper($this->db);
            $obnode = $mapper->findByUuid($uuid);
            if ($obnode) {
                $nodeId = $obnode->getId();
                $mapper->deleteNodeById($nodeId);
                //delete locations
                $locationMapper = new LocationsMapper($this->db);
                $locationLine = $locationMapper->findByOnodeId($nodeId);
                if (!empty($locationLine)) {
                        $id = $locationLine->getId();
                        $locationMapper->deleteById($id);
                }
            }
            return;
     }

    function execute($app, $mode, $versionFlag) {

        if ($mode == "predelete") {

            //$uuid = $this->xml->uuid;
            $mapper = new ObservationNodeMapper($this->db);
            $obnode = $mapper->findByUuid((string) $this->xml->uuid);
            if ($obnode) {
                $nodeId = $obnode->getId();
                $mapper->deleteNodeById($nodeId);
                //delete locations
                $locationMapper = new LocationsMapper($this->db);
                $locationLine = $locationMapper->findByOnodeId($nodeId);
                if (!empty($locationLine)) {
                        $id = $locationLine->getId();
                        $locationMapper->deleteById($id);
                }
            }

            return;
        }

        if ($versionFlag) {
            return;
        }

        $node = new ObservationNode();

        $root = $app->getContainer()->query('ServerContainer')->getRootFolder();

        $finfo = \OC\Files\Filesystem::getFileInfo($this->fileName);

        $owner = \OC\Files\Filesystem::getOwner($this->fileName);

        $stat = \OC\Files\Filesystem::stat($this->fileName);

        //$xml  = $app->getContainer()->query('XmlFactory')->makeXml($finfo->getId());
        $mainTag = (string) $this->xml->getName();
        $localFileName = substr(strrchr($this->fileName, "/"), 1);

        if ($mainTag == 'node') {
            $node->setTitle(trim((string) $this->xml->title));
            $node->setUuid((string) $this->xml->uuid);

            $node->setCreatedby((string) $this->xml->created_by);
            $node->setCreatedat((string) $this->xml->created_at);

            $localType = (string) $this->xml->attributes()->type;

            $node->setType($localType);

            $locationCnt = 0;
            if ($this->xml->locations->loc) {
                $locationCnt = (string) $this->xml->locations->loc->count();
            }

            $mapper = new ObservationNodeMapper($this->db);
            $obnode = $mapper->findByUuid((string) $this->xml->uuid);
            if (!$obnode) {
                $newNode = $mapper->insert($node);
            } else {
                $node->setId($obnode->getId());
                $newNode = $mapper->update($node);
            }

            if ($locationCnt > 0) {
                for ($i = 0; $i < $locationCnt; $i++) {
                    $location = new Locations();
                    $location->setLatitude((string) $this->xml->locations->loc[$i]->attributes()->lat);
                    $location->setLongtitude((string) $this->xml->locations->loc[$i]->attributes()->lon);
                    $location->setOnodeid($newNode->getId());

                    $locationMapper = new LocationsMapper($this->db);
                    $locationOnode = $locationMapper->findByOnodeId($newNode->getId());
                    if (!$locationOnode) {
                        $locationMapper->insert($location);
                    } else {
                        $location->setId($locationOnode->getId());
                        $locationMapper->update($location);
                    }
                }
            }

            $rscCnt = (string) $this->xml->resources->children()->count();
            if ($rscCnt > 0) {
                for ($i = 0; $i < $rscCnt; $i++) {
                    $childType = (string) $this->xml->resources->children()[$i]->getName();
                    if ($childType == "photo") {
                        $photo = new Photos();

                        $photo->setOnodeid($newNode->getId());
                        $photo->setPath((string) $this->xml->resources->children()[$i]->path);
                        $photo->setLatitude((string) $this->xml->resources->children()[$i]->loc->attributes()->lat);
                        $photo->setLongtitude((string) $this->xml->resources->children()[$i]->loc->attributes()->lon);

                        $photo->setGpsaccuracy((string) $this->xml->resources->children()[$i]->gps_accuracy);

                        $photoMapper = new PhotosMapper($this->db);
                        $photoOnode = $photoMapper->findByOnodeid($newNode->getId());
                        if (!$photoOnode) {
                            $photoMapper->insert($photo);
                        } else {
                            $photo->setId($photoOnode->getId());
                            $photoMapper->update($photo);
                        }
                        
                    }
                    if ($childType == "form") {
                        $form = new Forms();

                        $form->setPath((string) $this->xml->resources->children()[$i]);
                        $localFormName = substr(strrchr((string) $this->xml->resources->children()[$i], "/"), 1);
                        if (!$localFormName) {
                            $localFormName = (string) $this->xml->resources->children()[$i];
                        }

                        $form->setOnodeid($newNode->getId());

                        $formMapper = new FormsMapper($this->db);
                        $formExists = $formMapper->findByPath($localFormName);
                        if (!$formExists) {
                            $formMapper->insert($form);
                        } else {
                            $form->setId($formExists->getId());
                            $formMapper->update($form);
                        }
                    }
                }
            }
        }




        //$node->setTitle((string)$this->xml->attributes()->version);
    }

}

?>