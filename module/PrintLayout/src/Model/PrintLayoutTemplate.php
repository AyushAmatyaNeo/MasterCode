<?php 
namespace PrintLayout\Model;

use Application\Model\Model;

class PrintLayoutTemplate extends Model {
    const TABLE_NAME = "HRIS_PRINT_REPORT_MASTER";

    const PR_ID = "PR_ID";
    const PR_CODE = "PR_CODE";
    const LANG_CODE = "LANG_CODE";
    const SUBJECT = "SUBJECT";
    const BODY = "BODY";
    const CC = "CC";
    const STATUS = "STATUS";
    const CREATED_BY = "CREATED_BY";
    const CREATED_DT = "CREATED_DT";
    const APPROVED_BY = "APPROVED_BY"; 
    const APPROVED_DT = "APPROVED_DT";
    const CHECKED_BY = "CHECKED_BY";
    const CHECKED_DT = "CHECKED_DT";
    const MODIFIED_BY = "MODIFIED_BY";
    const MODIFIED_DT = "MODIFIED_DT";


    public $prId;
    public $prCode;
    public $langCode;
    public $subject;
    public $body;
    public $cc;
    public $status;
    public $createdBy;    
    public $createdDt;    
    public $checkedBy;    
    public $checkedDt;    
    public $approvedBy;
    public $approvedDt;
    public $modifiedBy;
    public $modifiedDt;

    public $mappings = [
        'prId' => self:: PR_ID,
        'prCode' => self:: PR_CODE,
        'langCode' => self:: LANG_CODE,
        'subject' => self:: SUBJECT,
        'body' => self:: BODY,
        'cc' => self:: CC,
        'status' => self:: STATUS,
        'createdBy' => self:: CREATED_BY,
        'createdDt' => self:: CREATED_DT,
        'checkedBy' => self:: APPROVED_BY,
        'checkedDt' => self:: APPROVED_DT,
        'approvedBy' => self:: CHECKED_BY,
        'approvedDt' => self:: CHECKED_DT,
        'modifiedBy' => self:: MODIFIED_BY,
        'modifiedDt' => self:: MODIFIED_DT,
    ];

}