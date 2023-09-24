<?php

namespace LeaveManagement\Form;

use Zend\Form\Annotation;

/**
 * @Annotation\Hydrator("Zend\Hydrator\ObjectPropertyHydrator")
 * @Annotation\Name("LeaveMaster")
 */
class LeaveMasterForm
{

    /**
     * @Annotation\Type("Zend\Form\Element\Text")
     * @Annotation\Required(false)
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"label":"Leave Code"})
     * @Annotation\Attributes({ "id":"leaveCode", "class":"form-control" })
     * @Annotation\Validator({"name":"StringLength", "options":{"max":"15"}})
     */
    public $leaveCode;

    /**
     * @Annotation\Type("Zend\Form\Element\Text")
     * @Annotation\Required(true)
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"label":"Leave Ename"})
     * @Annotation\Attributes({ "id":"leaveEname", "class":"form-control" })
     * @Annotation\Validator({"name":"StringLength", "options":{"max":"150"}})
     */
    public $leaveEname;

    /**
     * @Annotation\Type("Zend\Form\Element\Text")
     * @Annotation\Required(false)
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"label":"Leave Lname"})
     * @Annotation\Attributes({ "id":"leaveLname", "class":"form-control" })
     */
    public $leaveLname;

    /**
     * @Annotation\Type("Zend\Form\Element\Radio")
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"value_options":{"Y":"Yes","N":"No"},"label":"Allow Halfday"})
     * @Annotation\Required(false)
     * @Annotation\Attributes({ "id":"allowHalfday"})
     */
    public $allowHalfday;

    /**
     * @Annotation\Type("Zend\Form\Element\Radio")
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"value_options":{"Y":"Yes","N":"No"},"label":"Paid"})
     * @Annotation\Required(false)
     * @Annotation\Attributes({ "id":"paid","value":"N"})
     */
    public $paid;

    /**
     * @Annotation\Type("Zend\Form\Element\Number")
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"label":"Default Days"})
     * @Annotation\Required(true)
     * @Annotation\Attributes({ "id":"defaultDays", "class":"form-control","step":"0.1"})
     */
    public $defaultDays;

    /**
     * @Annotation\Type("Zend\Form\Element\Select")
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Required(true)
     * @Annotation\Options({"disable_inarray_validator":"true","label":"Fiscal Year"})
     * @Annotation\Attributes({ "id":"fiscalYear","class":"form-control"})
     */
    public $fiscalYear;

    /**
     * @Annotation\Type("Zend\Form\Element\Radio")
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"value_options":{"Y":"Yes","N":"No"},"label":"Carry Forward"})
     * @Annotation\Attributes({ "id":"carryForward"})
     */
    public $carryForward;

    /**
     * @Annotation\Type("Zend\Form\Element\Radio")
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"value_options":{"Y":"Yes","N":"No"},"label":"Cashable"})
     * @Annotation\Attributes({ "id":"cashable"})
     */
    public $cashable;

    /**
     * @Annotation\Type("Zend\Form\Element\Number")
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"label":"Max Accumulate Days"})
     * @Annotation\Required(false)
     * @Annotation\Attributes({ "id":"maxAccumulateDays","min":"0", "class":"form-control"})
     */
    public $maxAccumulateDays;

    /**
     * @Annotation\Type("Zend\Form\Element\Textarea")
     * @Annotation\Required(false)
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"label":"Remarks"})
     * @Annotation\Attributes({ "id":"remarks", "class":"form-control" })
     */
    public $remarks;

    /**
     * @Annotation\Type("Zend\Form\Element\Radio")
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"value_options":{"Y":"Yes","N":"No"},"label":"Allow Grace Leave"})
     * @Annotation\Value("N")
     * @Annotation\Required(false)
     * @Annotation\Attributes({ "id":"allowGraceLeave","value":"N"})
     */
    public $allowGraceLeave;

    /**
     * @Annotation\Type("Zend\Form\Element\Radio")
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"value_options":{"Y":"Yes","N":"No"},"label":"Is Monthly"})
     * @Annotation\Required(false)
     * @Annotation\Attributes({ "id":"isMonthly","value":"N"})
     */
    public $isMonthly;

