<?php

namespace Payroll\Service;

use Application\Helper\Helper;
use Payroll\Model\Rules;
use Payroll\Repository\FlatValueRepository;
use Payroll\Repository\MonthlyValueRepository;
use Payroll\Repository\PositionFlatValueRepo;
use Payroll\Repository\PositionMonthlyValueRepo;
use Payroll\Repository\RulesRepository;

class PayrollGenerator
{

    private $adapter;
    private $positionFlatValueDetRepo;
    private $positionMonthlyValueRepo;
    private $ruleRepo;
    private $sspvmRepo;
    private $employeeId;
    private $monthId = 0;
    private $sheetNo;
    private $formattedFlatValueList;
    private $formattedMonthlyvalueList;
    private $formattedVariableList;
    private $formattedSystemRuleList;
    private $formattedReferencingRuleList;
    private $ruleDetailList = [];

    const VARIABLES = [
        "BASIC_SALARY",
        "MONTH_DAYS",
        "PRESENT_DAYS",
        "ABSENT_DAYS",
        "PAID_LEAVES",
        "UNPAID_LEAVES",
        "DAY_OFFS",
        "HOLIDAYS",
        "DAYS_FROM_JOIN_DATE",
        "DAYS_FROM_PERMANENT_DATE",
        "IS_MALE",
        "IS_FEMALE",
        "IS_MARRIED",
        "IS_PERMANENT",
        "IS_PROBATION",
        "IS_CONTRACT",
        "IS_TEMPORARY",
        "TOTAL_DAYS_TO_PAY",
        "BRANCH_ALLOWANCE",
        "MONTH",
        "BRANCH_ID",
        "CAFE_MEAL_PREVIOUS",
        "CAFE_MEAL_CURRENT",
        "PAYROLL_EMPLOYEE_TYPE",
        "EMPLOYEE_SERVICE_ID",
        "SERVICE_TYPE_PF",
        "IS_DISABLE_PERSON",
        "PREVIOUS_MONTH_DAYS",
        "BRANCH_ALLOWANCE_REBATE",
        "IS_REMOTE_BRANCH",
        "AGE",
        "SALARY_DAYS",
        "OT_WORKED_HOURS",
        "HB_LOAN_AMT",
        "HB_INTEREST_AMT",
        "WL_LOAN_AMT",
        "WL_INTEREST_AMT",
        "SHL_LOAN_AMT",
        "SHL_INTEREST_AMT",
        "EXCHANGE_RATE",
        "BONUS",
        "ADVANCE_TAX",
        "HOUSE_INSURANCE",
        "LIFE_INSURANCE",
        "MEDICAL_ALLOW",
        "DONATION_AMT"


    ];
    const SYSTEM_RULE = [
        "TOTAL_ANNUAL_AMOUNT",
        "TOTAL_AMOUNT",
        "SELF_PREV_TOTAL",
        "MULTIPLICATION_FACTOR",
        "LOAN_AMT",
        "LOAN_INT",
        "PREVIOUS_TOTAL",
        "PREVIOUS_MONTH_AMOUNT",
        "EMPLOYEE_GRADE",
        "TOTAL_ADD",
        "TOTAL_DED",
        "GRATUITY_PER"
    ];

