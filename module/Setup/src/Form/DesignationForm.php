<?php
namespace Setup\Form;

use Zend\Form\Annotation;

/**
 * @Annotation\Hydrator("Zend\Hydrator\ObjectPropertyHydrator")
 * @Annotation\Name("DesignationForm")
 */
class DesignationForm {

    /**
     * @Annotation\Type("Zend\Form\Element\Text")
     * @Annotation\Required(true)
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"label":"Designation Title"})
     * @Annotation\Validator({"name":"StringLength", "options":{"min":"1","max":255}})
     * @Annotation\Attributes({ "id":"designationTitle", "class":"form-control" })
     */
    public $designationTitle;

    /**
     * @Annotation\Type("Zend\Form\Element\Select")
     * @Annotation\Required(false)
     * @Annotation\Options({"disable_inarray_validator":"true","label":"Parent Designation"})
     * @Annotation\Attributes({ "id":"parentDesignation","class":"form-control"})
     */
    public $parentDesignation;

    /**
     * @Annotation\Type("Zend\Form\Element\Select")
     * @Annotation\Required(false)
     * @Annotation\Options({"disable_inarray_validator":"true","label":"Company"})
     * @Annotation\Attributes({ "id":"companyId","class":"form-control"})
     */
    public $companyId;

    /**
     * @Annotation\Type("Zend\Form\Element\Radio")
     * @Annotation\Required(false)
     * @Annotation\Options({"label":"Within Branch","value_options":{"Y":"Yes","N":"No"}})
     * @Annotation\Attributes({"id":"withinBranch","class":"form-control"})
     */
    public $withinBranch;

    /**
     * @Annotation\Type("Zend\Form\Element\Radio")
     * @Annotation\Required(false)
     * @Annotation\Options({"label":"Within Department","value_options":{"Y":"Yes","N":"No"}})
     * @Annotation\Attributes({"id":"withinDepartment","class":"form-control"})
     */
    public $withinDepartment;

    /**
     * @Annotation\Type("Zend\Form\Element\Number")
     * @Annotation\Required(false)
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"label":"Basic Salary"})
     * @Annotation\Attributes({ "id":"basicSalary" ,"min":"0","step":"0.01","class":"form-basicSalary form-control" })
     */
    public $basicSalary;
    
    /**
     * @Annotation\Type("Zend\Form\Element\Number")
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"label":"Order No"})
     * @Annotation\Required(false)
     * @Annotation\Attributes({ "id":"orderNo","min":"0", "class":"form-control"})
     */
    public $orderNo;

    /**
     * @Annotation\Type("Zend\Form\Element\Submit")
     * @Annotation\Attributes({"value":"Submit","class":"btn btn-success"})
     */
    public $submit;

}
