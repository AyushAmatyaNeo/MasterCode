<?php

namespace Notification\Controller;

use Application\Helper\Helper;
use Notification\Model\AdvanceRequestNotificationModel;
use Notification\Model\AppraisalNotificationModel;
use Notification\Model\AttendanceRequestNotificationModel;
use Notification\Model\BirthdayNotificationModel;
use Notification\Model\EmailTemplate;
use Notification\Model\LeaveRequestNotificationModel;
use Notification\Model\LeaveSubNotificationModel;
use Notification\Model\LoanRequestNotificationModel;
use Notification\Model\OvertimeReqNotificationModel;
use Notification\Model\SalaryReviewNotificationModel;
use Notification\Model\TrainingReqNotificationModel;
use Notification\Model\EventReqNotificationModel;
use Notification\Model\EventNotificationModel;
use Notification\Model\TravelReqNotificationModel;
use Notification\Model\TravelSubNotificationModel;
use Notification\Model\WorkOnDayoffNotificationModel;
use Notification\Model\WorkOnHolidayNotificationModel;
use Notification\Repository\EmailTemplateRepo;
use Zend\Authentication\AuthenticationService;
use Zend\Db\Adapter\AdapterInterface;
use Notification\Model\ExpenseReqNotificationModel;
use Zend\Mvc\Controller\AbstractActionController;
use Notification\Model\NewUserNotificationModel;
use Notification\Model\PayslipEmailNotificationModel;

class EmailController extends AbstractActionController
{

    private $employeeId;
    private $adapter;
    private $templateRepo;

    const EMAIL_TYPES = [
        1 => "Leave_Request",
        2 => "Leave_Recommend",
        3 => "Leave_Approve",
        4 => "Attendance_Request",
        5 => "Attendance_Approved",
        6 => "Advance_Request",
        7 => "Advance_Recommend",
        8 => "Advance_Approve",
        9 => "Travel_Request",
        10 => "Travel_Recommend",
        11 => "Travel_Approve",
        12 => "Training",
        13 => "Loan_Request",
        14 => "Loan_Recommend",
        15 => "Loan_Approval",
        16 => "WorkOnDayoff_Request",
        17 => "WorkOnDayoff_Recommend",
        18 => "WorkOnDayoff_Approval",
        19 => "WorkOnHoliday_Request",
        20 => "WorkOnHoliday_Recommend",
        21 => "WorkOnHoliday_Approval",
        22 => "Training_Request",
        23 => "Training_Recommend",
        24 => "Training_Approval",
        25 => "Leave_Substitute_Applied",
        26 => "Leave_Substitute_Approval",
        27 => "Travel_Substitute_Applied",
        28 => "Travel_Substitute_Approval",
        29 => "Salary_Reviewed",
        30 => "KPI_Setting",
        31 => "KPI_Approval",
        32 => "Key_Achievement",
        33 => "Appraisal_Evaluated",
        34 => "Appraisal_Reviewed",
        35 => "Appraisee_Feedback",
        36 => "Attendance_Recommend",
        37 => "Overtime_Request",
        38 => "Overtime_Recommend",
        39 => "Overtime_Approve",
        40 => "Monthly_Appraisal_Assigned",
        41 => "Birthday_Wish",
        42 => "Leave_Cancel",
        43 => "Leave_Cancel_Recommend",
        44 => "Leave_Cancel_Approve",
        45 => "Expense_Request",
        46 => "Expense_Approval",
        47 => "Expense_Recommendation",
        48 => "Event_Assign",
        49 => "Event_Request",
        50 => "Event_Recommend",
        51 => "Event_Approval",
        52 => "New_User_Created",
        53 => "Email_Payslip",
    ];

