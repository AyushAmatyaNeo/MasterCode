<?php

namespace System\Form;

use Zend\Form\Annotation;

/**
 * @Annotation\Hydrator("Zend\Hydrator\ObjectPropertyHydrator")
 * @Annotation\Name("system-setting")
 */
class SystemSettingForm
{

    /**
     * @Annotation\Type("Zend\Form\Element\Radio")
     * @Annotation\Options({"value_options":{"Y":"Yes","N":"No"},"label":"Allow System Attendance"})
     * @Annotation\Attributes({ "id":"allowSystemAttendance"})
     */
    public $allowSystemAttendance;

    /**
     * @Annotation\Type("Zend\Form\Element\Radio")
     * @Annotation\Options({"value_options":{"Y":"Yes","N":"No"},"label":"Late Check In Approval"})
     * @Annotation\Attributes({ "id":"needApprovalForLateCheckIn"})
     */
    public $needApprovalForLateCheckIn;

    /**
     * @Annotation\Type("Zend\Form\Element\Radio")
     * @Annotation\Options({"value_options":{"Y":"Yes","N":"No"},"label":"Allow Account Lock"})
     * @Annotation\Attributes({ "id":"allowAccountLock"})
     */
    public $allowAccountLock;

    /**
     * @Annotation\Type("Zend\Form\Element\Number")
     * @Annotation\Options({"label":"Account Lock Try Chance"})
     * @Annotation\Attributes({ "id":"accountLockTryNumber", "class":" form-control","min":"0"})
     */
    public $accountLockTryNumber;

    /**
     * @Annotation\Type("Zend\Form\Element\Number")
     * @Annotation\Options({"label":"Account Lock Try Second"})
     * @Annotation\Attributes({ "id":"accountLockTrySecond", "class":" form-control","min":"0"})
     */
    public $accountLockTrySecond;

    /**
     * @Annotation\Type("Zend\Form\Element\Radio")
     * @Annotation\Options({"value_options":{"Y":"Yes","N":"No"},"label":"Force Password Renew"})
     * @Annotation\Attributes({ "id":"forcePasswordRenew"})
     */
    public $forcePasswordRenew;

    /**
     * @Annotation\Type("Zend\Form\Element\Number")
     * @Annotation\Options({"label":"Force Password Renew In"})
     * @Annotation\Attributes({ "id":"forcePasswordRenewDay", "class":" form-control","min":"0"})
     */
    public $forcePasswordRenewDay;

    /**
     * @Annotation\Type("Zend\Form\Element\Radio")
     * @Annotation\Options({"value_options":{"Y":"Yes","N":"No"},"label":"Show Address Book"})
     * @Annotation\Attributes({ "id":"showAddressBook"})
     */
    public $showAddressBook;

    /**
     * @Annotation\Type("Zend\Form\Element\Radio")
     * @Annotation\Options({"value_options":{"S":"Single","M":"Multiple"},"label":"Notice Type"})
     * @Annotation\Attributes({ "id":"noticeType"})
     */
    public $noticeType;

    /**
     * @Annotation\Type("Zend\Form\Element\Radio")
     * @Annotation\Options({"value_options":{"O":"Oracle","M":"Mysql","N":"None"},"label":"Old Payslip Type"})
     * @Annotation\Attributes({ "id":"oldPayslipType"})
     */
    public $oldPayslipType;

    /**
     * @Annotation\Type("Zend\Form\Element\Number")
     * @Annotation\Required(false)
     * @Annotation\Options({"label":"Late Penalty Leave Deduction"})
     * @Annotation\Attributes({ "id":"latePenaltyLeaveDeduction", "class": "form-control","min":"0","step":"0.5"})
     */
    public $latePenaltyLeaveDeduction;

    /**
     * @Annotation\Type("Zend\Form\Element\Radio")
     * @Annotation\Options({"value_options":{"Y":"Yes","N":"No"},"label":"Enable Previous Month Leave Request"})
     * @Annotation\Attributes({ "id":"enablePrevMthLeaveReq"})
     */
    public $enablePrevMthLeaveReq;

