<?php

namespace AttendanceManagement\Controller;

use Application\Controller\HrisController;
use Application\Custom\CustomViewModel;
use Application\Helper\EntityHelper;
use Application\Helper\Helper;
use AttendanceManagement\Form\AttendanceByHrForm;
use AttendanceManagement\Model\AttendanceDetail;
use SelfService\Model\AttendanceRequestModel;
use AttendanceManagement\Repository\AttendanceDetailRepository;
use AttendanceManagement\Repository\AttendanceRepository;
use AttendanceManagement\Repository\ShiftRepository;
use Exception;
use SelfService\Repository\AttendanceRepository as SelfServiceAttendanceRepository;
use Zend\Authentication\Storage\StorageInterface;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Form\Element\Select;
use Zend\View\Model\JsonModel;

class AttendanceByHr extends HrisController
{

    public function __construct(AdapterInterface $adapter, StorageInterface $storage)
    {
        parent::__construct($adapter, $storage);
        $this->initializeForm(AttendanceByHrForm::class);
        $this->initializeRepository(AttendanceDetailRepository::class);
    }

    private function getStatusSelect()
    {
        $statusFormElement = new Select();
        $statusFormElement->setName("status");
        $status = array(
            "P" => "Present Only",
            "A" => "Absent Only",
            "H" => "On Holiday",
            "L" => "On Leave",
            "T" => "On Training",
            "TVL" => "On Travel",
            "WOH" => "Work on Holiday",
            "WOD" => "Work on DAYOFF",
        );
        $statusFormElement->setValueOptions($status);
        $statusFormElement->setAttributes(["id" => "statusId", "class" => "form-control reset-field", "multiple" => "multiple"]);
        $statusFormElement->setLabel("Status");
        return $statusFormElement;
    }

    private function getPresentStatusSelect()
    {
        $statusFormElement = new Select();
        $statusFormElement->setName("presentStatus");
        $status = array(
            "LI" => "Late In",
            "EO" => "Early Out",
            "MP" => "Missed Punched",
        );
        $statusFormElement->setValueOptions($status);
        $statusFormElement->setAttributes(["id" => "presentStatusId", "class" => "form-control reset-field", "multiple" => "multiple"]);
        $statusFormElement->setLabel("Present Status");
        return $statusFormElement;
    }

    public function indexAction()
    {
        $shiftRepo = new ShiftRepository($this->adapter);
        $shiftList = iterator_to_array($shiftRepo->fetchAll(), false);
        return Helper::addFlashMessagesToArray($this, [
            'status' => $this->getStatusSelect(),
            'presentStatus' => $this->getPresentStatusSelect(),
            'searchValues' => EntityHelper::getSearchData($this->adapter),
            'acl' => $this->acl,
            'shiftList' => $shiftList,
            'employeeDetail' => $this->storageData['employee_detail'],
            'allowShiftChange' =>  isset($this->preference['attAppShiftChangeable']) ? $this->preference['attAppShiftChangeable']  : 'N',
            'allowTimeChange' =>  isset($this->preference['attAppTimeChangeable']) ? $this->preference['attAppTimeChangeable']  : 'N',
            'preference' => $this->preference,
            'provinces' => EntityHelper::getProvinceList($this->adapter),
            'braProv' => EntityHelper::getBranchFromProvince($this->adapter),
        ]);
    }

    public function reportAction()
    {
        return Helper::addFlashMessagesToArray($this, [
            'status' => $this->getStatusSelect(),
            'presentStatus' => $this->getPresentStatusSelect(),
            'searchValues' => EntityHelper::getSearchData($this->adapter),
            'acl' => $this->acl,
            'employeeDetail' => $this->storageData['employee_detail'],
            'preference' => $this->preference
        ]);
    }

