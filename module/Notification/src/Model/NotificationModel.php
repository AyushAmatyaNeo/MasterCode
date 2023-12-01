<?php

namespace Notification\Model;

use Zend\Mvc\Controller\Plugin\Url;

class NotificationModel
{

    public $fromId;
    public $toId;
    public $fromEmail;
    public $toEmail;
    public $fromName;
    public $toName;
    public $fromGender;
    public $toGender;
    public $fromMaritualStatus;
    public $toMaritualStatus;
    public $fromHonorific;
    public $toHonorific;
    public $route;
    public $logo;
    public $paySlipDetails;

    public function getObjectAttrs(): array
    {
        return array_keys(get_object_vars($this));
    }

    public function processString(string $input, Url $url)
    {
        $variables = array_keys(get_object_vars($this));
        $output = $input;
        foreach ($variables as $variable) {
            $output = $this->convertVariableToValue($output, $variable, $url);
        }
        return $output;
    }

    private function convertVariableToValue($message, $variable, Url $url)
    {
        if (strpos($message, $this->wrapWithLargeBracket($variable)) !== false) {
            $processedVariable = '';
            //            if ($variable == 'route') {
            //                $routeJson = (array) json_decode($this->{$variable});
            //                if (isset($routeJson['route'])) {
            //                    $routeName = $routeJson['route'];
            //                    unset($routeJson['route']);
            //                    $processedVariable = $url->fromRoute($routeName, $routeJson);
            //                }
            //            } else {
            //                $processedVariable = $this->{$variable};
            //            }
            switch ($variable) {
                case 'route':
                    $routeJson = (array) json_decode($this->{$variable});
                    if (isset($routeJson['route'])) {
                        $routeName = $routeJson['route'];
                        unset($routeJson['route']);
                        $request = $url->getController()->getRequest();
                        $processedVariable = 'http://' . $request->getServer('SERVER_ADDR') . ':' . $request->getServer('SERVER_PORT') . $url->fromRoute($routeName, $routeJson);
                    } else {
                        $processedVariable = "";
                    }
                    break;
                case 'fromGender':
                    $genderId = $this->{$variable};
                    $processedVariable = $genderId == 1 ? "Male" : ($genderId == 2 ? "Female" : "Other");
                    break;
                case 'toGender':
                    $genderId = $this->{$variable};
                    $processedVariable = $genderId == 1 ? "Male" : ($genderId == 2 ? "Female" : "Other");
                    break;
                case 'fromMaritualStatus':
                    $processedVariable = $this->{$variable} == 'M' ? "Married" : "Unmarried";
                    break;
                case 'toMaritualStatus':
                    $processedVariable = $this->{$variable} == 'M' ? "Married" : "Unmarried";
                    break;
                default:
                    $processedVariable = $this->{$variable};
                    break;
            }

            //            if (is_string($processedVariable)) {
            //                return str_replace($this->wrapWithLargeBracket($variable), "'" . $processedVariable . "'", $message);
            //            } else {
            return str_replace($this->wrapWithLargeBracket($variable), $processedVariable, $message);
            //            }
        } else {
            return $message;
        }
    }

    private function wrapWithLargeBracket($input)
    {
        return "[" . $input . "]";
    }

    public function setHonorific()
    {
        if ($this->fromGender == 1) {
            $this->fromHonorific = "Mr";
        } else if ($this->fromGender == 2) {
            if ($this->fromMaritualStatus == 'M') {
                $this->fromHonorific = "Mrs";
            } else {
                $this->fromHonorific = "Miss";
            }
        } else {
            $this->fromHonorific = "Mx";
        }
        if ($this->toGender == 1) {
            $this->toHonorific = "Mr";
        } else if ($this->toGender == 2) {
            if ($this->toMaritualStatus == 'M') {
                $this->toHonorific = "Mrs";
            } else {
                $this->toHonorific = "Miss";
            }
        } else {
            $this->fromHonorific = "Mx";
        }
    }
}
