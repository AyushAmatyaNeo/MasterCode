<?php
namespace Setup\Form;

/**
* Form Setup Job History
* Job History Form.
* Created By: Somkala Pachhai
* Edited By: 
* Date: August 11, 2016, Thursday 
* Last Modified By: 
* Last Modified Date: 
*/

use Zend\Form\Annotation;

/**
 * @Annotation\Hydrator("Zend\Hydrator\ObjectProperty")
 * @Annotation\Name("JobHistoryForm")
*/

class JobHistoryForm{

	/**
     * @Annotation\Type("Zend\Form\Element\Select")
     * @Annotation\Required({"required":"true"})
     * @Annotation\Filter({"name":"StripTags","name":"StringTrim"})
     * @Annotation\Options({"label":"Employee Name","value_options":{"1":"Emp1","2":"Emp2"}})
     * @Annotation\Attributes({ "id":"form-employeeId","data-init-plugin":"cs-select","class":"cs-select cs-skin-slide form-employeeId form-control"})
     */
    public $employeeId;

    /**
     * @Annotation\Type("Zend\Form\Element\Date")
     * @Annotation\Required({"required":"true"})
     * @Annotation\Filter({"name":"StripTags","name":"StringTrim"})
     * @Annotation\Options({"label":"Start Date"})
     * @Annotation\Attributes({"type":"text","class":"form-startDate form-control"})
     */
    public $startDate;

    /**
     * @Annotation\Type("Zend\Form\Element\Date")
     * @Annotation\Required({"required":"true"})
     * @Annotation\Filter({"name":"StripTags","name":"StringTrim"})
     * @Annotation\Options({"label":"End Date"})
     * @Annotation\Attributes({"type":"text", "class":"form-endDate form-control"})
     */
    public $endDate;

    /**
     * @Annotation\Type("Zend\Form\Element\Select")
     * @Annotation\Required({"required":"true"})
     * @Annotation\Filter({"name":"StripTags","name":"StringTrim"})
     * @Annotation\Options({"disable_inarray_validator":"true","label":"Service Type Name"})
     * @Annotation\Attributes({ "id":"form-serviceTypeId","data-init-plugin":"cs-select","class":"cs-select cs-skin-slide form-serviceTypeId form-control"})
     */
    public $serviceTypeId;

    /**
     * @Annotation\Type("Zend\Form\Element\Select")
     * @Annotation\Required({"required":"true"})
     * @Annotation\Filter({"name":"StripTags","name":"StringTrim"})
     * @Annotation\Options({"disable_inarray_validator":"true","label":"From Branch Name"})
     * @Annotation\Attributes({ "id":"form-fromBranchId","data-init-plugin":"cs-select","class":"cs-select cs-skin-slide form-fromBranchId form-control"})
     */
    public $fromBranchId;

    /**
     * @Annotation\Type("Zend\Form\Element\Select")
     * @Annotation\Required({"required":"true"})
     * @Annotation\Filter({"name":"StripTags","name":"StringTrim"})
     * @Annotation\Options({"disable_inarray_validator":"true","label":"To Branch Name"})
     * @Annotation\Attributes({ "id":"form-toBranchId","data-init-plugin":"cs-select","class":"cs-select cs-skin-slide form-toBranchId form-control"})
     */
    public $toBranchId;

    /**
     * @Annotation\Type("Zend\Form\Element\Select")
     * @Annotation\Required({"required":"true"})
     * @Annotation\Filter({"name":"StripTags","name":"StringTrim"})
     * @Annotation\Options({"disable_inarray_validator":"true","label":"From Department Name"})
     * @Annotation\Attributes({ "id":"form-fromDepartmentId","data-init-plugin":"cs-select","class":"cs-select cs-skin-slide form-fromDepartmentId form-control"})
     */
    public $fromDepartmentId;

    /**
     * @Annotation\Type("Zend\Form\Element\Select")
     * @Annotation\Required({"required":"true"})
     * @Annotation\Filter({"name":"StripTags","name":"StringTrim"})
     * @Annotation\Options({"disable_inarray_validator":"true","label":"To Department Name"})
     * @Annotation\Attributes({ "id":"form-toDepartmentId","data-init-plugin":"cs-select","class":"cs-select cs-skin-slide form-toDepartmentId form-control"})
     */
    public $toDepartmentId;

    /**
     * @Annotation\Type("Zend\Form\Element\Select")
     * @Annotation\Required({"required":"true"})
     * @Annotation\Filter({"name":"StripTags","name":"StringTrim"})
     * @Annotation\Options({"disable_inarray_validator":"true","label":"From Designation Name"})
     * @Annotation\Attributes({ "id":"form-fromDesignationId","data-init-plugin":"cs-select","class":"cs-select cs-skin-slide form-fromDesignationId form-control"})
     */
    public $fromDesignationId;

    /**
     * @Annotation\Type("Zend\Form\Element\Select")
     * @Annotation\Required({"required":"true"})
     * @Annotation\Filter({"name":"StripTags","name":"StringTrim"})
     * @Annotation\Options({"disable_inarray_validator":"true","label":"To Designation Name"})
     * @Annotation\Attributes({ "id":"form-toDesignationId","data-init-plugin":"cs-select","class":"cs-select cs-skin-slide form-toDesignationId form-control"})
     */
    public $toDesignationId;

    /**
     * @Annotation\Type("Zend\Form\Element\Select")
     * @Annotation\Required({"required":"true"})
     * @Annotation\Filter({"name":"StripTags","name":"StringTrim"})
     * @Annotation\Options({"disable_inarray_validator":"true","label":"From Position Name"})
     * @Annotation\Attributes({ "id":"form-fromPositionId","data-init-plugin":"cs-select","class":"cs-select cs-skin-slide form-fromPositionId form-control"})
     */
    public $fromPositionId;

    /**
     * @Annotation\Type("Zend\Form\Element\Select")
     * @Annotation\Required({"required":"true"})
     * @Annotation\Filter({"name":"StripTags","name":"StringTrim"})
     * @Annotation\Options({"disable_inarray_validator":"true","label":"To Position Name"})
     * @Annotation\Attributes({ "id":"form-toPositionId","data-init-plugin":"cs-select","class":"cs-select cs-skin-slide form-toPositionId form-control"})
     */
    public $toPositionId;

    /**
     * @Annotation\Type("Zend\Form\Element\Submit")
     * @Annotation\Attributes({"value":"Submit","class":"btn btn-primary pull-right"})
    */
    public $submit;

}

/* End of file JobHistoryForm.php */
/* Location: ./Setup/src/Form/JobHistoryForm.php */