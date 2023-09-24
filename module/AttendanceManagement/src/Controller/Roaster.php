<?php

namespace AttendanceManagement\Controller;

use Application\Controller\HrisController;
use Application\Helper\EntityHelper;
use AttendanceManagement\Model\ShiftSetup;
use AttendanceManagement\Repository\RoasterRepo;
use Exception;
use Zend\Authentication\Storage\StorageInterface;
use Zend\Db\Adapter\AdapterInterface;
use Zend\View\Model\JsonModel;
use AttendanceManagement\Model\RoasterModel;
use Notification\Controller\HeadNotification;
use Notification\Model\NotificationEvents;
use Notification\Model\RoasterAssignModel;

class Roaster extends HrisController
{

    public function __construct(AdapterInterface $adapter, StorageInterface $storage)
    {
        parent::__construct($adapter, $storage);
        $this->repository = new RoasterRepo($this->adapter);
    }

    public function indexAction()
    {
        return $this->stickFlashMessagesTo([
            'searchValues' => EntityHelper::getSearchData($this->adapter),
            'shifts' => EntityHelper::getTableList($this->adapter, ShiftSetup::TABLE_NAME, [ShiftSetup::SHIFT_ID, ShiftSetup::SHIFT_ENAME], [ShiftSetup::STATUS => EntityHelper::STATUS_ENABLED]),
            'acl' => $this->acl,
            'employeeDetail' => $this->storageData['employee_detail']
        ]);
    }

    public function getRoasterListAction()
    {
        try {
            $request = $this->getRequest();
            $data = $request->getPost();

            $result = $this->repository->getRosterDetailList($data['q']);

            return new JsonModel(['success' => true, 'data' => $result, 'error' => '']);
        } catch (Exception $e) {
            return new JsonModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
        }
    }

    public function assignRoasterAction()
    {
        try {
            $request = $this->getRequest();
            $data = $request->getPost();
            foreach ($data['data'] as $item) {
                $this->repository->merge($item['EMPLOYEE_ID'], $item['FOR_DATE'], $item['SHIFT_ID']);
            }
            $employeeData = [];

            foreach ($data['data'] as $result) {
                $employeeId = $result['EMPLOYEE_ID'];
                $name = $this->repository->getFullName($employeeId);

                // Check if the employeeId is already in $employeeData
                if (!isset($employeeData[$employeeId])) {
                    // If not, create a new record for this employee
                    $employeeData[$employeeId] = [
                        'employeeId' => $result['EMPLOYEE_ID'],
                        'fullName' => $name['FULL_NAME'],
                        'fromDate' => $result['FOR_DATE'],
                        'toDate' => $result['FOR_DATE'], // Initialize toDate with fromDate
                    ];
                } else {
                    // If the employee exists, update the toDate
                    $employeeData[$employeeId]['toDate'] = $result['FOR_DATE'];
                }
            }

            // After gathering data for all employees, send email notifications
            foreach ($employeeData as $employeeId => $record) {
                $model = new RoasterAssignModel();
                $model->employeeId = $record['employeeId'];
                $model->fullName = $record['fullName'];
                $model->fromDate = $record['fromDate'];
                $model->toDate = $record['toDate'];
                $model->fromId = $this->employeeId;
                try {
                    HeadNotification::pushNotification(NotificationEvents::RAOSTER_ASSIGN, $model, $this->adapter, $this);
                } catch (Exception $e) {
                    $this->flashmessenger()->addMessage($e->getMessage());
                }
            }
            return new JsonModel(['success' => true, 'data' => null, 'error' => '']);
        } catch (Exception $e) {
            return new JsonModel(['success' => false, 'data' => null, 'error' => $e->getMessage()]);
        }
    }

    public function getShiftDetailsAction()
    {
        try {
            $request = $this->getRequest();
            $data = $request->getPost();
            $result = $this->repository->getshiftDetail($data);
            return new JsonModel(['success' => true, 'data' => $result, 'error' => '']);
        } catch (Exception $e) {
            return new JsonModel(['success' => false, 'data' => null, 'error' => $e->getMessage()]);
        }
    }