    public function __construct($adapter)
    {
        $this->adapter = $adapter;
        $this->positionFlatValueDetRepo = new PositionFlatValueRepo($adapter);
        $this->positionMonthlyValueRepo = new PositionMonthlyValueRepo($adapter);
        $this->ruleRepo = new RulesRepository($adapter);
        $this->sspvmRepo = new \Payroll\Repository\SSPayValueModifiedRepo($adapter);
        $monthlyValueList = $this->getMonthlyValues();
        $flatValuesList = $this->getFlatValues();

        $this->formattedMonthlyvalueList = [];
        foreach ($monthlyValueList as $monthlyValue) {
            $this->formattedMonthlyvalueList["[M:" . $this->sanitizeString($monthlyValue['MTH_EDESC']) . "]"] = $monthlyValue['MTH_ID'];
        }
        $this->formattedFlatValueList = [];
        foreach ($flatValuesList as $flatValue) {
            $this->formattedFlatValueList["[F:" . $this->sanitizeString($flatValue['FLAT_EDESC']) . "]"] = $flatValue['FLAT_ID'];
            //            $this->formattedFlatValueList[$flatValue['FLAT_ID']] = "[F:{$this->sanitizeString($flatValue['FLAT_EDESC'])}]";
        }

        $this->formattedVariableList = [];
        foreach (self::VARIABLES as $variable) {
            $this->formattedVariableList["[V:" . $variable . "]"] = $variable;
            //            $this->formattedVariableList[$variable] = "[V:{$variable}]";
        }

        $this->formattedSystemRuleList = [];
        foreach (self::SYSTEM_RULE as $systemRule) {
            $this->formattedSystemRuleList[$systemRule] = "[S:{$systemRule}]";
        }
    }

