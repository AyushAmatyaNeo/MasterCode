<?php

namespace Setup\Form;

use Zend\Form\Annotation;

/**
 * @Annotation\Hydrator("Zend\Hydrator\ObjectPropertyHydrator")
 * @Annotation\Name("Position")
 */
class LevelForm {

    /**
     * @Annotion\Type("Zend\Form\Element\Text")
     * @Annotation\Required({"required":"true"})
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Validator({"name":"StringLength", "options":{"max":"15"}}) 
     * @Annotation\Options({"label":"Level Code"})
     * @Annotation\Attributes({ "id":"levelCode", "class":"form-control" })
     */
    public $levelCode;

    /**
     * @Annotion\Type("Zend\Form\Element\Text")
     * @Annotation\Required({"required":"true"})
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"label":"Level Name"})
     * @Annotation\Validator({"name":"StringLength", "options":{"max":"255"}})
     * @Annotation\Attributes({ "id":"form-levelName", "class":"form-levelName form-control" })
     */
    public $levelName;

    /**
     * @Annotation\Type("Zend\Form\Element\Textarea")
     * @Annotation\Required(false)
     * @Annotation\Filter({"name":"StripTags","name":"StringTrim"})
     * @Annotation\Options({"label":"Remarks"})
     * @Annotation\Validator({"name":"StringLength", "options":{"max":"255"}})
     * @Annotation\Attributes({"id":"form-remarks","class":"form-remarks form-control","style":"    height: 50px; font-size:12px"})
     */
    public $remarks;

    /**
     * @Annotation\Type("Zend\Form\Element\Submit")
     * @Annotation\Attributes({"value":"Submit","class":"btn btn-success"})
     */
    public $submit;

    // /**
    //  * @Annotation\Type("Zend\Form\Element\Number")
    //  * @Annotation\Required({"required":"true"})
    //  * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
    //  * @Annotation\Options({"label":"Level ID"})
    //  * @Annotation\Required(true)
    //  * @Annotation\Attributes({ "id":"levelNo","min":"0", "class":"form-control"})
    //  */
    // public $levelId;


       /**
     * @Annotation\Type("Zend\Form\Element\Number")
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"label":"Amount"})
     * @Annotation\Required(true)
     * @Annotation\Attributes({ "id":"levelNo","min":"0", "class":"form-control"})
     */
    public $amount;

}
