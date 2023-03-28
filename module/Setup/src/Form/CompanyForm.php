<?php

namespace Setup\Form;

use Zend\Form\Annotation;

/**
 * @Annotation\Hydrator("Zend\Hydrator\ObjectPropertyHydrator")
 * @Annotation\Name("Company")
 */
class CompanyForm {

    /**
     * @Annotion\Type("Zend\Form\Element\Text")
     * @Annotation\Required({"required":"true"})
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Validator({"name":"StringLength", "options":{"max":"15"}}) 
     * @Annotation\Options({"label":"Code"})
     * @Annotation\Attributes({ "id":"companyCode", "class":"form-control" })
     */
    public $companyCode;

    /**
     * @Annotion\Type("Zend\Form\Element\Text")
     * @Annotation\Required({"required":"true"})
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"label":"Company Name"})
     * @Annotation\Attributes({ "id":"form-companyName", "class":"form-companyName form-control" })
     */
    public $companyName;

    /**
     * @Annotion\Type("Zend\Form\Element\Text")
     * @Annotation\Required({"required":"true"})
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"label":"Address"})
     * @Annotation\Attributes({ "id":"form-address", "class":"form-address form-control" })
     */
    public $address;

    /**
     * @Annotation\Type("Application\Custom\FormElement\Telephone")
     * @Annotation\Required(false)
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"label":"Telephone"})
     * @Annotation\Attributes({ "id":"form-telephone", "placeholder":"xxx-xxxxxxx", "pattern":"^\(?\d{2,3}\)?[- ]?\d{7}$","class":"form-control"})
     */
    public $telephone;

    /**
     * @Annotion\Type("Zend\Form\Element\Text")
     * @Annotation\Required(false)
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"label":"Fax"})
     * @Annotation\Attributes({ "id":"form-fax", "class":"form-fax form-control","placeholder":"Enter fax number.." })
     */
    public $fax;

    /**
     * @Annotion\Type("Zend\Form\Element\Text")
     * @Annotation\Required(false)
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"label":"Swift"})
     * @Annotation\Attributes({ "id":"form-swift", "class":"form-web form-control"})
     */
    public $swift;

    /**
     * @Annotation\Type("Zend\Form\Element\Radio")
     * @Annotation\Required(false)
     * @Annotation\Options({"value_options":{"Y":"Yes","N":"No"},"label":"Link Travel To Synergy"})
     * @Annotation\Attributes({ "id":"linkTravelToSynergy"})
     */
    public $linkTravelToSynergy;

    /**
     * @Annotation\Type("Zend\Form\Element\Select")
     * @Annotation\Required(false)
     * @Annotation\Options({"disable_inarray_validator":"true","label":"Form Code Headoffice"})
     * @Annotation\Attributes({ "id":"formCode","class":"form-control"})
     */
    public $formCode;

    /**
     * @Annotation\Type("Zend\Form\Element\Select")
     * @Annotation\Required(false)
     * @Annotation\Options({"disable_inarray_validator":"true","label":"Dr Account Code Headoffice"})
     * @Annotation\Attributes({ "id":"formCode","class":"form-control"})
     */
    public $drAccCode;

    /**
     * @Annotation\Type("Zend\Form\Element\Select")
     * @Annotation\Required(false)
     * @Annotation\Options({"disable_inarray_validator":"true","label":"Cr Account Code Headoffice"})
     * @Annotation\Attributes({ "id":"formCode","class":"form-control"})
     */
    public $crAccCode;

    /**
     * @Annotation\Type("Zend\Form\Element\Select")
     * @Annotation\Required(false)
     * @Annotation\Options({"disable_inarray_validator":"true","label":"Excess Cr Account Code Headoffice"})
     * @Annotation\Attributes({ "id":"formCode","class":"form-control"})
     */
    public $excessCrAccCode;

    /**
     * @Annotation\Type("Zend\Form\Element\Select")
     * @Annotation\Required(false)
     * @Annotation\Options({"disable_inarray_validator":"true","label":"Less Dr Account Code Headoffice"})
     * @Annotation\Attributes({ "id":"formCode","class":"form-control"})
     */
    public $lessDrAccCode;

