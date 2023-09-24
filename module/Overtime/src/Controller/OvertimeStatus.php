<?php

namespace Overtime\Controller;

use Application\Controller\HrisController;
use Application\Helper\EntityHelper;
use Application\Helper\Helper;
use AttendanceManagement\Repository\AttendanceRepository;
use Exception;
use Notification\Controller\HeadNotification;
use Notification\Model\NotificationEvents;
use Overtime\Repository\OvertimeStatusRepository;
use SelfService\Form\OvertimeRequestForm;
use SelfService\Model\Overtime;
use SelfService\Model\OvertimeDetail;
use SelfService\Repository\OvertimeDetailRepository;
use SelfService\Repository\OvertimeRepository;
use Setup\Repository\EmployeeRepository;
use System\Repository\PreferenceSetupRepo;
use Zend\Authentication\Storage\StorageInterface;
use Zend\Db\Adapter\AdapterInterface;
use Zend\View\Model\JsonModel;

class OvertimeStatus extends HrisController
{

    private $detailRepo;

    public function __construct(AdapterInterface $adapter, StorageInterface $storage)
    {
        parent::__construct($adapter, $storage);
        $this->initializeRepository(OvertimeStatusRepository::class);
        $this->detailRepo = new OvertimeDetailRepository($this->adapter);
        $this->initializeForm(OvertimeRequestForm::class);
    }

    public function indexAction()
    {
        $request = $this->getRequest();
        if ($request->isPost()) {
            try {
                $data = $request->getPost();
                $result = $this->repository->getOTRequestList($data);
                $recordList = [];
                foreach ($result as $row) {
                    $row['DETAILS'] = $this->detailRepo->fetchByOvertimeId($row['OVERTIME_ID']);
                    array_push($recordList, $row);
                }
                return new JsonModel(["success" => "true", "data" => $recordList]);
            } catch (Exception $e) {
                return new JsonModel(['success' => false, 'data' => null, 'message' => $e->getMessage()]);
            }
        }
        $statusSE = $this->getStatusSelectElement(['name' => 'status', "id" => "requestStatusId", "class" => "form-control reset-field", 'label' => 'Status']);
        return $this->stickFlashMessagesTo([
            'status' => $statusSE,
            'searchValues' => EntityHelper::getSearchData($this->adapter),
            'acl' => $this->acl,
            'employeeDetail' => $this->storageData['employee_detail'],
            'preference' => $this->preference
        ]);
    }

    public function viewAction()
    {
        $id = (int) $this->params()->fromRoute('id');

        if ($id === 0) {
            return $this->redirect()->toRoute("overtimeStatus");
        }
        $overtimeModel = new Overtime();
        $request = $this->getRequest();

        $detail = $this->repository->fetchById($id);
        $status = $detail['STATUS'];
        $employeeId = $detail['EMPLOYEE_ID'];

        $recommApprove = $detail['RECOMMENDER_ID'] == $detail['APPROVER_ID'] ? 1 : 0;

        $employeeName = $detail['FULL_NAME'];
        $authRecommender = $detail['RECOMMENDED_BY_NAME'] == null ? $detail['RECOMMENDER_NAME'] : $detail['RECOMMENDED_BY_NAME'];
        $authApprover = $detail['APPROVED_BY_NAME'] == null ? $detail['APPROVER_NAME'] : $detail['APPROVED_BY_NAME'];


        if (!$request->isPost()) {
            $overtimeModel->exchangeArrayFromDB($detail);
            $this->form->bind($overtimeModel);
        } else {
            $getData = $request->getPost();
            $reason = $getData->approvedRemarks;
            $action = $getData->submit;

            $overtimeModel->approvedDate = Helper::getcurrentExpressionDate();
            if ($action == "Reject") {
                $overtimeModel->status = "R";
                $this->flashmessenger()->addMessage("Overtime Request Rejected!!!");
            } else if ($action == "Approve") {
                $overtimeModel->status = "AP";
                $this->flashmessenger()->addMessage("Overtime Request Approved");
            }
            $overtimeModel->approvedBy = $this->employeeId;
            $overtimeModel->approvedRemarks = $reason;
            $this->repository->edit($overtimeModel, $id);

            return $this->redirect()->toRoute("overtimeStatus");
        }
        $overtimeDetailResult = $this->detailRepo->fetchByOvertimeId($detail['OVERTIME_ID']);
        $overtimeDetails = [];
        foreach ($overtimeDetailResult as $overtimeDetailRow) {
            array_push($overtimeDetails, $overtimeDetailRow);
        }
        return Helper::addFlashMessagesToArray($this, [
            'form' => $this->form,
            'id' => $id,
            'employeeId' => $employeeId,
            'employeeName' => $employeeName,
            'requestedDt' => $detail['REQUESTED_DATE'],
            'recommender' => $authRecommender,
            'approvedDT' => $detail['APPROVED_DATE'],
            'approver' => $authApprover,
            'status' => $status,
            'customRenderer' => Helper::renderCustomView(),
            'recommApprove' => $recommApprove,
            'overtimeDetails' => $overtimeDetails,
            'totalHour' => $detail['TOTAL_HOUR_DETAIL']
        ]);
    }

