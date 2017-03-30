<?php

namespace OCA\DeductToDB\Commands;

use OCA\DeductToDB\Db\DeductFileMapper;
use OCA\DeductToDB\Db\DeductFile;
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

class FilesCommand extends BaseCommand {

    public function __construct($fileName, $xml, $db) {
        parent::__construct($fileName, $xml, $db);
    }

    function execute($app, $mode, $versionFlag) {

        ////////////////////////////////////////////////////////////////
        $name = substr($this->fileName, strrpos($this->fileName, '/') + 1);

        if ($mode == "predelete" && !$this->xml && strpos($name, "Project") !== false) {
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
                $path = substr($file['name'], strrpos($file['name'], '/'));
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

        ////////////////////////////////////////////////////////////////



        $file = new DeductFile();

        $file->setPath($this->fileName);
        $file->setType($mode);

        $root = $app->getContainer()->query('ServerContainer')->getRootFolder();

        $finfo = \OC\Files\Filesystem::getFileInfo($this->fileName);

        $owner = \OC\Files\Filesystem::getOwner($this->fileName);

        $file->setCreator($owner);
        $stat = \OC\Files\Filesystem::stat($this->fileName);

        $today = date_create();
        $today_str = $today->format('Y-m-d H:i:s');
        $ctime = date('Y-m-d H:i:s', $stat['ctime']);
        $mtime = date('Y-m-d H:i:s', $stat['mtime']);

        if (!$ctime) {
            $ctime = $mtime;
            if (!$ctime) {
                $ctime = $today_str;
            }
        }

        if (!$mtime) {
            $mtime = date('Y-m-d H:i:s', $stat['ctime']);
            if (!$mtime) {
                $mtime = $today_str;
            }
        }

        $file->setCreatedat($ctime);
        $file->setModified($mtime);

        $mapper = new DeductFileMapper($app->getContainer()->getServer()->getDb());
        $mapper->insert($file);
    }

}

?>