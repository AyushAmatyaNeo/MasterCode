<?php

namespace Notification\Model;

use Application\Model\Model;

class EmailTemplate extends Model
{

    const TABLE_NAME = "HRIS_EMAIL_TEMPLATE";
    const ID = "ID";
    const SUBJECT = "SUBJECT";
    const DESCRIPTION = "DESCRIPTION";
    const CC = "CC";
    const BCC = "BCC";
    const CREATED_BY = "CREATED_BY";
    const CREATED_DT = "CREATED_DT";
    const MODIFIED_BY = "MODIFIED_BY";
    const MODIFIED_DT = "MODIFIED_DT";

    public $id;
    public $subject;
    public $description;
    public $cc;
    public $bcc;
    public $createdBy;
    public $createdDt;
    public $modifiedBy;
    public $modifiedDt;
    public $mappings = [
        'id' => self::ID,
        'subject' => self::SUBJECT,
        'description' => self::DESCRIPTION,
        'cc' => self::CC,
        'bcc' => self::BCC,
        'createdBy' => self::CREATED_BY,
        'createdDt' => self::CREATED_DT,
        'modifiedBy' => self::MODIFIED_BY,
        'modifiedDt' => self::MODIFIED_DT,
    ];
}
