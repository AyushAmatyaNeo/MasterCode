<?php

namespace Overtime\Controller;

use Application\Controller\HrisController;
use Application\Helper\EntityHelper;
use Exception;
use Overtime\Repository\OvertimeReportRepo;
use Zend\Authentication\Storage\StorageInterface;
use Zend\Db\Adapter\AdapterInterface;
use Zend\View\Model\JsonModel;

class OvertimeReport extends HrisController {

    public function __construct(AdapterInterface $adapter, StorageInterface $storage, OvertimeReportRepo $repository) {
        parent::__construct($adapter, $storage);
        $this->repository = $repository;
    }

    public function indexAction() {
        $request = $this->getRequest();
        if ($request->isPost()) {
            try {
                $postedData = $request->getPost();
                $list = $this->repository->fetchColumns($postedData['monthId']);
                return new JsonModel(['success' => true, 'data' => $list, 'error' => '']);
            } catch (Exception $e) {
                return new JsonModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
            }
        }
        $data['getSearchDataLink'] = $this->url()->fromRoute('overtime-report', ['action' => 'getSearchData']);
        $data['getFiscalYearMonthLink'] = $this->url()->fromRoute('overtime-report', ['action' => 'getFiscalYearMonth']);
        $data['gridReadLink'] = $this->url()->fromRoute('overtime-report', ['action' => 'pvmRead']);
        $data['gridUpdateLink'] = $this->url()->fromRoute('overtime-report', ['action' => 'pvmUpdate']);

        return [
            'data' => json_encode($data),
            'acl' => $this->acl,
            'employeeDetail' => $this->storageData['employee_detail']
        ];
    }

    public function pvmReadAction() {
        $request = $this->getRequest();
        $postData = $request->getPost();
        $calenderType = 'N';
        if (isset($this->preference['calendarView'])) {
            $calenderType = $this->preference['calendarView'];
        }
        $data = $this->repository->fetchMonthlyForGrid($postData, $calenderType);

        return new JsonModel($data);
    }

    public function pvmUpdateAction() {
        $request = $this->getRequest();
        $postData = $request->getPost();
        $monthId = $postData->monthId;
        $data = json_decode($postData->models);

        $dataToUpdate = [];
        $addDedData = [];
        foreach ($data as $value) {
            $item = (array) $value;
            $common = ['EMPLOYEE_ID' => $item['EMPLOYEE_ID'], 'MONTH_ID' => $monthId,'ADDITION' => $item['ADDITION'], 'DEDUCTION' => $item['DEDUCTION']];
            array_push($addDedData,$common);

            foreach ($item as $k => $v) {
                if (!in_array($k, ['EMPLOYEE_CODE', 'EMPLOYEE_ID', 'FULL_NAME', 'MONTH_ID','ADDITION','DEDUCTION'])) {
//                    if ($v != null) {
                    $monthDay = str_replace('D_', '', $k);			
                    $dataUnit = array_merge($common, []);
                    $dataUnit['MONTH_DAY'] = $monthDay;
                    $dataUnit['OVERTIME_HOUR'] = $v;
                    array_push($dataToUpdate, $dataUnit);
//                    }
                }
            }
			
        }
        $this->repository->bulkEdit($dataToUpdate,$addDedData);
        return new JsonModel($data);
    }

    public function overtimeReportAction() {

        $request = $this->getRequest();
        if ($request->isPost()) {
            try {
                $postedData = $request->getPost();
                $from_date = date("d-M-y", strtotime($postedData['fromDate']));
                $to_date = date("d-M-y", strtotime($postedData['toDate']));

                $begin = new \DateTime($from_date);
                $end = new \DateTime($to_date);
                $end->modify('+1 day');
                
                $interval = \DateInterval::createFromDateString('1 day');
                $period = new \DatePeriod($begin, $interval, $end);

                $dates = array();
                
                foreach ($period as $dt) {
                    array_push($dates, $dt->format("d-M-y"));
                }
                $data = $this->repository->fetchOvertimeReport($postedData, $dates);
                
                return new JsonModel(['success' => true, 'data' => $data, 'dates' => $dates, 'error' => '']);
            } catch (Exception $e) {
                return new JsonModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
            }
        }

        return [
            'data' => json_encode($data),
            'searchValues' => EntityHelper::getSearchData($this->adapter),
            'acl' => $this->acl,
            'employeeDetail' => $this->storageData['employee_detail']
        ];
    }

}
