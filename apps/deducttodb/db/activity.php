<?php

namespace OCA\DeductToDB\Db;

use JsonSerializable;
use OCP\AppFramework\Db\Entity;

class Activity extends Entity implements JsonSerializable {

    protected $activityId;
    protected $timestamp;
    protected $priority;
    protected $type;
    protected $user;
    protected $affecteduser;
    protected $app;
    protected $subject;
    protected $subjectparams;
    protected $message;
    protected $messageparams;
    protected $file;
    protected $link;

    public function jsonSerialize() {
        return [
            'activity_id' => $this->activityId,
            'timestamp' => $this->timestamp,
            'priority' => $this->priority,
            'type' => $this->type,
            'user' => $this->user,
            'affecteduser' => $this->affecteduser,
            'app' => $this->app,
            'subject' => $this->subject,
            'subjectparams' => $this->subjectparams,
            'message' => $this->message,
            'messageparams' => $this->messageparams,
            'file' => $this->file,
            'link' => $this->link
        ];
    }

}

?>