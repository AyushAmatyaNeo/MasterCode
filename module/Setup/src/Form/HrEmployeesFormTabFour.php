<?php

namespace Setup\Form;

use Application\Model\Model;
use Zend\Form\Annotation;

class HrEmployeesFormTabFour extends Model {

    /**
     * @Annotation\Required(true)
     * @Annotation\Type("Zend\Form\Element\Text")
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"label":"Join Date"})
     * @Annotation\Attributes({"class":"form-control","id":"joinDate" })
     */
    public $joinDate;

    /**
     * @Annotation\Required(false)
     * @Annotation\Type("Zend\Form\Element\Number")
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"label":"Salary"})
     * @Annotation\Validator({"name":"StringLength", "options":{"max":"9"}})
     * @Annotation\Attributes({ "id":"salary", "class":"form-control","step":"0.01","min":"0"})
     */
    public $salary;

    /**
     * @Annotation\Required(false)
     * @Annotation\Type("Zend\Form\Element\Number")
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"label":"Salary PF"})
     * @Annotation\Validator({"name":"StringLength", "options":{"max":"9"}})
     * @Annotation\Attributes({ "id":"salaryPf", "class":"form-control","min":"1" })
     */
    public $salaryPf;

    /**
     * @Annotation\Type("Zend\Form\Element\Select")
     * @Annotation\Required(false)
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"disable_inarray_validator":"true","label":"Service Type Name"})
     * @Annotation\Attributes({ "id":"serviceTypeId","class":"form-control"})
     */
    public $serviceTypeId;

    /**
     * @Annotation\Type("Zend\Form\Element\Select")
     * @Annotation\Required(false)
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"disable_inarray_validator":"true","label":"Position Name"})
     * @Annotation\Attributes({ "id":"positionId","class":"form-control"})
     */
    public $positionId;

    /**
     * @Annotation\Type("Zend\Form\Element\Select")
     * @Annotation\Required(false)
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"disable_inarray_validator":"true","label":"Designation Name"})
     * @Annotation\Attributes({ "id":"designationId","class":"form-control"})
     */
    public $designationId;

    /**
     * @Annotation\Type("Zend\Form\Element\Select")
     * @Annotation\Required(false)
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"disable_inarray_validator":"true","label":"Department Name"})
     * @Annotation\Attributes({ "id":"departmentId","class":"form-control"})
     */
    public $departmentId;

    /**
     * @Annotation\Type("Zend\Form\Element\Select")
     * @Annotation\Required(false)
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"disable_inarray_validator":"true","label":"Branch Name"})
     * @Annotation\Attributes({ "id":"branchId","class":"form-control"})
     */
    public $branchId;

    /**
     * @Annotation\Type("Zend\Form\Element\Radio")
     * @Annotation\Required(false)
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"label":"HR Flag","value_options":{"Y":"Yes","N":"No"}})
     * @Annotation\Attributes({"id":"isHr","value":"N"})
     */
    public $isHR;

    /**
     * @Annotation\Type("Zend\Form\Element\Select")
     * @Annotation\Required(false)
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"disable_inarray_validator":"true","label":"Employee Type"})
     * @Annotation\Attributes({ "id":"employeeType","class":"form-control"})
     */
    public $employeeType;

    /**
     * @Annotation\Type("Zend\Form\Element\Select")
     * @Annotation\Required(false)
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"disable_inarray_validator":"true","label":"Location Name"})
     * @Annotation\Attributes({ "id":"locationId","class":"form-control"})
     */
    public $locationId;

    /**
     * @Annotation\Type("Zend\Form\Element\Select")
     * @Annotation\Required(false)
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"disable_inarray_validator":"true","label":"Functional Type Name"})
     * @Annotation\Attributes({ "id":"functionalTypeId","class":"form-control"})
     */
    public $functionalTypeId;

    /**
     * @Annotation\Type("Zend\Form\Element\Select")
     * @Annotation\Required(false)
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"disable_inarray_validator":"true","label":"Functional Level Name"})
     * @Annotation\Attributes({ "id":"functionalLevelId","class":"form-control"})
     */
    public $functionalLevelId;
    public $modifiedBy;
    public $modifiedDt;
    
    /**
     * @Annotation\Type("Zend\Form\Element\Select")
     * @Annotation\Required(false)
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"disable_inarray_validator":"true","label":"Payroll Emp Type"})
     * @Annotation\Attributes({ "id":"payEmpType","class":"form-control"})
     */
    public $payEmpType;

    /**
     * @Annotation\Type("Zend\Form\Element\Select")
     * @Annotation\Required(false)
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"disable_inarray_validator":"true","label":"Work On Holiday Reward","value_options":{"":"---","L":"Substitute Leave","O":"OverTime"}})
     * @Annotation\Attributes({ "id":"wohFlag","class":"form-control"})
     */
    public $wohFlag;

