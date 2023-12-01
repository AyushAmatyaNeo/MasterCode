<?php



namespace Notification\Controller;

require  '/apache24/htdocs/SDS-Neo-hris_GIT/SDS-Neo-hris/tcpdf_6_3_2/tcpdf/tcpdf.php';

use Application\Controller\HrisController;
use Advance\Model\AdvanceRequestModel;
use Advance\Repository\AdvanceRequestRepository;
use Application\Helper\EmailHelper;
use Application\Helper\Helper;
use Application\Model\ForgotPassword;
use Application\Model\Model;
use Notification\Model\PaySlipDetailsModel;
use Notification\Model\PayslipEmailNotificationModel;
use Application\Repository\RepositoryInterface;
use Appraisal\Model\AppraisalAssign;
use Appraisal\Model\AppraisalStatus;
use Appraisal\Repository\AppraisalAssignRepository;
use Exception;
use HolidayManagement\Repository\HolidayRepository;
use Html2Text\Html2Text;
use LeaveManagement\Model\LeaveApply;
use LeaveManagement\Repository\LeaveApplyRepository;
use LeaveManagement\Repository\LeaveMasterRepository;
use ManagerService\Model\SalaryDetail;
use ManagerService\Repository\LeaveApproveRepository;
use ManagerService\Repository\SalaryDetailRepo;
use Notification\Model\AppraisalNotificationModel;
use Notification\Model\LeaveRequestNotificationModel;
use Notification\Model\LeaveSubNotificationModel;
use Notification\Model\Notification;
use Notification\Model\NotificationEvents;
use Notification\Model\NotificationModel;
use Notification\Model\SalaryReviewNotificationModel;
use Notification\Model\TrainingReqNotificationModel;
use Notification\Model\TravelSubNotificationModel;
use Notification\Model\WorkOnDayoffNotificationModel;
use Notification\Model\WorkOnHolidayNotificationModel;
use Notification\Repository\NotificationRepo;
use SelfService\Model\AttendanceRequestModel;
use SelfService\Model\BirthdayModel;
use SelfService\Model\LoanRequest;
use SelfService\Model\Overtime;
use SelfService\Model\TrainingRequest;
use SelfService\Model\TravelRequest;
use SelfService\Model\WorkOnDayoff;
use SelfService\Model\WorkOnHoliday;
use SelfService\Repository\AttendanceRequestRepository;
use SelfService\Repository\LeaveSubstituteRepository;
use SelfService\Repository\LoanRequestRepository;
use SelfService\Repository\OvertimeRepository;
use SelfService\Repository\TrainingRequestRepository;
use SelfService\Repository\TravelRequestRepository;
use SelfService\Repository\TravelSubstituteRepository;
use SelfService\Repository\WorkOnDayoffRepository;
use SelfService\Repository\WorkOnHolidayRepository;
use Setup\Model\HrEmployees;
use Setup\Model\RecommendApprove;
use Setup\Model\Training;
use Setup\Repository\EmployeeRepository;
use Setup\Repository\RecommendApproveRepository;
use Setup\Repository\TrainingRepository;
use Training\Model\TrainingAssign;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Mail\Message;
use Zend\Mime\Message as MimeMessage;
use Zend\Mime\Part as MimePart;
use Zend\Mvc\Controller\AbstractController;
use Zend\Mvc\Controller\Plugin\Url;
use Zend\Authentication\Storage\StorageInterface;
use TCPDF;
use Zend\Mime\Mime;

class HeadNotification
{

    const EXPIRE_IN = 14;

    private $adapter;

    const RECOMMENDER = 1;
    const APPROVER = 2;
    const ACCEPTED = "Accepted";
    const REJECTED = "Rejected";
    const CANCELLED_ACCEPTED = "Cancelled";
    const CANCELLED_REJECTED = "Not Cancelled";
    const ASSIGNED = "Assigned";
    const CANCELLED = "Cancelled";
    const REVIEWER_EVALUATION = "REVIEWER_EVALUATION";
    const SUPER_REVIEWER_EVALUATION = "SUPER_REVIEWER_EVALUATION";
    const HR_FEEDBACK = "HR_FEEDBACK";
    const TRAVEL_EXPENSE_REQUEST = "ep";    //value from travel request form
    const TRAVEL_ADVANCE_REQUEST = "ad";


    public static function getNotifications(AdapterInterface $adapter, int $empId)
    {
        $notiRepo = new NotificationRepo($adapter);
        $notifications = $notiRepo->fetchAllBy([Notification::MESSAGE_TO => $empId, Notification::STATUS => 'U']);
        return Helper::extractDbData($notifications);
    }

    private static function addNotifications(NotificationModel $notiModel, string $title, string $desc, AdapterInterface $adapter)
    {
        $notificationRepo = new NotificationRepo($adapter);
        $notification = new Notification();
        $notification->messageTitle = $title;
        $notification->messageDesc = $desc;
        $notification->messageFrom = $notiModel->fromId;
        $notification->messageTo = $notiModel->toId;
        $notification->route = $notiModel->route;
        $notification->messageId = ((int) Helper::getMaxId($adapter, Notification::TABLE_NAME, Notification::MESSAGE_ID)) + 1;
        $notification->messageDateTime = Helper::getcurrentExpressionDateTime();
        $notification->expiryTime = Helper::getExpressionDate(date(Helper::PHP_DATE_FORMAT, strtotime("+" . self::EXPIRE_IN . " days")));
        $notification->status = 'U';

        return $notificationRepo->add($notification);
    }

    private static function sendEmail(NotificationModel $model, int $type, AdapterInterface $adapter, Url $url)
    {
        $isValidEmail = function ($email) {
            return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
        };
        $emailTemplateRepo = new \Notification\Repository\EmailTemplateRepo($adapter);
        $template = $emailTemplateRepo->fetchById($type);

        if (null == $template) {
            throw new Exception('Email template not set.');
        }
        $mail = new Message();
        $mail->setSubject($model->processString($template['SUBJECT'], $url));
        $htmlDescription = self::mailHeader();
        $htmlDescription .= $model->processString($template['DESCRIPTION'], $url);
        $htmlDescription .= self::mailFooter();

        $htmlPart = new MimePart($htmlDescription);
        $htmlPart->type = "text/html";

        $body = new MimeMessage();
        $body->setParts(array($htmlPart));

        $mail->setBody($body);

        if (!isset($model->fromEmail) || $model->fromEmail == null || $model->fromEmail == '' || !$isValidEmail($model->fromEmail)) {
            throw new Exception("Sender email is not set or valid.");
        }
        if (!isset($model->toEmail) || $model->toEmail == null || $model->toEmail == '' || !$isValidEmail($model->toEmail)) {
            //throw new Exception("Receiver email is not set or valid.");
        }
        $mail->addTo($model->toEmail, $model->toName);

        $cc = (array) json_decode($template['CC']);
        foreach ($cc as $ccObj) {
            $ccObj = (array) $ccObj;
            $mail->addCc($ccObj['email'], $ccObj['name']);
        }

        $bcc = (array) json_decode($template['BCC']);
        foreach ($bcc as $bccObj) {
            $bccObj = (array) $bccObj;
            $mail->addBcc($bccObj['email'], $bccObj['name']);
        }

        EmailHelper::sendEmail($mail);
    }

    private static function sendEmailWithPdfAttachment(NotificationModel $model, int $type, AdapterInterface $adapter, Url $url)
    {

        $paySlipDetails = $model->paySlipDetails;

        $isValidEmail = function ($email) {
            return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
        };
        $emailTemplateRepo = new \Notification\Repository\EmailTemplateRepo($adapter);
        $template = $emailTemplateRepo->fetchById($type);

        if (null == $template) {
            throw new Exception('Email template not set.');
        }
        $mail = new Message();
        $mail->setSubject($model->processString($template['SUBJECT'], $url));
        $htmlDescription = self::mailHeader();
        $htmlDescription .= $model->processString($paySlipDetails, $url);
        $htmlDescription .= self::mailFooter();

        $html = self::mailHeader();
        $html .= $model->processString($template['DESCRIPTION'], $url);
        $html .= self::mailFooter();
        $htmlPart = new MimePart($html);
        $htmlPart->type = "text/html";
        $body = new MimeMessage();
        $body->setParts(array($htmlPart));

        $mail->setBody($body);

        if (!isset($model->fromEmail) || $model->fromEmail == null || $model->fromEmail == '' || !$isValidEmail($model->fromEmail)) {
            throw new Exception("Sender email is not set or valid.");
        }
        if (!isset($model->toEmail) || $model->toEmail == null || $model->toEmail == '' || !$isValidEmail($model->toEmail)) {
            //throw new Exception("Receiver email is not set or valid.");
        }

        $mail->addTo($model->toEmail, $model->toName);

        $cc = (array) json_decode($template['CC']);
        foreach ($cc as $ccObj) {
            $ccObj = (array) $ccObj;
            $mail->addCc($ccObj['email'], $ccObj['name']);
        }

        $bcc = (array) json_decode($template['BCC']);
        foreach ($bcc as $bccObj) {
            $bccObj = (array) $bccObj;
            $mail->addBcc($bccObj['email'], $bccObj['name']);
        }
        $pdfContent = $htmlDescription; // The HTML content you want to convert to PDF

        $pdf = new TCPDF();
        $pdf->AddPage();
        $pdf->writeHTML($pdfContent, true, 0, true, 0);
        $pdfData = $pdf->Output('document.pdf', 'S'); // Get PDF content as a string
        $pdfPart = new MimePart($pdfData);
        $pdfPart->type = Mime::TYPE_PDF;
        $pdfPart->encoding = Mime::ENCODING_BASE64;
        $pdfPart->filename = 'document.pdf';
        $logoPath = $model->logo;  // Replace with the actual path to your company logo

        $logoPart = new MimePart(file_get_contents($logoPath));

        $logoPart->type = Mime::TYPE_OCTETSTREAM;
        $logoPart->encoding = Mime::ENCODING_BASE64;
        $logoPart->filename = 'company_logo.png';
        $body->setParts([$htmlPart, $pdfPart]);

        $mail->setBody($body);
        EmailHelper::sendEmail($mail);
    }
    public static function getName($id, $repo, $name)
    {
        $detail = $repo->fetchById($id);
        return $detail[$name];
    }

    private static function initFullModel(RepositoryInterface $repository, Model &$model, $id)
    {

        $dbModel = $repository->fetchById($id);
        $data = null;
        if (gettype($dbModel) === "array") {
            $data = $dbModel;
        } else {
            $data = $dbModel->getArrayCopy();
        }
        $model->exchangeArrayFromDB($data);
    }

    private static function leaveApplied(LeaveApply $leaveApply, AdapterInterface $adapter, Url $url, $type)
    {
        self::initFullModel(new LeaveApplyRepository($adapter), $leaveApply, $leaveApply->id);
        $recommdAppModel = self::findRecApp($leaveApply->employeeId, $adapter);
        $idAndRole = self::findRoleType($recommdAppModel, $type);
        $leaveReqNotiMod = self::initializeNotificationModel($recommdAppModel[RecommendApprove::EMPLOYEE_ID], $idAndRole['id'], LeaveRequestNotificationModel::class, $adapter);
        //	 print_r($idAndRole);die;
        $leaveName = self::getName($leaveApply->leaveId, new LeaveMasterRepository($adapter), 'LEAVE_ENAME');

        $leaveReqNotiMod->fromDate = $leaveApply->startDate;
        $leaveReqNotiMod->toDate = $leaveApply->endDate;
        $leaveReqNotiMod->leaveName = $leaveName;
        $leaveReqNotiMod->leaveType = $leaveApply->halfDay;
        $leaveReqNotiMod->noOfDays = $leaveApply->noOfDays;

        $leaveReqNotiMod->route = json_encode(["route" => "leaveapprove", "action" => "view", "id" => $leaveApply->id, "role" => $idAndRole['role']]);
        //
        $notificationTitle = "Leave Request";
        $notificationDesc = "Leave Request of $leaveReqNotiMod->fromName from $leaveReqNotiMod->fromDate to $leaveReqNotiMod->toDate";
        self::addNotifications($leaveReqNotiMod, $notificationTitle, $notificationDesc, $adapter);
        self::sendEmail($leaveReqNotiMod, 1, $adapter, $url);
    }

    private static function leaveRecommend(LeaveApply $leaveApply, AdapterInterface $adapter, Url $url, string $status)
    {
        self::initFullModel(new LeaveApplyRepository($adapter), $leaveApply, $leaveApply->id);
        $recommendAppModel = self::findRecApp($leaveApply->employeeId, $adapter);
        $leaveReqNotiMod = self::initializeNotificationModel($recommendAppModel[RecommendApprove::RECOMMEND_BY], $leaveApply->employeeId, LeaveRequestNotificationModel::class, $adapter);

        //
        $leaveReqNotiMod->fromDate = $leaveApply->startDate;
        $leaveReqNotiMod->toDate = $leaveApply->endDate;
        $leaveReqNotiMod->leaveName = self::getName($leaveApply->leaveId, new LeaveMasterRepository($adapter), 'LEAVE_ENAME');
        $leaveReqNotiMod->leaveType = $leaveApply->halfDay;
        $leaveReqNotiMod->noOfDays = $leaveApply->noOfDays;
        $leaveReqNotiMod->leaveRecommendStatus = $status;
        $leaveReqNotiMod->route = json_encode(["route" => "leaverequest", "action" => "view", "id" => $leaveApply->id]);
        //
        $notificationTitle = "Leave Request";
        $notificationDesc = "Recommendation of Leave Request by"
            . " $leaveReqNotiMod->fromName from $leaveReqNotiMod->fromDate"
            . " to $leaveReqNotiMod->toDate is $leaveReqNotiMod->leaveRecommendStatus";
        self::addNotifications($leaveReqNotiMod, $notificationTitle, $notificationDesc, $adapter);
        self::sendEmail($leaveReqNotiMod, 2, $adapter, $url);
    }

    public static function leaveApprove(LeaveApply $leaveApply, AdapterInterface $adapter, Url $url, string $status)
    {
        self::initFullModel(new LeaveApplyRepository($adapter), $leaveApply, $leaveApply->id);
        $recommendAppModel = self::findRecApp($leaveApply->employeeId, $adapter);
        $leaveReqNotiMod = self::initializeNotificationModel($recommendAppModel[RecommendApprove::APPROVED_BY], $leaveApply->employeeId, LeaveRequestNotificationModel::class, $adapter);


        $leaveReqNotiMod->fromDate = $leaveApply->startDate;
        $leaveReqNotiMod->toDate = $leaveApply->endDate;
        $leaveReqNotiMod->leaveName = self::getName($leaveApply->leaveId, new LeaveMasterRepository($adapter), 'LEAVE_ENAME');
        $leaveReqNotiMod->leaveType = $leaveApply->halfDay;
        $leaveReqNotiMod->noOfDays = $leaveApply->noOfDays;
        $leaveReqNotiMod->leaveApprovedStatus = $status;

        $leaveReqNotiMod->route = json_encode(["route" => "leaverequest", "action" => "view", "id" => $leaveApply->id]);

        $notificationTitle = "Leave Approval";
        $notificationDesc = "Approval of Leave Request by $leaveReqNotiMod->fromName from "
            . "$leaveReqNotiMod->fromDate to $leaveReqNotiMod->toDate is $leaveReqNotiMod->leaveApprovedStatus";
        self::addNotifications($leaveReqNotiMod, $notificationTitle, $notificationDesc, $adapter);
        self::sendEmail($leaveReqNotiMod, 3, $adapter, $url);
    }

    public static function attendanceRequest(AttendanceRequestModel $request, AdapterInterface $adapter, Url $url, $type)
    {
        self::initFullModel(new AttendanceRequestRepository($adapter), $request, $request->id);
        $recommdAppModel = self::findRecApp($request->employeeId, $adapter);
        $idAndRole = self::findRoleType($recommdAppModel, $type);
        $notification = self::initializeNotificationModel($recommdAppModel[RecommendApprove::EMPLOYEE_ID], $idAndRole['id'], \Notification\Model\AttendanceRequestNotificationModel::class, $adapter);

        $notification->attendanceDate = $request->attendanceDt;
        $notification->inTime = $request->inTime;
        $notification->outTime = $request->outTime;
        $notification->inRemarks = $request->inRemarks;
        $notification->outRemarks = $request->outRemarks;

        $notification->totalHours = $request->totalHour;
        $notification->route = json_encode(["route" => "attedanceapprove", "action" => "view", "id" => $request->id, "role" => $idAndRole['role']]);

        $title = "Attendance Request";
        $desc = "Attendance Request Applied";

        self::addNotifications($notification, $title, $desc, $adapter);
        self::sendEmail($notification, 4, $adapter, $url);
    }

    public static function attendanceRecommend(AttendanceRequestModel $request, AdapterInterface $adapter, Url $url, string $status)
    {
        self::initFullModel(new AttendanceRequestRepository($adapter), $request, $request->id);
        $recommdAppModel = self::findRecApp($request->employeeId, $adapter);
        $notification = self::initializeNotificationModel($recommdAppModel[RecommendApprove::RECOMMEND_BY], $recommdAppModel[RecommendApprove::EMPLOYEE_ID], \Notification\Model\AdvanceRequestNotificationModel::class, $adapter);

        $notification->attendanceDate = $request->attendanceDt;
        $notification->inTime = $request->inTime;
        $notification->outTime = $request->outTime;
        $notification->inRemarks = $request->inRemarks;
        $notification->outRemarks = $request->outRemarks;
        $notification->totalHours = $request->totalHour;
        $notification->status = $status;

        $notification->route = json_encode(["route" => "attendancerequest", "action" => "view", "id" => $request->id]);

        $title = "Attendance Request";
        $desc = "Attendance Request is " . $status;

        self::addNotifications($notification, $title, $desc, $adapter);
        self::sendEmail($notification, 5, $adapter, $url);
    }

    public static function attendanceApprove(AttendanceRequestModel $request, AdapterInterface $adapter, Url $url, string $status)
    {
        self::initFullModel(new AttendanceRequestRepository($adapter), $request, $request->id);
        $recApp = self::findRecApp($request->employeeId, $adapter);
        $notification = self::initializeNotificationModel($recApp[AttendanceRequestModel::APPROVED_BY], $request->employeeId, \Notification\Model\AttendanceRequestNotificationModel::class, $adapter);

        $notification->attendanceDate = $request->attendanceDt;
        $notification->inTime = $request->inTime;
        $notification->outTime = $request->outTime;
        $notification->inRemarks = $request->inRemarks;
        $notification->outRemarks = $request->outRemarks;
        $notification->totalHours = $request->totalHour;
        $notification->status = $status;

        $title = "Attendance Request";
        $desc = "Attendance Request " . $status;

        $notification->route = json_encode(["route" => "attendancerequest", "action" => "view", "id" => $request->id]);

        self::addNotifications($notification, $title, $desc, $adapter);
        self::sendEmail($notification, 5, $adapter, $url);
    }

