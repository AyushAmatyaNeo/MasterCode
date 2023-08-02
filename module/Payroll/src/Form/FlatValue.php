<?php

namespace Payroll\Form;

use Zend\Form\Annotation;

/**
 * @Annotation\Hydrator("Zend\Hydrator\ObjectPropertyHydrator")
 * @Annotation\Name("FlatValue")
 */
class FlatValue {

    /**
     * @Annotation\Type("Zend\Form\Element\Text")
     * @Annotation\Required(false)
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Validator({"name":"StringLength", "options":{"max":"15"}})
     * @Annotation\Options({"label":"Flat Code"})
     * @Annotation\Attributes({ "id":"flatCode","class":"form-control"})
     */
    public $flatCode;

    /**
     * @Annotation\Type("Zend\Form\Element\Text")
     * @Annotation\Required(true)
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Validator({"name":"StringLength", "options":{"max":"100"}})
     * @Annotation\Options({"label":"Flat EDesc"})
     * @Annotation\Attributes({ "id":"flatEdesc","class":"form-control"})
     */
    public $flatEdesc;

    /**
     * @Annotation\Type("Zend\Form\Element\Text")
     * @Annotation\Required(false)
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Validator({"name":"StringLength", "options":{"max":"100"}})
     * @Annotation\Options({"label":"Flat LDesc"})
     * @Annotation\Attributes({ "id":"flatLdesc","class":"form-control"})
     */
    public $flatLdesc;

    /**
     * @Annotation\Type("Zend\Form\Element\Radio")
     * @Annotation\Options({"value_options":{"P":"Position Wise","E":"Employee Wise"},"label":"Assign Type"})
     * @Annotation\Attributes({ "id":"assignType","class":"form-control"})
     */
    public $assignType;

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

}