    public function generate($employeeId, $monthId, $sheetNo)
    {
        $this->employeeId = $employeeId;
        $this->monthId = $monthId;
        $this->sheetNo = $sheetNo;
        $payList = $this->ruleRepo->fetchAllTypeWise($sheetNo);
        //        $systemRuleProcessor = new SystemRuleProcessor($this->adapter, $employeeId, null, $monthId, null);

        $startTime = microtime(true);
        $file = Helper::UPLOAD_DIR . "/PAYROLL_LOG.txt";
        file_put_contents($file, "Generate Start for employeeId=" . $employeeId . " monthId=" . $monthId . " sheetNo=" . $sheetNo . " time-" . (round(microtime(true) - $startTime, 3) * 1000));

        $ruleValueMap = [];
        $ruleTaxValueMap = [];
        $counter = 0;

        $generatedValues = [];

        // for previous sum data start
        $previousSumValData = $this->ruleRepo->fetchPreviousSumVal($employeeId, $monthId);
        $previousSumValList = [];
        foreach ($previousSumValData as $prevSumDtl) {
            $previousSumValList[$prevSumDtl['PAY_EDESC']] = $prevSumDtl['VALUE'];
        }
        // for previous sum data end

        // for previous MAX data start
        $maxPayValData = $this->ruleRepo->fetchMaxPayVal($employeeId, $monthId);
        $maxPayValList = [];
        foreach ($maxPayValData as $maxPayDtl) {
            $maxPayValList[$maxPayDtl['PAY_EDESC']] = $maxPayDtl['VALUE'];
        }
        // for previous MAX data end


        foreach ($payList as $ruleDetail) {
            $startTime = microtime(true);
            $ruleId = $ruleDetail[Rules::PAY_ID];
            $formula = $ruleDetail[Rules::FORMULA];
            $ruleCode = $ruleDetail[Rules::PAY_CODE];
            $ruleEdesc = $ruleDetail[Rules::PAY_EDESC];
            $ruleFormula = $formula;

            // to override formula start
            $salaryTypeId = $ruleDetail['SALARY_TYPE_ID'];
            $salaryTypeFlag = $ruleDetail['TYPE_FLAG'];
            $salaryTypeFormula = $ruleDetail['TYPE_FORMULA'];

            if ($salaryTypeId != 1  && ($salaryTypeFlag !== null or $salaryTypeFlag == 'Y')) {
                $formula = $salaryTypeFormula;
            } else if ($salaryTypeId != 1 && ($salaryTypeFlag !== null or $salaryTypeFlag != 'Y')) {
                $formula = 0;
            }
            // to override formula end
            $q = ['MONTH_ID' => $this->monthId, 'PAY_ID' => $ruleId, 'EMPLOYEE_ID' => $this->employeeId, 'SALARY_TYPE_ID' => $salaryTypeId];
            $ruleValue = $this->sspvmRepo->fetch($q);

            if ($ruleValue == null) {
                $refRules = $this->ruleRepo->fetchReferencingRules($ruleId);

                $monthlyValCheck = strpos($formula, "M:");

                if ($monthlyValCheck) {
                    while ($monthlyValCheck) {
                        $startPos = strpos($formula, '[M:');
                        $endPos = strpos($formula, ' ', $startPos);
                        if (!$endPos) {
                            $endPos = strlen($formula);
                        }
                        $length = abs($endPos - $startPos);
                        $monthlyValue = substr($formula, $startPos, $length);
                        $monthlyKey = $this->formattedMonthlyvalueList[$monthlyValue];
                        if ($monthlyKey) {
                            $formula = $this->convertMonthlyToValue($formula, $monthlyKey, $monthlyValue);
                            $monthlyValCheck = strpos($formula, "M:");
                        } else {
                            $monthlyValCheck = false;
                        }
                    }


                    //                foreach ($this->formattedMonthlyvalueList as $monthlyKey => $monthlyValue) {
                    //                    $formula = $this->convertMonthlyToValue($formula, $monthlyKey, $monthlyValue);
                    //                }
                }

                $flatValCheck = strpos($formula, "F:");
                if ($flatValCheck) {

                    while ($flatValCheck) {
                        $startPos = strpos($formula, '[F:');
                        $endPos = strpos($formula, ' ', $startPos);
                        if (!$endPos) {
                            $endPos = strpos($formula, ']', $startPos) + 1;
                        }
                        $length = abs($endPos - $startPos);
                        $flatValue = substr($formula, $startPos, $length);
                        $flatKey = $this->formattedFlatValueList[$flatValue];
                        if ($flatKey) {
                            $formula = $this->convertFlatToValue($formula, $flatKey, $flatValue);
                            $flatValCheck = strpos($formula, "F:");
                        } else {
                            $flatValCheck = false;
                        }
                    }


                    //                foreach ($this->formattedFlatValueList as $flatKey => $flatValue) {
                    //                    $formula = $this->convertFlatToValue($formula, $flatKey, $flatValue);
                    //                }
                }

                $variableValCheck = strpos($formula, "V:");
                if ($variableValCheck) {

                    while ($variableValCheck) {
                        $startPos = strpos($formula, '[V:');
                        $endPos = strpos($formula, ' ', $startPos);
                        if (!$endPos) {
                            $endPos = strpos($formula, ']', $startPos) + 1;
                        }
                        $length = abs($endPos - $startPos);
                        $variable = substr($formula, $startPos, $length);
                        $key = $this->formattedVariableList[$variable];
                        if ($key) {
                            $formula = $this->convertVariableToValue($formula, $key, $variable);
                            $variableValCheck = strpos($formula, "V:");
                        } else {
                            $variableValCheck = false;
                        }
                    }


                    //                foreach ($this->formattedVariableList as $key => $variable) {
                    //                    $formula = $this->convertVariableToValue($formula, $key, $variable);
                    //                }
                }

                // for prevous sum val calulation start
                $previousSumValCheck = strpos($formula, "PS:");
                if ($previousSumValCheck) {
                    while ($previousSumValCheck) {
                        $startPos = strpos($formula, '[PS:');
                        $endPos = strpos($formula, ' ', $startPos);
                        if (!$endPos) {
                            $endPos = strpos($formula, ']', $startPos) + 1;
                        }
                        $length = abs($endPos - $startPos);
                        $variable = substr($formula, $startPos, $length);
                        $key = $previousSumValList[$variable];
                        if ($key || $key >= 0) {
                            $formula = str_replace($variable, is_string($key) ? "'{$key}'" : $key, $formula);
                            $previousSumValCheck = strpos($formula, "PS:");
                        } else {
                            $previousSumValCheck = false;
                        }
                    }
                }
                // for prevous sum val calulation end

                // for max pay val calulation start
                $maxPayValCheck = strpos($formula, "MS:");
                if ($maxPayValCheck) {
                    while ($maxPayValCheck) {
                        $startPos = strpos($formula, '[MS:');
                        $endPos = strpos($formula, ' ', $startPos);
                        if (!$endPos) {
                            $endPos = strpos($formula, ']', $startPos) + 1;
                        }
                        $length = abs($endPos - $startPos);
                        $variable = substr($formula, $startPos, $length);
                        $key = $maxPayValList[$variable];
                        if ($key || $key >= 0) {
                            $formula = str_replace($variable, is_string($key) ? "'{$key}'" : $key, $formula);
                            $maxPayValCheck = strpos($formula, "MS:");
                        } else {
                            $maxPayValCheck = false;
                        }
                    }
                }
                // for max pay val calulation end


                $systemValCheck = strpos($formula, "S:");
                if ($systemValCheck) {
                    foreach ($this->formattedSystemRuleList as $key => $systemRule) {
                        $formula = $this->convertSystemRuleToValue($formula, $key, $systemRule, $ruleId);
                    }
                }
                //added by prabin to remoeve extra params PARS and PARAe start
                $formula = $this->deleteAllBetweenString("PARS", "PARE", $formula);
                //added by prabin to remoeve extra params PARS and PARAe end
                //                    print_r($formula);

                $referenceValCheck = strpos($formula, "R:");
                $processedformula = $formula;
                if ($referenceValCheck) {
                    while ($referenceValCheck) {
                        $startPos = strpos($processedformula, '[R:');
                        $endPos = strpos($processedformula, ' ', $startPos);
                        if (!$endPos) {
                            $endPos = strpos($processedformula, ']', $startPos) + 1;
                        }
                        $length = abs($endPos - $startPos);
                        $variable = substr($processedformula, $startPos, $length);
                        $key = $generatedValues[$variable];
                        if ($key || $key >= 0) {
                            $processedformula = str_replace($variable, is_string($key) ? "'{$key}'" : $key, $processedformula);
                            $referenceValCheck = strpos($processedformula, "R:");
                        } else {
                            $referenceValCheck = false;
                        }
                    }


                    //                $processedformula = $this->convertReferencingRuleToValue($formula, $refRules);
                }


                //print_r($processedformula);

                $current = file_get_contents($file);
                file_put_contents($file, $current . "\r\nstartRuleId(" . $ruleCode . ")=" . $ruleId . "-" . $ruleEdesc . "\n" . str_replace('_', ' ', $ruleFormula) . "\n" . $processedformula . " time-" . (round(microtime(true) - $startTime, 3) * 1000) . "\n");
                $ruleValue = @(eval("return {$processedformula} ;"));
                if ($ruleValue == INF) {
                    $ruleValue = 0;
                }
                if (is_nan($ruleValue)) {
                    $ruleValue = 0;
                }
                $finalFileAppend = file_get_contents($file);
                file_put_contents($file, $finalFileAppend . "total " . $ruleValue . " time-" . (round(microtime(true) - $startTime, 3) * 1000) . "\n");
            }
            $generatedValues["[R:" . $ruleDetail['PAY_EDESC_WITH_UNDERSCORE'] . "]"] = $ruleValue;
            $rule = ["ruleValue" => $ruleValue, "rule" => $ruleDetail];
            array_push($this->ruleDetailList, $rule);
            $ruleValueMap[$ruleId] = $ruleValue;
            $ruleTaxValueMap[$ruleId] = $ruleValue;
            //            $ruleTaxValueMap[$ruleId] = $systemRuleProcessor->getTaxValue($rule);
            $counter++;
        }

        return ["ruleValueKV" => $ruleValueMap, "ruleTaxValueKV" => $ruleTaxValueMap];
    }

