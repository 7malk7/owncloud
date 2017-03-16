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

    }

}

?>
