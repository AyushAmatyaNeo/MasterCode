<?php
namespace SelfService\Model;

use Application\Model\Model;

class TravelRequest extends Model{
    const TABLE_NAME = "HRIS_EMPLOYEE_TRAVEL_REQUEST";
    const TRAVEL_ID = "TRAVEL_ID";
    const EMPLOYEE_ID = "EMPLOYEE_ID";
    const REQUESTED_DATE = "REQUESTED_DATE";
    const FROM_DATE = "FROM_DATE";
    const TO_DATE = "TO_DATE";
    const DESTINATION = "DESTINATION";
    Const DEPARTURE = "DEPARTURE";
    const PURPOSE = "PURPOSE";
    const REQUESTED_TYPE = "REQUESTED_TYPE";
    const REQUESTED_AMOUNT = "REQUESTED_AMOUNT";
    const REMARKS = "REMARKS";
    const STATUS = "STATUS";
    const RECOMMENDED_DATE = "RECOMMENDED_DATE";
    const RECOMMENDED_BY = "RECOMMENDED_BY";
    const RECOMMENDED_REMARKS = "RECOMMENDED_REMARKS";
    const APPROVED_DATE = "APPROVED_DATE";
    const APPROVED_BY = "APPROVED_BY";
    const APPROVED_REMARKS = "APPROVED_REMARKS";
    const TRAVEL_CODE = "TRAVEL_CODE";
    const REFERENCE_TRAVEL_ID = "REFERENCE_TRAVEL_ID";
    const DEPARTURE_DATE = "DEPARTURE_DATE";
    const RETURNED_DATE = "RETURNED_DATE";
    const TRANSPORT_TYPE = "TRANSPORT_TYPE";
    const HARDCOPY_SIGNED_FLAG = "HARDCOPY_SIGNED_FLAG";
    const ITNARY_ID = "ITNARY_ID";
    
    public $travelId;
    public $employeeId;
    public $requestedDate;
    public $fromDate;
    public $toDate;
    public $destination;
    Public $departure;
    public $purpose;
    public $requestedType;
    public $requestedAmount;
    public $remarks;
    public $status;
    public $recommendedDate;
    public $recommendedBy;
    public $recommendedRemarks;
    public $approvedDate;
    public $approvedBy;
    public $approvedRemarks;
    public $travelCode;
    public $referenceTravelId;
    public $departureDate;
    public $returnedDate;
    public $transportType;
    public $hardcopySignedFlag;
    public $itnaryId;

    public $mappings= [
        'travelId'=>self::TRAVEL_ID,
        'employeeId'=>self::EMPLOYEE_ID,
        'requestedDate'=>self::REQUESTED_DATE,
        'fromDate'=>self::FROM_DATE,
        'toDate'=>self::TO_DATE,
        'destination'=>self::DESTINATION,
        'departure'=>self::DEPARTURE,
        'purpose'=>self::PURPOSE,
        'requestedAmount'=>self::REQUESTED_AMOUNT,
        'requestedType'=>self::REQUESTED_TYPE,
        'remarks'=>self::REMARKS,
        'status'=>self::STATUS,
        'recommendedBy'=>self::RECOMMENDED_BY,
        'recommendedDate'=>self::RECOMMENDED_DATE,       
        'recommendedRemarks'=>self::RECOMMENDED_REMARKS,       
        'approvedBy'=>self::APPROVED_BY,
        'approvedDate'=>self::APPROVED_DATE,
        'approvedRemarks'=>self::APPROVED_REMARKS,
        'travelCode'=>self::TRAVEL_CODE,
        'referenceTravelId'=>self::REFERENCE_TRAVEL_ID,
        'departureDate'=>self::DEPARTURE_DATE,
        'returnedDate'=>self::RETURNED_DATE,
        'transportType'=>self::TRANSPORT_TYPE,
        'hardcopySignedFlag' => self::HARDCOPY_SIGNED_FLAG,
        'itnaryId' => self::ITNARY_ID
    ];   
}
