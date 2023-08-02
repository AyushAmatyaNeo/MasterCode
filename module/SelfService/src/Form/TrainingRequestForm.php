<?php

namespace SelfService\Form;

use Zend\Form\Annotation;

/**
 * @Annotation\Hydrator("Zend\Hydrator\ObjectPropertyHydrator")
 * @Annotation\Name("TrainingRequest")
 */
class TrainingRequestForm {

    /**
     * @Annotation\Type("Zend\Form\Element\Select")
     * @Annotation\Required(false)
     * @Annotation\Options({"disable_inarray_validator":"true","label":"Employee Name"})
     * @Annotation\Attributes({ "id":"employeeId","class":"form-control"})
     */
    public $employeeId;

    /**
     * @Annotation\Type("Zend\Form\Element\Select")
     * @Annotation\Required(true)
     * @Annotation\Options({"disable_inarray_validator":"true","label":"Training Name"})
     * @Annotation\Attributes({ "id":"trainingId","class":"form-control"})
     */
    public $trainingId;

    /**
     * @Annotation\Type("Zend\Form\Element\Text")
     * @Annotation\Required(false)
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"label":"Training Name"})
     * @Annotation\Attributes({ "id":"title", "class":"form-control" })
     */
    public $title;

    /**
     * @Annotation\Type("Zend\Form\Element\Select")
     * @Annotation\Required(true)
     * @Annotation\Filter({"name":"StripTags","name":"StringTrim"})
     * @Annotation\Options({"disable_inarray_validator":"true","label":"Training Type"})
     * @Annotation\Attributes({ "id":"trainingType","class":"form-control"})
     */
    public $trainingType;

    /**
     * @Annotation\Type("Zend\Form\Element\Text")
     * @Annotation\Required(true)
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"label":"Start Date"})
     * @Annotation\Attributes({ "id":"startDate", "class":"form-control" })
     */
    public $startDate;

    /**
     * @Annotation\Type("Zend\Form\Element\Text")
     * @Annotation\Required(true)
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"label":"End Date"})
     * @Annotation\Attributes({ "id":"endDate", "class":"form-control" })
     */
    public $endDate;

    /**
     * @Annotation\Type("Zend\Form\Element\Number")
     * @Annotation\Required(true)
     * @Annotation\Options({"label":"Duration"})
     * @Annotation\Attributes({ "id":"duration","readonly":"readonly", "class":"form-control","step":"0.01" })
     */
    public $duration;

    /**
     * @Annotation\Type("Zend\Form\Element\Textarea")
     * @Annotation\Required(false)
     * @Annotation\Filter({"name":"StripTags","name":"StringTrim"})
     * @Annotation\Options({"label":"Description"})
     * @Annotation\Attributes({"id":"description","class":"form-control","style":"height: 50px; font-size:12px"})
     */
    public $description;

    /**
     * @Annotation\Type("Zend\Form\Element\Textarea")
     * @Annotation\Required(false)
     * @Annotation\Filter({"name":"StripTags","name":"StringTrim"})
     * @Annotation\Options({"label":"Remarks"})
     * @Annotation\Attributes({"id":"remarks","class":"form-control","style":"height: 50px; font-size:12px"})
     */
    public $remarks;

    /**
     * @Annotation\Type("Zend\Form\Element\Textarea")
     * @Annotation\Required(false)
     * @Annotation\Filter({"name":"StripTags","name":"StringTrim"})
     * @Annotation\Options({"label":"Recommender Remarks"})
     * @Annotation\Attributes({"id":"recommendedRemarks","class":"form-control","style":"height: 50px; font-size:12px"})
     */
    public $recommendedRemarks;

    /**
     * @Annotation\Type("Zend\Form\Element\Textarea")
     * @Annotation\Required(false)
     * @Annotation\Filter({"name":"StripTags","name":"StringTrim"})
     * @Annotation\Options({"label":"Approver Remarks"})
     * @Annotation\Attributes({"id":"form-approvedRemarks","class":"form-control","style":"height: 50px; font-size:12px"})
     */
    public $approvedRemarks;

    /**
     * @Annotation\Type("Zend\Form\Element\Radio")
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"value_options":{"Y":"Yes","N":"No"},"label":"Is Within Company"})
     * @Annotation\Attributes({ "id":"isWithinCompany"})
     */
    public $isWithinCompany;

    /**
     * @Annotation\Type("Zend\Form\Element\Submit")
     * @Annotation\Attributes({"value":"Submit","class":"btn btn-success"})
     */
    public $submit;
    
    /**
     * @Annotation\Type("Zend\Form\Element\Number")
     * @Annotation\Required(true)
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"label":"Daily Training Hour"})
     * @Annotation\Attributes({ "id":"dailyTrainingHour", "class":" form-control","step":"0.1"})
     */
    public $dailyTrainingHour;

}
