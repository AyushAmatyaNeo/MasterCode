<?php

namespace Setup\Form;

use Zend\Form\Annotation;

/**
 * @Annotation\Hydrator("Zend\Hydrator\ObjectPropertyHydrator")
 * @Annotation\Name("JobHistoryForm")
 */
class JobHistoryForm {

    /**
     * @Annotation\Type("Zend\Form\Element\Select")
     * @Annotation\Options({"disable_inarray_validator":"true","label":"Employee Name"})
     * @Annotation\Attributes({ "id":"employeeId","class":"form-control"})
     */
    public $employeeId;

    /**
     * @Annotation\Type("Zend\Form\Element\Text")
     * @Annotation\Required({"required":"true"})
     * @Annotation\Filter({"name":"StripTags","name":"StringTrim"})
     * @Annotation\Options({"label":"Start Date"})
     * @Annotation\Attributes({"class":"form-control","id":"startDate"})
     */
    public $startDate;

    /**
     * @Annotation\Type("Zend\Form\Element\Text")
     * @Annotation\Required(false)
     * @Annotation\Filter({"name":"StripTags","name":"StringTrim"})
     * @Annotation\Options({"label":"End Date"})
     * @Annotation\Attributes({ "class":"form-control","id":"endDate"})
     */
    public $endDate;

    /**
     * @Annotation\Type("Zend\Form\Element\Select")
     * @Annotation\Required({"required":"true"})
     * @Annotation\Filter({"name":"StripTags","name":"StringTrim"})
     * @Annotation\Options({"disable_inarray_validator":"true","label":"Service Event Type Name"})
     * @Annotation\Attributes({ "id":"serviceEventTypeId","class":"form-control"})
     */
    public $serviceEventTypeId;

    /**
     * @Annotation\Type("Zend\Form\Element\Select")
     * @Annotation\Required({"required":"true"})
     * @Annotation\Filter({"name":"StripTags","name":"StringTrim"})
     * @Annotation\Options({"disable_inarray_validator":"true","label":"To Service Type Name"})
     * @Annotation\Attributes({ "id":"toServiceTypeId","class":"form-control"})
     */
    public $toServiceTypeId;

    /**
     * @Annotation\Type("Zend\Form\Element\Select")
     * @Annotation\Required({"required":"true"})
     * @Annotation\Filter({"name":"StripTags","name":"StringTrim"})
     * @Annotation\Options({"disable_inarray_validator":"true","label":"To Branch Name"})
     * @Annotation\Attributes({ "id":"toBranchId","class":"form-control"})
     */
    public $toBranchId;

    /**
     * @Annotation\Type("Zend\Form\Element\Select")
     * @Annotation\Required({"required":"true"})
     * @Annotation\Filter({"name":"StripTags","name":"StringTrim"})
     * @Annotation\Options({"disable_inarray_validator":"true","label":"To Department Name"})
     * @Annotation\Attributes({ "id":"toDepartmentId","class":"form-control"})
     */
    public $toDepartmentId;

    /**
     * @Annotation\Type("Zend\Form\Element\Select")
     * @Annotation\Required({"required":"true"})
     * @Annotation\Filter({"name":"StripTags","name":"StringTrim"})
     * @Annotation\Options({"disable_inarray_validator":"true","label":"To Designation Name"})
     * @Annotation\Attributes({ "id":"toDesignationId","class":"form-control"})
     */
    public $toDesignationId;

    /**
     * @Annotation\Type("Zend\Form\Element\Select")
     * @Annotation\Required({"required":"true"})
     * @Annotation\Filter({"name":"StripTags","name":"StringTrim"})
     * @Annotation\Options({"disable_inarray_validator":"true","label":"To Position Name"})
     * @Annotation\Attributes({ "id":"toPositionId","class":"form-control"})
     */
    public $toPositionId;

    /**
     * @Annotation\Type("Zend\Form\Element\Select")
     * @Annotation\Required({"required":"true"})
     * @Annotation\Filter({"name":"StripTags","name":"StringTrim"})
     * @Annotation\Options({"disable_inarray_validator":"true","label":"To Company Name"})
     * @Annotation\Attributes({ "id":"toCompanyId","class":"form-control"})
     */
    public $toCompanyId;

    /**
     * @Annotion\Type("Zend\Form\Element\Number")
     * @Annotation\Required(true)
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"label":"To Salary"})
     * @Annotation\Attributes({ "id":"toSalary", "class":"form-control" })
     */
    public $toSalary;

    /**
     * @Annotation\Type("Zend\Form\Element\Checkbox")
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"label":"Is Retired"})
     * @Annotation\Attributes({ "id":"isRetired"})
     */
    public $retiredFlag;

    /**
     * @Annotation\Type("Zend\Form\Element\Checkbox")
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"label":"Is Disabled"})
     * @Annotation\Attributes({ "id":"isDisabled"})
     */
    public $disabledFlag;

    /**
     * @Annotation\Type("Zend\Form\Element\Text")
     * @Annotation\Required(true)
     * @Annotation\Filter({"name":"StripTags","name":"StringTrim"})
     * @Annotation\Options({"label":"Event Date"})
     * @Annotation\Attributes({ "class":"form-control","id":"eventDate"})
     */
    public $eventDate;

    /**
     * @Annotation\Type("Zend\Form\Element\Hidden")
     * @Annotation\Required(false)
     * @Annotation\Attributes({"id":"fileId"})
     */
    public $fileId;

    /**
     * @Annotation\Type("Zend\Form\Element\Submit")
     * @Annotation\Attributes({"value":"Submit","class":"btn btn-success"})
     */
    public $submit;

}
