<?php

namespace HolidayManagement\Form;

use Zend\Form\Annotation;

/**
 * @Annotation\Hydrator("Zend\Hydrator\ObjectProperty")
 * @Annotation\Name("Holiday")
 */
class HolidayForm
{
    /**
     * @Annotion\Type("Zend\Form\Element\Text")
     * @Annotation\Required(true)
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"label":"Holiday Code"})
     * @Annotation\Attributes({ "id":"holidayCode", "class":"form-control" })
     */
    public $holidayCode;

    /**
     * @Annotation\Type("Zend\Form\Element\Select")
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Required(true)
     * @Annotation\Options({"disable_inarray_validator":"true","label":"Gender"})
     * @Annotation\Attributes({ "id":"genderId","class":"full-width select2-offscreen","data-init-plugin":"select2"})
     */
    public $genderId;

    /**
     * @Annotation\Type("Zend\Form\Element\Select")
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Required(true)
     * @Annotation\Options({"disable_inarray_validator":"true","label":"Gender"})
     * @Annotation\Attributes({ "id":"branchId","class":"full-width select2-offscreen","data-init-plugin":"select2"})
     */
    public $branchId;

    /**
     * @Annotion\Type("Zend\Form\Element\Text")
     * @Annotation\Required(true)
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"label":"Holiday Ename"})
     * @Annotation\Attributes({ "id":"holidayEname", "class":"form-control" })
     * @Annotation\Validator({"name":"StringLength", "options":{"max":"255"}})
     */
    public $holidayEname;

    /**
     * @Annotion\Type("Zend\Form\Element\Text")
     * @Annotation\Required(true)
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"label":"Holiday Lname"})
     * @Annotation\Attributes({ "id":"holidayLname", "class":"form-control" })
     * @Annotation\Validator({"name":"StringLength", "options":{"max":"255"}})
     */
    public $holidayLname;

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
     * @Annotation\Type("Zend\Form\Element\Radio")
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"value_options":{"Y":"Yes","N":"No"},"label":"Halfday"})
     * @Annotation\Required(false)
     * @Annotation\Attributes({ "id":"halfday"})
     */
    public $halfday;

    /**
     * @Annotation\Type("Zend\Form\Element\Select")
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Required(true)
     * @Annotation\Options({"disable_inarray_validator":"true","label":"Fiscal Year"})
     * @Annotation\Attributes({ "id":"fiscalYear","class":"full-width select2-offscreen","data-init-plugin":"select2"})
     */
    public $fiscalYear;

    /**
     * @Annotion\Type("Zend\Form\Element\Textarea")
     * @Annotation\Required(false)
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"label":"Remarks"})
     * @Annotation\Attributes({ "id":"remarks", "class":"form-control" })
     */
    public $remarks;

    /**
     * @Annotation\Type("Zend\Form\Element\Submit")
     * @Annotation\Attributes({"value":"Submit","class":"btn btn-primary pull-right"})
     */
    public $submit;

}