    private function getMonthlyValues()
    {
        $monthlyValueRepo = new MonthlyValueRepository($this->adapter);
        $monthlyValueList = $monthlyValueRepo->fetchAll();
        return Helper::extractDbData($monthlyValueList);
    }

    private function getFlatValues()
    {
        $flatValueRepo = new FlatValueRepository($this->adapter);
        $flatValueList = $flatValueRepo->fetchAll();
        return Helper::extractDbData($flatValueList);
    }

    private function getReferencingRules($payId = null)
    {
        $referencingruleList = $this->repository->fetchReferencingRules($payId);
        return Helper::extractDbData($referencingruleList);
    }

    private function sanitizeString($input)
    {
        $processed = str_replace(" ", "_", $input);
        return strtoupper($processed);
    }

    private function convertFlatToValue($rule, $key, $constant)
    {
        if (strpos($rule, $constant) !== false) {
            $flatVal = $this->positionFlatValueDetRepo->fetchValue(['EMPLOYEE_ID' => $this->employeeId, 'MONTH_ID' => $this->monthId, 'FLAT_ID' => $key]);
            return str_replace($constant, $flatVal, $rule);
        } else {
            return $rule;
        }
    }

    private function convertMonthlyToValue($rule, $key, $constant)
    {
        if (strpos($rule, $constant) !== false) {
            $monthlyVal = $this->positionMonthlyValueRepo->fetchValue(['EMPLOYEE_ID' => $this->employeeId, 'MONTH_ID' => $this->monthId, 'MTH_ID' => $key]);
            return str_replace($constant, $monthlyVal, $rule);
        } else {
            return $rule;
        }
    }