    public static function advanceApplied(AdvanceRequestModel $request, AdapterInterface $adapter, Url $url, $type)
    {
        self::initFullModel(new AdvanceRequestRepository($adapter), $request, $request->advanceRequestId);
        $recommdAppModel = self::findRecApp($request->employeeId, $adapter);

        if ($request->overrideRecommenderId != null) {
            $recommdAppModel['RECOMMEND_BY'] = $request->overrideRecommenderId;
        }
        if ($request->overrideApproverId != null) {
            $recommdAppModel['APPROVED_BY'] = $request->overrideApproverId;
        }
        $roleAndId = self::findRoleType($recommdAppModel, $type);

        $notification = self::initializeNotificationModel($recommdAppModel[RecommendApprove::EMPLOYEE_ID], $roleAndId['id'], \Notification\Model\AdvanceRequestNotificationModel::class, $adapter);
        $notification->dateOfadvance = $request->dateOfadvance;
        $notification->reason = $request->reason;
        $notification->requestedAmount = $request->requestedAmount;
        $notification->deductionRate = $request->deductionRate;
        $notification->deductionIn = $request->deductionIn;

        $notification->route = json_encode(["route" => "advance-approve", "action" => "view", "id" => $request->advanceRequestId, "role" => $roleAndId['role']]);
        $title = "Advance Request";
        $desc = "Advance Request Applied";
        self::addNotifications($notification, $title, $desc, $adapter);
        self::sendEmail($notification, 6, $adapter, $url);
    }

    public static function advanceRecommend(AdvanceRequestModel $request, AdapterInterface $adapter, Url $url, string $status)
    {
        self::initFullModel(new AdvanceRequestRepository($adapter), $request, $request->advanceRequestId);
        $recommdAppModel = self::findRecApp($request->employeeId, $adapter);

        if ($request->overrideRecommenderId != null) {
            $recommdAppModel['RECOMMEND_BY'] = $request->overrideRecommenderId;
        }
        if ($request->overrideApproverId != null) {
            $recommdAppModel['APPROVED_BY'] = $request->overrideApproverId;
        }

        $notification = self::initializeNotificationModel($recommdAppModel[RecommendApprove::RECOMMEND_BY], $recommdAppModel[RecommendApprove::EMPLOYEE_ID], \Notification\Model\AdvanceRequestNotificationModel::class, $adapter);

        $notification->dateOfadvance = $request->dateOfadvance;
        $notification->reason = $request->reason;
        $notification->requestedAmount = $request->requestedAmount;
        $notification->deductionRate = $request->deductionRate;
        $notification->deductionIn = $request->deductionIn;
        $notification->status = $status;

        $notification->route = json_encode(["route" => "advance-request", "action" => "view", "id" => $request->advanceRequestId]);
        $title = "Advance Recommend";
        $desc = "Advance Recommend is {$status}";

        self::addNotifications($notification, $title, $desc, $adapter);
        self::sendEmail($notification, 7, $adapter, $url);
    }

    private static function advanceApprove(AdvanceRequestModel $request, AdapterInterface $adapter, Url $url, string $status)
    {
        self::initFullModel(new AdvanceRequestRepository($adapter), $request, $request->advanceRequestId);
        $recommdAppModel = self::findRecApp($request->employeeId, $adapter);

        if ($request->overrideRecommenderId != null) {
            $recommdAppModel['RECOMMEND_BY'] = $request->overrideRecommenderId;
        }
        if ($request->overrideApproverId != null) {
            $recommdAppModel['APPROVED_BY'] = $request->overrideApproverId;
        }
        if ($request->status = 'AP' && $request->approvedBy != null) {
            $recommdAppModel['APPROVED_BY'] = $request->approvedBy;
        }

        $notification = self::initializeNotificationModel($recommdAppModel[RecommendApprove::APPROVED_BY], $recommdAppModel[RecommendApprove::EMPLOYEE_ID], \Notification\Model\AdvanceRequestNotificationModel::class, $adapter);

        $notification->dateOfadvance = $request->dateOfadvance;
        $notification->reason = $request->reason;
        $notification->requestedAmount = $request->requestedAmount;
        $notification->deductionRate = $request->deductionRate;
        $notification->deductionIn = $request->deductionIn;
        $notification->status = $status;

        $notification->route = json_encode(["route" => "advance-request", "action" => "view", "id" => $request->advanceRequestId]);
        $title = "Advance Approve";
        $desc = "Advance Approve is {$status}";

        self::addNotifications($notification, $title, $desc, $adapter);
        self::sendEmail($notification, 8, $adapter, $url);
    }

    private static function travelApplied(TravelRequest $request, AdapterInterface $adapter, Url $url, $type)
    {
        self::initFullModel(new TravelRequestRepository($adapter), $request, $request->travelId);
        $recommdAppModel = self::findRecApp($request->employeeId, $adapter);
        $roleAndId = self::findRoleType($recommdAppModel, $type);
        $notification = self::initializeNotificationModel($recommdAppModel[RecommendApprove::EMPLOYEE_ID], $roleAndId['id'], \Notification\Model\TravelReqNotificationModel::class, $adapter);


        $notification->destination = $request->destination;
        $notification->fromDate = $request->fromDate;
        $notification->toDate = $request->toDate;
        $notification->purpose = $request->purpose;
        $notification->requestedAmount = $request->requestedAmount;
        $notification->requestedType = $request->requestedType;

        switch ($request->requestedType) {
            case self::TRAVEL_ADVANCE_REQUEST:
                $notification->route = json_encode(["route" => "travelApprove", "action" => "view", "id" => $request->travelId, "role" => $roleAndId['role']]);
                break;
            case self::TRAVEL_EXPENSE_REQUEST:
                $notification->route = json_encode(["route" => "travelApprove", "action" => "expenseDetail", "id" => $request->travelId, "role" => $roleAndId['role']]);
                break;
            default:
                $notification->route = json_encode(["route" => "travelApprove", "action" => "view", "id" => $request->travelId, "role" => $roleAndId['role']]);
                break;
        }
        $title = "Travel Request";
        $desc = "Travel Request";


        self::addNotifications($notification, $title, $desc, $adapter);
        self::sendEmail($notification, 9, $adapter, $url);
    }

    private static function travelRecommend(TravelRequest $request, AdapterInterface $adapter, Url $url, string $status)
    {
        self::initFullModel(new TravelRequestRepository($adapter), $request, $request->travelId);
        $recommdAppModel = self::findRecApp($request->employeeId, $adapter);
        $notification = self::initializeNotificationModel(
            $recommdAppModel[RecommendApprove::RECOMMEND_BY],
            $recommdAppModel[RecommendApprove::EMPLOYEE_ID],
            \Notification\Model\TravelReqNotificationModel::class,
            $adapter
        );

        $notification->destination = $request->destination;
        $notification->fromDate = $request->fromDate;
        $notification->toDate = $request->toDate;
        $notification->purpose = $request->purpose;
        $notification->requestedAmount = $request->requestedAmount;
        $notification->requestedType = $request->requestedType;

        $notification->status = $status;

        switch ($request->requestedType) {
            case self::TRAVEL_ADVANCE_REQUEST:
                $notification->route = json_encode(["route" => "travelRequest", "action" => "view", "id" => $request->travelId]);
                break;
            case self::TRAVEL_EXPENSE_REQUEST:
                $notification->route = json_encode(["route" => "travelRequest", "action" => "viewExpense", "id" => $request->travelId]);
                break;
            default:
                $notification->route = json_encode(["route" => "travelRequest", "action" => "view", "id" => $request->travelId]);
                break;
        }
        $title = "Travel Recommendation";
        $desc = "Travel Recommendation {$status}";

        self::addNotifications($notification, $title, $desc, $adapter);
        self::sendEmail($notification, 10, $adapter, $url);
    }

    private static function travelApprove(TravelRequest $request, AdapterInterface $adapter, Url $url, string $status)
    {
        self::initFullModel(new TravelRequestRepository($adapter), $request, $request->travelId);
        $recommdAppModel = self::findRecApp($request->employeeId, $adapter);
        $notification = self::initializeNotificationModel(
            $recommdAppModel[RecommendApprove::APPROVED_BY],
            $recommdAppModel[RecommendApprove::EMPLOYEE_ID],
            \Notification\Model\TravelReqNotificationModel::class,
            $adapter
        );

        $notification->destination = $request->destination;
        $notification->fromDate = $request->fromDate;
        $notification->toDate = $request->toDate;
        $notification->purpose = $request->purpose;
        $notification->requestedAmount = $request->requestedAmount;
        $notification->requestedType = $request->requestedType;

        $notification->status = $status;

        switch ($request->requestedType) {
            case self::TRAVEL_ADVANCE_REQUEST:
                $notification->route = json_encode(["route" => "travelRequest", "action" => "view", "id" => $request->travelId]);
                break;
            case self::TRAVEL_EXPENSE_REQUEST:
                $notification->route = json_encode(["route" => "travelRequest", "action" => "viewExpense", "id" => $request->travelId]);
                break;
            default:
                $notification->route = json_encode(["route" => "travelRequest", "action" => "view", "id" => $request->travelId]);
                break;
        }
        $title = "Travel Approval";
        $desc = "Travel Approval {$status}";

        self::addNotifications($notification, $title, $desc, $adapter);
        self::sendEmail($notification, 11, $adapter, $url);
    }

    private static function trainingAssigned(TrainingAssign $request, AdapterInterface $adapter, Url $url, $type)
    {
        $notification = self::initializeNotificationModel($request->createdBy, $request->employeeId, \Notification\Model\TrainingReqNotificationModel::class, $adapter);

        $training = new Training();
        self::initFullModel(new TrainingRepository($adapter), $training, $request->trainingId);

        $notification->duration = $training->duration;
        $notification->endDate = $training->endDate;
        $notification->startDate = $training->startDate;
        $notification->instructorName = $training->instructorName;
        //        $notification->trainingCode = $training->trainingCode;
        $notification->trainingName = $training->trainingName;
        $notification->trainingType = $training->trainingType;
        $notification->status = $type;


        $notification->route = json_encode(["route" => "trainingList", "action" => "view", "employeeId" => $request->employeeId, "trainingId" => $request->trainingId]);
        $title = "Training $type";
        $desc = "Training $type";

        // self::addNotifications($notification, $title, $desc, $adapter);
        self::sendEmail($notification, 12, $adapter, $url);
    }

    private static function loanApplied(LoanRequest $request, AdapterInterface $adapter, Url $url, $type)
    {
        self::initFullModel(new LoanRequestRepository($adapter), $request, $request->loanRequestId);
        $recommdAppModel = self::findRecApp($request->employeeId, $adapter);
        $roleAndId = self::findRoleType($recommdAppModel, $request->employeeId);
        $notification = self::initializeNotificationModel($recommdAppModel[RecommendApprove::EMPLOYEE_ID], $recommdAppModel[RecommendApprove::RECOMMEND_BY], \Notification\Model\LoanRequestNotificationModel::class, $adapter);

        $notification->approvedAmount = $request->approvedAmount;
        $notification->deductOnSalary = $request->deductOnSalary;
        $notification->loanDate = $request->loanDate;
        $notification->reason = $request->reason;
        $notification->requestedAmount = $request->requestedAmount;

        $notification->route = json_encode(["route" => "loanApprove", "action" => "view", "id" => $request->loanRequestId, "role" => $roleAndId['role']]);
        $title = "Loan Request";
        $desc = "Loan Request";
        self::addNotifications($notification, $title, $desc, $adapter);
        self::sendEmail($notification, 13, $adapter, $url);
    }

    private static function loanRecommend(LoanRequest $request, AdapterInterface $adapter, Url $url, string $status)
    {
        self::initFullModel(new LoanRequestRepository($adapter), $request, $request->loanRequestId);
        $recommdAppModel = self::findRecApp($request->employeeId, $adapter);
        $notification = self::initializeNotificationModel($recommdAppModel[RecommendApprove::RECOMMEND_BY], $recommdAppModel[RecommendApprove::EMPLOYEE_ID], \Notification\Model\LoanRequestNotificationModel::class, $adapter);

        $notification->approvedAmount = $request->approvedAmount;
        $notification->deductOnSalary = $request->deductOnSalary;
        $notification->loanDate = $request->loanDate;
        $notification->reason = $request->reason;
        $notification->requestedAmount = $request->requestedAmount;

        $notification->status = $status;

        $notification->route = json_encode(["route" => "loanRequest", "action" => "view", "id" => $request->loanRequestId]);
        $title = "Loan Recommend";
        $desc = "Loan Recommend $status";

        self::addNotifications($notification, $title, $desc, $adapter);
        self::sendEmail($notification, 14, $adapter, $url);
    }

    private static function loanApprove(LoanRequest $request, AdapterInterface $adapter, Url $url, string $status)
    {
        self::initFullModel(new LoanRequestRepository($adapter), $request, $request->loanRequestId);
        $recommdAppModel = self::findRecApp($request->employeeId, $adapter);
        $notification = self::initializeNotificationModel($recommdAppModel[RecommendApprove::APPROVED_BY], $recommdAppModel[RecommendApprove::EMPLOYEE_ID], \Notification\Model\LoanRequestNotificationModel::class, $adapter);

        $notification->approvedAmount = $request->approvedAmount;
        $notification->deductOnSalary = $request->deductOnSalary;
        $notification->loanDate = $request->loanDate;
        $notification->reason = $request->reason;
        $notification->requestedAmount = $request->requestedAmount;

        $notification->status = $status;

        $notification->route = json_encode(["route" => "loanRequest", "action" => "view", "id" => $request->loanRequestId]);
        $title = "Loan Approval";
        $desc = "Loan Approval $status";

        self::addNotifications($notification, $title, $desc, $adapter);
        self::sendEmail($notification, 15, $adapter, $url);
    }

    private static function workOnDayOffApplied(WorkOnDayoff $workOnDayoff, AdapterInterface $adapter, Url $url, $type)
    {
        self::initFullModel(new WorkOnDayoffRepository($adapter), $workOnDayoff, $workOnDayoff->id);

        $recommdAppModel = self::findRecApp($workOnDayoff->employeeId, $adapter);
        $roleAndId = self::findRoleType($recommdAppModel, $type);
        $workOnDayoffReqNotiMod = self::initializeNotificationModel($recommdAppModel[RecommendApprove::EMPLOYEE_ID], $roleAndId['id'], WorkOnDayoffNotificationModel::class, $adapter);

        $workOnDayoffReqNotiMod->route = json_encode(["route" => "dayoffWorkApprove", "action" => "view", "id" => $workOnDayoff->id, "role" => $roleAndId['role']]);
        $workOnDayoffReqNotiMod->fromDate = $workOnDayoff->fromDate;
        $workOnDayoffReqNotiMod->toDate = $workOnDayoff->toDate;
        $workOnDayoffReqNotiMod->duration = $workOnDayoff->duration;
        $workOnDayoffReqNotiMod->remarks = $workOnDayoff->remarks;

        $notificationTitle = "Work On Day-off Request";
        $notificationDesc = "Work On Day-off Request of $workOnDayoffReqNotiMod->fromName from $workOnDayoffReqNotiMod->fromDate to $workOnDayoffReqNotiMod->toDate";

        self::addNotifications($workOnDayoffReqNotiMod, $notificationTitle, $notificationDesc, $adapter);
        self::sendEmail($workOnDayoffReqNotiMod, 16, $adapter, $url);
    }

    private static function workOnDayOffRecommend(WorkOnDayoff $request, AdapterInterface $adapter, Url $url, string $status)
    {
        self::initFullModel(new WorkOnDayoffRepository($adapter), $request, $request->id);
        $recommdAppModel = self::findRecApp($request->employeeId, $adapter);
        $notification = self::initializeNotificationModel($recommdAppModel[RecommendApprove::RECOMMEND_BY], $recommdAppModel[RecommendApprove::EMPLOYEE_ID], WorkOnDayoffNotificationModel::class, $adapter);

        $notification->fromDate = $request->fromDate;
        $notification->toDate = $request->toDate;
        $notification->duration = $request->duration;
        $notification->remarks = $request->remarks;
        $notification->status = $status;

        $notification->route = json_encode(["route" => "workOnDayoff", "action" => "view", "id" => $request->id]);
        $title = "Work On Day-off Recommendation";
        $desc = "Recommendation of Work on Day-off Request by"
            . " $notification->fromName from $notification->fromDate"
            . " to $notification->toDate is $notification->status";

        self::addNotifications($notification, $title, $desc, $adapter);
        self::sendEmail($notification, 17, $adapter, $url);
    }

    private static function workOnDayOffApprove(WorkOnDayoff $request, AdapterInterface $adapter, Url $url, string $status)
    {
        self::initFullModel(new WorkOnDayoffRepository($adapter), $request, $request->id);
        $recommdAppModel = self::findRecApp($request->employeeId, $adapter);

        $notification = self::initializeNotificationModel(
            $recommdAppModel[RecommendApprove::APPROVED_BY],
            $recommdAppModel[RecommendApprove::EMPLOYEE_ID],
            WorkOnDayoffNotificationModel::class,
            $adapter
        );

        $notification->fromDate = $request->fromDate;
        $notification->toDate = $request->toDate;
        $notification->duration = $request->duration;
        $notification->remarks = $request->remarks;
        $notification->status = $status;

        $notification->route = json_encode(["route" => "workOnDayoff", "action" => "view", "id" => $request->id]);
        $title = "Work On Day-off Approval";
        $desc = "Approval of Work on Day-off Request by"
            . " $notification->fromName from $notification->fromDate"
            . " to $notification->toDate is $notification->status";

        self::addNotifications($notification, $title, $desc, $adapter);
        self::sendEmail($notification, 18, $adapter, $url);
    }

