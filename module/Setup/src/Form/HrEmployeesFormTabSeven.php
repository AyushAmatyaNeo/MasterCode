<?php
namespace Setup\Form;


use Application\Model\Model;
use Zend\Form\Annotation;

/**
 * @Annotation\Hydrator("Zend\Hydrator\ObjectPropertyHydrator")
 * @Annotation\Name("HrEmployeesFormTabSeven")
 */

class HrEmployeesFormTabSeven extends Model{
    
    
    public $employeeId;
    
    /**
     * @Annotation\Required(false)
     * @Annotation\Type("Zend\Form\Element\Text")
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"label":"Organization Name"})
     * @Annotation\Attributes({ "id":"organizationName", "class":"form-control" })
     */
    
    public $organizationName;

    /**
     * @Annotation\Type("Zend\Form\Element\Select")
     * @Annotation\Required(true)
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"disable_inarray_validator":"true","label":"Organization Type"})
     * @Annotation\Attributes({ "id":"organizationType","class":"form-control"})
     */
    
    public $organizationType;
    
    /**
     * @Annotation\Required(false)
     * @Annotation\Type("Zend\Form\Element\Text")
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"label":"Position"})
     * @Annotation\Attributes({ "id":"position", "class":"form-control" })
     */
    
    public $position;
    
     /**
     * @Annotation\Required(true)
     * @Annotation\Type("Zend\Form\Element\Text")
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"label":"From Date"})
     * @Annotation\Attributes({"class":"form-control","id":"expfromDate" })
     */
    public $fromDate;
    
     /**
     * @Annotation\Required(true)
     * @Annotation\Type("Zend\Form\Element\Text")
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"label":"To Date"})
     * @Annotation\Attributes({"class":"form-control","id":"exptoDate" })
     */
    public $toDate;
    
    public $id;
    public $remarks;
    public $companyId;
    public $branchId;
    public $createdBy;
    public $createdDate;
    public $modifiedBy;
    public $modifiedDate;
    public $approved;
    public $approvedBy;
    public $approvedDate;
    public $status;
    
    
    public $mappings=[
        'id'=>'ID',
        'employeeId'=>'EMPLOYEE_ID',
        'organizationName'=>'ORGANIZATION_NAME',
        'organizationType'=>'ORGANIZATION_TYPE',
        'position'=>'POSITION',
        'fromDate'=>'FROM_DATE',
        'toDate'=>'TO_DATE',
        'remarks'=>'REMARKS',
        'companyId'=>'COMPANY_ID',
        'branchId'=>'BRANCH_ID',
        'createdBy'=>'CREATED_BY',
        'createdDate'=>'CREATED_DATE',
        'modifiedBy'=>'MODIFIED_BY',
        'modifiedDate'=>'MODIFIED_DATE',
        'approved'=>'APPROVED',
        'approvedBy'=>'APPROVED_BY',
        'approvedDate'=>'APPROVED_DATE',
        'status'=>'STATUS'
        
    ];
    
    
    

}

