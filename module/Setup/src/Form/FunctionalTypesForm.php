<?php

namespace Setup\Form;

use Zend\Form\Annotation;

/**
 * @Annotation\Hydrator("Zend\Hydrator\ObjectPropertyHydrator")
 * @Annotation\Name("functionalTypes")
 */
class FunctionalTypesForm {

    /**
     * @Type("Zend\Form\Element\Text")
     * @Annotation\Required(false)
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"label":"Functional Types Code"})
     * @Annotation\Validator({"name":"StringLength", "options":{"min":"1","max":255}})
     * @Annotation\Attributes({ "id":"functionalTypesCode", "class":"form-control" })
     */
    public $functionalTypesCode;

    /**
     * @Type("Zend\Form\Element\Text")
     * @Annotation\Required(true)
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"label":"Functional Types Edesc"})
     * @Annotation\Validator({"name":"StringLength", "options":{"min":"1","max":255}})
     * @Annotation\Attributes({ "id":"functionalTypesEdesc", "class":"form-control" })
     */
    public $functionalTypesEdesc;

    /**
     * @Type("Zend\Form\Element\Text")
     * @Annotation\Required(false)
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"label":"Functional Types Ldesc"})
     * @Annotation\Validator({"name":"StringLength", "options":{"min":"1","max":255}})
     * @Annotation\Attributes({ "id":"functionalTypesLdesc", "class":"form-control" })
     */
    public $functionalTypesLdesc;

    /**
     * @Type("Zend\Form\Element\Submit")
     * @Annotation\Attributes({"value":"Submit","class":"btn btn-success"})
     */
    public $submit;

}