    public function weeklyRosterAction()
    {
        $request = $this->getRequest();
        if ($request->isPost()) {
            try {
                $request = $this->getRequest();
                $data = $request->getPost();
                $result = $this->repository->getWeeklyRosterDetailList($data['q']);
                return new JsonModel($result);
                //                return new JsonModel(['success' => true, 'data' => $result, 'error' => '']);
            } catch (Exception $e) {
                return new JsonModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
            }
        }

        $data['pvmReadLink'] = $this->url()->fromRoute('roaster', ['action' => 'weeklyRoster']);
        $data['pvmUpdateLink'] = $this->url()->fromRoute('roaster', ['action' => 'assignWeeklyRoster']);

        $shfitList = EntityHelper::getTableList($this->adapter, ShiftSetup::TABLE_NAME, [ShiftSetup::SHIFT_ID, ShiftSetup::SHIFT_ENAME], [ShiftSetup::STATUS => EntityHelper::STATUS_ENABLED]);

        array_unshift($shfitList, array('SHIFT_ID' => -1, 'SHIFT_ENAME' => 'select shift'));

        return $this->stickFlashMessagesTo([
            'searchValues' => EntityHelper::getSearchData($this->adapter),
            'shifts' => $shfitList,
            'acl' => $this->acl,
            'employeeDetail' => $this->storageData['employee_detail'],
            'data' => json_encode($data)
        ]);
    }

    public function getWeeklyShiftDetailsAction()
    {
        try {
            $request = $this->getRequest();
            $data = $request->getPost();
            $result = $this->repository->getWeeklyShiftDetail($data);
            return new JsonModel(['success' => true, 'data' => $result, 'error' => '']);
        } catch (Exception $e) {
            return new JsonModel(['success' => false, 'data' => null, 'error' => $e->getMessage()]);
        }
    }

    public function assignWeeklyRosterAction()
    {

        $request = $this->getRequest();
        if ($request->isPost()) {
            try {
                $data = $request->getPost();
                $modelData = json_decode($data['models']);
                $arryData = $modelData[0];

                //                print_r(json_decode($data->models));
                //                die();

                //                print_r($arryData);
                //                echo 'sdfsdf';
                //                die();


                //                $sun=$arryData->SUN;
                //                $mon=$arryData->MON;
                //                $tue=$arryData->TUE;
                //                $wed=$arryData->WED;
                //                $thu=$arryData->THU;
                //                $fri=$arryData->FRI;
                //                $sat=$arryData->SAT;
                $selectedEmp = $arryData->EMPLOYEE_ID;
                $sun = ($arryData->SUNARR->SHIFT_ID == $arryData->SUN) ? $arryData->SUN : $arryData->SUNARR->SHIFT_ID;
                $mon = ($arryData->MONARR->SHIFT_ID == $arryData->MON) ? $arryData->MON : $arryData->MONARR->SHIFT_ID;
                $tue = ($arryData->TUEARR->SHIFT_ID == $arryData->TUE) ? $arryData->TUE : $arryData->TUEARR->SHIFT_ID;
                $wed = ($arryData->WEDARR->SHIFT_ID == $arryData->WED) ? $arryData->WED : $arryData->WEDARR->SHIFT_ID;
                $thu = ($arryData->THUARR->SHIFT_ID == $arryData->THU) ? $arryData->THU : $arryData->THUARR->SHIFT_ID;
                $fri = ($arryData->FRIARR->SHIFT_ID == $arryData->FRI) ? $arryData->FRI : $arryData->FRIARR->SHIFT_ID;
                $sat = ($arryData->SATARR->SHIFT_ID == $arryData->SAT) ? $arryData->SAT : $arryData->SATARR->SHIFT_ID;


                $sql = "
                    BEGIN
                    hris_weekly_ros_assign(
                    {$selectedEmp},
                    {$sun},
                    {$mon},
                    {$tue},
                    {$wed},
                    {$thu},
                    {$fri},
                    {$sat}
                    );
                    END;
                    ";
                //                echo $sql;
                //                die();
                EntityHelper::rawQueryResult($this->adapter, $sql);
                //                ()
                //                
                //                print_r($arryData);
                //                die();

                //                foreach ($data['data'] as $item) {
                //                    $this->repository->merge($item['EMPLOYEE_ID'], $item['FOR_DATE'], $item['SHIFT_ID']);
                //                }
                return new JsonModel($modelData);
                //                return new JsonModel(['success' => true, 'data' => null, 'error' => '']);
            } catch (Exception $e) {
                return new JsonModel(['success' => false, 'data' => null, 'error' => $e->getMessage()]);
            }
        }
    }
}