    private static function workOnHoliday(WorkOnHoliday $request, AdapterInterface $adapter, Url $url, $type)
    {
        self::initFullModel(new WorkOnHolidayRepository($adapter), $request, $request->id);
        $recommdAppModel = self::findRecApp($request->employeeId, $adapter);
        $roleAndId = self::findRoleType($recommdAppModel, $type);
        $notification = self::initializeNotificationModel($recommdAppModel[RecommendApprove::EMPLOYEE_ID], $roleAndId['id'], WorkOnHolidayNotificationModel::class, $adapter);

        $holidayName = self::getName($request->holidayId, new HolidayRepository($adapter), 'HOLIDAY_ENAME');

        $notification->route = json_encode(["route" => "holidayWorkApprove", "action" => "view", "id" => $request->id, "role" => $roleAndId['role']]);
        $notification->holidayName = $holidayName;
        $notification->fromDate = $request->fromDate;
        $notification->toDate = $request->toDate;
        $notification->duration = $request->duration;
        $notification->remarks = $request->remarks;

        $title = "Work On Holiday Request";
        $desc = "Work On Holiday Request of $notification->fromName from $notification->fromDate to $notification->toDate";

        self::addNotifications($notification, $title, $desc, $adapter);
        self::sendEmail($notification, 19, $adapter, $url);
    }

    private static function workOnHolidayRecommend(WorkOnHoliday $request, AdapterInterface $adapter, Url $url, string $status)
    {
        self::initFullModel(new WorkOnHolidayRepository($adapter), $request, $request->id);
        $recommdAppModel = self::findRecApp($request->employeeId, $adapter);
        $notification = self::initializeNotificationModel($recommdAppModel[RecommendApprove::RECOMMEND_BY], $recommdAppModel[RecommendApprove::EMPLOYEE_ID], WorkOnHolidayNotificationModel::class, $adapter);

        $holidayName = self::getName($request->holidayId, new HolidayRepository($adapter), 'HOLIDAY_ENAME');
        $notification->holidayName = $holidayName;
        $notification->fromDate = $request->fromDate;
        $notification->toDate = $request->toDate;
        $notification->duration = $request->duration;
        $notification->remarks = $request->remarks;
        $notification->status = $status;

        $notification->route = json_encode(["route" => "workOnHoliday", "action" => "view", "id" => $request->id]);
        $title = "Work On Holiday Recommendation";
        $desc = "Recommendation of Work on Holiday Request by"
            . " $notification->fromName from $notification->fromDate"
            . " to $notification->toDate is $notification->status";

        self::addNotifications($notification, $title, $desc, $adapter);
        self::sendEmail($notification, 20, $adapter, $url);
    }

    private static function workOnHolidayApprove(WorkOnHoliday $request, AdapterInterface $adapter, Url $url, string $status)
    {
        self::initFullModel(new WorkOnHolidayRepository($adapter), $request, $request->id);
        $recommdAppModel = self::findRecApp($request->employeeId, $adapter);

        $notification = self::initializeNotificationModel(
            $recommdAppModel[RecommendApprove::APPROVED_BY],
            $recommdAppModel[RecommendApprove::EMPLOYEE_ID],
            WorkOnHolidayNotificationModel::class,
            $adapter
        );

        $holidayName = self::getName($request->holidayId, new HolidayRepository($adapter), 'HOLIDAY_ENAME');
        $notification->holidayName = $holidayName;
        $notification->fromDate = $request->fromDate;
        $notification->toDate = $request->toDate;
        $notification->duration = $request->duration;
        $notification->remarks = $request->remarks;
        $notification->status = $status;

        $notification->route = json_encode(["route" => "workOnHoliday", "action" => "view", "id" => $request->id]);
        $title = "Work On Holiday Approval";
        $desc = "Approval of Work on Holiday Request by"
            . " $notification->fromName from $notification->fromDate"
            . " to $notification->toDate is $notification->status";

        self::addNotifications($notification, $title, $desc, $adapter);
        self::sendEmail($notification, 21, $adapter, $url);
    }

    private static function trainingApplied(TrainingRequest $request, AdapterInterface $adapter, Url $url, $type)
    {
        $trainingRequestRepo = new TrainingRequestRepository($adapter);
        $trainingRequestDetail = $trainingRequestRepo->fetchById($request->requestId);
        $request->exchangeArrayFromDB($trainingRequestDetail);

        $recommdAppModel = self::findRecApp($request->employeeId, $adapter);
        $roleAndId = self::findRoleType($recommdAppModel, $type);
        $notification = self::initializeNotificationModel($recommdAppModel[RecommendApprove::EMPLOYEE_ID], $roleAndId['id'], TrainingReqNotificationModel::class, $adapter);

        $notification->route = json_encode(["route" => "trainingApprove", "action" => "view", "id" => $request->requestId, "role" => $roleAndId['role']]);

        $notification->trainingType = $trainingRequestDetail['TRAINING_TYPE_DETAIL'];
        $notification->trainingName = $trainingRequestDetail['TITLE'];
        $notification->fromDate = $trainingRequestDetail['START_DATE'];
        $notification->toDate = $trainingRequestDetail['END_DATE'];
        $notification->duration = $trainingRequestDetail['DURATION'];

        $title = "Training Request";
        $desc = "Training Request of $notification->fromName from $notification->fromDate to $notification->toDate";

        self::addNotifications($notification, $title, $desc, $adapter);
        self::sendEmail($notification, 22, $adapter, $url);
    }

    private static function trainingRecommend(TrainingRequest $request, AdapterInterface $adapter, Url $url, string $status)
    {
        $trainingRequestRepo = new TrainingRequestRepository($adapter);
        $trainingRequestDetail = $trainingRequestRepo->fetchById($request->requestId);
        $request->exchangeArrayFromDB($trainingRequestDetail);

        $recommdAppModel = self::findRecApp($request->employeeId, $adapter);
        $notification = self::initializeNotificationModel($recommdAppModel[RecommendApprove::RECOMMEND_BY], $recommdAppModel[RecommendApprove::EMPLOYEE_ID], TrainingReqNotificationModel::class, $adapter);

        $notification->trainingType = $trainingRequestDetail['TRAINING_TYPE_DETAIL'];
        $notification->trainingName = $trainingRequestDetail['TITLE'];
        //        $notification->trainingCode = $trainingRequestDetail['TRAINING_CODE'];
        $notification->fromDate = $trainingRequestDetail['START_DATE'];
        $notification->toDate = $trainingRequestDetail['END_DATE'];
        $notification->duration = $trainingRequestDetail['DURATION'];
        $notification->remarks = $request->remarks;
        $notification->status = $status;

        $notification->route = json_encode(["route" => "trainingRequest", "action" => "view", "id" => $request->requestId]);
        $title = "Training Recommendation";
        $desc = "Recommendation of Training Request by"
            . " $notification->fromName from $notification->fromDate"
            . " to $notification->toDate is $notification->status";

        self::addNotifications($notification, $title, $desc, $adapter);
        self::sendEmail($notification, 23, $adapter, $url);
    }

    private static function trainingApprove(TrainingRequest $request, AdapterInterface $adapter, Url $url, string $status)
    {
        $trainingRequestRepo = new TrainingRequestRepository($adapter);
        $trainingRequestDetail = $trainingRequestRepo->fetchById($request->requestId);
        $request->exchangeArrayFromDB($trainingRequestDetail);

        $recommdAppModel = self::findRecApp($request->employeeId, $adapter);

        $notification = self::initializeNotificationModel(
            $recommdAppModel[RecommendApprove::APPROVED_BY],
            $recommdAppModel[RecommendApprove::EMPLOYEE_ID],
            TrainingReqNotificationModel::class,
            $adapter
        );

        $notification->trainingType = $trainingRequestDetail['TRAINING_TYPE_DETAIL'];
        $notification->trainingName = $trainingRequestDetail['TITLE'];
        //        $notification->trainingCode = $trainingRequestDetail['TRAINING_CODE'];
        $notification->fromDate = $trainingRequestDetail['START_DATE'];
        $notification->toDate = $trainingRequestDetail['END_DATE'];
        $notification->duration = $trainingRequestDetail['DURATION'];
        $notification->remarks = $request->remarks;
        $notification->status = $status;

        $notification->route = json_encode(["route" => "trainingRequest", "action" => "view", "id" => $request->requestId]);
        $title = "Training Approval";
        $desc = "Approval of Training Request by"
            . " $notification->fromName from $notification->fromDate"
            . " to $notification->toDate is $notification->status";

        self::addNotifications($notification, $title, $desc, $adapter);
        self::sendEmail($notification, 24, $adapter, $url);
    }

    private static function leaveSubstituteApplied(LeaveApply $request, AdapterInterface $adapter, Url $url)
    {
        self::initFullModel(new LeaveApplyRepository($adapter), $request, $request->id);

        $leaveSubstituteRepo = new LeaveSubstituteRepository($adapter);
        $leaveSubstituteDetail = $leaveSubstituteRepo->fetchById($request->id);

        $notification = self::initializeNotificationModel($request->employeeId, $leaveSubstituteDetail['EMPLOYEE_ID'], LeaveSubNotificationModel::class, $adapter);

        $leaveName = self::getName($request->leaveId, new LeaveMasterRepository($adapter), 'LEAVE_ENAME');
        $notification->leaveName = $leaveName;
        $notification->fromDate = $request->startDate;
        $notification->toDate = $request->endDate;
        $notification->duration = $request->noOfDays;
        $notification->remarks = $request->remarks;

        $notification->route = json_encode(["route" => "leaveNotification", "action" => "view", "id" => $request->id]);
        $title = "Substitue Work Request On Leave";
        $desc = "Substitue Work Request On Leave From " . $notification->fromDate . " To " . $notification->toDate;

        self::addNotifications($notification, $title, $desc, $adapter);
        self::sendEmail($notification, 25, $adapter, $url);
    }

    private static function leaveSubstituteAccepted(LeaveApply $request, AdapterInterface $adapter, Url $url, string $status)
    {
        self::initFullModel(new LeaveApplyRepository($adapter), $request, $request->id);
        $leaveSubstituteRepo = new LeaveSubstituteRepository($adapter);
        $leaveSubstituteDetail = $leaveSubstituteRepo->fetchById($request->id);

        $notification = self::initializeNotificationModel($leaveSubstituteDetail['EMPLOYEE_ID'], $request->employeeId, LeaveSubNotificationModel::class, $adapter);

        $leaveName = self::getName($request->leaveId, new LeaveMasterRepository($adapter), 'LEAVE_ENAME');
        $notification->leaveName = $leaveName;
        $notification->fromDate = $request->startDate;
        $notification->toDate = $request->endDate;
        $notification->duration = $request->noOfDays;
        $notification->remarks = $request->remarks;
        $notification->status = $status;

        $notification->route = json_encode(["route" => "leaverequest", "action" => "view", "id" => $request->id]);
        $title = "Substitue Work On Leave Recommendation";
        $desc = "Substitue Work Request On Leave From " . $notification->fromDate . " To " . $notification->toDate . " is " . $status;

        self::addNotifications($notification, $title, $desc, $adapter);
        self::sendEmail($notification, 26, $adapter, $url);
    }

    private static function travelSubstituteApplied(TravelRequest $request, AdapterInterface $adapter, Url $url)
    {
        self::initFullModel(new TravelRequestRepository($adapter), $request, $request->travelId);

        $travelSubstituteRepo = new TravelSubstituteRepository($adapter);
        $travelSubstituteDetail = $travelSubstituteRepo->fetchById($request->travelId);

        $notification = self::initializeNotificationModel($request->employeeId, $travelSubstituteDetail['EMPLOYEE_ID'], TravelSubNotificationModel::class, $adapter);

        $notification->travelCode = $request->travelCode;
        $notification->fromDate = $request->fromDate;
        $notification->toDate = $request->toDate;
        $notification->duration = Helper::dateDiff($request->fromDate, $request->toDate) + 1;
        $notification->destination = $request->destination;
        $notification->purpose = $request->purpose;
        $notification->remarks = $request->remarks;

        $notification->route = json_encode(["route" => "travelNotification", "action" => "view", "id" => $request->travelId]);
        $title = "Substitue Work Request On Travel";
        $desc = "Substitue Work Request On Travel From " . $notification->fromDate . " To " . $notification->toDate;

        self::addNotifications($notification, $title, $desc, $adapter);
        self::sendEmail($notification, 27, $adapter, $url);
    }

    private static function travelSubstituteAccepted(TravelRequest $request, AdapterInterface $adapter, Url $url, string $status)
    {
        self::initFullModel(new TravelRequestRepository($adapter), $request, $request->travelId);

        $travelSubstituteRepo = new TravelSubstituteRepository($adapter);
        $travelSubstituteDetail = $travelSubstituteRepo->fetchById($request->travelId);

        $notification = self::initializeNotificationModel($travelSubstituteDetail['EMPLOYEE_ID'], $request->employeeId, TravelSubNotificationModel::class, $adapter);

        $notification->travelCode = $request->travelCode;
        $notification->fromDate = $request->fromDate;
        $notification->toDate = $request->toDate;
        $notification->duration = Helper::dateDiff($request->fromDate, $request->toDate) + 1;
        $notification->destination = $request->destination;
        $notification->purpose = $request->purpose;
        $notification->remarks = $request->remarks;
        $notification->status = $status;

        $notification->route = json_encode(["route" => "travelRequest", "action" => "view", "id" => $request->travelId]);
        $title = "Substitue Work On Travel Recommendation";
        $desc = "Substitue Work Request On Travel From " . $notification->fromDate . " To " . $notification->toDate . " is " . $status;

        self::addNotifications($notification, $title, $desc, $adapter);
        self::sendEmail($notification, 28, $adapter, $url);
    }

    private static function forgotPassword(ForgotPassword $forgotPassword, AdapterInterface $adapter)
    {
        $isValidEmail = function ($email) {
            return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
        };

        $employeeRepo = new EmployeeRepository($adapter);
        $toEmployee = $employeeRepo->fetchById($forgotPassword->employeeId);
        $toEmail = $toEmployee['EMAIL_OFFICIAL'];
        $toName = $toEmployee['FIRST_NAME'] . " " . $toEmployee['MIDDLE_NAME'] . " " . $toEmployee['LAST_NAME'];

        try {
            $mail = new Message();
            $mail->setSubject($forgotPassword->code . " is your password recovery code");
            $htmlDescription = "Hi " . $toName . ", You can enter the following reset code<br>" . $forgotPassword->code . "<br><br>Your Code will be expired in " . $forgotPassword->expiryDate;
            $html2txt = new Html2Text($htmlDescription);
            $mail->setBody($html2txt->getText());

            if (!isset($toEmail) || $toEmail == null || $toEmail == '' || !$isValidEmail($toEmail)) {
                //throw new Exception("Receiver email is not set or valid.");
            }
            $mail->addTo($toEmail, $toName);

            EmailHelper::sendEmail($mail);
        } catch (Exception $e) {
            print "<pre>";
            print($e->getMessage());
            exit;
        }
    }

    private static function salaryReview(SalaryDetail $request, AdapterInterface $adapter, Url $url)
    {
        self::initFullModel(new SalaryDetailRepo($adapter), $request, $request->salaryDetailId);
        $notification = self::initializeNotificationModel($request->createdBy, $request->employeeId, SalaryReviewNotificationModel::class, $adapter);

        $notification->newAmount = $request->newAmount;
        $notification->oldAmount = $request->oldAmount;
        $notification->effectiveDate = $request->effectiveDate;

        $notification->route = json_encode(["route" => "salaryReview", "action" => "edit", "id" => $request->salaryDetailId]);
        $title = "Salary Review";
        $desc = "Salary Review From " . $notification->oldAmount . " To " . $notification->newAmount;

        self::addNotifications($notification, $title, $desc, $adapter);
        self::sendEmail($notification, 29, $adapter, $url);
    }

    private static function kpiSetting(AppraisalStatus $request, AdapterInterface $adapter, Url $url, $recieverDetail)
    {
        $appraisalAssignRepo = new AppraisalAssignRepository($adapter);
        $assignedAppraisalDetail = $appraisalAssignRepo->getEmployeeAppraisalDetail($request->employeeId, $request->appraisalId);

        $fullName = function ($id, $adapter) {
            if ($id != null) {
                $empRepository = new EmployeeRepository($adapter);
                $empDtl = $empRepository->fetchById($id);
                $empMiddleName = ($empDtl['MIDDLE_NAME'] != null) ? " " . $empDtl['MIDDLE_NAME'] . " " : " ";
                return $empDtl['FIRST_NAME'] . $empMiddleName . $empDtl['LAST_NAME'];
            } else {
                return "";
            }
        };
        $notification = self::initializeNotificationModel($request->employeeId, $recieverDetail['ID'], AppraisalNotificationModel::class, $adapter);

        $notification->appraisalName = $assignedAppraisalDetail['APPRAISAL_EDESC'];
        $notification->appraisalType = $assignedAppraisalDetail['APPRAISAL_TYPE_EDESC'];
        $notification->appraiseeName = $fullName($assignedAppraisalDetail['EMPLOYEE_ID'], $adapter);
        $notification->appraiserName = $fullName($assignedAppraisalDetail['APPRAISER_ID'], $adapter);
        $notification->reviewerName = $fullName($assignedAppraisalDetail['REVIEWER_ID'], $adapter);
        $notification->startDate = $assignedAppraisalDetail['START_DATE'];
        $notification->endDate = $assignedAppraisalDetail['END_DATE'];
        $notification->rating = $assignedAppraisalDetail['APPRAISER_OVERALL_RATING'];
        $notification->currentStage = $assignedAppraisalDetail['STAGE_EDESC'];

        if ($recieverDetail['USER_TYPE'] == 'APPRAISER') {
            $notification->route = json_encode(["route" => "appraisal-evaluation", "action" => "view", "appraisalId" => $request->appraisalId, "employeeId" => $request->employeeId, "tab" => 1]);
        } else if ($recieverDetail['USER_TYPE'] == 'REVIEWER') {
            $notification->route = json_encode(["route" => "appraisal-review", "action" => "view", "appraisalId" => $request->appraisalId, "employeeId" => $request->employeeId, "tab" => 1]);
        } else if ($recieverDetail['USER_TYPE'] == 'HR') {
            $notification->route = json_encode(["route" => "appraisalReport", "action" => "view", "appraisalId" => $request->appraisalId, "employeeId" => $request->employeeId]);
        }

        $title = "KPI Setting on Appraisal";
        $desc = "KPI Set by"
            . " $notification->fromName on $notification->appraisalName of type $notification->appraisalType";

        self::addNotifications($notification, $title, $desc, $adapter);
        self::sendEmail($notification, 30, $adapter, $url);
    }