    /*
    public function addAction() {
        $request = $this->getRequest();
        try {
            if ($request->isPost()) {
                $this->form->setData($request->getPost());
                if ($this->form->isValid()) {
                    $attendanceByHrModel = new AttendanceDetail();
                    $formData = $this->form->getData();
                    $attendanceByHrModel->exchangeArrayFromForm($formData);
                    $attendanceByHrModel->attendanceDt = Helper::getExpressionDate($attendanceByHrModel->attendanceDt);
                    $attendanceByHrModel->id = ((int) Helper::getMaxId($this->adapter, AttendanceDetail::TABLE_NAME, "ID")) + 1;
                    $attendanceByHrModel->inTime = Helper::getExpressionTime($attendanceByHrModel->inTime);
                    $attendanceByHrModel->outTime = Helper::getExpressionTime($attendanceByHrModel->outTime);

                    $employeeId = $attendanceByHrModel->employeeId;
                    $attendanceDt = $formData['attendanceDt'];

                    $previousDtl = $this->repository->getDtlWidEmpIdDate($employeeId, $attendanceDt);

                    if ($previousDtl == null) {
                        throw new Exception("Attendance of employee with employeeId :$employeeId on $attendanceDt is not found.");
                    } else {
                        $this->repository->edit($attendanceByHrModel, $previousDtl['ID']);
                    }

                    $this->flashmessenger()->addMessage("Attendance Submitted Successfully!!");
                    return $this->redirect()->toRoute("attendancebyhr");
                }
            }
            return Helper::addFlashMessagesToArray($this, [
                    'form' => $this->form,
                    'employees' => EntityHelper::getTableKVListWithSortOption($this->adapter, "HRIS_EMPLOYEES", "EMPLOYEE_ID", ["EMPLOYEE_CODE","FULL_NAME"], ["STATUS" => 'E', 'RETIRED_FLAG' => 'N'], "FIRST_NAME", "ASC", "-", FALSE, TRUE, $this->employeeId)
                    ]
            );
        } catch (Exception $e) {
            $this->flashmessenger()->addMessage("Attendance Submit Failed!!");
            $this->flashmessenger()->addMessage($e->getMessage());
            return Helper::addFlashMessagesToArray($this, [
                    'form' => $this->form,
                    'employees' => EntityHelper::getTableKVListWithSortOption($this->adapter, "HRIS_EMPLOYEES", "EMPLOYEE_ID", ["FIRST_NAME", "MIDDLE_NAME", "LAST_NAME"], ["STATUS" => 'E', 'RETIRED_FLAG' => 'N'], "FIRST_NAME", "ASC", " ", FALSE, TRUE)
                    ]
            );
        }
    }
    */

    public function addAction()
    {
        $request = $this->getRequest();
        try {
            if ($request->isPost()) {
                $this->form->setData($request->getPost());
                if ($this->form->isValid()) {
                    $data = $request->getPost();
                    $data['requestId'] = ((int) Helper::getMaxId($this->adapter, AttendanceRequestModel::TABLE_NAME, "ID")) + 1;
                    $data['status'] = 'AP';
                    $data['approvedBy'] = $this->employeeId;
                    $data['approvedRemarks'] = 'Auto Approved By HR';
                    $data['createdBy'] = $this->employeeId;
                    $attendanceRepository = new AttendanceRepository($this->adapter);
                    $attendanceRepository->insertAttendance($data);
                    $this->flashmessenger()->addMessage("Attendance Submitted Successfully!!");
                    return $this->redirect()->toRoute("attendancebyhr");
                }
            }
            return Helper::addFlashMessagesToArray(
                $this,
                [
                    'form' => $this->form,
                    'employees' => EntityHelper::getTableKVListWithSortOption($this->adapter, "HRIS_EMPLOYEES", "EMPLOYEE_ID", ["EMPLOYEE_CODE", "FULL_NAME"], ["STATUS" => 'E', 'RETIRED_FLAG' => 'N'], "FIRST_NAME", "ASC", "-", FALSE, TRUE, $this->employeeId)
                ]
            );
        } catch (Exception $e) {
            $this->flashmessenger()->addMessage("Attendance Submit Failed!!");
            $this->flashmessenger()->addMessage($e->getMessage());
            return Helper::addFlashMessagesToArray(
                $this,
                [
                    'form' => $this->form,
                    'employees' => EntityHelper::getTableKVListWithSortOption($this->adapter, "HRIS_EMPLOYEES", "EMPLOYEE_ID", ["FIRST_NAME", "MIDDLE_NAME", "LAST_NAME"], ["STATUS" => 'E', 'RETIRED_FLAG' => 'N'], "FIRST_NAME", "ASC", " ", FALSE, TRUE)
                ]
            );
        }
    }

