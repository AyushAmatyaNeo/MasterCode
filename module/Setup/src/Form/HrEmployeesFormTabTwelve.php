<?php
namespace Setup\Form;


use Application\Model\Model;
use Zend\Form\Annotation;

/**
 * @Annotation\Hydrator("Zend\Hydrator\ObjectPropertyHydrator")
 * @Annotation\Name("HrEmployeesFormTabTwelve")
 */

class HrEmployeesFormTabTwelve extends Model{
    
    
    public $employeeId;
    
    /**
     * @Annotation\Required(true)
     * @Annotation\Type("Zend\Form\Element\Number")
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"label":"Amount"})
     * @Annotation\Validator({"name":"StringLength", "options":{"max":"9"}})
     * @Annotation\Attributes({ "id":"amount", "class":"form-control","step":"0.01","min":"0"})
     */
    public $amount;

    /**
     * @Annotation\Type("Zend\Form\Element\Select")
     * @Annotation\Required(true)
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"disable_inarray_validator":"true","label":"Salary Head"})
     * @Annotation\Attributes({ "id":"salaryHead","class":"form-control"})
     */    
    public $salaryHead;

    /**
     * @Annotation\Type("Zend\Form\Element\Select")
     * @Annotation\Required(true)
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"disable_inarray_validator":"true","label":"Effect by Working Day"})
     * @Annotation\Attributes({ "id":"effectByWorkingDay","class":"form-control"})
     */    
    public $effectByWorkingDay;
    
     /**
     * @Annotation\Required(true)
     * @Annotation\Type("Zend\Form\Element\Text")
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"label":"Date From"})
     * @Annotation\Attributes({"class":"form-control","id":"expfromDate" })
     */
    public $fromDate;
    
     /**
     * @Annotation\Required(false)
     * @Annotation\Type("Zend\Form\Element\Text")
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"label":"Date To"})
     * @Annotation\Attributes({"class":"form-control","id":"exptoDate" })
     */
    public $toDate;
    
    public $id;
    public $createdBy;
    public $createdDate;
    public $modifiedBy;
    public $modifiedDate;
    public $status;
    
    
    public $mappings=[
        'id'=>'ID',
        'employeeId'=>'EMPLOYEE_ID',
        'amount'=>'AMOUNT',
        'effectByWorkingDay'=>'EFFECT_BY_WORKING_DAY',
        'salaryHead'=>'PAY_ID',
        'fromDate'=>'FROM_DATE',
        'toDate'=>'TO_DATE',
        'isDefaultHead'=>'IS_DEFAULT_HEAD',
        'isEnable'=>'IS_ENABLE',
        'createdBy'=>'CREATED_BY',
        'createdDate'=>'CREATED_DATE',
        'modifiedBy'=>'MODIFIED_BY',
        'modifiedDate'=>'MODIFIED_DATE',
        'status'=>'STATUS'
    ];
    
    
    

}

