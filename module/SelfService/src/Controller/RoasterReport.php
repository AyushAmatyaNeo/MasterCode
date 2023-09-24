<?php

namespace SelfService\Controller;

use Application\Controller\HrisController;
use Application\Helper\Helper;
use Exception;
use SelfService\Repository\RoasterReportRepository;
use Report\Repository\ReportRepository;
use Zend\Authentication\Storage\StorageInterface;
use Zend\Db\Adapter\AdapterInterface;
use Zend\View\Model\JsonModel;
use Zend\View\Model\ViewModel;
use Application\Helper\EntityHelper;

class RoasterReport extends HrisController
{

    public function __construct(AdapterInterface $adapter, StorageInterface $storage)
    {
        parent::__construct($adapter, $storage);
        $this->initializeRepository(ReportRepository::class);
    }

    public function indexAction()
    {
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
                $postedData['employeeId'] = $this->employeeId;
                $data = $this->repository->fetchRosterReport($postedData, $dates);
                return new JsonModel(['success' => true, 'data' => $data, 'dates' => $dates, 'error' => '']);
            } catch (Exception $e) {
                return new JsonModel(['success' => false, 'data' => [], 'dates' => $dates, 'error' => $e->getMessage()]);
            }
        }

        return [
            'searchValues' => EntityHelper::getSearchData($this->adapter),
            'acl' => $this->acl,
            'employeeDetail' => $this->storageData['employee_detail']
        ];
    }
}
