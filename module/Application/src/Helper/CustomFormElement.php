<?php
namespace Application\Helper;

use Zend\Form\Element\Select;
use Zend\Form\Element\Radio;
use Zend\Form\Element\MultiCheckbox;
use Zend\Form\Element\Number;
use Zend\Form\Element\Textarea;
use Zend\Form\Element\Text;

class CustomFormElement {

    const SELECT = "SELECT";
    const RADIO = "RADIO";
    const CHECKBOX = "CHECKBOX";
    const TEXT = "TEXT";
    const NUMBER = "NUMBER";
    const TEXTAREA = "TEXTAREA";
    const PERCENTAGE = "PERCENTAGE";

    public function __construct() {
        
    }

    public static function formElement() {
        return function($formElementName, $formElementType, $value = null, $selectedValue = null, $extraAttr = null) {
            switch ($formElementType) {
                case self::SELECT:
                    $formElement = new Select();
                    $formElement->setName($formElementName);
                    $formElement->setValueOptions($value);
                    $formElement->setValue($selectedValue);
                    $formElement->setAttributes(["id" => $formElementName . "Id", "class" => "form-control", $extraAttr => $extraAttr]);
                    break;
                case self::RADIO:
                    $formElement = new Radio();
                    $formElement->setName($formElementName);
                    $formElement->setValueOptions($value);
                    $formElement->setValue($selectedValue);
                    $formElement->setAttributes(["id" => $formElementName . "Id", "required" => "required", $extraAttr => $extraAttr]);
                    $formElement->setLabelAttributes(["class" => "checkbox-inline"]);
                    break;
                case self::CHECKBOX:
                    $formElement = new MultiCheckbox();
                    $formElement->setName($formElementName);
                    $formElement->setValueOptions($value);
                    $formElement->setValue(json_decode($selectedValue));
                    $formElement->setAttributes(["id" => $formElementName . "Id", "required" => "required", $extraAttr => $extraAttr]);
                    $formElement->setLabelAttributes(["class" => "checkbox-inline"]);
                    break;
                case self::TEXT:
                    $formElement = new Text();
                    $formElement->setName($formElementName);
                    $formElement->setValue($selectedValue);
                    $formElement->setAttributes(["id" => $formElementName . "Id", "class" => "form-control", "required" => "required", $extraAttr => $extraAttr]);
                    break;
                case self::TEXTAREA:
                    $formElement = new Textarea();
                    $formElement->setName($formElementName);
                    $formElement->setValue($selectedValue);
                    $formElement->setAttributes(["id" => $formElementName . "Id", "class" => "form-control", "style" => "height:150px!important;  resize: none;", "required" => "required", $extraAttr => $extraAttr]);
                    break;
                case self::NUMBER:
                    $formElement = new Number();
                    $formElement->setName($formElementName);
                    $formElement->setValue($selectedValue);
                    $formElement->setAttributes(["id" => $formElementName . "Id", "class" => "form-control", "min" => 0, "required" => "required", $extraAttr => $extraAttr]);
                    break;
                case self::PERCENTAGE:
                    $formElement = new Number();
                    $formElement->setName($formElementName);
                    $formElement->setValue($selectedValue);
                    $formElement->setAttributes(["id" => $formElementName . "Id", "class" => "form-control", "min" => 0, "max" => 100, "required" => "required", $extraAttr => $extraAttr]);
                    break;
                default:
                    $formElement = new Text();
                    $formElement->setName($formElementName);
                    $formElement->setValue($selectedValue);
                    $formElement->setAttributes(["id" => $formElementName . "Id", "class" => "form-control", "required" => "required", $extraAttr => $extraAttr]);
                    break;
            }
            return $formElement;
        };
    }
}
