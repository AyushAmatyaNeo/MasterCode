<?php

namespace Payroll\Form;

use Zend\Form\Annotation;

/**
 * @Annotation\Hydrator("Zend\Hydrator\ObjectPropertyHydrator")
 * @Annotation\Name("Monthly Value")
 */
class MonthlyValue {

    /**
     * @Annotation\Type("Zend\Form\Element\Text")
     * @Annotation\Required(false)
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Validator({"name":"StringLength", "options":{"max":"15"}})
     * @Annotation\Options({"label":"Code"})
     * @Annotation\Attributes({ "id":"mthCode","class":"form-control"})
     */
    public $mthCode;

    /**
     * @Annotation\Type("Zend\Form\Element\Text")
     * @Annotation\Required(true)
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Validator({"name":"StringLength", "options":{"max":"100"}})
     * @Annotation\Options({"label":"Monthly Value EDesc"})
     * @Annotation\Attributes({ "id":"mthEdesc","class":"form-control"})
     */
    public $mthEdesc;

    /**
     * @Annotation\Type("Zend\Form\Element\Text")
     * @Annotation\Required(false)
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Validator({"name":"StringLength", "options":{"max":"100"}})
     * @Annotation\Options({"label":"Monthly Value NDesc"})
     * @Annotation\Attributes({ "id":"mthLdesc","class":"form-control"})
     */
    public $mthLdesc;

    /**
     * @Annotation\Type("Zend\Form\Element\Radio")
     * @Annotation\Options({"value_options":{"P":"Position Wise","E":"Employee Wise"},"label":"Assign Type"})
     * @Annotation\Attributes({ "id":"assignType","class":"form-control"})
     */
    public $assignType;

    /**
     * @Annotation\Type("Zend\Form\Element\Textarea")
     * @Annotation\Required(true)
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
