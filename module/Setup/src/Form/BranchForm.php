<?php
namespace Setup\Form;

use Zend\Form\Annotation;

/**
 * @Annotation\Hydrator("Zend\Hydrator\ObjectPropertyHydrator")
 * @Annotation\Name("Branch")
 */
class BranchForm {

    /**
     * @Annotion\Type("Zend\Form\Element\Text")
     * @Annotation\Required(true)
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"label":"Branch Name"})
     * @Annotation\Validator({"name":"StringLength", "options":{"max":"255"}})
     * @Annotation\Attributes({ "id":"form-branchName", "class":"form-branchName form-control" })
     */
    public $branchName;

    /**
     * @Annotion\Type("Zend\Form\Element\Text")
     * @Annotation\Required(false)
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"label":"Street Address"})
     * @Annotation\Validator({"name":"StringLength", "options":{"max":"255"}})
     * @Annotation\Attributes({ "id":"form-streetAddress", "class":"form-streetAddress form-control"  })
     */
    public $streetAddress;

    /**
     * @Annotation\Type("Zend\Form\Element\Select")
     * @Annotation\Required(false)
     * @Annotation\Options({"disable_inarray_validator":"true","label":"Country"})
     * @Annotation\Attributes({ "id":"countryId","class":"form-control"})
     */
    public $countryId;

    /**
     * @Annotation\Type("Zend\Form\Element\Select")
     * @Annotation\Required(false)
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"disable_inarray_validator":"true","label":"Company"})
     * @Annotation\Attributes({ "id":"companyId","class":"form-control"})
     */
    public $companyId;

    /**
     * @Annotation\Type("Application\Custom\FormElement\Telephone")
     * @Annotation\Required(false)
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"label":"Telephone"})
     * @Annotation\Attributes({ "id":"form-telephone", "placeholder":"xxx-xxxxxxx", "pattern":"^\(?\d{2,3}\)?[- ]?\d{7}$", "class":"form-control","title"="Enter your mobile number(xx-xxxxxxx)"})
     */
    public $telephone;

    /**
     * @Annotion\Type("Zend\Form\Element\Text")
     * @Annotation\Required(false)
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Validator({"name":"StringLength", "options":{"max":"10"}})
     * @Annotation\Options({"label":"Fax"})
     * @Annotation\Attributes({ "id":"fax", "class":"form-control"})
     */
    public $fax;

    /**
     * @Annotation\Type("Zend\Form\Element\Email")
     * @Annotation\Required(false)
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"label":"Email"})
     * @Annotation\Attributes({ "id":"email", "class":"form-control", "pattern":"^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$" })
     */
    public $email;

    /**
     * @Annotation\Type("Zend\Form\Element\Textarea")
     * @Annotation\Required(false)
     * @Annotation\Options({"label":"Remarks"})
     * @Annotation\Attributes({"id":"remarks","class":"form-control"})
     */
    public $remarks;

    /**
     * @Annotation\Type("Zend\Form\Element\Submit")
     * @Annotation\Attributes({"value":"Submit","class":"btn btn-success"})
     */
    public $submit;
    
    /**
     * @Annotation\Type("Zend\Form\Element\Select")
     * @Annotation\Required(false)
     * @Annotation\Options({"disable_inarray_validator":"true","label":"Branch Manager"})
     * @Annotation\Attributes({ "id":"branchManager","class":"form-control"})
     */
    public $branchManager;
    
    /**
     * @Annotation\Type("Zend\Form\Element\Select")
     * @Annotation\Required(false)
     * @Annotation\Options({"disable_inarray_validator":"true","label":"Province"})
     * @Annotation\Attributes({ "id":"province","class":"form-control"})
     */
    public $province;

    /**
     * @Annotation\Type("Zend\Form\Element\Radio")
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"value_options":{"Y":"Yes","N":"No"},"label":"Is Remote"})
     * @Annotation\Required(false)
     * @Annotation\Attributes({ "id":"isRemote","value":"N"})
     */
    public $isRemote;

    /**
     * @Annotation\Type("Zend\Form\Element\Number")
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"label":"ALLOWANCE REBATE"})
     * @Annotation\Required(false)
     * @Annotation\Attributes({ "id":"allowanceRebate", "class":"form-control","step":"1"})
     */
    public $allowanceRebate;
}