    private static function kpiApproved(AppraisalStatus $request, AdapterInterface $adapter, Url $url, $senderDetail, $recieverDetail)
    {
        $appraisalAssignRepo = new AppraisalAssignRepository($adapter);
        $assignedAppraisalDetail = $appraisalAssignRepo->getEmployeeAppraisalDetail($request->employeeId, $request->appraisalId);

        $fullName = function ($id, $adapter) {
            if ($id != null) {
                $empRepository = new EmployeeRepository($adapter);
                $empDtl = $empRepository->fetchById($id);
                $empMiddleName = ($empDtl['MIDDLE_NAME'] != null) ? " " . $empDtl['MIDDLE_NAME'] . " " : " ";
                return $empDtl['FIRST_NAME'] . $empMiddleName . $empDtl['LAST_NAME'];
            } else {
                return "";
            }
        };
        $notification = self::initializeNotificationModel($senderDetail['ID'], $recieverDetail['ID'], AppraisalNotificationModel::class, $adapter);

        $notification->appraisalName = $assignedAppraisalDetail['APPRAISAL_EDESC'];
        $notification->appraisalType = $assignedAppraisalDetail['APPRAISAL_TYPE_EDESC'];
        $notification->appraiseeName = $fullName($assignedAppraisalDetail['EMPLOYEE_ID'], $adapter);
        $notification->appraiserName = $fullName($assignedAppraisalDetail['APPRAISER_ID'], $adapter);
        $notification->reviewerName = $fullName($assignedAppraisalDetail['REVIEWER_ID'], $adapter);
        $notification->startDate = $assignedAppraisalDetail['START_DATE'];
        $notification->endDate = $assignedAppraisalDetail['END_DATE'];
        $notification->rating = $assignedAppraisalDetail['APPRAISER_OVERALL_RATING'];
        $notification->currentStage = $assignedAppraisalDetail['STAGE_EDESC'];

        if ($recieverDetail['USER_TYPE'] == 'APPRAISER') {
            $notification->route = json_encode(["route" => "appraisal-evaluation", "action" => "view", "appraisalId" => $request->appraisalId, "employeeId" => $request->employeeId, "tab" => 1]);
        } else if ($recieverDetail['USER_TYPE'] == 'REVIEWER') {
            $notification->route = json_encode(["route" => "appraisal-review", "action" => "view", "appraisalId" => $request->appraisalId, "employeeId" => $request->employeeId, "tab" => 1]);
        } else if ($recieverDetail['USER_TYPE'] == 'HR') {
            //                print_r($recieverDetail['USER_TYPE']);
            //                print_r("hellow"); die();
            $notification->route = json_encode(["route" => "appraisalReport", "action" => "view", "appraisalId" => $request->appraisalId, "employeeId" => $request->employeeId]);
        } else if ($recieverDetail['USER_TYPE'] == 'APPRAISEE') {
            //                print_r($recieverDetail['USER_TYPE']);
            //                print_r("hellow"); die();
            $notification->route = json_encode(["route" => "performanceAppraisal", "action" => "view", "appraisalId" => $request->appraisalId]);
        }

        $title = "KPI Approval";
        $desc = "KPI Approved by"
            . " $notification->fromName on $notification->appraisalName of type $notification->appraisalType";

        self::addNotifications($notification, $title, $desc, $adapter);
        self::sendEmail($notification, 31, $adapter, $url);
    }

    private static function keyAchievement(AppraisalStatus $request, AdapterInterface $adapter, Url $url, $recieverDetail)
    {
        $appraisalAssignRepo = new AppraisalAssignRepository($adapter);
        $assignedAppraisalDetail = $appraisalAssignRepo->getEmployeeAppraisalDetail($request->employeeId, $request->appraisalId);

        $fullName = function ($id, $adapter) {
            if ($id != null) {
                $empRepository = new EmployeeRepository($adapter);
                $empDtl = $empRepository->fetchById($id);
                $empMiddleName = ($empDtl['MIDDLE_NAME'] != null) ? " " . $empDtl['MIDDLE_NAME'] . " " : " ";
                return $empDtl['FIRST_NAME'] . $empMiddleName . $empDtl['LAST_NAME'];
            } else {
                return "";
            }
        };
        $notification = self::initializeNotificationModel($request->employeeId, $recieverDetail['ID'], AppraisalNotificationModel::class, $adapter);

        $notification->appraisalName = $assignedAppraisalDetail['APPRAISAL_EDESC'];
        $notification->appraisalType = $assignedAppraisalDetail['APPRAISAL_TYPE_EDESC'];
        $notification->appraiseeName = $fullName($assignedAppraisalDetail['EMPLOYEE_ID'], $adapter);
        $notification->appraiserName = $fullName($assignedAppraisalDetail['APPRAISER_ID'], $adapter);
        $notification->reviewerName = $fullName($assignedAppraisalDetail['REVIEWER_ID'], $adapter);
        $notification->startDate = $assignedAppraisalDetail['START_DATE'];
        $notification->endDate = $assignedAppraisalDetail['END_DATE'];
        $notification->rating = $assignedAppraisalDetail['APPRAISER_OVERALL_RATING'];
        $notification->currentStage = $assignedAppraisalDetail['STAGE_EDESC'];

        if ($recieverDetail['USER_TYPE'] == 'APPRAISER') {
            $notification->route = json_encode(["route" => "appraisal-evaluation", "action" => "view", "appraisalId" => $request->appraisalId, "employeeId" => $request->employeeId, "tab" => 1]);
        } else if ($recieverDetail['USER_TYPE'] == 'REVIEWER') {
            $notification->route = json_encode(["route" => "appraisal-review", "action" => "view", "appraisalId" => $request->appraisalId, "employeeId" => $request->employeeId, "tab" => 1]);
        } else if ($recieverDetail['USER_TYPE'] == 'HR') {
            $notification->route = json_encode(["route" => "appraisalReport", "action" => "view", "appraisalId" => $request->appraisalId, "employeeId" => $request->employeeId]);
        }

        $title = "Key Achievement Update on Appraisal";
        $desc = "Key Achievement Updated by"
            . " $notification->fromName on $notification->appraisalName of type $notification->appraisalType";

        self::addNotifications($notification, $title, $desc, $adapter);
        self::sendEmail($notification, 32, $adapter, $url);
    }
    function formatNepaliNumber($number)
    {

        $number_str = (string)$number;

        if ($number == 0) {
            $formatted_number = 0.00;
        } else {
            list($integer_part, $decimal_part) = explode('.', $number_str);
            $integerPartCount = strlen(strval(intval($integer_part)));
            $lastThreeDigits = substr($integer_part, -3);
            $restOfDigits = substr($integer_part, 0, -3);
            $formatted_integer = implode(',', str_split(strrev($restOfDigits), 2));
            $formatted_integer = strrev($formatted_integer);
            if ($decimal_part == 0) {
                $decimal_part = .00;
            } else {
                $decimal_part = str_pad($decimal_part, 2, '0', STR_PAD_RIGHT);
            }

            if ($integer_part == 0) {
                $integer_part = 0;
            } else if ($integerPartCount > 3) {
                $lastThreeDigits = ',' . $lastThreeDigits;
            } else {
                $lastThreeDigits = $lastThreeDigits;
            }
            $formatted_number = $formatted_integer . $lastThreeDigits . '.' . $decimal_part;
        }

        return $formatted_number;
    }

    private static function sendPayslipEmail(PaySlipDetailsModel $payslipDetail, AdapterInterface $adapter, Url $url)
    {

        self::initFullModel(new EmployeeRepository($adapter), $payslipDetail, $payslipDetail->setProperty1['EMPLOYEE_ID']);
        $payslipModel = self::initializeNotificationModel(72, $payslipDetail->setProperty1['EMPLOYEE_ID'], PayslipEmailNotificationModel::class, $adapter);
        $property = $payslipDetail->setProperty2;


        $paySlipDetail = '';
        if ($payslipDetail->setProperty1['FILE_PATH'] == null) {
            $companyLogoPath = 'C:/apache24/htdocs/SDS-Neo-hris_GIT/SDS-Neo-hris/public/uploads/1701407516.png';
        } else {
            $companyLogoPath = 'C:/apache24/htdocs/SDS-Neo-hris_GIT/SDS-Neo-hris/public/uploads/' . $payslipDetail->setProperty1['FILE_PATH'];
        }

        $paySlipDetail .= '
        <div>
        <table style="width: 100%; border-collapse: collapse;">
        <tr style="text-align: center;">
        <td style="text-align: center;padding-left:50px;" colspan="3">
            <h3><b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' . $payslipDetail->setProperty1['COMPANY_NAME'] . '</b></h3>
            <h5><b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' . $payslipDetail->setProperty1['ADDRESS'] . '</b></h5>
        </td>
        <td style="text-align: center;" > 
        <div style="margin-top: 40px;">
            <img src="' . $companyLogoPath . '" style="height: 35px; width: 60px;">
            </div>
        </td>
    </tr>
</table>
        <br><br>
        <table class="table table-bordered" style="width: 100%; border-collapse: collapse;"> <br>
               <tr style="text-align: center;padding-top: 8px;"><td colspan="4"><h4 style="text-decoration: underline;"><b><span id="yearMonthDetails">Salary Slip for the month of ' . ' for ' . $payslipDetail->setProperty1['MONTH_EDESC'] . ' ' . $payslipDetail->setProperty1['YEAR'] . '</span></b></h4>  </td> </tr> <br>
               <tr>
               <td style="text-align: left; padding: 8px; font-size: 11px;">Employee Name</td>
               <td style="text-align: left; padding: 8px; font-size: 11px;">' . $payslipDetail->setProperty1['FULL_NAME'] . '</td>
                <td style="text-align: left; padding: 8px; font-size: 11px;">Present Days</td>
                <td style="text-align: left; padding: 8px; font-size: 11px;">' . $payslipDetail->setProperty1['PRESENT'] . '</td>
                </tr>

                <tr>
                <td style="text-align: left;padding: 8px;font-size: 11px;">Employee Code</td>
                <td style="text-align: left;padding: 8px;font-size: 11px;">' . $payslipDetail->setProperty1['EMPLOYEE_ID'] . '</td>
                <td style="text-align: left;padding: 8px;font-size: 11px;">Absent Days</td>
                <td style="text-align: left;padding: 8px;font-size: 11px;">' . $payslipDetail->setProperty1['ABSENT'] . '</td>
                
               
                </tr>

                <tr>
                    <td style="text-align: left;padding: 8px; font-size: 11px;">Employee Id</td>
                    <td style="text-align: left;padding: 8px;font-size: 11px;">' . $payslipDetail->setProperty1['EMPLOYEE_ID'] . '</td>
                    <td style="text-align: left;padding: 8px;font-size: 11px;">Week Day Off</td>
                    <td style="text-align: left;padding: 8px;font-size: 11px;">' . $payslipDetail->setProperty1['DAYOFF'] . '</td>
                </tr>
                <tr>
                    <td style="text-align: left;padding: 8px;font-size: 11px;">Designation</td>
                    <td style="text-align: left;padding: 8px;font-size: 11px;">' . $payslipDetail->setProperty1['DESIGNATION_TITLE'] . '</td>
                    <td style="text-align: left;padding: 8px;font-size: 11px;">Paid Leave Days</td>
                    <td style="text-align: left;padding: 8px;font-size: 11px;">' . $payslipDetail->setProperty1['PAID_LEAVE'] . '</td>
                </tr>
                
                <tr>
                    <td style="text-align: left;padding: 8px;font-size: 11px;">Department</td>
                    <td style="text-align: left;padding: 8px;font-size: 11px;">' . $payslipDetail->setProperty1['DEPARTMENT_NAME'] . '</td>
                    <td style="text-align: left;padding: 8px;font-size: 11px;">Unpaid Leave Days</td>
                    <td style="text-align: left;padding: 8px;font-size: 11px;">' . $payslipDetail->setProperty1['UNPAID_LEAVE'] . '</td>
                    
                </tr>
                <tr>
                    <td style="text-align: left;padding: 8px;font-size: 11px;">Bank Name</td>
                    <td style="text-align: left;padding: 8px;font-size: 11px;">' . $payslipDetail->setProperty1['BANK_NAME'] . '</td>
                    <td style="text-align: left;padding: 8px;font-size: 11px;">Holiday</td>
                    <td style="text-align: left;padding: 8px;font-size: 11px;">' . $payslipDetail->setProperty1['HOLIDAY'] . '</td>

                </tr>
                <tr>
                    <td style="text-align: left;padding: 8px;font-size: 11px;">Bank Acc No</td>
                    <td style="text-align: left;padding: 8px;font-size: 11px;">' . $payslipDetail->setProperty1['ID_ACCOUNT_NO'] . '</td>
                    <td style="text-align: left;padding: 8px;font-size: 11px;">Pay Days</td>
                    <td style="text-align: left;padding: 8px;font-size: 11px;">' . $payslipDetail->setProperty1['PAY_DAYS'] . '</td>
                </tr>
                <tr>
                    <td style="text-align: left;padding: 8px;font-size: 11px;">SSF No</td>
                    <td style="text-align: left;padding: 8px;font-size: 11px;">' . $payslipDetail->setProperty1['SSF_NO'] . '</td>
                    <td style="text-align: left;padding: 8px;font-size: 11px;">Total Days</td>
                    <td style="text-align: left;padding: 8px;font-size: 11px;">' . $payslipDetail->setProperty1['TOTAL_DAYS'] . '</td>
                </tr>
                <tr>
                    <td style="text-align: left;padding: 8px;font-size: 11px;">CIT No</td>
                    <td style="text-align: left;padding: 8px;font-size: 11px;">' . $payslipDetail->setProperty1['ID_RETIREMENT_NO'] . '</td>
                    <td style="text-align: left;padding: 8px;font-size: 11px;">Overtime Hours</td>
                    <td style="text-align: left;padding: 8px;font-size: 11px;">' . $payslipDetail->setProperty1['OVERTIME_HOUR'] . '</td>
                </tr>
                <tr>
                    <td style="text-align: left;padding: 8px;font-size: 11px;">PAN No</td>
                    <td style="text-align: left;padding: 8px;font-size: 11px;">' . $payslipDetail->setProperty1['ID_PAN_NO'] . '</td>
                    <td style="text-align: left;padding: 8px;font-size: 11px;">Marital Status:</td>
                    <td style="text-align: left;padding: 8px;font-size: 11px;">' . $payslipDetail->setProperty1['MARITAL_STATUS_DESC'] . '</td>
                </tr>
                 </table> <br>
                 ';

        $tableHeader = '<br>
            <table class="table table-bordered" style="width: 100%; border-collapse: collapse;"> <br>
            <tr style="text-align: center;padding-top: 8px;"><td colspan="4"><h4 style="text-decoration: underline;"><b><span id="yearMonthDetails">Monthly Details for ' . $payslipDetail->setProperty1['MONTH_EDESC'] . ' ' . $payslipDetail->setProperty1['YEAR'] . '</span></b></h4>  </td> </tr> <br>
            <tr>
                    <th colspan="2" style="font-size: 10px;text-align: center;padding: 8px;"><b>Monthly Earnings</b></th>
                    <th colspan="2" style="font-size: 10px;text-align: center;padding: 8px;"><b>Monthly Deductions</b></th>
                </tr>
                <tbody>';
        $additionData = [];
        $additionCounter = 0;
        $additionSum = 0;
        $deductionData = [];
        $deductionCounter = 0;
        $deductionSum = 0;

        $netSum = 0;
        $net = 0;
        $add = 0;
        $sub = 0;

        foreach ($property as $data) {
            switch ($data['PAY_TYPE_FLAG']) {
                case 'A':
                    $additionData[$additionCounter] = $data;
                    $myString = trim($data['VAL']);
                    $additionSum += floatval(str_replace(',', '', $myString));
                    $additionCounter++;
                    break;
                case 'D':
                    $deductionData[$deductionCounter] = $data;
                    $myString = trim($data['VAL']);
                    $deductionSum += floatval(str_replace(',', '', $myString));
                    $deductionCounter++;
                    break;
            }
            $netSum = $additionSum - $deductionSum;
            $add = self::formatNepaliNumber($additionSum);
            $sub = self::formatNepaliNumber($deductionSum);
            $net = self::formatNepaliNumber($netSum);
        }

        $maxRow = max($additionCounter, $deductionCounter);
        $additionRows = '';
        for ($i = 0; $i < $maxRow; $i++) {
            $additionRow = '
            <tr>
                <td style="padding: 8px;text-align: left;font-size: 11px;">' . (isset($additionData[$i]) ? $additionData[$i]['PAY_EDESC'] : '') . '</td>
                <td style="text-align: right;padding: 8px;font-size: 11px;">' . (isset($additionData[$i]) ? $additionData[$i]['VAL'] : '') . '</td>
                <td style="padding: 8px;text-align: left;font-size: 11px;">' . (isset($deductionData[$i]) ? $deductionData[$i]['PAY_EDESC'] : '') . '</td>
                <td style="text-align: right;padding: 8px;font-size: 11px;">' . (isset($deductionData[$i]) ? $deductionData[$i]['VAL'] : '') . '</td>
            </tr>';
            $additionRows .= $additionRow;
        }
        $tableFooter = ' <br>
            <tr>
                <td style="text-align: left;padding: 8px; border-top: 1px solid #000000;font-size:10px"><b>Total Earnings </b></td>
                <td style="text-align: right;padding: 8px; border-top: 1px solid #000000;font-size: 10px;"><b>' . $add . '</b></td>
                <td style="text-align: left;padding: 8px; border-top: 1px solid #000000;font-size: 10px;"><b>Total Deductions </b></td>
                <td style="text-align: right;padding: 8px; border-top: 1px solid #000000;font-size: 10px;"><b>' . $sub . ' </b></td>
            </tr>
            <tr>
                <td style="text-align: left;padding: 8px;font-size:10px" ><b>Net Salary(NRS) </b></td>
                <td style="text-align: right;padding: 8px;font-size:10px"><b>' . $net . '</b></td>
                <td style="text-align: left;padding: 8px;font-size:10px" ><b></b></td>
                <td style="text-align: left;padding: 8px;font-size:10px"><b></b></td>
            </tr>
        </tbody>
        </table> <br> <br>';
        $firstLoop = '';
        $sencondLoop = '';
        $thirdLoop = '';
        $firstLoopArr = [
            count($payslipDetail->incomes),
            count($payslipDetail->taxExcemptions),
            count($payslipDetail->otherTax)
        ];
        $thirdLoopArr = [
            count($payslipDetail->otherTax)
        ];
        $taxLoopArr = [
            count($payslipDetail->setProperty4)
        ];
        $sendLoopArr = [
            count($payslipDetail->miscellaneous),
            count($payslipDetail->bMiscellaneou),
            count($payslipDetail->cMiscellaneou)
        ];
        $maxFirstLoop = max($firstLoopArr);
        $maxThirdLoop = max($thirdLoopArr);
        $maxSecLoop = max($sendLoopArr);
        $maxTaxLoop = max($taxLoopArr);

        $total = 0;
        for ($n = 0; $n < $maxFirstLoop; ++$n) {
            $incomeName = isset($payslipDetail->incomes[$n]) ? $payslipDetail->incomes[$n]['VARIANCE_NAME'] : '';
            $incomeTemp = isset($payslipDetail->incomes[$n]) ? $payslipDetail->incomes[$n]['TEMPLATE_NAME'] : '';
            $taxEmpName = isset($payslipDetail->taxExcemptions[$n]) ? $payslipDetail->taxExcemptions[$n]['VARIANCE_NAME'] : '';
            $taxEmpTemp = isset($payslipDetail->taxExcemptions[$n]) ? $payslipDetail->taxExcemptions[$n]['TEMPLATE_NAME'] : '';
            $otherTaxName = isset($payslipDetail->otherTax[$n]) ? $payslipDetail->otherTax[$n]['VARIANCE_NAME'] : '';
            $otherTaxTemp = isset($payslipDetail->otherTax[$n]) ? $payslipDetail->otherTax[$n]['TEMPLATE_NAME'] : '';
            $incomeValue = $payslipDetail->setProperty3[0][$incomeTemp];
            $taxVal = $payslipDetail->setProperty3[0][$taxEmpTemp];
            if ($taxEmpName == 'TAX Slab') {
                continue; // Skip the current iteration and move to the next one
            }
            if ($taxEmpTemp == 'V56') {
                continue; // Skip the current iteration and move to the next one
            }
            if ($incomeValue == 0) {
                $incomeSum = $incomeValue; // Return the value as is
            } else {
                $incomeSum = self::formatNepaliNumber($incomeValue); // Format the value
            }
            if ($taxVal == 0) {
                $taxSum = $taxVal; // Return the value as is
            } else {
                $taxSum = self::formatNepaliNumber($taxVal); // Format the value
            }
            $total = $total + $payslipDetail->setProperty3[0][$incomeTemp];
            $firstLoop .= '<tr>';
            $firstLoop .= '<td style="text-align: left;padding: 8px;font-size: 11px;">' . $incomeName . ' </td>';
            $firstLoop .= '<td style="text-align: right;padding: 8px;font-size: 11px;">' . $incomeSum . '</td>';
            $firstLoop .= '<td style="text-align: left;padding: 8px;font-size: 11px;">' . $taxEmpName . ' </td>';
            $firstLoop .= '<td style="text-align: right;padding: 8px;font-size: 11px;">' . $taxSum  . '</td>';
            $firstLoop .= '</tr>';
        }
        $taxVal = 0;
        for ($n = 0; $n < $maxTaxLoop; ++$n) {
            // $otherTaxName = isset($payslipDetail->otherTax[$n]) ? $payslipDetail->otherTax[$n]['VARIANCE_NAME'] : '';
            // $otherTaxTemp = isset($payslipDetail->otherTax[$n]) ? $payslipDetail->otherTax[$n]['TEMPLATE_NAME'] : '';
            $taxSlabName = isset($payslipDetail->setProperty4[$n]) ? $payslipDetail->setProperty4[$n]['SALARY_RANGE'] : '';
            $taxSlabTemp = isset($payslipDetail->setProperty4[$n]) ? $payslipDetail->setProperty4[$n]['TAX_PERCENTAGE'] : '';
            $taxSlabAmt = isset($payslipDetail->setProperty4[$n]) ? $payslipDetail->setProperty4[$n]['AMOUNT'] : '';

            $taxVal = $taxVal + $taxSlabAmt;

            $thirdLoop .= '<tr>';
            // $thirdLoop .= '<td style="text-align: left;padding: 8px;font-size: 11px;">' . $otherTaxName . '</td>';
            // $thirdLoop .= '<td style="text-align: right;padding: 8px;font-size: 11px;">' . $taxSum . '</td>';
            $thirdLoop .= '<td style="text-align: left;padding: 8px;font-size: 11px;">' . $taxSlabName . '</td>';
            $thirdLoop .= '<td style="text-align: right;padding: 8px;font-size: 11px;">' . $taxSlabTemp . '</td>';
            $thirdLoop .= '<td style="text-align: right;padding: 8px;font-size: 11px;">' . $taxSlabAmt . '</td>';
            $thirdLoop .= '<td style="text-align: right;padding: 8px;font-size: 11px;"></td>';
            $thirdLoop .= '</tr>';
        }

        for ($n = 0; $n < $maxSecLoop; ++$n) {
            $misName = isset($payslipDetail->miscellaneous[$n]) ? $payslipDetail->miscellaneous[$n]['VARIANCE_NAME'] : '';
            $misTemp = isset($payslipDetail->miscellaneous[$n]) ? $payslipDetail->miscellaneous[$n]['TEMPLATE_NAME'] : '';
            $bMisName = isset($payslipDetail->bMiscellaneou[$n]) ? $payslipDetail->bMiscellaneou[$n]['VARIANCE_NAME'] : '';
            $bMisTemp = isset($payslipDetail->bMiscellaneou[$n]) ? $payslipDetail->bMiscellaneou[$n]['TEMPLATE_NAME'] : '';
            $cMisName = isset($payslipDetail->cMiscellaneou[$n]) ? $payslipDetail->cMiscellaneou[$n]['VARIANCE_NAME'] : '';
            $cMisTemp = isset($payslipDetail->cMiscellaneou[$n]) ? $payslipDetail->cMiscellaneou[$n]['TEMPLATE_NAME'] : '';
            $MisVal = $payslipDetail->setProperty3[0][$misTemp];
            $bMisVal = $payslipDetail->setProperty3[0][$bMisTemp];
            $cMisVal = $payslipDetail->setProperty3[0][$cMisTemp];

            if ($MisVal == 0) {
                $misSum = $MisVal; // Return the value as is
            } else {
                $misSum = self::formatNepaliNumber($MisVal); // Format the value
            }
            if ($bMisVal == 0) {
                $bMisSum = $bMisVal; // Return the value as is
            } else {
                $bMisSum = self::formatNepaliNumber($bMisVal); // Format the value
            }
            if ($cMisVal == 0) {
                $cMisSum = $cMisVal; // Return the value as is
            } else {
                $cMisSum = self::formatNepaliNumber($cMisVal); // Format the value
            }
            $sencondLoop .= '<tr>';
            $sencondLoop .= '<td style="text-align: left;padding: 8px;font-size: 11px;">' . $misName . ' </td>';
            $sencondLoop .= '<td style="padding: 8px;text-align: right;font-size: 11px;">' . $misSum . '</td>';
            $sencondLoop .= '<td style="text-align: left;padding: 8px;font-size: 11px;">' . $bMisName . ' </td>';
            $sencondLoop .= '<td style=";padding: 8px;text-align: right;font-size: 11px;">' . $bMisSum . '</td>';
            $sencondLoop .= '<td style="text-align: left;padding: 8px;font-size: 11px;">' . $cMisName . ' </td>';
            $sencondLoop .= '<td  style="padding: 8px;text-align: right;font-size: 11px;">' . $cMisSum . '</td>';
            $sencondLoop .= '</tr><br>';
        }
        $currentDate = date('d-M-Y');
        $total = self::formatNepaliNumber($total);
        $repTemplate = '<br>
<table class="table table-bordered" style="width: 100%; border-collapse: collapse;"> <br>
<tr style="text-align: center;padding-top: 8px;"><td colspan="4"><h4 style="text-decoration: underline;"><b><span id="yearMonthDetails">Annual Details for Financial Year ' . $payslipDetail->setProperty1['FISCAL_YEAR_NAME'] . '</span></b></h4>  </td> </tr>    <br>
<tr>
                    <th colspan="2" style="font-size: 10px;;text-align: center;padding: 8px;"><b>Annual Earnings</b></th>
                    <th colspan="2" style="font-size: 10px;text-align: center;padding: 8px;"><b>Annual Deductions</b></th>
                    </tr>

    ' . $firstLoop . '
    <tr>
        <td style="border-top: 1px solid #000000;font-size: 10px;"><b>Total Earnings</b></td>
        <td style="text-align: right; border-top: 1px solid #000000;">' . $total . '</td>
        <td style="border-top: 1px solid #000000;font-size: 10px;"><b>Total Deductions</b></td>
        <td style="text-align: right;border-top: 1px solid #000000;">' . self::formatNepaliNumber($payslipDetail->sumOfExemption) . '</td>     
    </tr>
    <tr>
    <td style="font-size: 10px;"><b>Taxable Income</b></td>
    <td style="text-align: right; ">' . self::formatNepaliNumber($payslipDetail->setProperty3[0]['V40']) . '</td>
    <td style="font-size: 10px;"><b></b></td>
    <td style="text-align: right;"></td>     
</tr>
    </table> <br> <br> <br> <br>';
        $footer = '<br> <br><table class="table table-bordered" style="width: 100%; border-collapse: collapse;"> <br>
    <tr>
    <th colspan="4" style="font-size: 10px;;text-align: center;padding: 8px;"><b>Tax Slab </b></th>    <br>
    </tr>' . $thirdLoop . '
 </table>
<h6 style="font-size: 10px;border-top: 1px solid #000000;">Annual TDS&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; ' . self::formatNepaliNumber($taxVal) . '</b></h6> <br>
<h6 style="font-size: 10px;">Female Rebate&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' . self::formatNepaliNumber($payslipDetail->setProperty3[0]['V38']) . '</b></h6> <br>
<h6 style="font-size: 10px;">Net Annual TDS&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' . self::formatNepaliNumber($payslipDetail->setProperty3[0]['V61'] - $payslipDetail->setProperty3[0]['V38']) . '</b></h6><br>
<h6 style="font-size: 10px;">Previously Deducted TDS&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; ' . self::formatNepaliNumber($payslipDetail->setProperty3[0]['V37']) . '</b></h6><br>
<h6 style="font-size: 10px;">Net TDS Payable&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' . self::formatNepaliNumber($payslipDetail->setProperty3[0]['V61']) . '</b></h6><br>
<h6 style="font-size: 10px;">Per Month TDS&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; ' . self::formatNepaliNumber($payslipDetail->setProperty3[0]['V62']) . '</b></h6><br>
<br>
<h5><i>This is system generated payslip,doesnot require any signature.</i></h5><br>
<h5>Printed by : ' . $payslipDetail->setProperty1['COMPANY_NAME'] . '</h5><br>
<h5>Printed on : ' . $currentDate . '</h5><br>
</div>';

        $paySlipDetail .= $tableHeader . $additionRows . $tableFooter  . $repTemplate . $footer;
        $payslipModel->paySlipDetails = $paySlipDetail;
        $payslipModel->logo = $companyLogoPath;

        self::sendEmailWithPdfAttachment($payslipModel, 53, $adapter, $url);
    }