    /**
     * @Annotation\Type("Zend\Form\Element\Select")
     * @Annotation\Required(false)
     * @Annotation\Options({"disable_inarray_validator":"true","label":"Equal Dr Account Code Headoffice"})
     * @Annotation\Attributes({ "id":"equalCrAccCode","class":"form-control"})
     */
    public $equalCrAccCode;

    /**
     * @Annotation\Type("Zend\Form\Element\Select")
     * @Annotation\Required(false)
     * @Annotation\Options({"disable_inarray_validator":"true","label":"Advance Dr Account Code Headoffice"})
     * @Annotation\Attributes({ "id":"advanceDrAccCode","class":"form-control"})
     */
    public $advanceDrAccCode;

    /**
     * @Annotation\Type("Zend\Form\Element\Select")
     * @Annotation\Required(false)
     * @Annotation\Options({"disable_inarray_validator":"true","label":"Advance Cr Account Code Headoffice"})
     * @Annotation\Attributes({ "id":"advanceCrAccCode","class":"form-control"})
     */
    public $advanceCrAccCode;
    
    /**
     * @Annotation\Type("Zend\Form\Element\Select")
     * @Annotation\Required(false)
     * @Annotation\Options({"disable_inarray_validator":"true","label":"Form Code Factory"})
     * @Annotation\Attributes({ "id":"formCodeFac","class":"form-control"})
     */
    public $formCodeFac;

    /**
     * @Annotation\Type("Zend\Form\Element\Select")
     * @Annotation\Required(false)
     * @Annotation\Options({"disable_inarray_validator":"true","label":"Dr Account Code Factory"})
     * @Annotation\Attributes({ "id":"formCodeFac","class":"form-control"})
     */
    public $drAccCodeFac;

    /**
     * @Annotation\Type("Zend\Form\Element\Select")
     * @Annotation\Required(false)
     * @Annotation\Options({"disable_inarray_validator":"true","label":"Cr Account Code Factory"})
     * @Annotation\Attributes({ "id":"formCodeFac","class":"form-control"})
     */
    public $crAccCodeFac;

    /**
     * @Annotation\Type("Zend\Form\Element\Select")
     * @Annotation\Required(false)
     * @Annotation\Options({"disable_inarray_validator":"true","label":"Excess Cr Account Code Factory"})
     * @Annotation\Attributes({ "id":"formCodeFac","class":"form-control"})
     */
    public $excessCrAccCodeFac;

    /**
     * @Annotation\Type("Zend\Form\Element\Select")
     * @Annotation\Required(false)
     * @Annotation\Options({"disable_inarray_validator":"true","label":"Less Dr Account Code Factory"})
     * @Annotation\Attributes({ "id":"formCodeFac","class":"form-control"})
     */
    public $lessDrAccCodeFac;

    /**
     * @Annotation\Type("Zend\Form\Element\Select")
     * @Annotation\Required(false)
     * @Annotation\Options({"disable_inarray_validator":"true","label":"Equal Dr Account Code Factory"})
     * @Annotation\Attributes({ "id":"equalCrAccCodeFac","class":"form-control"})
     */
    public $equalCrAccCodeFac;

    /**
     * @Annotation\Type("Zend\Form\Element\Select")
     * @Annotation\Required(false)
     * @Annotation\Options({"disable_inarray_validator":"true","label":"Advance Dr Account Code Factory"})
     * @Annotation\Attributes({ "id":"advanceDrAccCodeFac","class":"form-control"})
     */
    public $advanceDrAccCodeFac;

    /**
     * @Annotation\Type("Zend\Form\Element\Select")
     * @Annotation\Required(false)
     * @Annotation\Options({"disable_inarray_validator":"true","label":"Advance Cr Account Code Factory"})
     * @Annotation\Attributes({ "id":"advanceCrAccCodeFac","class":"form-control"})
     */
    public $advanceCrAccCodeFac;

    /**
     * @Annotation\Type("Zend\Form\Element\Submit")
     * @Annotation\Attributes({"value":"Submit","class":"btn btn-success", "id":"submit"})
     */
    public $submit;

}