    public function editAction()
    {
        $id = (int) $this->params()->fromRoute("id");
        if ($id === 0) {
            return $this->redirect()->toRoute("attendancebyhr");
        }

        $request = $this->getRequest();
        $attendanceByHrModel = new AttendanceByHrModel();
        if (!$request->isPost()) {
            $attendanceByHrModel->exchangeArrayFromDB($this->repository->fetchById($id));
            $this->form->bind($attendanceByHrModel);
        } else {
            $this->form->setData($request->getPost());
            if ($this->form->isValid()) {
                $attendanceByHrModel->exchangeArrayFromForm($this->form->getData());
                $attendanceByHrModel->inTime = Helper::getExpressionTime($attendanceByHrModel->inTime);
                $attendanceByHrModel->outTime = Helper::getExpressionTime($attendanceByHrModel->outTime);
                $this->repository->edit($attendanceByHrModel, $id);
                $this->flashmessenger()->addMessage("Attendance Updated Successfully!!");
                return $this->redirect()->toRoute("attendancebyhr");
            }
        }
        return Helper::addFlashMessagesToArray(
            $this,
            [
                'form' => $this->form,
                'id' => $id,
                'employees' => EntityHelper::getTableKVListWithSortOption($this->adapter, "HRIS_EMPLOYEES", "EMPLOYEE_ID", ["FIRST_NAME", "MIDDLE_NAME", "LAST_NAME"], ["STATUS" => 'E', 'RETIRED_FLAG' => 'N'], "FIRST_NAME", "ASC", NULL, FALSE, TRUE)
            ]
        );
    }

    public function deleteAction()
    {
    }

    public function pullAttendanceAction()
    {
        try {
            $request = $this->getRequest();
            $data = $request->getPost();

            $companyId = isset($data['companyId']) ? $data['companyId'] : -1;
            $branchId = isset($data['branchId']) ? $data['branchId'] : -1;
            $departmentId = isset($data['departmentId']) ? $data['departmentId'] : -1;
            $designationId = isset($data['designationId']) ? $data['designationId'] : -1;
            $positionId = isset($data['positionId']) ? $data['positionId'] : -1;
            $serviceTypeId = isset($data['serviceTypeId']) ? $data['serviceTypeId'] : -1;
            $serviceEventTypeId = isset($data['serviceEventTypeId']) ? $data['serviceEventTypeId'] : -1;
            $employeeTypeId = isset($data['employeeTypeId']) ? $data['employeeTypeId'] : -1;
            $genderId = isset($data['genderId']) ? $data['genderId'] : -1;
            $functionalTypeId = isset($data['functionalTypeId']) ? $data['functionalTypeId'] : -1;
            $employeeId = isset($data['employeeId']) ? $data['employeeId'] : -1;
            $fromDate = $data['fromDate'];
            $toDate = $data['toDate'];
            $status = $data['status'];
            $presentStatus = $data['presentStatus'];
            $presentType = $data['presentType'];

            $results = $this->repository->filterRecord($companyId, $branchId, $departmentId, $designationId, $positionId, $serviceTypeId, $serviceEventTypeId, $employeeTypeId, $genderId, $functionalTypeId, $employeeId, $fromDate, $toDate, $status, $presentStatus, null, null, $presentType);
            $result = [];
            $result['success'] = true;
            $result['data'] = Helper::extractDbData($results);
            $result['error'] = "";
            return new CustomViewModel($result);
        } catch (Exception $e) {
            return new CustomViewModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
        }
    }

    //    public function pullAttendanceAction() {
    //        try {
    //            $request = $this->getRequest();
    //            $data = $request->getPost();
    //
    //            $take = $data['take'];
    //            $skip = $data['skip'];
    //            $page = $data['page'];
    //            $pageSize = $data['pageSize'];
    //
    //            $max = $pageSize * $page;
    //            $min = $pageSize * ($page - 1);
    //
    //            $employeeId = isset($data['employeeId']) ? $data['employeeId'] : -1;
    //            $companyId = isset($data['companyId']) ? $data['companyId'] : -1;
    //            $branchId = isset($data['branchId']) ? $data['branchId'] : -1;
    //            $departmentId = isset($data['departmentId']) ? $data['departmentId'] : -1;
    //            $positionId = isset($data['positionId']) ? $data['positionId'] : -1;
    //            $designationId = isset($data['designationId']) ? $data['designationId'] : -1;
    //            $serviceTypeId = isset($data['serviceTypeId']) ? $data['serviceTypeId'] : -1;
    //            $serviceEventTypeId = isset($data['serviceEventTypeId']) ? $data['serviceEventTypeId'] : -1;
    //            $employeeTypeId = isset($data['employeeTypeId']) ? $data['employeeTypeId'] : -1;
    //            $fromDate = $data['fromDate'];
    //            $toDate = $data['toDate'];
    //            $status = $data['status'];
    //            $missPunchOnly = ((int) $data['missPunchOnly'] == 1) ? true : false;
    //            $results = $this->repository->filterRecord($employeeId, $branchId, $departmentId, $positionId, $designationId, $serviceTypeId, $serviceEventTypeId, $fromDate, $toDate, $status, $companyId, $employeeTypeId, false, $missPunchOnly, $min, $max);
    //            $total = $this->repository->filterRecordCount($employeeId, $branchId, $departmentId, $positionId, $designationId, $serviceTypeId, $serviceEventTypeId, $fromDate, $toDate, $status, $companyId, $employeeTypeId, false, $missPunchOnly);
    //
    //            $result = [];
    //            $result['total'] = $total['TOTAL'];
    //            $result['results'] = Helper::extractDbData($results);
    //
    //            return new CustomViewModel($result);
    //        } catch (Exception $e) {
    //            return new CustomViewModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
    //        }
    //    }