    private function getVariables()
    {


        $type1 = new LeaveRequestNotificationModel();
        $type1ObjVars = $type1->getObjectAttrs();

        $type2 = new AttendanceRequestNotificationModel();
        $type2ObjVars = $type2->getObjectAttrs();

        $type3 = new AdvanceRequestNotificationModel();
        $type3ObjVars = $type3->getObjectAttrs();

        $type4 = new TravelReqNotificationModel();
        $type4ObjVars = $type4->getObjectAttrs();

        $type5 = new TrainingReqNotificationModel();
        $type5ObjVars = $type5->getObjectAttrs();

        $type6 = new LoanRequestNotificationModel();
        $type6ObjVars = $type6->getObjectAttrs();

        $type7 = new WorkOnDayoffNotificationModel();
        $type7ObjVars = $type7->getObjectAttrs();

        $type8 = new WorkOnHolidayNotificationModel();
        $type8ObjVars = $type8->getObjectAttrs();

        $type9 = new TrainingReqNotificationModel();
        $type9ObjVars = $type9->getObjectAttrs();

        $type10 = new LeaveSubNotificationModel();
        $type10ObjVars = $type10->getObjectAttrs();

        $type11 = new TravelSubNotificationModel();
        $type11ObjVars = $type11->getObjectAttrs();

        $type12 = new SalaryReviewNotificationModel();
        $type12ObjVars = $type12->getObjectAttrs();

        $type13 = new AppraisalNotificationModel();
        $type13ObjVars = $type13->getObjectAttrs();

        $type14 = new ExpenseReqNotificationModel();
        $type14ObjVars = $type14->getObjectAttrs();

        $overtimeNotiModel = new OvertimeReqNotificationModel();
        $overtimeNotiModelOA = $overtimeNotiModel->getObjectAttrs();

        $birthdayWish = new BirthdayNotificationModel();
        $birthdayWishOA = $birthdayWish->getObjectAttrs();

        $type48 = new EventNotificationModel();
        $type48ObjVars = $type48->getObjectAttrs();

        $type49 = new EventReqNotificationModel();
        $type49ObjVars = $type49->getObjectAttrs();

        $type52 = new NewUserNotificationModel();
        $type52ObjVars = $type52->getObjectAttrs();

        $type53 = new PayslipEmailNotificationModel();
        $type53ObjVars = $type53->getObjectAttrs();

        return [
            1 => $type1ObjVars,
            2 => $type1ObjVars,
            3 => $type1ObjVars,
            4 => $type2ObjVars,
            5 => $type2ObjVars,
            6 => $type3ObjVars,
            7 => $type3ObjVars,
            8 => $type3ObjVars,
            9 => $type4ObjVars,
            10 => $type4ObjVars,
            11 => $type4ObjVars,
            12 => $type5ObjVars,
            13 => $type6ObjVars,
            14 => $type6ObjVars,
            15 => $type6ObjVars,
            16 => $type7ObjVars,
            17 => $type7ObjVars,
            18 => $type7ObjVars,
            19 => $type8ObjVars,
            20 => $type8ObjVars,
            21 => $type8ObjVars,
            22 => $type9ObjVars,
            23 => $type9ObjVars,
            24 => $type9ObjVars,
            25 => $type10ObjVars,
            26 => $type10ObjVars,
            27 => $type11ObjVars,
            28 => $type11ObjVars,
            29 => $type12ObjVars,
            30 => $type13ObjVars,
            31 => $type13ObjVars,
            32 => $type13ObjVars,
            33 => $type13ObjVars,
            34 => $type13ObjVars,
            35 => $type13ObjVars,
            36 => $type2ObjVars,
            37 => $overtimeNotiModelOA,
            38 => $overtimeNotiModelOA,
            39 => $overtimeNotiModelOA,
            40 => $type13ObjVars,
            41 => $birthdayWishOA,
            42 => $type1ObjVars,
            43 => $type1ObjVars,
            44 => $type1ObjVars,
            45 => $type14ObjVars,
            46 => $type14ObjVars,
            47 => $type14ObjVars,
            48 => $type48ObjVars,
            49 => $type49ObjVars,
            50 => $type49ObjVars,
            51 => $type49ObjVars,
            52 => $type52ObjVars,
            53 => $type53ObjVars,
        ];
    }

    public function __construct(AdapterInterface $adapter)
    {
        $this->adapter = $adapter;
        $this->templateRepo = new EmailTemplateRepo($adapter);

        $auth = new AuthenticationService();
        $this->employeeId = $auth->getStorage()->read()['employee_id'];
    }

    public function indexAction()
    {
        $tab = (int) $this->params()->fromRoute('id');
        if ($tab == 0) {
            $tab = array_keys(self::EMAIL_TYPES)[0];
        }
        $templates = $this->templateRepo->fetchAll();
        return Helper::addFlashMessagesToArray($this, [
            'emailTypes' => self::EMAIL_TYPES,
            'templates' => $templates,
            'tab' => $tab,
            'variables' => $this->getVariables()
        ]);
    }

    public function editAction()
    {
        $request = $this->getRequest();
        if ($request->isPost()) {

            $postedData = $request->getPost();
            $template = new EmailTemplate();
            $template->subject = $postedData['subject'];

            $patterns = array("/\s+/", "/\s([?.!])/");
            $replacer = array(" ", "$1");


            $template->description = preg_replace('#([a-z0-9\\-]) {2,}([a-z0-9\\-])#i', '\\1 \\2', $postedData['description']);

            $cc = [];
            if (isset($postedData['ccEmail'])) {
                foreach ($postedData['ccEmail'] as $key => $ccEmail) {
                    if (isset($ccEmail) && strlen($ccEmail) > 0) {
                        array_push($cc, ['email' => $ccEmail, 'name' => $postedData['ccName'][$key]]);
                    }
                }
            }
            $bcc = [];
            if (isset($postedData['bccEmail'])) {
                foreach ($postedData['bccEmail'] as $key => $bccEmail) {
                    if (isset($bccEmail) && strlen($bccEmail) > 0) {
                        array_push($bcc, ['email' => $bccEmail, 'name' => $postedData['bccName'][$key]]);
                    }
                }
            }

            $template->cc = json_encode($cc);
            $template->bcc = json_encode($bcc);

            if ($this->templateRepo->fetchById($postedData['id']) == null) {
                $template->id = $postedData['id'];
                $template->createdBy = $this->employeeId;
                $template->createdDt = Helper::getcurrentExpressionDate();
                $this->templateRepo->add($template);
            } else {
                $template->modifiedBy = $this->employeeId;
                $template->modifiedDt = Helper::getcurrentExpressionDate();
                $this->templateRepo->edit($template, $postedData['id']);
            }
            $this->flashmessenger()->addMessage("Email Template Sucessfully Updated");
            return $this->redirect()->toRoute('email', ['id' => $postedData['id']]);
        }
    }
}
