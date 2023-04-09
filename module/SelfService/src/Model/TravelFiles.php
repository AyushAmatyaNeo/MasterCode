<?php
namespace SelfService\Model;

use Application\Model\Model;

class TRAVELFILES extends Model{
    const TABLE_NAME = "HRIS_TRAVEL_FILES";
    const FILE_ID = "FILE_ID";
    const FILE_NAME = "FILE_NAME";
    const TRAVEL_ID = "TRAVEL_ID";
    const FILE_IN_DIR_NAME = "FILE_IN_DIR_NAME";
    const UPLOADED_DATE = "UPLOADED_DATE";
    
    public $fileId;
    public $travelId;
    public $fileName;
    public $fileInDirName;
    public $uploadedDate;
   
    public $mappings= [
        'fileId'=>self::FILE_ID,
        'travelId'=>self::TRAVEL_ID,
        'fileName'=>self::FILE_NAME,
        'fileInDirName'=>self::FILE_IN_DIR_NAME,
        'uploadedDate'=>self::UPLOADED_DATE,
    ];   
}
