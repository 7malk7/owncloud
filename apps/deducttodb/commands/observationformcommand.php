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
            $formId = $formsLine->getId();
            $entryMapper = new EntryMapper($this->db);
            $entryLine = $entryMapper->findByFormId($formId);
            // delete entries
            if (!empty($entryLine)) {
                $entryMapper->deleteEntriesByFormid($formId);
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
                $formId = $formsLine->getId();
                $entryMapper = new EntryMapper($this->db);
                $entryLine = $entryMapper->findByFormId($formId);
                // delete entries
                if (!empty($entryLine)) {
                    $entryMapper->deleteEntriesByFormid($formId);
                }
            }

            return;
        }

        if ($versionFlag) {
            return;
        }

        $root = $app->getContainer()->query('ServerContainer')->getRootFolder();
        $finfo = \OC\Files\Filesystem::getFileInfo($this->fileName);
        $owner = \OC\Files\Filesystem::getOwner($this->fileName);
        $stat = \OC\Files\Filesystem::stat($this->fileName);

        $mainTag = (string) $this->xml->getName();
        $localFileName = substr(strrchr($this->fileName, "/"), 1);

        $uuid = "";
        if (strlen($localFileName) >= 36) {
            $uuid = substr($localFileName, 0, 36);
        }
        $newRecord = false;

        if ($mainTag == 'form') {
            $formsMapper = new FormsMapper($this->db);
            $node = $formsMapper->findByPath($localFileName);

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
        }
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