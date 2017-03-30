<?php
namespace OCA\DeductToDB\Commands;

use OCA\DeductToDB\Db\ProjectsMapper;
use OCA\DeductToDB\Db\Projects;
use OCA\DeductToDB\Db\ObservationNodeMapper;
use OCA\DeductToDB\Db\ObservationNode;
use OCA\DeductToDB\Db\Locations;
use OCA\DeductToDB\Db\LocationsMapper;
use OCA\DeductToDB\Db\Entry;
use OCA\DeductToDB\Db\EntryMapper;
use OCA\DeductToDB\Db\Photos;
use OCA\DeductToDB\Db\PhotosMapper;
use OCA\DeductToDB\Db\Forms;
use OCA\DeductToDB\Db\FormsMapper;
use OCA\DeductToDB\AppInfo\DeductToDB;

class ProjectCommand extends BaseCommand {

    public function __construct($fileName, $xml, $db) {
        parent::__construct($fileName, $xml, $db);
    }
    
     function executeForDeleted() {
            // delete project
            //$folderName = substr($this->fileName, strrpos($this->fileName, '/') + 1);
         
           $folders = split("/", $this->fileName);
            if (count($folders) >= 2) {
                 $folderName = $folders[1];
            }
            
           // $folderName = split("/", $this->fileName);
            $projectMapper = new ProjectsMapper($this->db);
            $projectLine = $projectMapper->findByFolder($folderName);
            if (!empty($projectLine)) {
                $uuid = $projectLine->getUuid();
                $projectMapper->deleteByUUID($uuid);
            }
     }

    function execute($app, $mode, $versionFlag) {

        if ($mode == "predelete") {
            $data = array();
            $fileList = \OC\Files\Filesystem::getDirectoryContent($this->fileName);
            foreach ($fileList as $fileInfo) {

                $newPath = $this->fileName . '/' . $fileInfo['name'];
                $content = \OC\Files\Filesystem::getDirectoryContent($newPath);

                foreach ($content as $node) {
                    $nodeType = $node->getType();
                    $mimeType = $node->getMimetype();
                    $name = $node->getName();
                    $folder = $newPath;
                    $newPath .= '/' . $name;

                    // 3 types of file: form, node, picture
                    if ($nodeType === 'file') {
                        switch ($mimeType) {
                            case 'application/xml':
                                if (strrpos($name, '_Node') > 0) {
                                    $data[$node->getEtag()] = [
                                        'name' => $name,
                                        'mediatype' => $mimeType,
                                        'id' => $node->getId(),
                                        'type' => 'node',
                                    ];
                                } else {
                                    $data[$node->getEtag()] = [
                                        'name' => $name,
                                        'mediatype' => $mimeType,
                                        'id' => $node->getId(),
                                        'type' => 'form',
                                    ];
                                }
                                break;
                            case 'image/jpeg':
                                $data[$node->getEtag()] = [
                                    'name' => $name,
                                    'mediatype' => $mimeType,
                                    'id' => $node->getId(),
                                    'type' => 'photo',
                                ];
                                break;
                        }
                    }
                }
            }
            $app = new DeductToDB();
            $c = $app->getContainer();
            foreach ($data as $file) {
                $path = substr($file['name'], strrpos($file['name'], '/') + 1);
                switch ($file['type']) {
                    case 'photo':
                        $photoMapper = new PhotosMapper($this->db);
                        $photoLine = $photoMapper->findByPath($path);
                        if (!empty($photoLine)) {
                                $onodeid = $photoLine->getOnodeid();
                                $photoMapper->deletePhotoByOnodeid($onodeid);
                        }
                        break;
                    case 'node':
                        $mapper = new ObservationNodeMapper($this->db);
                        $xml = $c->query('XmlFactory')->makeXml($file['id']);
                        $obnode = $mapper->findByUuid((string) $xml->uuid);
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
                        break;
                    case 'form':
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
                        break;
                }
            }

            // delete project
            $folderName = substr($this->fileName, strrpos($this->fileName, '/') + 1);
            $projectMapper = new ProjectsMapper($this->db);
            $projectLine = $projectMapper->findByFolder($folderName);
            if (!empty($projectLine)) {
                $uuid = $projectLine->getUuid();
                $projectMapper->deleteByUUID($uuid);
            }
        }

        $project = new Projects();

        $root = $app->getContainer()->query('ServerContainer')->getRootFolder();

        $finfo = \OC\Files\Filesystem::getFileInfo($this->fileName);

        $owner = \OC\Files\Filesystem::getOwner($this->fileName);

        $stat = \OC\Files\Filesystem::stat($this->fileName);

        //$xml  = $app->getContainer()->query('XmlFactory')->makeXml($finfo->getId());

        $project->setName((string) $this->xml->name);
        $project->setCreatedby((string) $this->xml->created_by);
        $project->setCreatedat((string) $this->xml->created_at);
        $project->setOwner((string) $this->xml->owner);
        $project->setUuid((string) $this->xml->uuid);
        //$project->setVersion((string)$this->xml->attributes()->version);
        $folderName = split("/", $this->fileName);
        if (count($folderName) >= 2) {
            $project->setFoldername($folderName[1]);
        }

        $mapper = new ProjectsMapper($this->db);

        if (!$mapper->findByUuid((string) $this->xml->uuid)) {
            $mapper->insert($project);
        }
        else{
            $project = $mapper->findByUuid((string) $this->xml->uuid);
            $project->setName((string) $this->xml->name);
            $project->setCreatedby((string) $this->xml->created_by);
            $project->setCreatedat((string) $this->xml->created_at);
            $project->setOwner((string) $this->xml->owner);
            $mapper->update($project);
        }
    }

}

?>