<?php

namespace Payroll\Form;

use Zend\Form\Annotation;

/**
 * @Annotation\Hydrator("Zend\Hydrator\ObjectPropertyHydrator")
 * @Annotation\Name("FlatValue")
 */
class Variance {

    /**
     * @Annotation\Type("Zend\Form\Element\Text")
     * @Annotation\Required(true)
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Validator({"name":"StringLength", "options":{"max":"100"}})
     * @Annotation\Options({"label":"Name"})
     * @Annotation\Attributes({ "id":"varianceName","class":"form-control"})
     */
    public $varianceName;

    /**
     * @Annotation\Type("Zend\Form\Element\Select")
     * @Annotation\Required({"required":"true"})
     * @Annotation\Filter({"name":"StripTags","name":"StringTrim"})
     * @Annotation\Options({"disable_inarray_validator":"true","label":"Pay Heads"})
     * @Annotation\Attributes({ "id":"payId","class":"form-control","multiple":"multiple"})
     */
    public $payId;

    /**
     * @Annotation\Type("Zend\Form\Element\Radio")
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"value_options":{"Y":"Yes","N":"No"},"label":" Show Default"})
     * @Annotation\Required(false)
     * @Annotation\Attributes({"id":"showDefault","value":"N"})
     */
    public $showDefault;
    
    /**
     * @Annotation\Type("Zend\Form\Element\Radio")
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"value_options":{"Y":"Yes","N":"No"},"label":" Show Difference"})
     * @Annotation\Required(false)
     * @Annotation\Attributes({"id":"showDifference","value":"N"})
     */
    public $showDifference;
    
    /**
     * @Annotation\Type("Zend\Form\Element\Radio")
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"value_options":{"Y":"Yes","N":"No"},"label":" Calc Sum"})
     * @Annotation\Required(false)
     * @Annotation\Attributes({"id":"isSum","value":"N"})
     */
    public $isSum;
    
    /**
     * @Annotation\Type("Zend\Form\Element\Select")
     * @Annotation\Required({"required":"fa"})
     * @Annotation\Filter({"name":"StripTags","name":"StringTrim"})
     * @Annotation\Options({"disable_inarray_validator":"true","label":"Variable Type"})
     * @Annotation\Attributes({ "id":"variableType","class":"form-control"})
     */
    public $variableType;

    /**
     * @Annotation\Type("Zend\Form\Element\Textarea")
     * @Annotation\Required(false)
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Validator({"name":"StringLength", "options":{"max":"255"}})
     * @Annotation\Options({"label":"Remarks"})
     * @Annotation\Attributes({ "id":"remarks","class":"form-control"})
     */
    public $remarks;

    /**
     * @Annotation\Type("Zend\Form\Element\Submit")
     * @Annotation\Attributes({"value":"Submit","class":"btn btn-success"})
     */
    public $submit;
    
    
    /**
     * @Annotation\Type("Zend\Form\Element\Number")
     * @Annotation\Required(false)
     * @Annotation\Filter({"name":"StripTags","name":"StringTrim"})
     * @Annotation\Options({"label":"Order No"})
     * @Annotation\Attributes({ "id":"orderNo","class":"form-control","min":"0"})
     */
    public $orderNo;

    /**
     * @Annotation\Type("Zend\Form\Element\Select")
     * @Annotation\Required({"required":"false"})
     * @Annotation\Filter({"name":"StripTags","name":"StringTrim"})
     * @Annotation\Options({"disable_inarray_validator":"true","label":"Heads"})
     * @Annotation\Attributes({ "id":"vHeads","class":"form-control"})
     */
    public $vHeads;
    
}
