<?php

namespace Notification\Controller;

use Advance\Model\AdvanceRequestModel;
use Advance\Repository\AdvanceRequestRepository;
use Application\Helper\EmailHelper;
use Application\Helper\Helper;
use Application\Model\ForgotPassword;
use Application\Model\Model;
use Application\Repository\RepositoryInterface;
use Appraisal\Model\AppraisalAssign;
use Appraisal\Model\AppraisalStatus;
use Appraisal\Repository\AppraisalAssignRepository;
use Exception;
use AttendanceManagement\Model\ShiftSetup;
use System\Model\UserSetup;
use Notification\Model\PaySlipDetailsModel;
use System\Repository\UserSetupRepository;
use HolidayManagement\Repository\HolidayRepository;
use Html2Text\Html2Text;
use SelfService\Repository\EventRequestRepository;
use LeaveManagement\Model\LeaveApply;
use LeaveManagement\Repository\LeaveApplyRepository;
use LeaveManagement\Repository\LeaveMasterRepository;
use ManagerService\Model\SalaryDetail;
use ManagerService\Repository\LeaveApproveRepository;
use ManagerService\Repository\SalaryDetailRepo;
use Notification\Model\AppraisalNotificationModel;
use Notification\Model\LeaveRequestNotificationModel;
use Notification\Model\PayslipEmailNotificationModel;
use Notification\Model\LeaveSubNotificationModel;
use Notification\Model\NewUserNotificationModel;
use Notification\Model\Notification;
use Notification\Model\NotificationEvents;
use SelfService\Model\EventRequest as EventRequestModel;
use Notification\Model\NotificationModel;
use Notification\Model\SalaryReviewNotificationModel;
use Notification\Model\TrainingReqNotificationModel;
use Notification\Model\EventReqNotificationModel;
use Notification\Model\TravelSubNotificationModel;
use Notification\Model\WorkOnDayoffNotificationModel;
use Notification\Model\WorkOnHolidayNotificationModel;
use Notification\Repository\NotificationRepo;
use SelfService\Controller\EventRequest;
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
use Training\Model\EventAssign;
use Setup\Model\Events;
use Setup\Repository\EmployeeRepository;
use Setup\Repository\RecommendApproveRepository;
use Setup\Repository\TrainingRepository;
use Setup\Repository\EventsRepository;
use Training\Model\TrainingAssign;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Mail\Message;
use Zend\Mime\Message as MimeMessage;
use Zend\Mime\Part as MimePart;
use Zend\Mvc\Controller\AbstractController;
use Zend\Mvc\Controller\Plugin\Url;
use Notification\Model\RoasterAssignModel;

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
        // echo '<pre>';
        // print_r($notification);
        // die;
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

            throw new Exception("Receiver email is not set or valid.");
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
        // echo '<pre>';print_r($url);die;
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
                $title = "Travel Request";
                $desc = "Travel Request";
                self::sendEmail($notification, 9, $adapter, $url);
                break;
            case self::TRAVEL_EXPENSE_REQUEST:
                $notification->route = json_encode(["route" => "travelApprove", "action" => "expenseDetail", "id" => $request->travelId, "role" => $roleAndId['role']]);
                $title = "Expense Reimbursement Request";
                $desc = "Expense Reimbursement Request";
                self::sendEmail($notification, 45, $adapter, $url);
                break;
            default:
                $notification->route = json_encode(["route" => "travelApprove", "action" => "view", "id" => $request->travelId, "role" => $roleAndId['role']]);
                $title = "Travel Request";
                $desc = "Travel Request";
                self::sendEmail($notification, 9, $adapter, $url);
                break;
        }

        self::addNotifications($notification, $title, $desc, $adapter);
    }

    private static function sendPayslipEmail(PaySlipDetailsModel $payslipDetail, AdapterInterface $adapter, Url $url)
    {
        // self::initFullModel(new EmployeeRepository($adapter), $payslipDetail, $payslipDetail->setProperty1['EMPLOYEE_ID']);
        $payslipModel = self::initializeNotificationModel(72, $payslipDetail->setProperty1['EMPLOYEE_ID'], PayslipEmailNotificationModel::class, $adapter);
        $property = $payslipDetail->setProperty2;
        $paySlipDetail = '';

        $paySlipDetail .= '
        <table class="table table-bordered" style="width: 100%; border-collapse: collapse;border:1px solid #dddddd;">
                <tr style="text-align: center;padding-top: 8px;"><td colspan="4"><h2><b><span id="yearMonthDetails">PaySlip of ' . $payslipDetail->setProperty1['FULL_NAME'] . ' for ' . $payslipDetail->setProperty1['MONTH_EDESC'] . ' ' . $payslipDetail->setProperty1['YEAR'] . '</span></b></h2>  </td> </tr>
                <tr>
                    <td style="border: 1px solid #dddddd;text-align: left;padding: 8px;">Employee Id </td>
                    <td style="border: 1px solid #dddddd;text-align: left;padding: 8px;">' . $payslipDetail->setProperty1['EMPLOYEE_ID'] . '</td>
                    <td style="border: 1px solid #dddddd;text-align: left;padding: 8px;">Employee Name  </td>
                    <td style="border: 1px solid #dddddd;text-align: left;padding: 8px;">' . $payslipDetail->setProperty1['FULL_NAME'] . '</td>
                </tr>
                <tr>
                    <td style="border: 1px solid #dddddd;text-align: left;padding: 8px;">Marital Status  </td>
                    <td style="border: 1px solid #dddddd;text-align: left;padding: 8px;">' . $payslipDetail->setProperty1['MARITAL_STATUS_DESC'] . '</td>
                    <td style="border: 1px solid #dddddd;text-align: left;padding: 8px;">Department </td>
                    <td style="border: 1px solid #dddddd;text-align: left;padding: 8px;">' . $payslipDetail->setProperty1['DEPARTMENT_NAME'] . '</td>
                </tr>
                <tr>
                <td style="border: 1px solid #dddddd;text-align: left;padding: 8px;">PAN No </td>
                <td style="border: 1px solid #dddddd;text-align: left;padding: 8px;">' . $payslipDetail->setProperty1['ID_PAN_NO'] . '</td>
                    <td style="border: 1px solid #dddddd;text-align: left;padding: 8px;">A/c No </td>
                    <td style="border: 1px solid #dddddd;text-align: left;padding: 8px;">' . $payslipDetail->setProperty1['ID_ACCOUNT_NO'] . '</td>
                </tr>
                
                <tr>
                    <td style="border: 1px solid #dddddd;text-align: left;padding: 8px;">CIT No </td>
                    <td style="border: 1px solid #dddddd;text-align: left;padding: 8px;">' . $payslipDetail->setProperty1['ID_RETIREMENT_NO'] . '</td>
                    <td style="border: 1px solid #dddddd;text-align: left;padding: 8px;">Date of Join </td>
                    <td style="border: 1px solid #dddddd;text-align: left;padding: 8px;">' . $payslipDetail->setProperty1['JOIN_DATE'] . '</td>
                    
                </tr>
               

                <tr>
                    <td style="border: 1px solid #dddddd;text-align: left;padding: 8px;">Position Name </td>
                    <td style="border: 1px solid #dddddd;text-align: left;padding: 8px;">' . $payslipDetail->setProperty1['POSITION_NAME'] . '</td>
                    <td style="border: 1px solid #dddddd;text-align: left;padding: 8px;">Designation Name </td>
                    <td style="border: 1px solid #dddddd;text-align: left;padding: 8px;">' . $payslipDetail->setProperty1['DESIGNATION_TITLE'] . '</td>
                </tr>
            ';
        if ($payslipDetail->setProperty1['EXCHANGE_RATE'] == 'Rs ') {
            $exchangeRateHtml = ' </table>';
        } else {
            $exchangeRateHtml = '
            <tr>
             <td style="border: 1px solid #dddddd;text-align: left;padding: 8px;">Exchange Rate </td>
             <td style="border: 1px solid #dddddd;text-align: left;padding: 8px;">' . $payslipDetail->setProperty1['EXCHANGE_RATE'] . '</td>
             <td style="border: 1px solid #dddddd;text-align: left;padding: 8px;"></td>
             <td style="border: 1px solid #dddddd;text-align: left;padding: 8px;"></td>
         </tr>
         </table>';
        }
        $tableHeader = '
            <table class="table table-bordered" style="width: 100%; border-collapse: collapse;border:1px solid #dddddd;">
                <tr>
                    <th colspan="2" style="font-size: 14px;border: 1px solid #dddddd;text-align: left;padding: 8px;"><b>Addition</b></th>
                    <th colspan="2" style="font-size: 14px;border: 1px solid #dddddd;text-align: left;padding: 8px;"><b>Deduction</b></th>
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
            $add = number_format($additionSum, 2, '.', '');
            $sub = number_format($deductionSum, 2, '.', '');
            $net = number_format($netSum, 2, '.', '');
        }

        $maxRow = max($additionCounter, $deductionCounter);
        $additionRows = '';
        for ($i = 0; $i < $maxRow; $i++) {
            $additionRow = '
            <tr>
                <td style="border: 1px solid #dddddd;text-align: left;padding: 8px;">' . (isset($additionData[$i]) ? $additionData[$i]['PAY_EDESC'] : '') . '</td>
                <td style="border: 1px solid #dddddd;text-align: left;padding: 8px;">' . (isset($additionData[$i]) ? $additionData[$i]['VAL'] : '') . '</td>
                <td style="border: 1px solid #dddddd;text-align: left;padding: 8px;">' . (isset($deductionData[$i]) ? $deductionData[$i]['PAY_EDESC'] : '') . '</td>
                <td style="border: 1px solid #dddddd;text-align: left;padding: 8px;">' . (isset($deductionData[$i]) ? $deductionData[$i]['VAL'] : '') . '</td>
            </tr>';
            $additionRows .= $additionRow;
        }
        $tableFooter = '
            <tr>
                <td style="border: 1px solid #dddddd;text-align: left;padding: 8px;"><b>Total Addition</b></td>
                <td style="border: 1px solid #dddddd;text-align: left;padding: 8px;"><b>' . $add . '</b></td>
                <td style="border: 1px solid #dddddd;text-align: left;padding: 8px;"><b>Total Deduction</b></td>
                <td style="border: 1px solid #dddddd;text-align: left;padding: 8px;"><b>' . $sub . ' </b></td>
            </tr>
            <tr>
                <td style="border: 1px solid #dddddd;text-align: left;padding: 8px;" ><b>Net Salary</b></td>
                <td style="border: 1px solid #dddddd;text-align: left;padding: 8px;"colspan="3"><b>' . $net . '</b></td>
            </tr>
        </tbody>
        </table>';
        $paySlipDetail .= $exchangeRateHtml . $tableHeader . $additionRows . $tableFooter;

        $payslipModel->paySlipDetails = $paySlipDetail;
        self::sendEmail($payslipModel, 53, $adapter, $url);
    }

    private static function newUserEmail(UserSetup $userSetup, AdapterInterface $adapter, Url $url)
    {
        self::initFullModel(new UserSetupRepository($adapter), $userSetup, $userSetup->userId);
        $newUserModel = self::initializeNotificationModel(72, $userSetup->employeeId, NewUserNotificationModel::class, $adapter);


        $newUserModel->userName = $userSetup->userName;
        $newUserModel->password = $userSetup->password;

        self::sendEmail($newUserModel, 52, $adapter, $url);
    }

    private static function roasterAssigned(RoasterAssignModel $roasterReport, AdapterInterface $adapter, Url $url)
    {

        // self::initFullModel(new RoasterRepo($adapter), $roasterReport, $roasterReport->employeeId);
        $notification = self::initializeNotificationModel(
            $roasterReport->fromId,
            $roasterReport->employeeId,
            \Notification\Model\RoasterModel::class,
            $adapter
        );
        $notification->employeeId = $roasterReport->employeeId;
        $notification->fromDate = $roasterReport->fromDate;
        $notification->toDate = $roasterReport->toDate;
        $notification->fromId = $roasterReport->fromId;
        $notification->fullName = $roasterReport->fullName;
        $notification->route = json_encode(["route" => "roasterReport", "action" => "index", "id" => $roasterReport->employeeId]);
        $title = "Roaster Assign";
        $desc = "$roasterReport->fullName has been assigned roaster from $roasterReport->fromDate to  $roasterReport->toDate";

        self::addNotifications($notification, $title, $desc, $adapter);
        self::sendEmail($notification, 54, $adapter, $url);
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
                $notification->route = json_encode(["route" => "newtravelrequest", "action" => "view", "id" => $request->travelId]);
                $title = "Travel Recommendation";
                $desc = "Travel Recommendation {$status}";
                self::sendEmail($notification, 10, $adapter, $url);
                break;
            case self::TRAVEL_EXPENSE_REQUEST:
                $notification->route = json_encode(["route" => "newtravelrequest", "action" => "expenseView", "id" => $request->travelId]);
                $title = "Expense Reimbursement Recommendation";
                $desc = "Expense Reimbursement Recommendation {$status}";
                self::sendEmail($notification, 47, $adapter, $url);
                break;
            default:
                $notification->route = json_encode(["route" => "newtravelrequest", "action" => "view", "id" => $request->travelId]);
                $title = "Travel Recommendation";
                $desc = "Travel Recommendation {$status}";
                self::sendEmail($notification, 10, $adapter, $url);
                break;
        }

        // echo '<pre>';print_r($notification);die;
        self::addNotifications($notification, $title, $desc, $adapter);
        // self::sendEmail($notification, 10, $adapter, $url);
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
                $notification->route = json_encode(["route" => "newtravelrequest", "action" => "view", "id" => $request->travelId]);
                $title = "Travel Approval";
                $desc = "Travel Approval {$status}";
                self::sendEmail($notification, 11, $adapter, $url);
                break;
            case self::TRAVEL_EXPENSE_REQUEST:
                $notification->route = json_encode(["route" => "newtravelrequest", "action" => "expenseView", "id" => $request->travelId]);
                $title = "Expense Reimbursement";
                $desc = "Expense Reimbursement {$status}";
                self::sendEmail($notification, 46, $adapter, $url);
                break;
            default:
                $notification->route = json_encode(["route" => "newtravelrequest", "action" => "view", "id" => $request->travelId]);
                $title = "Travel Approval";
                $desc = "Travel Approval {$status}";
                self::sendEmail($notification, 11, $adapter, $url);
                break;
        }
        // echo '<pre>';print_r($notification);die;

        self::addNotifications($notification, $title, $desc, $adapter);
        // self::sendEmail($notification, 11, $adapter, $url);
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
        // echo '<pre>';print_r($request);die;
        self::addNotifications($notification, $title, $desc, $adapter);
        self::sendEmail($notification, 12, $adapter, $url);
    }

    private static function eventAssigned(EventAssign $request, AdapterInterface $adapter, Url $url, $type)
    {
        $notification = self::initializeNotificationModel($request->createdBy, $request->employeeId, \Notification\Model\EventReqNotificationModel::class, $adapter);

        $events = new Events();
        self::initFullModel(new EventsRepository($adapter), $events, $request->eventId);

        $notification->duration = $events->duration;
        $notification->endDate = $events->endDate;
        $notification->startDate = $events->startDate;
        $notification->instructorName = $events->instructorName;
        //        $notification->eventsCode = $events->eventsCode;
        $notification->eventName = $events->eventName;
        $notification->eventType = $events->eventType;
        $notification->status = $type;


        $notification->route = json_encode(["route" => "eventList", "action" => "view", "employeeId" => $request->employeeId, "eventId" => $request->eventId]);
        $title = "Event $type";
        $desc = "Event $type";

        self::addNotifications($notification, $title, $desc, $adapter);
        self::sendEmail($notification, 48, $adapter, $url);
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
        $notification->startDate = $trainingRequestDetail['START_DATE'];
        $notification->endDate = $trainingRequestDetail['END_DATE'];
        $notification->duration = $trainingRequestDetail['DURATION'];

        $title = "Training Request";
        $desc = "Training Request of $notification->fromName from $notification->startDate to $notification->endDate";

        self::addNotifications($notification, $title, $desc, $adapter);
        self::sendEmail($notification, 22, $adapter, $url);
    }

    private static function eventApplied(EventRequestModel $request, AdapterInterface $adapter, Url $url, $type)
    {
        $eventReqRepo = new EventRequestRepository($adapter);
        $eventReqDetail = $eventReqRepo->fetchById($request->requestId);
        $request->exchangeArrayFromDB($eventReqDetail);

        $recommdAppModel = self::findRecApp($request->employeeId, $adapter);
        $roleAndId = self::findRoleType($recommdAppModel, $type);
        $notification = self::initializeNotificationModel($recommdAppModel[RecommendApprove::EMPLOYEE_ID], $roleAndId['id'], EventReqNotificationModel::class, $adapter);

        $notification->route = json_encode(["route" => "eventApprove", "action" => "view", "id" => $request->requestId, "role" => $roleAndId['role']]);

        $notification->eventType = $eventReqDetail['EVENT_TYPE_DETAIL'];
        $notification->eventName = $eventReqDetail['TITLE'];
        $notification->startDate = $eventReqDetail['START_DATE'];
        $notification->endDate = $eventReqDetail['END_DATE'];
        $notification->duration = $eventReqDetail['DURATION'];

        $title = "Event Request";
        $desc = "Event Request of $notification->fromName from $notification->startDate to $notification->endDate";

        self::addNotifications($notification, $title, $desc, $adapter);
        self::sendEmail($notification, 49, $adapter, $url);
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
        $notification->startDate = $trainingRequestDetail['START_DATE'];
        $notification->endDate = $trainingRequestDetail['END_DATE'];
        $notification->duration = $trainingRequestDetail['DURATION'];
        $notification->remarks = $request->remarks;
        $notification->status = $status;

        $notification->route = json_encode(["route" => "trainingRequest", "action" => "view", "id" => $request->requestId]);
        $title = "Training Recommendation";
        $desc = "Recommendation of Training Request by"
            . " $notification->fromName from $notification->startDate"
            . " to $notification->endDate is $notification->status";

        self::addNotifications($notification, $title, $desc, $adapter);
        self::sendEmail($notification, 23, $adapter, $url);
    }

    private static function eventRecommend(EventRequestModel $request, AdapterInterface $adapter, Url $url, string $status)
    {
        $eventReqRepo = new EventRequestRepository($adapter);
        $eventReqDetail = $eventReqRepo->fetchById($request->requestId);
        $request->exchangeArrayFromDB($eventReqDetail);

        $recommdAppModel = self::findRecApp($request->employeeId, $adapter);
        $notification = self::initializeNotificationModel($recommdAppModel[RecommendApprove::RECOMMEND_BY], $recommdAppModel[RecommendApprove::EMPLOYEE_ID], EventReqNotificationModel::class, $adapter);

        $notification->eventType = $eventReqDetail['EVENT_TYPE_DETAIL'];
        $notification->eventName = $eventReqDetail['TITLE'];
        //        $notification->trainingCode = $eventReqDetail['TRAINING_CODE'];
        $notification->startDate = $eventReqDetail['START_DATE'];
        $notification->endDate = $eventReqDetail['END_DATE'];
        $notification->duration = $eventReqDetail['DURATION'];
        $notification->remarks = $request->remarks;
        $notification->status = $status;

        $notification->route = json_encode(["route" => "eventRequest", "action" => "view", "id" => $request->requestId]);
        $title = "Event Recommendation";
        $desc = "Recommendation of Event Request by"
            . " $notification->fromName from $notification->startDate"
            . " to $notification->endDate is $notification->status";

        self::addNotifications($notification, $title, $desc, $adapter);
        self::sendEmail($notification, 50, $adapter, $url);
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
        $notification->startDate = $trainingRequestDetail['START_DATE'];
        $notification->endDate = $trainingRequestDetail['END_DATE'];
        $notification->duration = $trainingRequestDetail['DURATION'];
        $notification->remarks = $request->remarks;
        $notification->status = $status;

        $notification->route = json_encode(["route" => "trainingRequest", "action" => "view", "id" => $request->requestId]);
        $title = "Training Approval";
        $desc = "Approval of Training Request by"
            . " $notification->fromName from $notification->startDate"
            . " to $notification->endDate is $notification->status";

        self::addNotifications($notification, $title, $desc, $adapter);
        self::sendEmail($notification, 24, $adapter, $url);
    }

    private static function eventApprove(EventRequestModel $request, AdapterInterface $adapter, Url $url, string $status)
    {
        $eventReqRepo = new EventRequestRepository($adapter);
        $eventReqDetail = $eventReqRepo->fetchById($request->requestId);
        $request->exchangeArrayFromDB($eventReqDetail);

        $recommdAppModel = self::findRecApp($request->employeeId, $adapter);

        $notification = self::initializeNotificationModel(
            $recommdAppModel[RecommendApprove::APPROVED_BY],
            $recommdAppModel[RecommendApprove::EMPLOYEE_ID],
            EventReqNotificationModel::class,
            $adapter
        );

        $notification->eventType = $eventReqDetail['EVENT_TYPE_DETAIL'];
        $notification->eventName = $eventReqDetail['TITLE'];
        //        $notification->trainingCode = $eventReqDetail['TRAINING_CODE'];
        $notification->startDate = $eventReqDetail['START_DATE'];
        $notification->endDate = $eventReqDetail['END_DATE'];
        $notification->duration = $eventReqDetail['DURATION'];
        $notification->remarks = $request->remarks;
        $notification->status = $status;

        $notification->route = json_encode(["route" => "eventRequest", "action" => "view", "id" => $request->requestId]);
        $title = "Event Approval";
        $desc = "Approval of Event Request by"
            . " $notification->fromName from $notification->startDate"
            . " to $notification->endDate is $notification->status";

        self::addNotifications($notification, $title, $desc, $adapter);
        self::sendEmail($notification, 51, $adapter, $url);
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
                // self::travelApplied($model, $adapter, $url, self::APPROVER);
                break;
            case NotificationEvents::TRAVEL_RECOMMEND_ACCEPTED:
                self::travelApplied($model, $adapter, $url, self::APPROVER);
                self::travelRecommend($model, $adapter, $url, self::ACCEPTED);
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
            case NotificationEvents::EVENT_ASSIGNED:
                self::eventAssigned($model, $adapter, $url, self::ASSIGNED);
                break;
            case NotificationEvents::EVENT_CANCELLED:
                self::eventAssigned($model, $adapter, $url, self::CANCELLED);
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
            case NotificationEvents::EVENT_APPLIED:
                self::eventApplied($model, $adapter, $url, self::RECOMMENDER);
                break;
            case NotificationEvents::EVENT_RECOMMEND_ACCEPTED:
                self::eventRecommend($model, $adapter, $url, self::ACCEPTED);
                self::eventApplied($model, $adapter, $url, self::APPROVER);
                break;
            case NotificationEvents::EVENT_RECOMMEND_REJECTED:
                self::eventRecommend($model, $adapter, $url, self::REJECTED);
                break;
            case NotificationEvents::EVENT_APPROVE_ACCEPTED:
                self::eventApprove($model, $adapter, $url, self::ACCEPTED);
                break;
            case NotificationEvents::EVENT_APPROVE_REJECTED:
                self::eventApprove($model, $adapter, $url, self::REJECTED);
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
                self::leaveCancelRecommend($model, $adapter, $url, self::ACCEPTED);
                self::leaveCancelled($model, $adapter, $url, self::APPROVER);
                break;
            case NotificationEvents::LEAVE_CANCELLED_RECOMMEND_REJECTED:
                self::leaveCancelRecommend($model, $adapter, $url, self::REJECTED);
                break;
            case NotificationEvents::LEAVE_CANCELLED_APPROVE_ACCEPTED:
                self::leaveCancelApprove($model, $adapter, $url, self::ACCEPTED);
                break;
            case NotificationEvents::LEAVE_CANCELLED_APPROVE_REJECTED:
                self::leaveCancelApprove($model, $adapter, $url, self::REJECTED);
                break;
            case NotificationEvents::TRAVEL_EXPENSE_APPLIED:
                self::travelApplied($model, $adapter, $url, self::RECOMMENDER);
                self::travelApplied($model, $adapter, $url, self::APPROVER);
                break;
            case NotificationEvents::TRAVEL_EXPENSE_APPROVED:
                self::travelApprove($model, $adapter, $url, self::ACCEPTED);
                self::travelApplied($model, $adapter, $url, self::APPROVER);
                break;
            case NotificationEvents::TRAVEL_EXPENSE_REJECTED:
                self::travelApprove($model, $adapter, $url, self::REJECTED);
                break;

            case NotificationEvents::NEW_USER_CREATED:
                self::newUserEmail($model, $adapter, $url);
                break;
            case NotificationEvents::PAYSLIP_EMAIL:
                self::sendPayslipEmail($model, $adapter, $url);
                break;
            case NotificationEvents::RAOSTER_ASSIGN:
                self::roasterAssigned($model, $adapter, $url);
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