    /**
     * @Annotation\Type("Zend\Form\Element\Radio")
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"value_options":{"Y":"Yes","N":"No"},"label":"Is Substitute Mandatory"})
     * @Annotation\Required(false)
     * @Annotation\Attributes({ "id":"isSubstituteMandatory","value":"N"})
     */
    public $isSubstituteMandatory;

    /**
     * @Annotation\Type("Zend\Form\Element\Radio")
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"value_options":{"Y":"Yes","N":"No"},"label":"Assign On Employee Setup"})
     * @Annotation\Required(false)
     * @Annotation\Attributes({ "id":"assignOnEmployeeSetup","value":"N"})
     */
    public $assignOnEmployeeSetup;

    /**
     * @Annotation\Type("Zend\Form\Element\Radio")
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"value_options":{"Y":"Yes","N":"No"},"label":"Is Prodata Basis"})
     * @Annotation\Required(false)
     * @Annotation\Attributes({ "id":"isProdataBasis","value":"N"})
     */
    public $isProdataBasis;

    /**
     * @Annotation\Type("Zend\Form\Element\Radio")
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"value_options":{"Y":"Yes","N":"No"},"label":"Enable Substitute"})
     * @Annotation\Required(false)
     * @Annotation\Attributes({ "id":"enableSubstitute","value":"N"})
     */
    public $enableSubstitute;

    /**
     * @Annotation\Type("Zend\Form\Element\Submit")
     * @Annotation\Attributes({"value":"Submit","class":"btn btn-success"})
     */
    public $submit;


    /**
     * @Annotation\Type("Zend\Form\Element\Radio")
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"value_options":{"Y":"Yes","N":"No"},"label":"Day Off As Leave"})
     * @Annotation\Required(false)
     * @Annotation\Attributes({ "id":"dayOffAsLeave","value":"Y"})
     */
    public $dayOffAsLeave;


    /**
     * @Annotation\Type("Zend\Form\Element\Radio")
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"value_options":{"Y":"Yes","N":"No"},"label":"Holiday As Leave"})
     * @Annotation\Required(false)
     * @Annotation\Attributes({ "id":"holidayAsLeave","value":"Y"})
     */
    public $holidayAsLeave;

    /**
     * @Annotation\Type("Zend\Form\Element\Number")
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"label":"Apply Limit"})
     * @Annotation\Required(false)
     * @Annotation\Attributes({ "id":"applyLimit","min":"0", "class":"form-control"})
     */
    public $applyLimit;

    /**
     * @Annotation\Type("Zend\Form\Element\Radio")
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"value_options":{"Y":"Yes","N":"No"},"label":"HR only"})
     * @Annotation\Required(false)
     * @Annotation\Attributes({ "id":"hrOnly","value":"N"})
     */
    public $hrOnly;

    /**
     * @Annotation\Type("Zend\Form\Element\Radio")
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"value_options":{"Y":"Yes","N":"No"},"label":"Enable Override"})
     * @Annotation\Required(false)
     * @Annotation\Attributes({ "id":"enableOverride","value":"N"})
     */
    public $enableOverride;

    /**
     * @Annotation\Type("Zend\Form\Element\Number")
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"label":"View Order"})
     * @Annotation\Required(false)
     * @Annotation\Attributes({ "id":"viewOrder", "class":"form-control"})
     */
    public $viewOrder;

    /**
     * @Annotation\Type("Zend\Form\Element\Radio")
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"value_options":{"Y":"Yes","N":"No"},"label":"Substitute Leave"})
     * @Annotation\Attributes({ "id":"isSubstitute","value":"N"})
     */
    public $isSubstitute;

    /**
     * @Annotation\Type("Zend\Form\Element\Radio")
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"value_options":{"Y":"Yes","N":"No"},"label":"In Sub Leave"})
     * @Annotation\Attributes({ "id":"inSubLeave","value":"N"})
     */
    public $inSubLeave;
}