    public function calculateAction()
    {
        $preferenceSetupRepo = new PreferenceSetupRepo($this->adapter);
        $employeeRepo = new EmployeeRepository($this->adapter);
        $overtimeModel = new Overtime();
        $overtimeRepository = new OvertimeRepository($this->adapter);
        $overtimeDetailModel = new OvertimeDetail();
        $overtimeDetailRepo = new OvertimeDetailRepository($this->adapter);
        $overtimeRequestSetting = $preferenceSetupRepo->fetchByPreferenceName("OVERTIME_REQUEST");
        $employeeAdmin = $employeeRepo->fetchByAdminFlag();
        foreach ($overtimeRequestSetting as $overtimeRequestSettingRow) {
            //            $attendanceDt = date(Helper::PHP_DATE_FORMAT);
            $attendanceDt = "10-May-2017";
            $employeeResult = $employeeRepo->fetchByEmployeeTypeWidShift($overtimeRequestSettingRow['EMPLOYEE_TYPE'], $attendanceDt);
            $preferenceConstraint = $overtimeRequestSettingRow['PREFERENCE_CONSTRAINT'];
            $preferenceCondition = $overtimeRequestSettingRow['PREFERENCE_CONDITION'];
            $constraintValue = $overtimeRequestSettingRow['CONSTRAINT_VALUE'];
            $constraintType = $overtimeRequestSettingRow['CONSTRAINT_TYPE'];
            $requestType = $overtimeRequestSettingRow['REQUEST_TYPE'];
            foreach ($employeeResult as $employeeRow) {
                if ($preferenceConstraint == 'OVERTIME_GRACE_TIME' && $constraintType == 'HOUR') {
                    $attendanceRepository = new AttendanceRepository($this->adapter);
                    $attendanceResult = $attendanceRepository->fetchAllByEmpIdAttendanceDt($employeeRow['EMPLOYEE_ID'], $attendanceDt);
                    $attendanceNum = count($attendanceResult);
                    if ($attendanceNum != 0 && $attendanceNum % 2 == 0) {
                        $getTotalHourTime = $attendanceRepository->getTotalByEmpIdAttendanceDt($employeeRow['EMPLOYEE_ID'], $attendanceDt);
                        $shiftTotalWorkingHrMin = Helper::hoursToMinutes($employeeRow['TOTAL_WORKING_HR']);
                        $lateInHrMin = Helper::hoursToMinutes($employeeRow['LATE_IN']);
                        $earlyOutHrMin = Helper::hoursToMinutes($employeeRow['EARLY_OUT']);
                        $actualWorkingHrMin = Helper::hoursToMinutes($employeeRow['ACTUAL_WORKING_HR']);
                        $actualBreakTime = $shiftTotalWorkingHrMin - $actualWorkingHrMin;
                        $totalWorkingHrMin = $getTotalHourTime['WORKING']['TOTAL_MINS'];
                        $totalNonWorkingHrMin = $getTotalHourTime['NON-WORKING']['TOTAL_MINS'];
                        if ($totalWorkingHrMin > $actualWorkingHrMin) {
                            $extraOvertime = ($actualBreakTime > $totalNonWorkingHrMin) ? $actualBreakTime - $totalNonWorkingHrMin : 0;
                            $overtime = ($totalWorkingHrMin - $actualWorkingHrMin) - $extraOvertime;
                            $overtimeHr = Helper::minutesToHours($overtime);
                            $constraintValueMin = Helper::hoursToMinutes($constraintValue);
                            $overtimeModel->overtimeId = ((int) Helper::getMaxId($this->adapter, Overtime::TABLE_NAME, Overtime::OVERTIME_ID)) + 1;
                            $overtimeModel->employeeId = $employeeRow['EMPLOYEE_ID'];
                            $overtimeModel->overtimeDate = Helper::getExpressionDate($attendanceDt);
                            $overtimeModel->requestedDate = Helper::getcurrentExpressionDate();
                            $overtimeModel->description = "Overtime Request";
                            $overtimeModel->allTotalHour = Helper::getExpressionTime($overtimeHr, Helper::ORACLE_TIMESTAMP_FORMAT);
                            $overtimeModel->status = $requestType;
                            if ($requestType == 'AP') {
                                $overtimeModel->recommendedBy = $employeeAdmin['EMPLOYEE_ID'];
                                $overtimeModel->approvedBy = $employeeAdmin['EMPLOYEE_ID'];
                            }
                            $inTime = strtotime($employeeRow['IN_TIME']);
                            $shiftStartTime = strtotime($employeeRow['START_TIME']);
                            $outTime = strtotime($employeeRow['OUT_TIME']);
                            $shiftEndTime = strtotime($employeeRow['END_TIME']);
                            $result = 0;
                            if ($preferenceCondition == "LESS_THAN") {
                                if ($overtime < $constraintValueMin) {
                                    $result = $overtimeRepository->add($overtimeModel);
                                }
                            } else if ($preferenceCondition == "GREATER_THAN") {
                                if ($overtime > $constraintValueMin) {
                                    $result = $overtimeRepository->add($overtimeModel);
                                }
                            } else if ($preferenceCondition == 'EQUAL') {
                                if ($overtime == $constraintValueMin) {
                                    $result = $overtimeRepository->add($overtimeModel);
                                }
                            }
                            if ($result == 1) {
                                if ($inTime != $shiftStartTime && $inTime < $shiftStartTime) {
                                    $dtlTotalHr = Helper::minutesToHours(round(abs($shiftStartTime - $inTime) / 60, 2));
                                    $overtimeDetailModel->overtimeId = $overtimeModel->overtimeId;
                                    $overtimeDetailModel->detailId = ((int) Helper::getMaxId($this->adapter, OvertimeDetail::TABLE_NAME, OvertimeDetail::DETAIL_ID)) + 1;
                                    $overtimeDetailModel->startTime = Helper::getExpressionTime($employeeRow['IN_TIME']);
                                    $overtimeDetailModel->endTime = Helper::getExpressionTime($employeeRow['START_TIME']);
                                    $overtimeDetailModel->status = 'E';
                                    $overtimeDetailModel->createdBy = $this->employeeId;
                                    $overtimeDetailModel->totalHour = Helper::getExpressionTime($dtlTotalHr, Helper::ORACLE_TIMESTAMP_FORMAT);
                                    $overtimeDetailModel->createdDate = Helper::getcurrentExpressionDate();
                                    $overtimeDetailRepo->add($overtimeDetailModel);
                                }
                                if ($outTime != $shiftEndTime && $shiftEndTime < $outTime) {
                                    $dtlTotalHr = Helper::minutesToHours(round(abs($outTime - $shiftEndTime) / 60, 2));
                                    $overtimeDetailModel->overtimeId = $overtimeModel->overtimeId;
                                    $overtimeDetailModel->detailId = ((int) Helper::getMaxId($this->adapter, OvertimeDetail::TABLE_NAME, OvertimeDetail::DETAIL_ID)) + 1;
                                    $overtimeDetailModel->startTime = Helper::getExpressionTime($employeeRow['END_TIME']);
                                    $overtimeDetailModel->endTime = Helper::getExpressionTime($employeeRow['OUT_TIME']);
                                    $overtimeDetailModel->status = 'E';
                                    $overtimeDetailModel->totalHour = Helper::getExpressionTime($dtlTotalHr, Helper::ORACLE_TIMESTAMP_FORMAT);
                                    $overtimeDetailModel->createdBy = $this->employeeId;
                                    $overtimeDetailModel->createdDate = Helper::getcurrentExpressionDate();
                                    $overtimeDetailRepo->add($overtimeDetailModel);
                                }
                                $this->flashmessenger()->addMessage("Overtime Request Successfully Generated!!!");
                            } else {
                                $this->flashmessenger()->addMessage("There is no required data to generate overtime request!!!");
                            }
                        }
                    }
                }
            }
        }
        $this->redirect()->toRoute('overtimeStatus');
    }

