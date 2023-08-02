<?php

namespace Payroll\Form;

use Zend\Form\Annotation;

/**
 * @Annotation\Hydrator("Zend\Hydrator\ObjectPropertyHydrator")
 * @Annotation\Name("rules")
 */
class Rules {

    /**
     * @Annotation\Type("Zend\Form\Element\Text")
     * @Annotation\Required(false)
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Validator({"name":"StringLength", "options":{"max":"15"}})
     * @Annotation\Options({"label":"Pay Code"})
     * @Annotation\Attributes({ "id":"payCode","class":"form-control"})
     */
    public $payCode;

    /**
     * @Annotation\Type("Zend\Form\Element\Text")
     * @Annotation\Required(true)
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Validator({"name":"StringLength", "options":{"max":"100"}})
     * @Annotation\Options({"label":"Pay EDesc"})
     * @Annotation\Attributes({ "id":"payEdesc","class":"form-control"})
     */
    public $payEdesc;

    /**
     * @Annotation\Type("Zend\Form\Element\Text")
     * @Annotation\Required(false)
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Validator({"name":"StringLength", "options":{"max":"100"}})
     * @Annotation\Options({"label":"Pay LDesc"})
     * @Annotation\Attributes({ "id":"payLdesc","class":"form-control"})
     */
    public $payLdesc;

    /**
     * @Annotation\Type("Zend\Form\Element\Radio")
     * @Annotation\Required(true)
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"value_options":{"A":"Addition","D":"Deduction","V":"View","T":"Tax"},"label":"Pay Type"})
     * @Annotation\Attributes({ "id":"payTypeFlag","class":"form-control"})
     */
    public $payTypeFlag;

    /**
     * @Annotation\Type("Zend\Form\Element\Number")
     * @Annotation\Required(true)
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"label":"Priority Index"})
     * @Annotation\Attributes({ "id":"priorityIndex","class":"form-control"})
     */
    public $priorityIndex;

    /**
     * @Annotation\Type("Zend\Form\Element\Textarea")
     * @Annotation\Required(false)
     * @Annotation\Options({"label":"Formula"})
     * @Annotation\Attributes({ "id":"formula","class":"form-control"})
     */
    public $formula;

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
     * @Annotation\Type("Zend\Form\Element\Radio")
     * @Annotation\Options({"value_options":{"Y":"Yes","N":"No"},"label":"Include In Tax"})
     * @Annotation\Required(false)
     * @Annotation\Attributes({ "id":"Include In Tax"})
     */
    public $includeInTax;

    /**
     * @Annotation\Type("Zend\Form\Element\Radio")
     * @Annotation\Options({"value_options":{"Y":"Yes","N":"No"},"label":"Include In Salary"})
     * @Annotation\Required(false)
     * @Annotation\Attributes({ "id":"Include In Salary"})
     */
    public $includeInSalary;

    /**
     * @Annotation\Type("Zend\Form\Element\Radio")
     * @Annotation\Options({"value_options":{"Y":"Yes","N":"No"},"label":"Include Past Value"})
     * @Annotation\Required(false)
     * @Annotation\Attributes({ "id":"Include Past Value"})
     */
    public $includePastValue;

    /**
     * @Annotation\Type("Zend\Form\Element\Radio")
     * @Annotation\Options({"value_options":{"Y":"Yes","N":"No"},"label":"Include Future Value"})
     * @Annotation\Required(false)
     * @Annotation\Attributes({ "id":"Include Future Value"})
     */
    public $includeFutureValue;

    /**
     * @Annotation\Type("Zend\Form\Element\Submit")
     * @Annotation\Attributes({"value":"Submit","class":"btn btn-success"})
     */
    public $submit;

}
