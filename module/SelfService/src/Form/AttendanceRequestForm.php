<?php

/**
 * Created by PhpStorm.
 * User: punam
 * Date: 10/6/16
 * Time: 3:06 PM
 */

namespace SelfService\Form;

use Zend\Form\Annotation;

/**
 * @Annotation\Hydrator("Zend\Hydrator\ObjectPropertyHydrator")
 * @Annotation\Name("attendanceByHr")
 */
class AttendanceRequestForm
{

    /**
     * @Annotation\Type("Zend\Form\Element\Text")
     * @Annotation\Required(true)
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"label":"Attendance Date"})
     * @Annotation\Attributes({ "class":"form-control","id":"attendanceDt" })
     */
    public $attendanceDt;

    /**
     * @Annotation\Type("Zend\Form\Element\Text")
     * @Annotation\Required(true)
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"label":"In Time"})
     * @Annotation\Attributes({ "id":"inTime",  "data-format":"h:mm a", "data-template":"hh : mm A", "class":"form-control"  })
     */
    public $inTime;

    /**
     * @Annotation\Type("Zend\Form\Element\Text")
     * @Annotation\Required(true)
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"label":"Out Time"})
     * @Annotation\Attributes({ "id":"outTime",  "data-format":"h:mm a", "data-template":"hh : mm A", "class":"form-control"  })
     */
    public $outTime;

    /**
     * @Annotion\Type("Zend\Form\Element\Textarea")
     * @Annotation\Required(false)
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"label":"In Remarks"})
     * @Annotation\Attributes({ "id":"inRemarks", "class":"form-control" })
     */
    public $inRemarks;

    /**
     * @Annotion\Type("Zend\Form\Element\Textarea")
     * @Annotation\Required(false)
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"label":"Out Remarks"})
     * @Annotation\Attributes({ "id":"outRemarks", "class":"form-control" })
     */
    public $outRemarks;

    /**
     * @Annotion\Type("Zend\Form\Element\Number")
     * @Annotation\Required(false)
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"label":"Total Hour"})
     * @Annotation\Attributes({ "id":"totalHour", "class":"form-control" })
     */
    public $totalHour;

    /**
     * @Annotation\Type("Zend\Form\Element\Textarea")
     * @Annotation\Required(false)
     * @Annotation\Filter({"name":"StripTags","name":"StringTrim"})
     * @Annotation\Options({"label":"Reason for action"})
     * @Annotation\Attributes({"id":"form-recommendedRemarks","class":"form-reason form-control","style":"    height: 50px; font-size:12px"})
     */
    public $recommendedRemarks;

    /**
     * @Annotation\Type("Zend\Form\Element\Textarea")
     * @Annotation\Required(false)
     * @Annotation\Filter({"name":"StripTags","name":"StringTrim"})
     * @Annotation\Options({"label":"Reason for action"})
     * @Annotation\Attributes({"id":"form-approvedRemarks","class":"form-reason form-control","style":"    height: 50px; font-size:12px"})
     */
    public $approvedRemarks;

    /**
     * @Annotation\Type("Zend\Form\Element\Submit")
     * @Annotation\Attributes({"value":"Submit","class":"btn btn-success"})
     */
    public $submit;

    /**
     * @Annotation\Type("Zend\Form\Element\Select")
     * @Annotation\Required(false)
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"disable_inarray_validator":"true","label":"Employee"})
     * @Annotation\Attributes({ "id":"employeeId","class":"form-control"})
     */
    public $employeeId;
}