    private static function sendSalaryEmail(PaySlipDetailsModel $payslipDetail, AdapterInterface $adapter, Url $url)
    {

        $salarySheetModel = self::initializeNotificationModel(72, $payslipDetail->setProperty2[0]['EMPLOYEE_ID'], PayslipEmailNotificationModel::class, $adapter);

        $alarySheetDetail = '';
        $firstLoop = '';
        $secondLoop = '';
        $thirdLoop = '';

        $firstLoopArr = [
            count($payslipDetail->setProperty2)
        ];
        $secondLoopArr = [
            count($payslipDetail->setProperty1)
        ];

        $maxFirstLoop = max($firstLoopArr);
        $maxSecondLoop = max($secondLoopArr);
        $columnSums = array_fill(0, $maxSecondLoop, 0);
        $total = 0;
        for ($n = 0; $n < $maxFirstLoop; ++$n) {
            $monthTemp = isset($payslipDetail->setProperty2[$n]) ? $payslipDetail->setProperty2[$n]['MONTH_EDESC'] : '';
            $firstLoop .= '<tr>';
            $firstLoop .= '<td style="text-align: left;padding: 8px;font-size: 6px;border: 1px solid #000000;">' . ($n + 1) . ' </td>';
            $firstLoop .= '<td style="text-align: left;padding: 8px;font-size: 6px;border: 1px solid #000000;">' . $monthTemp . ' </td>';

            // Inner loop for columns
            for ($m = 0; $m < $maxSecondLoop; ++$m) {
                $variance = isset($payslipDetail->setProperty1[$m]) ? $payslipDetail->setProperty1[$m]['VARIANCE'] : '';
                $value = isset($payslipDetail->setProperty2[$n][$variance]) ? $payslipDetail->setProperty2[$n][$variance] : '';

                if ($value == 0) {
                    $varSum = $value; // Return the value as is
                } else {
                    $varSum = self::formatNepaliNumber($value); // Format the value
                }

                $firstLoop .= '<td style="text-align: right;font-size: 6px;padding: 8px;border: 1px solid #000000;">' . $varSum . ' </td>';

                // Add the value to the column sum
                $columnSums[$m] += $value;
            }

            $firstLoop .= '</tr>';
        }

        // Add the total row at the bottom
        $firstLoop .= '<tr>';
        $firstLoop .= '<td style="text-align: left;padding: 8px;font-size: 6px;border: 1px solid #000000;"colspan="2"><b>Total </b></td>';

        // Display the column sums in the total row
        foreach ($columnSums as $columnSum) {
            $firstLoop .= '<td style="text-align: right;font-size: 6px;padding: 8px;border: 1px solid #000000;"><b>' . self::formatNepaliNumber($columnSum) . '</b></td>';
        }

        $firstLoop .= '</tr>';

        for ($n = 0; $n < $maxSecondLoop; ++$n) {
            $variableName = isset($payslipDetail->setProperty1[$n]) ? $payslipDetail->setProperty1[$n]['VARIANCE_NAME'] : '';
            $secondLoop .= '<td style="text-align: left;font-size: 6px;padding: 8px;border: 1px solid #000000;">' . $variableName . ' </td>';
        }

        // for ($n = 0; $n < $maxThirdLoop; ++$n) {

        //     $variance = isset($payslipDetail->setProperty1[$n]) ? $payslipDetail->setProperty1[$n]['VARIANCE'] : '';
        //     $thirdLoop .= '<td style="text-align: left;padding: 8px;border: 1px solid #000000;">' . $payslipDetail->setProperty2[$n][$variance]. ' </td>';
        // }
        $currentDate = date('d-M-Y');
        $total = self::formatNepaliNumber($total);
        if ($payslipDetail->setProperty2[0]['FILE_PATH'] == null) {
            $companyLogoPath = 'C:/apache24/htdocs/SDS-Neo-hris_GIT/SDS-Neo-hris/public/uploads/1701407516.png';
        } else {
            $companyLogoPath = 'C:/apache24/htdocs/SDS-Neo-hris_GIT/SDS-Neo-hris/public/uploads/' . $payslipDetail->setProperty2[0]['FILE_PATH'];
        }

        $repTemplate = '
<div>
<table style="width: 100%; border-collapse: collapse;">
<tr style="text-align: center;">
<td style="text-align: center;padding-left:50px;" colspan="3">
    <h3><b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' . $payslipDetail->setProperty2[0]['COMPANY_NAME'] . '</b></h3>
    <h5><b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' . $payslipDetail->setProperty2[0]['ADDRESS'] . '</b></h5>
</td>
<td style="text-align: center;" > 
<div style="margin-top: 40px;">
    <img src="' . $companyLogoPath . '" style="height: 35px; width: 60px;">
    </div>
</td>
</tr>
</table>
<br>
<h6 style="text-align: left; margin: 0;"><b>Employee Name&nbsp;&nbsp;&nbsp;: ' . $payslipDetail->setProperty2[0]['FULL_NAME'] . '</b></h6>
<h6 style="text-align: left; margin: 0;"><b>Employee Code&nbsp;&nbsp;&nbsp;: ' . $payslipDetail->setProperty2[0]['EMPLOYEE_CODE'] . '</b></h6>
<h6 style="text-align: left; margin: 0;"><b>Department&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; : ' . $payslipDetail->setProperty2[0]['DEPARTMENT_NAME'] . '</b></h6>
<h6 style="text-align: left; margin: 0;"><b>Designation&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; : ' . $payslipDetail->setProperty2[0]['DESIGNATION_TITLE'] . '</b></h6>
<table class="table table-bordered" style="width: 100%; border-collapse: collapse;border: 1px solid #000000;"><br>
<tr>
        <td style="text-align: left;font-size: 6px;border: 1px solid #000000; padding: 8px;width:15px;"><b>S.N</b></td>
        <td style="text-align: left;font-size: 6px; border: 1px solid #000000;padding: 8px;">Month</td>
        ' . $secondLoop . '
</tr>
    ' . $firstLoop . '

</table>
<h6><i>This is system generated payslip,doesnot require any signature.</i></h6><br>
<h6>Printed by :' . $payslipDetail->setProperty2[0]['COMPANY_NAME'] . '</h6><br>
<h6>Printed on : ' . $currentDate . '</h6><br>
</div>';
        $alarySheetDetail .=  $repTemplate;
        $salarySheetModel->paySlipDetails = $alarySheetDetail;
        $salarySheetModel->logo = $companyLogoPath;

        self::sendEmailWithPdfAttachment($salarySheetModel, 53, $adapter, $url);
    }
    private static function sendLetterToBankEmail(PaySlipDetailsModel $payslipDetail, AdapterInterface $adapter, Url $url)
    {
        $salarySheetModel = self::initializeNotificationModel(72, $payslipDetail->setProperty1[0]['EMPLOYEE_ID'], PayslipEmailNotificationModel::class, $adapter);
        $salarySheetModel->toEmail = $payslipDetail->setProperty3[0]['EMAIL'];
        $letterDetail = '';

        $firstLoop = '';
        $firstLoopArr = [
            count($payslipDetail->setProperty1)
        ];
        $maxFirstLoop = max($firstLoopArr);
        $total = 0;
        for ($n = 0; $n < $maxFirstLoop; ++$n) {
            $firstLoop .= '<tr>';
            $firstLoop .= '<td style="text-align: left;padding: 8px;font-size: 8px;border: 1px solid #000000;">' . ($n + 1) . '</td>';
            $firstLoop .= '<td style="text-align: left;padding: 8px;font-size: 8px;border: 1px solid #000000;">' . $payslipDetail->setProperty1[$n]['FULL_NAME'] . '</td>';
            $firstLoop .= '<td style="text-align: left;padding: 8px;font-size: 8px;border: 1px solid #000000;">' . $payslipDetail->setProperty1[$n]['BANK_NAME'] . ' </td>';
            $firstLoop .= '<td style="text-align: left;padding: 8px;font-size: 8px;border: 1px solid #000000;">' . $payslipDetail->setProperty1[$n]['ID_ACCOUNT_NO'] . '</td>';
            $firstLoop .= '<td style="text-align: right;padding: 8px;font-size: 8px;border: 1px solid #000000;">' . $payslipDetail->setProperty1[$n]['VAL'] . ' </td>';
            $firstLoop .= '</tr>';
            $myString = trim($payslipDetail->setProperty1[$n]['VAL']);
            $total += floatval(str_replace(',', '', $myString));
        }

        $firstLoop .= '<tr>';
        $firstLoop .= '<td style="text-align: left;padding: 8px;font-size: 8px;border: 1px solid #000000;"colspan="4"><b>Total </b></td>';
        $firstLoop .= '<td style="text-align: right;font-size: 8px;padding: 8px;border: 1px solid #000000;"><b>' . self::formatNepaliNumber($total) . '</b></td>';
        $firstLoop .= '</tr>';
        $currentDate = date('d-M-Y');
        $total = self::formatNepaliNumber($total);

        if ($payslipDetail->setProperty2[0]['FILE_PATH'] == null) {
            $companyLogoPath = 'C:/apache24/htdocs/SDS-Neo-hris_GIT/SDS-Neo-hris/public/uploads/1701407516.png';
        } else {
            $companyLogoPath = 'C:/apache24/htdocs/SDS-Neo-hris_GIT/SDS-Neo-hris/public/uploads/' . $payslipDetail->setProperty2[0]['FILE_PATH'];
        }

        $repTemplate = '
        <div>
        <table style="width: 100%; border-collapse: collapse;">
        <tr style="text-align: center;">
        <td style="text-align: center;padding-left:50px;" colspan="3">
            <h4><b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' . $payslipDetail->setProperty2[0]['COMPANY_NAME'] . '</b></h4>
            <h5><b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' . $payslipDetail->setProperty2[0]['ADDRESS'] . '</b></h5>
        </td>
        <td style="text-align: center;" > 
            <div style="margin-top: 40px;">
            <img src="' . $companyLogoPath . '" style="height: 35px; width: 60px;">
            </div>
        </td>
        </tr>
        <tr>
        <td></td>
        <td></td>
        <td></td>
        <td style="text-align:right;font-size:8px"> Date : ' . $payslipDetail->setProperty1[0]['TODAY_DATE'] . '</td></tr>
        </table>
        <p style="font-size:10px;">
        To,<br>
        The Branch Manager,<br>
        <b>' . $payslipDetail->setProperty3[0]['BANK_NAME'] . '</b>.</p>
        <p style="text-align:center;font-size:10px;">Subject: <u><b>Request for amount transfer</b></u></p>
<br><br>
<p style="font-size:10px">Dear Sir / Madam,</p><br><br>
<p style="font-size:10px";>We have provided with account holders name, account number and the amount
to be deposited in the account number. Our Bank Account Number and total amount
to be debited is as mentioned below.<p>

<div style="font-weight:bold";"margin-left:7%";"font-size:10px">
(A) Amount to be deposited: ' . $total . '
<br>
(B) Account Number:
<br>
(C) Cheque No.:
</div> <p>
For the Month of :' . $payslipDetail->setProperty1[0]['MONTH_EDESC'] . '
<br>
For the Fiscal year of :' . $payslipDetail->setProperty1[0]['FISCAL_YEAR_NAME'] . '
<br>
For Bank Information :
    </p>
<br>
        <table class="table table-bordered" style="width: 100%; border-collapse: collapse;border: 1px solid #000000;">
        <tr>
                        <td style="text-align: left;padding: 8px;font-size: 8px;border: 1px solid #000000;width: 7%;">S.N.</td>
                        <td style="text-align: left;padding: 8px;font-size: 8px;border: 1px solid #000000;width: 23%;">Employee Name</td>
                        <td style="text-align: left;padding: 8px;font-size: 8px;border: 1px solid #000000;width: 27%;">Bank</td>
                        <td style="text-align: left;padding: 8px;font-size: 8px;border: 1px solid #000000;width: 20%;">Account No.</td>
                        <td style="text-align: left;padding: 8px;font-size: 8px;border: 1px solid #000000;width: 23%;">Amount</td>
                        
         </tr> 
        ' . $firstLoop . '
        </table>
        <br>
<p style="font-size:10px">' . $payslipDetail->setProperty2[0]['COMPANY_NAME'] . '
<br>
' . $payslipDetail->setProperty2[0]['ADDRESS'] . '</p>
        </div>';
        $letterDetail .=  $repTemplate;
        $salarySheetModel->paySlipDetails = $letterDetail;
        $salarySheetModel->logo = $companyLogoPath;
        self::sendEmailWithPdfAttachment($salarySheetModel, 55, $adapter, $url);
    }
    private static function appraisalEvaluation(AppraisalStatus $request, AdapterInterface $adapter, Url $url, $senderDetail, $recieverDetail)
    {
        $appraisalAssignRepo = new AppraisalAssignRepository($adapter);
        $assignedAppraisalDetail = $appraisalAssignRepo->getEmployeeAppraisalDetail($request->employeeId, $request->appraisalId);

        $fullName = function ($id, $adapter) {
            if ($id != null) {
                $empRepository = new EmployeeRepository($adapter);
                $empDtl = $empRepository->fetchById($id);
                $empMiddleName = ($empDtl['MIDDLE_NAME'] != null) ? " " . $empDtl['MIDDLE_NAME'] . " " : " ";
                return $empDtl['FIRST_NAME'] . $empMiddleName . $empDtl['LAST_NAME'];
            } else {
                return "";
            }
        };
        $notification = self::initializeNotificationModel($senderDetail['ID'], $recieverDetail['ID'], AppraisalNotificationModel::class, $adapter);

        $notification->appraisalName = $assignedAppraisalDetail['APPRAISAL_EDESC'];
        $notification->appraisalType = $assignedAppraisalDetail['APPRAISAL_TYPE_EDESC'];
        $notification->appraiseeName = $fullName($assignedAppraisalDetail['EMPLOYEE_ID'], $adapter);
        $notification->appraiserName = $fullName($assignedAppraisalDetail['APPRAISER_ID'], $adapter);
        $notification->reviewerName = $fullName($assignedAppraisalDetail['REVIEWER_ID'], $adapter);
        $notification->startDate = $assignedAppraisalDetail['START_DATE'];
        $notification->endDate = $assignedAppraisalDetail['END_DATE'];
        $notification->rating = $assignedAppraisalDetail['APPRAISER_OVERALL_RATING'];
        $notification->currentStage = $assignedAppraisalDetail['STAGE_EDESC'];

        if ($recieverDetail['USER_TYPE'] == 'APPRAISER') {
            $notification->route = json_encode(["route" => "appraisal-evaluation", "action" => "view", "appraisalId" => $request->appraisalId, "employeeId" => $request->employeeId, "tab" => 1]);
        } else if ($recieverDetail['USER_TYPE'] == 'REVIEWER') {
            $notification->route = json_encode(["route" => "appraisal-review", "action" => "view", "appraisalId" => $request->appraisalId, "employeeId" => $request->employeeId, "tab" => 1]);
        } else if ($recieverDetail['USER_TYPE'] == 'HR') {
            $notification->route = json_encode(["route" => "appraisalReport", "action" => "view", "appraisalId" => $request->appraisalId, "employeeId" => $request->employeeId]);
        } else if ($recieverDetail['USER_TYPE'] == 'APPRAISEE') {
            $notification->route = json_encode(["route" => "performanceAppraisal", "action" => "view", "appraisalId" => $request->appraisalId]);
        }

        $title = "Appraisal Evaluation";
        $desc = "Appraisal Evaluated by"
            . " $notification->fromName of type $notification->appraisalType";

        self::addNotifications($notification, $title, $desc, $adapter);
        self::sendEmail($notification, 33, $adapter, $url);
    }