    /**
     * @Annotation\Type("Zend\Form\Element\Radio")
     * @Annotation\Options({"value_options":{"Y":"Yes","N":"No"},"label":"Include Dayoff As Leave"})
     * @Annotation\Attributes({ "id":"includeDayoffAsLeave"})
     */
    public $includeDayoffAsLeave;

    /**
     * @Annotation\Type("Zend\Form\Element\Radio")
     * @Annotation\Options({"value_options":{"Y":"Yes","N":"No"},"label":"Include Holiday As Leave"})
     * @Annotation\Attributes({ "id":"includeHolidayAsLeave"})
     */
    public $includeHolidayAsLeave;

    /**
     * @Annotation\Type("Zend\Form\Element\Radio")
     * @Annotation\Options({"value_options":{"Y":"Yes","N":"No"},"label":"Employee Code"})
     * @Annotation\Attributes({ "id":"includeEmployeeCode"})
     */
    public $includeEmployeeCode;

    /**
     * @Annotation\Type("Zend\Form\Element\Radio")
     * @Annotation\Options({"value_options":{"Y":"Yes","N":"No"},"label":"Company"})
     * @Annotation\Attributes({ "id":"includeCompany"})
     */
    public $includeCompany;

    /**
     * @Annotation\Type("Zend\Form\Element\Radio")
     * @Annotation\Options({"value_options":{"Y":"Yes","N":"No"},"label":"Branch"})
     * @Annotation\Attributes({ "id":"includeBranch"})
     */
    public $includeBranch;

    /**
     * @Annotation\Type("Zend\Form\Element\Radio")
     * @Annotation\Options({"value_options":{"Y":"Yes","N":"No"},"label":"First Time Password Renew"})
     * @Annotation\Attributes({ "id":"firstTimePwdRenew","value":"N"})
     */
    public $firstTimePwdRenew;

    /**
     * @Annotation\Type("Zend\Form\Element\Radio")
     * @Annotation\Options({"value_options":{"Y":"Yes","N":"No"},"label":"Travel Substitute Cycle"})
     * @Annotation\Attributes({ "id":"travelSubCycle","value":"Y"})
     */
    public $travelSubCycle;

    /**
     * @Annotation\Type("Zend\Form\Element\Radio")
     * @Annotation\Options({"value_options":{"Y":"Yes","N":"No"},"label":"Sub Leave Reference"})
     * @Annotation\Attributes({ "id":"subLeaveReference","value":"N"})
     */
    public $subLeaveReference;

    /**
     * @Annotation\Type("Zend\Form\Element\Number")
     * @Annotation\Options({"label":"Sub Leave Max Days"})
     * @Annotation\Attributes({ "id":"subLeaveMaxDays", "class":" form-control","min":"0"})
     */
    public $subLeaveMaxDays;

    /**
     * @Annotation\Type("Zend\Form\Element\Number")
     * @Annotation\Options({"label":"leave Encash Max Days"})
     * @Annotation\Attributes({ "id":"leaveEncashMaxDays", "class":" form-control","min":"0"})
     */
    public $leaveEncashMaxDays;

    /**
     * @Annotation\Type("Zend\Form\Element\Radio")
     * @Annotation\Options({"value_options":{"Y":"Yes","N":"No"},"label":"Order By Name"})
     * @Annotation\Attributes({ "id":"orderByName","value":"Y"})
     */
    public $orderByName;

    /**
     * @Annotation\Type("Zend\Form\Element\Radio")
     * @Annotation\Options({"value_options":{"Y":"Yes","N":"No"},"label":"Order By Position"})
     * @Annotation\Attributes({ "id":"orderByPosition","value":"N"})
     */
    public $orderByPosition;

    /**
     * @Annotation\Type("Zend\Form\Element\Radio")
     * @Annotation\Options({"value_options":{"Y":"Yes","N":"No"},"label":"Order By Designation"})
     * @Annotation\Attributes({ "id":"orderByDesignation","value":"N"})
     */
    public $orderByDesignation;

    /**
     * @Annotation\Type("Zend\Form\Element\Radio")
     * @Annotation\Options({"value_options":{"Y":"Yes","N":"No"},"label":"Order By JoinDate"})
     * @Annotation\Attributes({ "id":"orderByJoinDate","value":"N"})
     */
    public $orderByJoinDate;