    /**
     * @Annotation\Type("Zend\Form\Element\Radio")
     * @Annotation\Required(false)
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"label":"Overtime Eligible","value_options":{"Y":"Yes","N":"No"}})
     * @Annotation\Attributes({"id":"overtimeEligible","value":"N"})
     */
    public $overtimeEligible;

    /**
     * @Annotation\Type("Zend\Form\Element\Select")
     * @Annotation\Required(false)
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"disable_inarray_validator":"true","label":"Salary Group"})
     * @Annotation\Attributes({ "id":"groupId","class":"form-control"})
     */
    public $groupId;

    /**
     * @Annotation\Type("Zend\Form\Element\Select")
     * @Annotation\Required(false)
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"disable_inarray_validator":"true","label":"Service Event Type"})
     * @Annotation\Attributes({ "id":"serviceEventTypeId","class":"form-control"})
     */
    public $serviceEventTypeId;

    /**
     * @Annotation\Required(false)
     * @Annotation\Type("Zend\Form\Element\Text")
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"label":"Event Date"})
     * @Annotation\Attributes({"class":"form-control","id":"eventDate" })
     */
    public $eventDate;

    /**
     * * @Annotation\Required(false)
     * @Annotation\Type("Zend\Form\Element\Text")
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"label":"Start Date"})
     * @Annotation\Attributes({"class":"form-control","id":"startDate" })
     */
    public $startDate;

    /**
     * @Annotation\Required(false)
     * @Annotation\Type("Zend\Form\Element\Text")
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"label":"End Date"})
     * @Annotation\Attributes({"class":"form-control","id":"endDate" })
     */
    public $endDate;

    /**
     * @Annotation\Required(false)
     * @Annotation\Type("Zend\Form\Element\Number")
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"label":"Allowance"})
     * @Annotation\Validator({"name":"StringLength", "options":{"max":"9"}})
     * @Annotation\Attributes({ "id":"allowance", "class":"form-control","step":"1","min":"0"})
     */
    public $allowance ;

    /**
     * @Annotation\Type("Zend\Form\Element\Select")
     * @Annotation\Required(false)
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"disable_inarray_validator":"true","label":"Tax Base","value_options":{"":"---","U":"Unmarried","M":"Married"}})
     * @Annotation\Attributes({ "id":"taxBase","class":"form-control"})
     */
    public $taxBase;

    /**
     * * @Annotation\Required(false)
     * @Annotation\Type("Zend\Form\Element\Text")
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"label":"Permanent Date"})
     * @Annotation\Attributes({"class":"form-control","id":"permanentDate" })
     */
    public $permanentDate;

    /**
     * * @Annotation\Required(false)
     * @Annotation\Type("Zend\Form\Element\Text")
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"label":"Gratuity Date"})
     * @Annotation\Attributes({"class":"form-control","id":"gratuityDate" })
     */
    public $gratuityDate;

    /**
     * @Annotation\Type("Zend\Form\Element\Radio")
     * @Annotation\Required(false)
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"label":"Disabled Person","value_options":{"Y":"Yes","N":"No"}})
     * @Annotation\Attributes({"id":"disabledFlag","value":"N"})
     */
    public $disabledFlag;

    public $mappings = [
        'joinDate' => 'JOIN_DATE',
        'salary' => 'SALARY',
        'salaryPf' => 'SALARY_PF',
        'branchId' => 'BRANCH_ID',
        'departmentId' => 'DEPARTMENT_ID',
        'designationId' => 'DESIGNATION_ID',
        'positionId' => 'POSITION_ID',
        'serviceTypeId' => 'SERVICE_TYPE_ID',
        'employeeType' => 'EMPLOYEE_TYPE',
        'modifiedBy' => 'MODIFIED_BY',
        'modifiedDt' => 'MODIFIED_DT',
        'isHR' => 'IS_HR',
        'locationId' => 'LOCATION_ID',
        'functionalTypeId' => 'FUNCTIONAL_TYPE_ID',
        'functionalLevelId' => 'FUNCTIONAL_LEVEL_ID',
        'payEmpType' => 'PAY_EMP_TYPE',
        'wohFlag' => 'WOH_FLAG',
        'overtimeEligible' => 'OVERTIME_ELIGIBLE',
        'groupId' => 'GROUP_ID',
        'allowance' => 'ALLOWANCE',
        'taxBase' => 'TAX_BASE',
        'permanentDate' => 'PERMANENT_DATE',
        'gratuityDate' => 'GRATUITY_DATE',
        'disabledFlag' => 'DISABLED_FLAG'
    ];

}