    public function bulkAttendanceWSAction()
    {
        try {
            $request = $this->getRequest();
            $postedData = $request->getPost();
            //print_r($postedData);die;
            $inTime = "TO_DATE('{$postedData['in_time']}', 'HH:MI AM')";
            $outTime = "TO_DATE('{$postedData['out_time']}', 'HH:MI AM')";
            $createdBy = $this->employeeId;
            //return new JsonModel(['success' => true, 'data' => $postedData, 'error' => '']);
            $this->repository->manualAttendance($postedData['employeeId'], Helper::getExpressionDate($postedData['attendanceDt'])->getExpression(), $postedData['action'], $postedData['impactOtherDays'] === 'true', $postedData['shiftId'], $inTime, $outTime, $postedData['outNextDay'] === 'true', $createdBy);
            return new JsonModel(['success' => true, 'data' => [], 'error' => '']);
        } catch (Exception $e) {
            return new JsonModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
        }
    }

    public function pullInOutTimeAction()
    {
        try {
            $request = $this->getRequest();
            $data = $request->getPost();

            $attendanceDt = $data['attendanceDt'];
            $employeeId = $data['employeeId'];

            $attendanceRepository = new AttendanceRepository($this->adapter);
            $result = $attendanceRepository->fetchInOutTimeList($employeeId, $attendanceDt);
            $list = [];
            foreach ($result as $row) {
                array_push($list, $row);
            }

            return new JsonModel(['success' => true, 'data' => $list, 'message' => null]);
        } catch (Exception $e) {
            return new JsonModel(['success' => false, 'data' => null, 'message' => $e->getMessage()]);
        }
    }

    public function attendanceReportWithLocationAction()
    {
        return Helper::addFlashMessagesToArray($this, [
            'status' => $this->getStatusSelect(),
            'presentStatus' => $this->getPresentStatusSelect(),
            'searchValues' => EntityHelper::getSearchData($this->adapter),
            'acl' => $this->acl,
            'employeeDetail' => $this->storageData['employee_detail'],
            'preference' => $this->preference
        ]);
    }

    public function pullAttendanceWithLocationAction()
    {
        try {
            $request = $this->getRequest();
            $data = $request->getPost();

            $employeeId = isset($data['employeeId']) ? $data['employeeId'] : -1;
            $companyId = isset($data['companyId']) ? $data['companyId'] : -1;
            $branchId = isset($data['branchId']) ? $data['branchId'] : -1;
            $departmentId = isset($data['departmentId']) ? $data['departmentId'] : -1;
            $positionId = isset($data['positionId']) ? $data['positionId'] : -1;
            $designationId = isset($data['designationId']) ? $data['designationId'] : -1;
            $serviceTypeId = isset($data['serviceTypeId']) ? $data['serviceTypeId'] : -1;
            $serviceEventTypeId = isset($data['serviceEventTypeId']) ? $data['serviceEventTypeId'] : -1;
            $employeeTypeId = isset($data['employeeTypeId']) ? $data['employeeTypeId'] : -1;
            $fromDate = $data['fromDate'];
            $toDate = $data['toDate'];
            $status = $data['status'];
            $results = $this->repository->filterRecordWithLocation($employeeId, $branchId, $departmentId, $positionId, $designationId, $serviceTypeId, $serviceEventTypeId, $fromDate, $toDate, $status, $companyId, $employeeTypeId, $data['presentStatus']);

            $result = [];
            $result['success'] = true;
            $result['data'] = Helper::extractDbData($results);
            $result['error'] = "";

            return new JsonModel($result);
        } catch (Exception $e) {
            return new JsonModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
        }
    }