    public function bulkAction()
    {
        $request = $this->getRequest();
        try {
            $postData = $request->getPost();
            $this->makeDecision($postData['id'], $postData['action'] == "approve");
            return new JsonModel(['success' => true, 'data' => null]);
        } catch (Exception $e) {
            return new JsonModel(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    private function makeDecision($id, $approve, $remarks = null, $enableFlashNotification = false)
    {
        $model = new Overtime();
        $model->overtimeId = $id;
        $model->recommendedDate = Helper::getcurrentExpressionDate();
        $model->recommendedBy = $this->employeeId;
        $model->approvedRemarks = $remarks;
        $model->approvedDate = Helper::getcurrentExpressionDate();
        $model->approvedBy = $this->employeeId;
        $model->status = $approve ? "AP" : "R";
        $message = $approve ? "Overtime Request Approved" : "Overtime Request Rejected";
        $notificationEvent = $approve ? NotificationEvents::OVERTIME_APPROVE_ACCEPTED : NotificationEvents::OVERTIME_APPROVE_REJECTED;
        $this->repository->edit($model, $id);
        if ($enableFlashNotification) {
            $this->flashmessenger()->addMessage($message);
        }
        try {
            HeadNotification::pushNotification($notificationEvent, $model, $this->adapter, $this);
        } catch (Exception $e) {
            $this->flashmessenger()->addMessage($e->getMessage());
        }
    }
}