    private function convertVariableToValue($rule, $key, $variable)
    {
        if (strpos($rule, $variable) !== false) {
            $variableProcessor = new VariableProcessor($this->adapter, $this->employeeId, $this->monthId, $this->sheetNo);
            $processedVariable = $variableProcessor->processVariable($key);
            return str_replace($variable, is_string($processedVariable) ? "{$processedVariable}" : $processedVariable, $rule);
        } else {
            return $rule;
        }
    }

    private function convertSystemRuleToValue($rule, $key, $variable, $ruleId)
    {
        if (strpos($rule, $variable) !== false) {
            $systemRuleProcessor = new SystemRuleProcessor($this->adapter, $this->employeeId, $this->ruleDetailList, $this->monthId, $ruleId);
            $processedSystemRule = $systemRuleProcessor->processSystemRule($key);
            return str_replace($variable, is_string($processedSystemRule) ? "{$processedSystemRule}" : $processedSystemRule, $rule);
        } else {
            return $rule;
        }
    }

    private function convertReferencingRuleToValue($rule, $refRules)
    {
        foreach ($refRules as $refRule) {
            $payEdesc = "[R:{$this->sanitizeString($refRule['PAY_EDESC'])}]";
            $payId = $refRule['PAY_ID'];
            if (strpos($rule, $payEdesc) !== false) {
                $value = $this->getReferencingRuleValue($payId);
                $rule = str_replace($payEdesc, is_string($value) ? "'{$value}'" : $value, $rule);
            }
        }
        return isset($rule) ? $rule : 0;
    }

    private function getReferencingRuleValue($payId)
    {
        $payValue = 0;
        foreach ($this->ruleDetailList as $ruleDetail) {
            if ($ruleDetail['rule']['PAY_ID'] == $payId) {
                $payValue = $ruleDetail['ruleValue'];
            }
        }
        return $payValue;
    }

    // added by prabin start
    private function deleteAllBetweenString($beginning, $end, $string)
    {
        $beginningPos = strpos($string, $beginning);
        $endPos = strpos($string, $end);
        if ($beginningPos === false || $endPos === false) {
            return $string;
        }
        $textToDelete = substr($string, $beginningPos, ($endPos + strlen($end)) - $beginningPos);
        return $this->deleteAllBetweenString($beginning, $end, str_replace($textToDelete, '', $string)); // recursion to ensure all occurrences are replaced
    }
    // added by prabin end

    function get_string_between($string, $start, $end)
    {
        $string = ' ' . $string;
        $ini = strpos($string, $start);
        if ($ini == 0)
            return '';
        $ini += strlen($start);
        $len = strpos($string, $end, $ini) - $ini;
        return substr($string, $ini, $len);
    }
}