    private static function appraisalReview(AppraisalStatus $request, AdapterInterface $adapter, Url $url, $type, $senderDetail, $recieverDetail)
    {
        $appraisalAssignRepo = new AppraisalAssignRepository($adapter);
        $assignedAppraisalDetail = $appraisalAssignRepo->getEmployeeAppraisalDetail($request->employeeId, $request->appraisalId);

        $fullName = function ($id, $adapter) {
            if ($id != null) {
                $empRepository = new EmployeeRepository($adapter);
                $empDtl = $empRepository->fetchById($id);
                $empMiddleName = ($empDtl['MIDDLE_NAME'] != null) ? " " . $empDtl['MIDDLE_NAME'] . " " : " ";
                return $empDtl['FIRST_NAME'] . $empMiddleName . $empDtl['LAST_NAME'];
            } else {
                return "";
            }
        };
        $notification = self::initializeNotificationModel($senderDetail['ID'], $recieverDetail['ID'], AppraisalNotificationModel::class, $adapter);

        $notification->appraisalName = $assignedAppraisalDetail['APPRAISAL_EDESC'];
        $notification->appraisalType = $assignedAppraisalDetail['APPRAISAL_TYPE_EDESC'];
        $notification->appraiseeName = $fullName($assignedAppraisalDetail['EMPLOYEE_ID'], $adapter);
        $notification->appraiserName = $fullName($assignedAppraisalDetail['APPRAISER_ID'], $adapter);
        $notification->reviewerName = $fullName($assignedAppraisalDetail['REVIEWER_ID'], $adapter);
        $notification->startDate = $assignedAppraisalDetail['START_DATE'];
        $notification->endDate = $assignedAppraisalDetail['END_DATE'];
        $notification->rating = $assignedAppraisalDetail['APPRAISER_OVERALL_RATING'];
        $notification->currentStage = $assignedAppraisalDetail['STAGE_EDESC'];

        if ($recieverDetail['USER_TYPE'] == 'APPRAISER') {
            $notification->route = json_encode(["route" => "appraisal-evaluation", "action" => "view", "appraisalId" => $request->appraisalId, "employeeId" => $request->employeeId, "tab" => 1]);
        } else if ($recieverDetail['USER_TYPE'] == 'REVIEWER') {
            $notification->route = json_encode(["route" => "appraisal-review", "action" => "view", "appraisalId" => $request->appraisalId, "employeeId" => $request->employeeId, "tab" => 1]);
        } else if ($recieverDetail['USER_TYPE'] == 'HR') {
            $notification->route = json_encode(["route" => "appraisalReport", "action" => "view", "appraisalId" => $request->appraisalId, "employeeId" => $request->employeeId]);
        } else if ($recieverDetail['USER_TYPE'] == 'APPRAISEE') {
            $notification->route = json_encode(["route" => "performanceAppraisal", "action" => "view", "appraisalId" => $request->appraisalId]);
        } else if ($recieverDetail['USER_TYPE'] == 'SUPER_REVIEWER') {
            $notification->route = json_encode(["route" => "appraisal-final-review", "action" => "view", "appraisalId" => $request->appraisalId, "employeeId" => $request->employeeId]);
        }
        $getValue = function ($val) {
            if ($val != null && $val != "") {
                if ($val == 'Y')
                    return "Agreed";
                else if ($val == 'N')
                    return "Disgreed";
            } else {
                return "";
            }
        };
        $agree = ($type == 'REVIEWER_EVALUATION') ? $assignedAppraisalDetail['REVIEWER_AGREE'] : $assignedAppraisalDetail['SUPER_REVIEWER_AGREE'];
        $title = "Appraisal Review";
        if ($agree == null) {
            $desc = "Appraisal reviewed";
        } else {
            $desc = $getValue($agree);
        }
        $desc .= " by " . $notification->fromName . " on " . $notification->appraisalName . " of type " . $notification->appraisalType;

        self::addNotifications($notification, $title, $desc, $adapter);
        self::sendEmail($notification, 34, $adapter, $url);
    }

    private static function appraiseeFeedback(AppraisalStatus $request, AdapterInterface $adapter, Url $url, $recieverDetail)
    {
        $appraisalAssignRepo = new AppraisalAssignRepository($adapter);
        $assignedAppraisalDetail = $appraisalAssignRepo->getEmployeeAppraisalDetail($request->employeeId, $request->appraisalId);

        $fullName = function ($id, $adapter) {
            if ($id != null) {
                $empRepository = new EmployeeRepository($adapter);
                $empDtl = $empRepository->fetchById($id);
                $empMiddleName = ($empDtl['MIDDLE_NAME'] != null) ? " " . $empDtl['MIDDLE_NAME'] . " " : " ";
                return $empDtl['FIRST_NAME'] . $empMiddleName . $empDtl['LAST_NAME'];
            } else {
                return "";
            }
        };
        $notification = self::initializeNotificationModel($request->employeeId, $recieverDetail['ID'], AppraisalNotificationModel::class, $adapter);

        $notification->appraisalName = $assignedAppraisalDetail['APPRAISAL_EDESC'];
        $notification->appraisalType = $assignedAppraisalDetail['APPRAISAL_TYPE_EDESC'];
        $notification->appraiseeName = $fullName($assignedAppraisalDetail['EMPLOYEE_ID'], $adapter);
        $notification->appraiserName = $fullName($assignedAppraisalDetail['APPRAISER_ID'], $adapter);
        $notification->reviewerName = $fullName($assignedAppraisalDetail['REVIEWER_ID'], $adapter);
        $notification->startDate = $assignedAppraisalDetail['START_DATE'];
        $notification->endDate = $assignedAppraisalDetail['END_DATE'];
        $notification->rating = $assignedAppraisalDetail['APPRAISER_OVERALL_RATING'];
        $notification->currentStage = $assignedAppraisalDetail['STAGE_EDESC'];

        if ($recieverDetail['USER_TYPE'] == 'APPRAISER') {
            $notification->route = json_encode(["route" => "appraisal-evaluation", "action" => "view", "appraisalId" => $request->appraisalId, "employeeId" => $request->employeeId, "tab" => 1]);
        } else if ($recieverDetail['USER_TYPE'] == 'REVIEWER') {
            $notification->route = json_encode(["route" => "appraisal-review", "action" => "view", "appraisalId" => $request->appraisalId, "employeeId" => $request->employeeId, "tab" => 1]);
        } else if ($recieverDetail['USER_TYPE'] == 'HR') {
            $notification->route = json_encode(["route" => "appraisalReport", "action" => "view", "appraisalId" => $request->appraisalId, "employeeId" => $request->employeeId]);
        } else if ($recieverDetail['USER_TYPE'] == 'SUPER_REVIEWER') {
            $notification->route = json_encode(["route" => "appraisal-final-review", "action" => "view", "appraisalId" => $request->appraisalId, "employeeId" => $request->employeeId]);
        }

        $getValue = function ($val) {
            if ($val != null && $val != "") {
                if ($val == 'Y')
                    return "Agreed";
                else if ($val == 'N')
                    return "Disagreed";
            } else {
                return "";
            }
        };
        $title = "Final Feedback on Appraisal";
        $desc = ($assignedAppraisalDetail['APPRAISEE_AGREE'] == null) ? "Feedback" : $getValue($assignedAppraisalDetail['APPRAISEE_AGREE']);
        $desc .= " by $notification->fromName on $notification->appraisalName of type $notification->appraisalType";

        self::addNotifications($notification, $title, $desc, $adapter);
        self::sendEmail($notification, 35, $adapter, $url);
    }

    public static function monthlyAppraisalAssigned(AppraisalAssign $request, AdapterInterface $adapter, Url $url)
    {
        $appraisalAssignRepo = new AppraisalAssignRepository($adapter);
        $assignedAppraisalDetail = $appraisalAssignRepo->getEmployeeAppraisalDetail($request->employeeId, $request->appraisalId);

        $fullName = function ($id, $adapter) {
            if ($id != null) {
                $empRepository = new EmployeeRepository($adapter);
                $empDtl = $empRepository->fetchById($id);
                $empMiddleName = ($empDtl['MIDDLE_NAME'] != null) ? " " . $empDtl['MIDDLE_NAME'] . " " : " ";
                return $empDtl['FIRST_NAME'] . $empMiddleName . $empDtl['LAST_NAME'];
            } else {
                return "";
            }
        };
        $notification = self::initializeNotificationModel($request->createdBy, $assignedAppraisalDetail['APPRAISER_ID'], AppraisalNotificationModel::class, $adapter);

        $notification->appraisalName = $assignedAppraisalDetail['APPRAISAL_EDESC'];
        $notification->appraisalType = $assignedAppraisalDetail['APPRAISAL_TYPE_EDESC'];
        $notification->appraiseeName = $fullName($assignedAppraisalDetail['EMPLOYEE_ID'], $adapter);
        $notification->appraiserName = $fullName($assignedAppraisalDetail['APPRAISER_ID'], $adapter);
        $notification->reviewerName = $fullName($assignedAppraisalDetail['REVIEWER_ID'], $adapter);
        $notification->startDate = $assignedAppraisalDetail['START_DATE'];
        $notification->endDate = $assignedAppraisalDetail['END_DATE'];
        $notification->rating = $assignedAppraisalDetail['APPRAISER_OVERALL_RATING'];
        $notification->currentStage = $assignedAppraisalDetail['STAGE_EDESC'];

        $notification->route = json_encode(["route" => "appraisal-evaluation", "action" => "view", "appraisalId" => $request->appraisalId, "employeeId" => $request->employeeId, "tab" => 1]);

        $title = "Monthly Appraisal Assigned";
        $desc = "$notification->appraisalName for $notification->appraiseeName is ready to evaluate";

        self::addNotifications($notification, $title, $desc, $adapter);
        self::sendEmail($notification, 40, $adapter, $url);
    }

    private static function overtimeApplied(Overtime $request, AdapterInterface $adapter, Url $url, $type)
    {
        self::initFullModel(new OvertimeRepository($adapter), $request, $request->overtimeId);
        $recommdAppModel = self::findRecApp($request->employeeId, $adapter);

        $roleAndId = self::findRoleType($recommdAppModel, $type);
        $notification = self::initializeNotificationModel($recommdAppModel[RecommendApprove::EMPLOYEE_ID], $roleAndId['id'], \Notification\Model\OvertimeReqNotificationModel::class, $adapter);

        $keys = get_object_vars($notification);
        foreach ($keys as $v) {
            if (!isset($notification->{$v}) && isset($request->{$v})) {
                $notification->{$v} = $request->{$v};
            }
        }

        $notification->route = json_encode(["route" => "overtimeApprove", "action" => "view", "id" => $request->overtimeId, "role" => $roleAndId['role']]);

        $title = "Overtime Request";
        $desc = "Overtime Request Applied";

        self::addNotifications($notification, $title, $desc, $adapter);
        self::sendEmail($notification, 37, $adapter, $url);
    }

