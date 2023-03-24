<?php
namespace SelfService\Form;

use Zend\Form\Annotation;
/**
 * @Annotation\Hydrator("Zend\Hydrator\ObjectPropertyHydrator")
 * @Annotation\Name("TravelRequest")
 */

class TravelRequestForm{
    
    /**
     * @Annotation\Type("Zend\Form\Element\Select")
     * @Annotation\Required(false)
     * @Annotation\Filter({"name":"StripTags","name":"StringTrim"})
     * @Annotation\Options({"disable_inarray_validator":"true","label":"Employee Name"})
     * @Annotation\Attributes({ "id":"form-employeeId","class":"form-control"})
     */
    public $employeeId;
    
     /**
     * @Annotation\Type("Zend\Form\Element\Number")
     * @Annotation\Required(false)
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"label":"Request Amount"})
     * @Annotation\Attributes({ "id":"form-requestedAmount", "class":"form-requestedAmount form-control","min":"0","step":"0.01" })
     */
    public $requestedAmount;
    
    /**
     * @Annotation\Type("Zend\Form\Element\Text")
     * @Annotation\Required({"required":"true"})
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"label":"From Date"})
     * @Annotation\Attributes({ "id":"form-fromDate", "class":"form-toDate form-control" })
     */
    public $fromDate;
    
    /**
     * @Annotation\Type("Zend\Form\Element\Text")
     * @Annotation\Required({"required":"true"})
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"label":"To Date"})
     * @Annotation\Attributes({ "id":"form-toDate", "class":"form-toDate form-control" })
     */
    public $toDate;   
    
    /**
     * @Annotation\Type("Zend\Form\Element\Text")
     * @Annotation\Options({"label":"No of Days"})
     * @Annotation\Required(true)
     * @Annotation\Attributes({ "id":"noOfDays", "class":"form-control","readonly":"true"})
     */
    public $noOfDays;
    
    /**
     * @Annotation\Type("Zend\Form\Element\Text")
     * @Annotation\Required(true)
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"label":"Destination"})
     * @Annotation\Attributes({ "class":"form-destination","class":"form-control"})
     */
    public $destination;
    
    /**
     * @Annotation\Type("Zend\Form\Element\Text")
     * @Annotation\Required(true)
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"label":"Departure"})
     * @Annotation\Attributes({ "class":"form-departure","class":"form-control"})
     */
    public $departure;
    
    /**
     * @Annotation\Type("Zend\Form\Element\Select")
     * @Annotation\Required(false)
     * @Annotation\Filter({"name":"StripTags","name":"StringTrim"})
     * @Annotation\Options({"disable_inarray_validator":"true","label":"Request For"})
     * @Annotation\Attributes({ "id":"form-requestedType","class":"form-control"})
     */
    public $requestedType;
    
    /**
     * @Annotation\Type("Zend\Form\Element\Select")
     * @Annotation\Required(false)
     * @Annotation\Filter({"name":"StripTags","name":"StringTrim"})
     * @Annotation\Options({"disable_inarray_validator":"true","label":"Transport Type"})
     * @Annotation\Attributes({ "id":"form-transportType","class":"form-control"})
     */
    public $transportType;
    
    /**
     * @Annotation\Type("Zend\Form\Element\Textarea")
     * @Annotation\Required(false)
     * @Annotation\Filter({"name":"StripTags","name":"StringTrim"})
     * @Annotation\Options({"label":"Purpose"})
     * @Annotation\Attributes({"id":"form-purpose","class":"form-purpose form-control","style":"    height: 50px; font-size:12px"})
     */
    public $purpose;
   
    /**
     * @Annotation\Type("Zend\Form\Element\Textarea")
     * @Annotation\Required(false)
     * @Annotation\Filter({"name":"StripTags","name":"StringTrim"})
     * @Annotation\Options({"label":"Remarks"})
     * @Annotation\Attributes({"id":"form-remarks","class":"form-remarks form-control","style":"    height: 50px; font-size:12px"})
     */
    public $remarks;
    
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
}