<?php

namespace System\Repository;

use Application\Repository\HrisRepository;
use Zend\Db\Adapter\AdapterInterface;

class SynergyRepository extends HrisRepository {

    private $linkedWithSynergy = false;

    const FORM_SETUP = "FORM_SETUP";
    const FA_CHART_OF_ACCOUNTS_SETUP = "FA_CHART_OF_ACCOUNTS_SETUP";

    public function __construct(AdapterInterface $adapter, $tableName = null) {
        parent::__construct($adapter, $tableName);
        $this->linkedWithSynergy = $this->checkIfTableExists(self::FORM_SETUP);
    }

    public function getFormList($companyCode = null) {
        if (!$this->linkedWithSynergy) {
            return [];
        }
        $condition = $companyCode != null ? " AND COMPANY_CODE = '{$companyCode}'" : "";
        $sql = "SELECT * FROM FORM_SETUP WHERE GROUP_SKU_FLAG = 'I'" . $condition;
        return $this->rawQuery($sql);
    }

    public function getAccountList($companyCode = null) {
        if (!$this->linkedWithSynergy) {
            return [];
        }
        $condition = $companyCode != null ? " AND COMPANY_CODE = '{$companyCode}'" : "";
        $sql = "SELECT * FROM FA_CHART_OF_ACCOUNTS_SETUP WHERE   DELETED_FLAG='N' " . $condition;
        return $this->rawQuery($sql);
    }

}