    private static function overtimeRecommend(Overtime $request, AdapterInterface $adapter, Url $url, $status)
    {
        self::initFullModel(new OvertimeRepository($adapter), $request, $request->overtimeId);
        $recommdAppModel = self::findRecApp($request->employeeId, $adapter);

        $notification = self::initializeNotificationModel($recommdAppModel[RecommendApprove::RECOMMEND_BY], $recommdAppModel[RecommendApprove::EMPLOYEE_ID], \Notification\Model\OvertimeReqNotificationModel::class, $adapter);

        $keys = get_object_vars($notification);
        foreach ($keys as $v) {
            if (!isset($notification->{$v}) && isset($request->{$v})) {
                $notification->{$v} = $request->{$v};
            }
        }
        $notification->status = $status;

        $notification->route = json_encode(["route" => "overtimeRequest", "action" => "view", "id" => $request->overtimeId]);

        $title = "Overtime Request";
        $desc = "Recommendation of Overtime request is {$status}";

        self::addNotifications($notification, $title, $desc, $adapter);
        self::sendEmail($notification, 38, $adapter, $url);
    }

    private static function overtimeApprove(Overtime $request, AdapterInterface $adapter, Url $url, $status)
    {
        self::initFullModel(new OvertimeRepository($adapter), $request, $request->overtimeId);
        $recommdAppModel = self::findRecApp($request->employeeId, $adapter);

        $notification = self::initializeNotificationModel($recommdAppModel[RecommendApprove::APPROVED_BY], $recommdAppModel[RecommendApprove::EMPLOYEE_ID], \Notification\Model\OvertimeReqNotificationModel::class, $adapter);

        $keys = get_object_vars($notification);
        foreach ($keys as $v) {
            if (!isset($notification->{$v}) && isset($request->{$v})) {
                $notification->{$v} = $request->{$v};
            }
        }
        $notification->status = $status;

        $notification->route = json_encode(["route" => "overtimeRequest", "action" => "view", "id" => $request->overtimeId]);

        $title = "Overtime Request";
        $desc = "Approval of Overtime request is {$status}";

        self::addNotifications($notification, $title, $desc, $adapter);
        self::sendEmail($notification, 39, $adapter, $url);
    }

    private static function birthdayWished(BirthdayModel $wish, AdapterInterface $adapter, Url $url)
    {
        $notification = self::initializeNotificationModel($wish->fromEmployee, $wish->toEmployee, \Notification\Model\BirthdayNotificationModel::class, $adapter);
        $notification->route = json_encode(["route" => "birthday", "action" => "wish", "id" => $wish->toEmployee]);
        $notification->message = $wish->message;
        $title = "Birthday Wish";
        $desc = "{$notification->fromName} wished on your timeline.";

        self::addNotifications($notification, $title, $desc, $adapter);
        self::sendEmail($notification, 41, $adapter, $url);
    }

    public static function pushNotification(int $eventType, Model $model, AdapterInterface $adapter, AbstractController $context = null, $senderDetail = null, $receiverDetail = null)
    {
        $url = null;
        if ($context != null) {
            $url = $context->plugin('url');
        }

        switch ($eventType) {
            case NotificationEvents::LEAVE_APPLIED:
                self::leaveApplied($model, $adapter, $url, self::RECOMMENDER);
                break;
            case NotificationEvents::LEAVE_RECOMMEND_ACCEPTED:
                self::leaveRecommend($model, $adapter, $url, self::ACCEPTED);
                self::leaveApplied($model, $adapter, $url, self::APPROVER);
                break;
            case NotificationEvents::LEAVE_RECOMMEND_REJECTED:
                self::leaveRecommend($model, $adapter, $url, self::REJECTED);
                break;
            case NotificationEvents::LEAVE_APPROVE_ACCEPTED:
                self::leaveApprove($model, $adapter, $url, self::ACCEPTED);
                break;
            case NotificationEvents::LEAVE_APPROVE_REJECTED:
                self::leaveApprove($model, $adapter, $url, self::REJECTED);
                break;
            case NotificationEvents::ATTENDANCE_APPLIED:
                self::attendanceRequest($model, $adapter, $url, self::RECOMMENDER);
                break;
            case NotificationEvents::ATTENDANCE_RECOMMEND_ACCEPTED:
                self::attendanceRecommend($model, $adapter, $url, self::ACCEPTED);
                self::attendanceRequest($model, $adapter, $url, self::APPROVER);
                break;
            case NotificationEvents::ATTENDANCE_RECOMMEND_REJECTED:
                self::attendanceRecommend($model, $adapter, $url, self::REJECTED);
                break;
            case NotificationEvents::ATTENDANCE_APPROVE_ACCEPTED:
                self::attendanceApprove($model, $adapter, $url, self::ACCEPTED);
                break;
            case NotificationEvents::ATTENDANCE_APPROVE_REJECTED:
                self::attendanceApprove($model, $adapter, $url, self::REJECTED);
                break;
            case NotificationEvents::ADVANCE_APPLIED:
                self::advanceApplied($model, $adapter, $url, self::RECOMMENDER);
                break;
            case NotificationEvents::ADVANCE_RECOMMEND_ACCEPTED:
                self::advanceApplied($model, $adapter, $url, self::APPROVER);
                self::advanceRecommend($model, $adapter, $url, self::ACCEPTED);
                break;
            case NotificationEvents::ADVANCE_RECOMMEND_REJECTED:
                self::advanceRecommend($model, $adapter, $url, self::REJECTED);
                break;
            case NotificationEvents::ADVANCE_APPROVE_ACCEPTED:
                self::advanceApprove($model, $adapter, $url, self::ACCEPTED);
                break;
            case NotificationEvents::ADVANCE_APPROVE_REJECTED:
                self::advanceApprove($model, $adapter, $url, self::REJECTED);
                break;
            case NotificationEvents::ADVANCE_CANCELLED:
                //                ${"fn" . NotificationEvents::ADVANCE_CANCELLED}($model, $adapter, $url);
                break;
            case NotificationEvents::TRAVEL_APPLIED:
                self::travelApplied($model, $adapter, $url, self::RECOMMENDER);
                break;
            case NotificationEvents::TRAVEL_RECOMMEND_ACCEPTED:
                self::travelRecommend($model, $adapter, $url, self::ACCEPTED);
                self::travelApplied($model, $adapter, $url, self::APPROVER);
                break;
            case NotificationEvents::TRAVEL_RECOMMEND_REJECTED:
                self::travelRecommend($model, $adapter, $url, self::REJECTED);
                break;
            case NotificationEvents::TRAVEL_APPROVE_ACCEPTED:
                self::travelApprove($model, $adapter, $url, self::ACCEPTED);
                break;
            case NotificationEvents::TRAVEL_APPROVE_REJECTED:
                self::travelApprove($model, $adapter, $url, self::REJECTED);
                break;
            case NotificationEvents::TRAVEL_CANCELLED:
                //                ${"fn" . NotificationEvents::TRAVEL_CANCELLED}($model, $adapter, $url);
                break;
            case NotificationEvents::TRAINING_ASSIGNED:
                self::trainingAssigned($model, $adapter, $url, self::ASSIGNED);
                break;
            case NotificationEvents::TRAINING_CANCELLED:
                self::trainingAssigned($model, $adapter, $url, self::CANCELLED);
                break;
            case NotificationEvents::LOAN_APPLIED:
                self::loanApplied($model, $adapter, $url, self::RECOMMENDER);
                break;
            case NotificationEvents::LOAN_RECOMMEND_ACCEPTED:
                self::loanRecommend($model, $adapter, $url, self::ACCEPTED);
                self::loanApplied($model, $adapter, $url, self::APPROVER);
                break;
            case NotificationEvents::LOAN_RECOMMEND_REJECTED:
                self::loanRecommend($model, $adapter, $url, self::REJECTED);
                break;
            case NotificationEvents::LOAN_APPROVE_ACCEPTED:
                self::loanApprove($model, $adapter, $url, self::ACCEPTED);
                break;
            case NotificationEvents::LOAN_APPROVE_REJECTED:
                self::loanApprove($model, $adapter, $url, self::REJECTED);
                break;
            case NotificationEvents::WORKONDAYOFF_APPLIED:
                self::workOnDayOffApplied($model, $adapter, $url, self::RECOMMENDER);
                break;
            case NotificationEvents::WORKONDAYOFF_RECOMMEND_ACCEPTED:
                self::workOnDayOffRecommend($model, $adapter, $url, self::ACCEPTED);
                self::workOnDayOffApplied($model, $adapter, $url, self::APPROVER);
                break;
            case NotificationEvents::WORKONDAYOFF_RECOMMEND_REJECTED:
                self::workOnDayOffRecommend($model, $adapter, $url, self::REJECTED);
                break;
            case NotificationEvents::WORKONDAYOFF_APPROVE_ACCEPTED:
                self::workOnDayOffApprove($model, $adapter, $url, self::ACCEPTED);
                break;
            case NotificationEvents::WORKONDAYOFF_APPROVE_REJECTED:
                self::workOnDayOffApprove($model, $adapter, $url, self::REJECTED);
                break;
            case NotificationEvents::WORKONHOLIDAY_APPLIED:
                self::workOnHoliday($model, $adapter, $url, self::RECOMMENDER);
                break;
            case NotificationEvents::WORKONHOLIDAY_RECOMMEND_ACCEPTED:
                self::workOnHolidayRecommend($model, $adapter, $url, self::ACCEPTED);
                self::workOnHoliday($model, $adapter, $url, self::APPROVER);
                break;
            case NotificationEvents::WORKONHOLIDAY_RECOMMEND_REJECTED:
                self::workOnHolidayRecommend($model, $adapter, $url, self::REJECTED);
                break;
            case NotificationEvents::WORKONHOLIDAY_APPROVE_ACCEPTED:
                self::workOnHolidayApprove($model, $adapter, $url, self::ACCEPTED);
                break;
            case NotificationEvents::WORKONHOLIDAY_APPROVE_REJECTED:
                self::workOnHolidayApprove($model, $adapter, $url, self::REJECTED);
                break;
            case NotificationEvents::TRAINING_APPLIED:
                self::trainingApplied($model, $adapter, $url, self::RECOMMENDER);
                break;
            case NotificationEvents::TRAINING_RECOMMEND_ACCEPTED:
                self::trainingRecommend($model, $adapter, $url, self::ACCEPTED);
                self::trainingApplied($model, $adapter, $url, self::APPROVER);
                break;
            case NotificationEvents::TRAINING_RECOMMEND_REJECTED:
                self::trainingRecommend($model, $adapter, $url, self::REJECTED);
                break;
            case NotificationEvents::TRAINING_APPROVE_ACCEPTED:
                self::trainingApprove($model, $adapter, $url, self::ACCEPTED);
                break;
            case NotificationEvents::TRAINING_APPROVE_REJECTED:
                self::trainingApprove($model, $adapter, $url, self::REJECTED);
                break;
            case NotificationEvents::LEAVE_SUBSTITUTE_APPLIED:
                self::leaveSubstituteApplied($model, $adapter, $url);
                break;
            case NotificationEvents::LEAVE_SUBSTITUTE_ACCEPTED:
                self::leaveSubstituteAccepted($model, $adapter, $url, self::ACCEPTED);
                break;
            case NotificationEvents::LEAVE_SUBSTITUTE_REJECTED:
                self::leaveSubstituteAccepted($model, $adapter, $url, self::REJECTED);
                break;
            case NotificationEvents::TRAVEL_SUBSTITUTE_APPLIED:
                self::travelSubstituteApplied($model, $adapter, $url);
                break;
            case NotificationEvents::TRAVEL_SUBSTITUTE_ACCEPTED:
                self::travelSubstituteAccepted($model, $adapter, $url, self::ACCEPTED);
                break;
            case NotificationEvents::TRAVEL_SUBSTITUTE_REJECTED:
                self::travelSubstituteAccepted($model, $adapter, $url, self::REJECTED);
                break;
            case NotificationEvents::FORGOT_PASSWORD:
                self::forgotPassword($model, $adapter, $senderDetail);
                break;
            case NotificationEvents::SALARY_REVIEW:
                self::salaryReview($model, $adapter, $url);
                break;
            case NotificationEvents::KPI_SETTING:
                self::kpiSetting($model, $adapter, $url, $receiverDetail);
                break;
            case NotificationEvents::KPI_APPROVED:
                self::kpiApproved($model, $adapter, $url, $senderDetail, $receiverDetail);
                break;
            case NotificationEvents::KEY_ACHIEVEMENT:
                self::keyAchievement($model, $adapter, $url, $receiverDetail);
                break;
            case NotificationEvents::APPRAISAL_EVALUATION:
                self::appraisalEvaluation($model, $adapter, $url, $senderDetail, $receiverDetail);
                break;
            case NotificationEvents::APPRAISAL_REVIEW:
                self::appraisalReview($model, $adapter, $url, self::REVIEWER_EVALUATION, $senderDetail, $receiverDetail);
                break;
            case NotificationEvents::APPRAISAL_FINAL_REVIEW:
                self::appraisalReview($model, $adapter, $url, self::SUPER_REVIEWER_EVALUATION, $senderDetail, $receiverDetail);
                break;
            case NotificationEvents::HR_FEEDBACK:
                self::appraisalReview($model, $adapter, $url, self::HR_FEEDBACK, $senderDetail, $receiverDetail);
                break;
            case NotificationEvents::APPRAISEE_FEEDBACK:
                self::appraiseeFeedback($model, $adapter, $url, $receiverDetail);
                break;
            case NotificationEvents::MONTHLY_APPRAISAL_ASSIGNED:
                self::monthlyAppraisalAssigned($model, $adapter, $url);
                break;
            case NotificationEvents::OVERTIME_APPLIED:
                self::overtimeApplied($model, $adapter, $url, self::RECOMMENDER);
                break;
            case NotificationEvents::OVERTIME_RECOMMEND_ACCEPTED:
                self::overtimeRecommend($model, $adapter, $url, self::ACCEPTED);
                self::overtimeApplied($model, $adapter, $url, self::APPROVER);
                break;
            case NotificationEvents::OVERTIME_RECOMMEND_REJECTED:
                self::overtimeRecommend($model, $adapter, $url, self::REJECTED);
                break;
            case NotificationEvents::OVERTIME_APPROVE_ACCEPTED:
                self::overtimeApprove($model, $adapter, $url, self::ACCEPTED);
                break;
            case NotificationEvents::OVERTIME_APPROVE_REJECTED:
                self::overtimeApprove($model, $adapter, $url, self::REJECTED);
                break;
            case NotificationEvents::BIRTHDAY_WISHED:
                self::birthdayWished($model, $adapter, $url);
                break;
            case NotificationEvents::LEAVE_CANCELLED:
                self::leaveCancelled($model, $adapter, $url, self::RECOMMENDER);
                break;
            case NotificationEvents::LEAVE_CANCELLED_RECOMMEND_ACCEPTED:
                self::leaveCancelRecommend($model, $adapter, $url, self::CANCELLED_ACCEPTED);
                self::leaveCancelled($model, $adapter, $url, self::APPROVER);
                break;
            case NotificationEvents::LEAVE_CANCELLED_RECOMMEND_REJECTED:
                self::leaveCancelRecommend($model, $adapter, $url, self::CANCELLED_REJECTED);
                break;
            case NotificationEvents::LEAVE_CANCELLED_APPROVE_ACCEPTED:
                self::leaveCancelApprove($model, $adapter, $url, self::CANCELLED_ACCEPTED);
                break;

            case NotificationEvents::PAYSLIP_EMAIL:
                self::sendPayslipEmail($model, $adapter, $url);
                break;
            case NotificationEvents::SALARY_EMAIL:
                self::sendSalaryEmail($model, $adapter, $url);
                break;
            case NotificationEvents::LETTER_TO_BANK_EMAIL:
                self::sendLetterToBankEmail($model, $adapter, $url);
                break;
            case NotificationEvents::LEAVE_CANCELLED_APPROVE_REJECTED:
                self::leaveCancelApprove($model, $adapter, $url, self::CANCELLED_REJECTED);
                break;
        }
    }

    public static function mailHeader()
    {
        $headerImg = "";
        return $headerImg;
    }

    public static function mailFooter()
    {
        $footer = "";
        return $footer;
    }

    private static function initializeNotificationModel($fromId, $toId, $class, AdapterInterface $adapter)
    {
        $employeeRepo = new EmployeeRepository($adapter);
        $fromEmployee = $employeeRepo->fetchById($fromId);
        $toEmployee = $employeeRepo->fetchById($toId);
        $notification = new $class();

        $notification->fromId = $fromEmployee['EMPLOYEE_ID'];
        $notification->fromName = $fromEmployee['FIRST_NAME'] . " " . $fromEmployee['MIDDLE_NAME'] . " " . $fromEmployee['LAST_NAME'];
        $notification->fromEmail = $fromEmployee['EMAIL_OFFICIAL'];
        $notification->fromGender = $fromEmployee['GENDER_ID'];
        $notification->fromMaritualStatus = $fromEmployee['MARITAL_STATUS'];
        $notification->toEmail = $toEmployee['EMAIL_OFFICIAL'];
        $notification->toGender = $toEmployee['GENDER_ID'];
        $notification->toId = $toEmployee['EMPLOYEE_ID'];
        $notification->toMaritualStatus = $toEmployee['MARITAL_STATUS'];
        $notification->toName = $toEmployee['FIRST_NAME'] . " " . $toEmployee['MIDDLE_NAME'] . " " . $toEmployee['LAST_NAME'];
        $notification->setHonorific();

        return $notification;
    }

    private static function findRoleType($recAppModel, $type)
    {
        $id = '';
        $role = '';
        switch ($type) {
            case self::RECOMMENDER:
                $id = $recAppModel[RecommendApprove::RECOMMEND_BY];
                $role = RecommendApprove::RECOMMENDER_VALUE;
                break;
            case self::APPROVER:
                $id = $recAppModel[RecommendApprove::APPROVED_BY];
                $role = RecommendApprove::APPROVER_VALUE;
                break;
        }
        if ($recAppModel[RecommendApprove::RECOMMEND_BY] == $recAppModel[RecommendApprove::APPROVED_BY]) {
            $id = $recAppModel[RecommendApprove::RECOMMEND_BY];
            $role = RecommendApprove::BOTH_VALUE;
        }
        return ['id' => $id, 'role' => $role];
    }

