<?php

namespace System\Form;

use Zend\Form\Annotation;

/**
 * @Annotation\Hydrator("Zend\Hydrator\ObjectPropertyHydrator")
 * @Annotation\Name("UserSetup")
 */
class UserSetupForm {

    /**
     * @Annotation\Type("Zend\Form\Element\Select")
     * @Annotation\Required({"required":"true"})
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"disable_inarray_validator":"true","label":"Employee"})
     * @Annotation\Attributes({ "id":"employeeID","class":"form-control"})
     */
    public $employeeId;

    /**
     * @Annotation\Type("Zend\Form\Element\Select")
     * @Annotation\Required({"required":"true"})
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"disable_inarray_validator":"true","label":"Role"})
     * @Annotation\Attributes({ "id":"roleId","class":"form-control"})
     */
    public $roleId;

    /**
     * @Annotion\Type("Zend\Form\Element\Text")
     * @Annotation\Required(true)
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"label":"Username"})
     * @Annotation\Attributes({ "id":"form-userName", "class":"form-userName form-control" })
     */
    public $userName;
    // * @Annotation\Validator({"name":"Regex", "options":{"pattern":"/^(?=.*?[A-Z])(?=(.*[a-z]){1,})(?=(.*[\d]){1,})(?=(.*[\W]){1,})(?!.*\s).{8,}$/","messages":{"regexNotMatch":"the password should be at least 8 character long and should contain Numeric, Alphabet, Capital Letter, Symbol Combinations"}}})

    /**
     * @Annotion\Type("Zend\Form\Element\Password")
     * @Annotation\Required(false)
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"label":"Password"})
     * @Annotation\Attributes({ "id":"form-password", "class":"form-password form-control" })
     */
    public $password;

    /**
     * @Annotion\Type("Zend\Form\Element\Password")
     * @Annotation\Required(false)
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"label":"Re-Enter Password"})
     * @Annotation\Attributes({ "id":"form-repassword", "class":"form-repassword form-control" })
     */
    public $repassword;
    
    /**
     * @Annotation\Type("Zend\Form\Element\Select")
     * @Annotation\Required(true)
     * @Annotation\Options({"disable_inarray_validator":"true","label":"Locked Status","value_options":{"N":"No","Y":"Yes"}})
     * @Annotation\Attributes({ "id":"isLocked","class":"form-control"})
     */
    public $isLocked;

    /**
     * @Annotation\Type("Zend\Form\Element\Submit")
     * @Annotation\Attributes({"value":"Submit","class":"btn btn-success","id":"btnSubmit"})
     */
    public $submit;

}