    /**
     * @Annotation\Type("Zend\Form\Element\Radio")
     * @Annotation\Options({"value_options":{"Y":"Yes","N":"No"},"label":"Order By Seniority"})
     * @Annotation\Attributes({ "id":"orderBySeniority","value":"N"})
     */
    public $orderBySeniority;

    /**
     * @Annotation\Type("Zend\Form\Element\Radio")
     * @Annotation\Options({"value_options":{"N":"Nepali","E":"English"},"label":"Calendar View"})
     * @Annotation\Attributes({ "id":"calendarView","value":"N"})
     */
    public $calendarView;

    /**
     * @Annotation\Type("Zend\Form\Element\Radio")
     * @Annotation\Options({"value_options":{"N":"No","Y":"Yes"},"label":"Att App Shift changeable"})
     * @Annotation\Attributes({ "id":"attAppShiftChangeable","value":"N"})
     */
    public $attAppShiftChangeable;

    /**
     * @Annotation\Type("Zend\Form\Element\Radio")
     * @Annotation\Options({"value_options":{"N":"No","Y":"Yes"},"label":"Att App Time changeable"})
     * @Annotation\Attributes({ "id":"attAppTimeChangeable","value":"N"})
     */
    public $attAppTimeChangeable;

    /**
     * @Annotation\Type("Zend\Form\Element\Radio")
     * @Annotation\Options({"value_options":{"Y":"Yes","N":"No"},"label":"Province wise branch filter"})
     * @Annotation\Attributes({ "id":"provinceWiseBranchFilter","value":"N"})
     */
    public $provinceWiseBranchFilter;

    /**
     * @Annotation\Type("Zend\Form\Element\Text")
     * @Annotation\Required(false)
     * @Annotation\Options({"label":"Company Name"})
     * @Annotation\Attributes({ "id":"companyName", "class": "form-control"})
     */
    public $companyName;

    /**
     * @Annotation\Type("Zend\Form\Element\Text")
     * @Annotation\Required(false)
     * @Annotation\Options({"label":"Company Address"})
     * @Annotation\Attributes({ "id":"companyAddress", "class": "form-control"})
     */
    public $companyAddress;

    /**
     * @Annotation\Type("Zend\Form\Element\Number")
     * @Annotation\Required(false)
     * @Annotation\Options({"label":"Company Account No."})
     * @Annotation\Attributes({ "id":"companyAccountNo", "class": "form-control"})
     */
    public $companyAccountNo;

    /**
     * @Annotation\Type("Zend\Form\Element\Radio")
     * @Annotation\Options({"value_options":{"Y":"Yes","N":"No"},"label":"Display HR Approved"})
     * @Annotation\Attributes({ "id":"displayHrApproved","value":"N"})
     */
    public $displayHrApproved;

    /**
     * @Annotation\Type("Zend\Form\Element\Select")
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"value_options":{null:"---","D":"Debit","C":"Credit","B":"Both"},"label":"Do not Insert Sub Detail of:"})
     * @Annotation\Required(false)
     * @Annotation\Attributes({ "id":"doNotInsertSubDetailFlag"})
     */
    public $doNotInsertSubDetailFlag;

    /**
     * @Annotation\Type("Zend\Form\Element\Radio")
     * @Annotation\Options({"value_options":{"Y":"Yes","N":"No"},"label":"Include Exchange Rate"})
     * @Annotation\Attributes({ "id":"exchangeRate"})
     */
    public $exchangeRate;

    /**
     * @Annotation\Type("Zend\Form\Element\Radio")
     * @Annotation\Options({"value_options":{"Y":"Yes","N":"No"},"label":"Send Payslip in Email"})
     * @Annotation\Attributes({ "id":"payslipEmail"})
     */
    public $payslipEmail;


    /**
     * @Annotation\Type("Zend\Form\Element\Radio")
     * @Annotation\Options({"value_options":{"Y":"Yes","N":"No"},"label":"Show Salary History"})
     * @Annotation\Attributes({ "id":"salaryHistory"})
     */
    public $salaryHistory;
    /**
     * @Annotation\Type("Zend\Form\Element\Submit")
     * @Annotation\Attributes({"value":"Submit","class":"btn btn-success","id":"btnSubmit"})
     */

    public $submit;
}