    private static function findRecApp($employeeId, $adapter)
    {
        $recommdAppRepo = new RecommendApproveRepository($adapter);
        $recommdAppModel = $recommdAppRepo->getDetailByEmployeeID($employeeId);
        if ($recommdAppModel == null) {
            throw new Exception("recommender and approver not set for employee with id =>" . $employeeId);
        }
        //echo '<pre>';print_r($recommdAppModel);die;
        return $recommdAppModel;
    }

    public static function findRecAppForTrvl($employeeId, $adapter, $approverRole)
    {
        $recommdAppRepo = new RecommendApproveRepository($adapter);
        $empRepository = new EmployeeRepository($adapter);

        $recommdAppModel = $recommdAppRepo->getDetailByEmployeeID($employeeId);
        $approverFlag = ($approverRole == 'DCEO') ? [HrEmployees::IS_DCEO => 'Y'] : [HrEmployees::IS_CEO => 'Y'];
        $whereCondition = array_merge([HrEmployees::STATUS => 'E', HrEmployees::RETIRED_FLAG => 'N'], $approverFlag);
        $approverDetail = $empRepository->fetchByCondition($whereCondition);

        $recommdAppModel[RecommendApprove::RECOMMEND_BY] = $recommdAppModel[RecommendApprove::APPROVED_BY];
        $recommdAppModel[RecommendApprove::APPROVED_BY] = $approverDetail['EMPLOYEE_ID'];

        if ($recommdAppModel == null) {
            throw new Exception("recommender and approver not set for employee with id =>" . $employeeId);
        }

        return $recommdAppModel;
    }

    private static function leaveAppliedLaxmi(LeaveApply $leaveApply, AdapterInterface $adapter, Url $url, $type)
    {
        self::initFullModel(new LeaveApplyRepository($adapter), $leaveApply, $leaveApply->id);
        $recommdAppModel = self::findRecApp($leaveApply->employeeId, $adapter);

        $leaveApproveRepository = new LeaveApproveRepository($adapter);
        $empRepository = new EmployeeRepository($adapter);
        $detail = $leaveApproveRepository->fetchById($leaveApply->id);
        $CEOFlag = ($detail['PAID'] == 'N' && $detail['NO_OF_DAYS'] > 3) ? true : false;
        if ($CEOFlag) {
            $CEODtl = $empRepository->fetchByCondition([HrEmployees::STATUS => 'E', HrEmployees::IS_CEO => 'Y', HrEmployees::RETIRED_FLAG => 'N']);
            $recommdAppModel['RECOMMEND_BY'] = $recommdAppModel['APPROVED_BY'];
            $recommdAppModel['APPROVED_BY'] = $CEODtl['EMPLOYEE_ID'];
        }

        $idAndRole = self::findRoleType($recommdAppModel, $type);
        $leaveReqNotiMod = self::initializeNotificationModel($recommdAppModel[RecommendApprove::EMPLOYEE_ID], $idAndRole['id'], LeaveRequestNotificationModel::class, $adapter);

        $leaveName = self::getName($leaveApply->leaveId, new LeaveMasterRepository($adapter), 'LEAVE_ENAME');

        $leaveReqNotiMod->fromDate = $leaveApply->startDate;
        $leaveReqNotiMod->toDate = $leaveApply->endDate;
        $leaveReqNotiMod->leaveName = $leaveName;
        $leaveReqNotiMod->leaveType = $leaveApply->halfDay;
        $leaveReqNotiMod->noOfDays = $leaveApply->noOfDays;

        $leaveReqNotiMod->route = json_encode(["route" => "leaveapprove", "action" => "view", "id" => $leaveApply->id, "role" => $idAndRole['role']]);

        $notificationTitle = "Leave Request";
        $notificationDesc = "Leave Request of $leaveReqNotiMod->fromName from $leaveReqNotiMod->fromDate to $leaveReqNotiMod->toDate";

        self::addNotifications($leaveReqNotiMod, $notificationTitle, $notificationDesc, $adapter);
        self::sendEmail($leaveReqNotiMod, 1, $adapter, $url);
    }

    public static function leaveApproveLaxmi(LeaveApply $leaveApply, AdapterInterface $adapter, Url $url, string $status)
    {
        self::initFullModel(new LeaveApplyRepository($adapter), $leaveApply, $leaveApply->id);
        $recommendAppModel = self::findRecApp($leaveApply->employeeId, $adapter);
        $leaveApproveRepository = new LeaveApproveRepository($adapter);
        $empRepository = new EmployeeRepository($adapter);
        $detail = $leaveApproveRepository->fetchById($leaveApply->id);
        $CEOFlag = ($detail['PAID'] == 'N' && $detail['NO_OF_DAYS'] > 3) ? true : false;
        if ($CEOFlag) {
            $CEODtl = $empRepository->fetchByCondition([HrEmployees::STATUS => 'E', HrEmployees::IS_CEO => 'Y', HrEmployees::RETIRED_FLAG => 'N']);
            $recommendAppModel['RECOMMEND_BY'] = $recommendAppModel['APPROVED_BY'];
            $recommendAppModel['APPROVED_BY'] = $CEODtl['EMPLOYEE_ID'];
        }
        $leaveReqNotiMod = self::initializeNotificationModel($recommendAppModel[RecommendApprove::APPROVED_BY], $leaveApply->employeeId, LeaveRequestNotificationModel::class, $adapter);


        $leaveReqNotiMod->fromDate = $leaveApply->startDate;
        $leaveReqNotiMod->toDate = $leaveApply->endDate;
        $leaveReqNotiMod->leaveName = self::getName($leaveApply->leaveId, new LeaveMasterRepository($adapter), 'LEAVE_ENAME');
        $leaveReqNotiMod->leaveType = $leaveApply->halfDay;
        $leaveReqNotiMod->noOfDays = $leaveApply->noOfDays;
        $leaveReqNotiMod->leaveApprovedStatus = $status;

        $leaveReqNotiMod->route = json_encode(["route" => "leaverequest", "action" => "view", "id" => $leaveApply->id]);

        $notificationTitle = "Leave Approval";
        $notificationDesc = "Approval of Leave Request by $leaveReqNotiMod->fromName from "
            . "$leaveReqNotiMod->fromDate to $leaveReqNotiMod->toDate is $leaveReqNotiMod->leaveApprovedStatus";
        self::addNotifications($leaveReqNotiMod, $notificationTitle, $notificationDesc, $adapter);
        self::sendEmail($leaveReqNotiMod, 3, $adapter, $url);
    }

    private static function travelAppliedLaxmi(TravelRequest $request, AdapterInterface $adapter, Url $url, $type)
    {
        self::initFullModel(new TravelRequestRepository($adapter), $request, $request->travelId);
        $recommdAppModel = self::findRecAppForTrvl($request->employeeId, $adapter, $request->approverRole);
        $roleAndId = self::findRoleType($recommdAppModel, $type);
        $notification = self::initializeNotificationModel($recommdAppModel[RecommendApprove::EMPLOYEE_ID], $roleAndId['id'], \Notification\Model\TravelReqNotificationModel::class, $adapter);


        $notification->destination = $request->destination;
        $notification->fromDate = $request->fromDate;
        $notification->toDate = $request->toDate;
        $notification->purpose = $request->purpose;
        $notification->requestedAmount = $request->requestedAmount;
        $notification->requestedType = $request->requestedType;

        switch ($request->requestedType) {
            case self::TRAVEL_ADVANCE_REQUEST:
                $notification->route = json_encode(["route" => "travelApprove", "action" => "view", "id" => $request->travelId, "role" => $roleAndId['role']]);
                break;
            case self::TRAVEL_EXPENSE_REQUEST:
                $notification->route = json_encode(["route" => "travelApprove", "action" => "expenseDetail", "id" => $request->travelId, "role" => $roleAndId['role']]);
                break;
            default:
                $notification->route = json_encode(["route" => "travelApprove", "action" => "view", "id" => $request->travelId, "role" => $roleAndId['role']]);
                break;
        }
        $title = "Travel Request";
        $desc = "Travel Request of $notification->fromName from $notification->fromDate to $notification->toDate";

        self::addNotifications($notification, $title, $desc, $adapter);
        self::sendEmail($notification, 9, $adapter, $url);
    }

    private static function travelRecommendLaxmi(TravelRequest $request, AdapterInterface $adapter, Url $url, string $status)
    {
        self::initFullModel(new TravelRequestRepository($adapter), $request, $request->travelId);
        $recommdAppModel = self::findRecAppForTrvl($request->employeeId, $adapter, $request->approverRole);
        $notification = self::initializeNotificationModel(
            $recommdAppModel[RecommendApprove::RECOMMEND_BY],
            $recommdAppModel[RecommendApprove::EMPLOYEE_ID],
            \Notification\Model\TravelReqNotificationModel::class,
            $adapter
        );

        $notification->destination = $request->destination;
        $notification->fromDate = $request->fromDate;
        $notification->toDate = $request->toDate;
        $notification->purpose = $request->purpose;
        $notification->requestedAmount = $request->requestedAmount;
        $notification->requestedType = $request->requestedType;

        $notification->status = $status;

        switch ($request->requestedType) {
            case self::TRAVEL_ADVANCE_REQUEST:
                $notification->route = json_encode(["route" => "travelRequest", "action" => "view", "id" => $request->travelId]);
                break;
            case self::TRAVEL_EXPENSE_REQUEST:
                $notification->route = json_encode(["route" => "travelRequest", "action" => "viewExpense", "id" => $request->travelId]);
                break;
            default:
                $notification->route = json_encode(["route" => "travelRequest", "action" => "view", "id" => $request->travelId]);
                break;
        }
        $title = "Travel Recommendation";
        $desc = "Recommendation of Travel Request by"
            . " $notification->fromName from $notification->fromDate"
            . " to $notification->toDate is $notification->status";

        self::addNotifications($notification, $title, $desc, $adapter);
        self::sendEmail($notification, 10, $adapter, $url);
    }

    private static function travelApproveLaxmi(TravelRequest $request, AdapterInterface $adapter, Url $url, string $status)
    {
        self::initFullModel(new TravelRequestRepository($adapter), $request, $request->travelId);
        $recommdAppModel = self::findRecAppForTrvl($request->employeeId, $adapter, $request->approverRole);
        $notification = self::initializeNotificationModel(
            $recommdAppModel[RecommendApprove::APPROVED_BY],
            $recommdAppModel[RecommendApprove::EMPLOYEE_ID],
            \Notification\Model\TravelReqNotificationModel::class,
            $adapter
        );

        $notification->destination = $request->destination;
        $notification->fromDate = $request->fromDate;
        $notification->toDate = $request->toDate;
        $notification->purpose = $request->purpose;
        $notification->requestedAmount = $request->requestedAmount;
        $notification->requestedType = $request->requestedType;

        $notification->status = $status;

        switch ($request->requestedType) {
            case self::TRAVEL_ADVANCE_REQUEST:
                $notification->route = json_encode(["route" => "travelRequest", "action" => "view", "id" => $request->travelId]);
                break;
            case self::TRAVEL_EXPENSE_REQUEST:
                $notification->route = json_encode(["route" => "travelRequest", "action" => "viewExpense", "id" => $request->travelId]);
                break;
            default:
                $notification->route = json_encode(["route" => "travelRequest", "action" => "view", "id" => $request->travelId]);
                break;
        }
        $title = "Travel Approval";
        $desc = "Approval of Travel Request by"
            . " $notification->fromName from $notification->fromDate"
            . " to $notification->toDate is $notification->status";

        self::addNotifications($notification, $title, $desc, $adapter);
        self::sendEmail($notification, 11, $adapter, $url);
    }

    public static function sendMassMail(AdapterInterface $adapter, $postData, $subject, $description)
    {
        $bcc = true;

        $sendList = [];
        if (
            !isset($postData['company']) &&
            !isset($postData['branch']) &&
            !isset($postData['department']) &&
            !isset($postData['position']) &&
            !isset($postData['designation']) &&
            !isset($postData['serviceType']) &&
            !isset($postData['serviceEventType']) &&
            !isset($postData['employeeType']) &&
            !isset($postData['employee'])
        ) {
            $bcc = false;
            array_push($sendList, [
                'FULL_NAME' => 'All Staff',
                'EMAIL_OFFICIAL' => EmailHelper::massEmailId
            ]);
        } else {
            $notiRepo = new NotificationRepo($adapter);
            $sendList = $notiRepo->fetchAllEmployeeEmail($postData);
        }

        $mail = new Message();
        $mail->setSubject($subject);
        $htmlDescription = $description;
        $htmlPart = new MimePart($htmlDescription);
        $htmlPart->type = "text/html";
        $body = new MimeMessage();
        $body->setParts(array($htmlPart));
        $mail->setBody($body);


        if (EmailHelper::maxMassMail != 0 && $sendList > EmailHelper::maxMassMail) {
            $serial = 1;
            $MaxMailCounter = EmailHelper::maxMassMail;
            foreach ($sendList as $emailList) {

                $mail->addBcc($emailList['EMAIL_OFFICIAL'], $emailList['FULL_NAME']);

                if ($serial == $MaxMailCounter) {
                    $MaxMailCounter = $MaxMailCounter + EmailHelper::maxMassMail;
                    EmailHelper::sendEmail($mail);
                    $mail->setBcc([]);
                }
                $serial++;
            }
            EmailHelper::sendEmail($mail);
        } else {

            foreach ($sendList as $emailList) {
                (!$bcc) ?
                    $mail->addTo($emailList['EMAIL_OFFICIAL'], $emailList['FULL_NAME']) :
                    $mail->addBcc($emailList['EMAIL_OFFICIAL'], $emailList['FULL_NAME']);
            }

            EmailHelper::sendEmail($mail);
        }
    }

    private static function leaveCancelled(LeaveApply $leaveApply, AdapterInterface $adapter, Url $url, $type)
    {
        self::initFullModel(new LeaveApplyRepository($adapter), $leaveApply, $leaveApply->id);
        $recommdAppModel = self::findRecApp($leaveApply->employeeId, $adapter);
        $idAndRole = self::findRoleType($recommdAppModel, $type);
        $leaveReqNotiMod = self::initializeNotificationModel($recommdAppModel[RecommendApprove::EMPLOYEE_ID], $idAndRole['id'], LeaveRequestNotificationModel::class, $adapter);

        //
        $leaveName = self::getName($leaveApply->leaveId, new LeaveMasterRepository($adapter), 'LEAVE_ENAME');

        $leaveReqNotiMod->fromDate = $leaveApply->startDate;
        $leaveReqNotiMod->toDate = $leaveApply->endDate;
        $leaveReqNotiMod->leaveName = $leaveName;
        $leaveReqNotiMod->leaveType = $leaveApply->halfDay;
        $leaveReqNotiMod->noOfDays = $leaveApply->noOfDays;

        $leaveReqNotiMod->route = json_encode(["route" => "leaveapprove", "action" => "cancelView", "id" => $leaveApply->id, "role" => $idAndRole['role']]);
        //
        $notificationTitle = "Leave Cancel Request";
        $notificationDesc = "Leave Cancel Request of $leaveReqNotiMod->fromName from $leaveReqNotiMod->fromDate to $leaveReqNotiMod->toDate";

        self::addNotifications($leaveReqNotiMod, $notificationTitle, $notificationDesc, $adapter);
        self::sendEmail($leaveReqNotiMod, 42, $adapter, $url);
    }

    private static function leaveCancelRecommend(LeaveApply $leaveApply, AdapterInterface $adapter, Url $url, string $status)
    {
        self::initFullModel(new LeaveApplyRepository($adapter), $leaveApply, $leaveApply->id);
        $recommendAppModel = self::findRecApp($leaveApply->employeeId, $adapter);
        $leaveReqNotiMod = self::initializeNotificationModel($recommendAppModel[RecommendApprove::RECOMMEND_BY], $leaveApply->employeeId, LeaveRequestNotificationModel::class, $adapter);

        //
        $leaveReqNotiMod->fromDate = $leaveApply->startDate;
        $leaveReqNotiMod->toDate = $leaveApply->endDate;
        $leaveReqNotiMod->leaveName = self::getName($leaveApply->leaveId, new LeaveMasterRepository($adapter), 'LEAVE_ENAME');
        $leaveReqNotiMod->leaveType = $leaveApply->halfDay;
        $leaveReqNotiMod->noOfDays = $leaveApply->noOfDays;
        $leaveReqNotiMod->leaveRecommendStatus = $status;
        $leaveReqNotiMod->route = json_encode(["route" => "leaverequest", "action" => "view", "id" => $leaveApply->id]);
        //
        $notificationTitle = "Leave Cancel Recommended";
        $notificationDesc = "Recommendation of Leave Cancel Request by"
            . " $leaveReqNotiMod->fromName from $leaveReqNotiMod->fromDate"
            . " to $leaveReqNotiMod->toDate is $leaveReqNotiMod->leaveRecommendStatus";
        self::addNotifications($leaveReqNotiMod, $notificationTitle, $notificationDesc, $adapter);
        self::sendEmail($leaveReqNotiMod, 43, $adapter, $url);
    }

    public static function leaveCancelApprove(LeaveApply $leaveApply, AdapterInterface $adapter, Url $url, string $status)
    {
        self::initFullModel(new LeaveApplyRepository($adapter), $leaveApply, $leaveApply->id);
        $recommendAppModel = self::findRecApp($leaveApply->employeeId, $adapter);
        $leaveReqNotiMod = self::initializeNotificationModel($recommendAppModel[RecommendApprove::APPROVED_BY], $leaveApply->employeeId, LeaveRequestNotificationModel::class, $adapter);


        $leaveReqNotiMod->fromDate = $leaveApply->startDate;
        $leaveReqNotiMod->toDate = $leaveApply->endDate;
        $leaveReqNotiMod->leaveName = self::getName($leaveApply->leaveId, new LeaveMasterRepository($adapter), 'LEAVE_ENAME');
        $leaveReqNotiMod->leaveType = $leaveApply->halfDay;
        $leaveReqNotiMod->noOfDays = $leaveApply->noOfDays;
        $leaveReqNotiMod->leaveApprovedStatus = $status;

        $leaveReqNotiMod->route = json_encode(["route" => "leaverequest", "action" => "view", "id" => $leaveApply->id]);

        $notificationTitle = "Leave Cancel Approval";
        $notificationDesc = "Approval of Leave Cancel Request by $leaveReqNotiMod->fromName from "
            . "$leaveReqNotiMod->fromDate to $leaveReqNotiMod->toDate is $leaveReqNotiMod->leaveApprovedStatus";
        self::addNotifications($leaveReqNotiMod, $notificationTitle, $notificationDesc, $adapter);
        self::sendEmail($leaveReqNotiMod, 44, $adapter, $url);
    }
}