    public function dailyPerformanceReportAction()
    {
        $request = $this->getRequest();
        $data = $request->getPost();

        if ($request->isPost()) {
            $reportData = Helper::extractDbData($this->repository->fetchDailyPerformanceReport($data));
            return new JsonModel(['success' => true, 'data' => $reportData, 'error' => '']);
        }

        return Helper::addFlashMessagesToArray($this, [
            'status' => $this->getStatusSelect(),
            'presentStatus' => $this->getPresentStatusSelect(),
            'searchValues' => EntityHelper::getSearchData($this->adapter),
            'acl' => $this->acl,
            'employeeDetail' => $this->storageData['employee_detail'],
            'preference' => $this->preference
        ]);
    }

    public function checkInAction()
    {
        $request = $this->getRequest();
        try {
            if ($request->isPost()) {
                $this->form->setData($request->getPost());
                if ($this->form->isValid()) {
                    $data = $request->getPost();
                    $data['requestId'] = ((int) Helper::getMaxId($this->adapter, AttendanceRequestModel::TABLE_NAME, "ID")) + 1;
                    $data['status'] = 'AP';
                    $data['approvedBy'] = $this->employeeId;
                    $data['approvedRemarks'] = 'Auto Approved By HR';
                    $data['totalHour'] = null;
                    $data['createdBy'] = $this->employeeId;
                    $attendanceRepository = new AttendanceRepository($this->adapter);
                    $attendanceRepository->insertAttendance($data);
                    $this->flashmessenger()->addMessage("Attendance Submitted Successfully!!");
                    return $this->redirect()->toRoute("attendancebyhr");
                }
            }
            return Helper::addFlashMessagesToArray(
                $this,
                [
                    'form' => $this->form,
                    'employees' => EntityHelper::getTableKVListWithSortOption($this->adapter, "HRIS_EMPLOYEES", "EMPLOYEE_ID", ["EMPLOYEE_CODE", "FULL_NAME"], ["STATUS" => 'E', 'RETIRED_FLAG' => 'N'], "FIRST_NAME", "ASC", "-", FALSE, TRUE, $this->employeeId)
                ]
            );
        } catch (Exception $e) {
            $this->flashmessenger()->addMessage("Attendance Submit Failed!!");
            $this->flashmessenger()->addMessage($e->getMessage());
            return Helper::addFlashMessagesToArray(
                $this,
                [
                    'form' => $this->form,
                    'employees' => EntityHelper::getTableKVListWithSortOption($this->adapter, "HRIS_EMPLOYEES", "EMPLOYEE_ID", ["FIRST_NAME", "MIDDLE_NAME", "LAST_NAME"], ["STATUS" => 'E', 'RETIRED_FLAG' => 'N'], "FIRST_NAME", "ASC", " ", FALSE, TRUE)
                ]
            );
        }
    }

    public function checkOutAction()
    {
        $request = $this->getRequest();
        try {
            if ($request->isPost()) {
                $this->form->setData($request->getPost());
                if ($this->form->isValid()) {
                    $data = $request->getPost();
                    $data['requestId'] = ((int) Helper::getMaxId($this->adapter, AttendanceRequestModel::TABLE_NAME, "ID")) + 1;
                    $data['status'] = 'AP';
                    $data['approvedBy'] = $this->employeeId;
                    $data['approvedRemarks'] = 'Auto Approved By HR';
                    $data['totalHour'] = null;
                    $data['createdBy'] = $this->employeeId;
                    $attendanceRepository = new AttendanceRepository($this->adapter);
                    $attendanceRepository->insertAttendance($data);
                    $this->flashmessenger()->addMessage("Attendance Submitted Successfully!!");
                    return $this->redirect()->toRoute("attendancebyhr");
                }
            }
            return Helper::addFlashMessagesToArray(
                $this,
                [
                    'form' => $this->form,
                    'employees' => EntityHelper::getTableKVListWithSortOption($this->adapter, "HRIS_EMPLOYEES", "EMPLOYEE_ID", ["EMPLOYEE_CODE", "FULL_NAME"], ["STATUS" => 'E', 'RETIRED_FLAG' => 'N'], "FIRST_NAME", "ASC", "-", FALSE, TRUE, $this->employeeId)
                ]
            );
        } catch (Exception $e) {
            $this->flashmessenger()->addMessage("Attendance Submit Failed!!");
            $this->flashmessenger()->addMessage($e->getMessage());
            return Helper::addFlashMessagesToArray(
                $this,
                [
                    'form' => $this->form,
                    'employees' => EntityHelper::getTableKVListWithSortOption($this->adapter, "HRIS_EMPLOYEES", "EMPLOYEE_ID", ["FIRST_NAME", "MIDDLE_NAME", "LAST_NAME"], ["STATUS" => 'E', 'RETIRED_FLAG' => 'N'], "FIRST_NAME", "ASC", " ", FALSE, TRUE)
                ]
            );
        }
    }

