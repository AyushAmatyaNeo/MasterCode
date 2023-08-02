<?php
namespace System\Form;

use Zend\Form\Annotation;

/**
 * @Annotation\Hydrator("Zend\Hydrator\ObjectPropertyHydrator")
 * @Annotation\Name("RoleSetup")
 */
class RoleSetupForm {

    /**
     * @Annotion\Type("Zend\Form\Element\Text")
     * @Annotation\Required(true)
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"label":"Role Name"})
     * @Annotation\Attributes({ "id":"form-roleName", "class":"form-roleName form-control" })
     */
    public $roleName;

    /**
     * @Annotation\Type("Zend\Form\Element\Select")
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"disable_inarray_validator":"true","label":"Control"})
     * @Annotation\Required(false)
     * @Annotation\Attributes({ "id":"control","class":"form-control","multiple":"True"})
     */
    public $control;

    /**
     * @Annotation\Type("Zend\Form\Element\Radio")
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"value_options":{"Y":"Yes","N":"No"},"label":"Allow Add"})
     * @Annotation\Required(false)
     * @Annotation\Attributes({ "id":"allowAdd"})
     */
    public $allowAdd;

    /**
     * @Annotation\Type("Zend\Form\Element\Radio")
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"value_options":{"Y":"Yes","N":"No"},"label":"Allow Update"})
     * @Annotation\Required(false)
     * @Annotation\Attributes({ "id":"allowUpdate"})
     */
    public $allowUpdate;

    /**
     * @Annotation\Type("Zend\Form\Element\Radio")
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"value_options":{"Y":"Yes","N":"No"},"label":"Allow Delete"})
     * @Annotation\Required(false)
     * @Annotation\Attributes({ "id":"allowDelete"})
     */
    public $allowDelete;

    /**
     * @Annotation\Type("Zend\Form\Element\Textarea")
     * @Annotation\Required(false)
     * @Annotation\Filter({"name":"StripTags","name":"StringTrim"})
     * @Annotation\Options({"label":"Remarks"})
     * @Annotation\Attributes({"id":"remarks","class":"form-control"})
     */
    public $remarks;
    
    /**
     * @Annotation\Type("Zend\Form\Element\Select")
     * @Annotation\Required(false)
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"disable_inarray_validator":"true"})
     * @Annotation\Attributes({ "id":"selectOptionsC","class":"form-control","multiple":"True"})
     */
    public $selectOptionsC;
        /**
     * @Annotation\Type("Zend\Form\Element\Select")
     * @Annotation\Required(false)
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"disable_inarray_validator":"true"})
     * @Annotation\Attributes({ "id":"selectOptionsDP","class":"form-control","multiple":"True"})
     */
    public $selectOptionsDP;
        /**
     * @Annotation\Type("Zend\Form\Element\Select")
     * @Annotation\Required(false)
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"disable_inarray_validator":"true"})
     * @Annotation\Attributes({ "id":"selectOptionsDS","class":"form-control","multiple":"True"})
     */
    public $selectOptionsDS;
        /**
     * @Annotation\Type("Zend\Form\Element\Select")
     * @Annotation\Required(false)
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"disable_inarray_validator":"true"})
     * @Annotation\Attributes({ "id":"selectOptionsP","class":"form-control","multiple":"True"})
     */
    public $selectOptionsP;
        /**
     * @Annotation\Type("Zend\Form\Element\Select")
     * @Annotation\Required(false)
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"disable_inarray_validator":"true"})
     * @Annotation\Attributes({ "id":"selectOptionsB","class":"form-control","multiple":"True"})
     */
    public $selectOptionsB;
        /**
     * @Annotation\Type("Zend\Form\Element\Select")
     * @Annotation\Required(false)
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"disable_inarray_validator":"true"})
     * @Annotation\Attributes({ "id":"selectOptions","class":"form-control","multiple":"True"})
     */
    public $selectOptions;
    
    /**
     * @Annotation\Type("Zend\Form\Element\Radio")
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"value_options":{"Y":"Yes","N":"No"},"label":"Hr Approve"})
     * @Annotation\Required(false)
     * @Annotation\Attributes({ "id":"hrApprove"})
     */
    public $hrApprove;
  
    /**
     * @Annotation\Type("Zend\Form\Element\Radio")
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"value_options":{"Y":"Yes","N":"No"},"label":"Hr Cancel"})
     * @Annotation\Required(false)
     * @Annotation\Attributes({ "id":"hrCancel"})
     */
    public $hrCancel;
  
    /**
     * @Annotation\Type("Zend\Form\Element\Submit")
     * @Annotation\Attributes({"value":"Submit","class":"btn btn-success"})
     */
    public $submit;

}

