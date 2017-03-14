<?php

namespace OCA\DeductToDB\Commands;

use OCA\DeductToDB\Db\DeductFileMapper;
use OCA\DeductToDB\Db\DeductFile;
use OCA\DeductToDB\Db\Photos;
use OCA\DeductToDB\Db\PhotosMapper;

class PhotoCommand extends BaseCommand {

    public function __construct($fileName, $xml, $db) {
        parent::__construct($fileName, $xml, $db);
    }

    function execute($app, $mode, $versionFlag) {

        if ($mode == "predelete") {
            //delete entries of current form
            $path = substr($this->fileName, strrpos($this->fileName, '/') + 1);
            // get id
            $photoMapper = new PhotosMapper($this->db);
            $photoLine = $photoMapper->findByPath($path);
            if (!empty($photoLine)) {
                    $onodeid = $photoLine->getOnodeid();
                    $photoMapper->deletePhotoByOnodeid($onodeid);
            }
            return;
        }

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