    public function reportOnlyAction()
    {
        return Helper::addFlashMessagesToArray($this, [
            'status' => $this->getStatusSelect(),
            'presentStatus' => $this->getPresentStatusSelect(),
            'searchValues' => EntityHelper::getSearchData($this->adapter),
            'acl' => $this->acl,
            'employeeDetail' => $this->storageData['employee_detail'],
            'preference' => $this->preference,
            'provinces' => EntityHelper::getProvinceList($this->adapter),
            'braProv' => EntityHelper::getBranchFromProvince($this->adapter),
        ]);
    }

    public function attdBotAction()
    {
        $shiftRepo = new ShiftRepository($this->adapter);
        $shiftList = iterator_to_array($shiftRepo->fetchAll(), false);
        return Helper::addFlashMessagesToArray($this, [
            'status' => $this->getStatusSelect(),
            'presentStatus' => $this->getPresentStatusSelect(),
            'searchValues' => EntityHelper::getSearchData($this->adapter),
            'acl' => $this->acl,
            'shiftList' => $shiftList,
            'employeeDetail' => $this->storageData['employee_detail'],
            'allowShiftChange' =>  isset($this->preference['attAppShiftChangeable']) ? $this->preference['attAppShiftChangeable']  : 'N',
            'allowTimeChange' =>  isset($this->preference['attAppTimeChangeable']) ? $this->preference['attAppTimeChangeable']  : 'N'
        ]);
    }

    public function pullAttendanceBotAction()
    {
        try {
            $request = $this->getRequest();
            $data = $request->getPost();

            $companyId = isset($data['companyId']) ? $data['companyId'] : -1;
            $branchId = isset($data['branchId']) ? $data['branchId'] : -1;
            $departmentId = isset($data['departmentId']) ? $data['departmentId'] : -1;
            $designationId = isset($data['designationId']) ? $data['designationId'] : -1;
            $positionId = isset($data['positionId']) ? $data['positionId'] : -1;
            $serviceTypeId = isset($data['serviceTypeId']) ? $data['serviceTypeId'] : -1;
            $serviceEventTypeId = isset($data['serviceEventTypeId']) ? $data['serviceEventTypeId'] : -1;
            $employeeTypeId = isset($data['employeeTypeId']) ? $data['employeeTypeId'] : -1;
            $genderId = isset($data['genderId']) ? $data['genderId'] : -1;
            $functionalTypeId = isset($data['functionalTypeId']) ? $data['functionalTypeId'] : -1;
            $employeeId = isset($data['employeeId']) ? $data['employeeId'] : -1;
            $fromDate = $data['fromDate'];
            $toDate = $data['toDate'];
            $status = $data['status'];
            $presentStatus = $data['presentStatus'];

            $results = $this->repository->filterRecordBot($companyId, $branchId, $departmentId, $designationId, $positionId, $serviceTypeId, $serviceEventTypeId, $employeeTypeId, $genderId, $functionalTypeId, $employeeId, $fromDate, $toDate, $status, $presentStatus);
            $result = [];
            $result['success'] = true;
            $result['data'] = Helper::extractDbData($results);
            $result['error'] = "";
            return new CustomViewModel($result);
        } catch (Exception $e) {
            return new CustomViewModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
        }
    }

    public function rawAttendanceAction()
    {
        $shiftRepo = new ShiftRepository($this->adapter);
        $shiftList = iterator_to_array($shiftRepo->fetchAll(), false);
        $request = $this->getRequest();
        if ($request->isPost()) {
            $data = $request->getPost();
            $employeeId = isset($data['employeeId']) ? $data['employeeId'] : -1;
            $fromDate = $data['fromDate'];
            $toDate = $data['toDate'];
            $results = $this->repository->filterRecordRaw($employeeId, $fromDate, $toDate);
            $result = [];
            $result['success'] = true;
            $result['data'] = Helper::extractDbData($results);
            $result['error'] = "";
            return new CustomViewModel($result);
        }
        return Helper::addFlashMessagesToArray($this, [
            'searchValues' => EntityHelper::getSearchData($this->adapter),
            'acl' => $this->acl,
            'employeeDetail' => $this->storageData['employee_detail']
        ]);
    }
}
