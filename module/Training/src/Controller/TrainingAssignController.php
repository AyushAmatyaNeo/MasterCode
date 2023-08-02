<?php

namespace Training\Controller;

use Application\Controller\HrisController;
use Application\Helper\EntityHelper;
use Application\Helper\Helper;
use Exception;
use Notification\Controller\HeadNotification;
use Notification\Model\NotificationEvents;
use Setup\Model\Training;
use Setup\Repository\EmployeeRepository;
use Training\Form\TrainingAssignForm;
use Training\Model\TrainingAssign;
use Training\Repository\TrainingAssignRepository;
use Zend\Authentication\Storage\StorageInterface;
use Zend\Db\Adapter\AdapterInterface;
use Zend\View\Model\JsonModel;

class TrainingAssignController extends HrisController {

    public function __construct(AdapterInterface $adapter, StorageInterface $storage) {
        parent::__construct($adapter, $storage);
        $this->initializeRepository(TrainingAssignRepository::class);
        $this->initializeForm(TrainingAssignForm::class);
    }

    public function indexAction() {
        $request = $this->getRequest();
        if ($request->isPost()) {
            try {
                $data = $request->getPost();
                $rawList = $this->repository->filterRecords($data);
                $list = Helper::extractDbData($rawList);
                return new JsonModel(['success' => true, 'data' => $list, 'error' => '']);
            } catch (Exception $e) {
                return new JsonModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
            }
        }

        $trainings = EntityHelper::getTableKVListWithSortOption($this->adapter, Training::TABLE_NAME, Training::TRAINING_ID, [Training::TRAINING_NAME], [Training::STATUS => 'E'], "TRAINING_NAME", "ASC", null, [-1 => "All Training"], true);
        $trainingSE = $this->getSelectElement(['name' => 'trainingId', 'id' => 'trainingId', 'class' => 'form-control reset-field', 'label' => 'Training'], $trainings);
        return $this->stickFlashMessagesTo([
                    'trainings' => $trainingSE,
                    'searchValues' => EntityHelper::getSearchData($this->adapter),
                    'acl' => $this->acl,
        ]);
    }

    public function assignAction() {
        $trainings = EntityHelper::getTableKVListWithSortOption($this->adapter, Training::TABLE_NAME, Training::TRAINING_ID, [Training::TRAINING_NAME], [Training::STATUS => 'E'], "TRAINING_NAME", "ASC", null, [-1 => "Select Training"], true);
        $trainingSE = $this->getSelectElement(['name' => 'trainingId', 'id' => 'trainingId', 'class' => 'form-control', 'label' => 'Training'], $trainings);
        return [
            'searchValues' => EntityHelper::getSearchData($this->adapter),
            'trainings' => $trainingSE
        ];
    }

    public function deleteAction() {
        $employeeId = (int) $this->params()->fromRoute("employeeId");
        $trainingId = (int) $this->params()->fromRoute("trainingId");
        if (!$trainingId && !$employeeId) {
            return $this->redirect()->toRoute('trainingAssign');
        }
        $this->repository->delete([$employeeId, $trainingId]);
        $model = new \Training\Model\TrainingAssign();
        $model->trainingId = $trainingId;
        $model->employeeId = $employeeId;
        $model->createdBy = $this->employeeId;
        $this->flashmessenger()->addMessage("Training Assign Successfully Cancelled!!!");
        try {
            HeadNotification::pushNotification(NotificationEvents::TRAINING_CANCELLED, $model, $this->adapter, $this);
        } catch (Exception $e) {
            $this->flashmessenger()->addMessage($e->getMessage());
        }
        return $this->redirect()->toRoute('trainingAssign');
    }

    public function viewAction() {
        $employeeId = (int) $this->params()->fromRoute("employeeId");
        $trainingId = (int) $this->params()->fromRoute("trainingId");

        if (!$employeeId && !$trainingId) {
            return $this->redirect()->toRoute('trainingAssign');
        }

        $detail = $this->repository->getDetailByEmployeeID($employeeId, $trainingId);

        return Helper::addFlashMessagesToArray($this, ['detail' => $detail]);
    }

    public function assignEmployeeTrainingAction() {
        try {
            $request = $this->getRequest();
            $data = $request->getPost();


            if (!isset($data['trainingId']) || $data['trainingId'] == '' || $data['trainingId'] == -1) {
                throw new Exception('Invalid training selection.');
            }
            $trainingAssignRepo = new TrainingAssignRepository($this->adapter);
            $trainingAssignModel = new TrainingAssign();

            $trainingAssignModel->employeeId = $data['employeeId'];
            $trainingAssignModel->trainingId = $data['trainingId'];

            $emptrainingAssignedList = $trainingAssignRepo->getAllDetailByEmployeeID($data['employeeId'], $data['trainingId']);
            $empTrainingAssignedDetail = $emptrainingAssignedList->current();

            if ($empTrainingAssignedDetail != null) {
                if ($empTrainingAssignedDetail['STATUS'] == EntityHelper::STATUS_ENABLED) {
                    throw new Exception('Already Assigned');
                }
                $trainingAssignClone = clone $trainingAssignModel;
                unset($trainingAssignClone->employeeId);
                unset($trainingAssignClone->trainingId);
                unset($trainingAssignClone->createdDt);

                $trainingAssignClone->status = 'E';
                $trainingAssignClone->modifiedDt = Helper::getcurrentExpressionDate();
                $trainingAssignClone->modifiedBy = $this->employeeId;
                $trainingAssignRepo->edit($trainingAssignClone, [$data['employeeId'], $data['trainingId']]);
            } else {
                $trainingAssignModel->createdDt = Helper::getcurrentExpressionDate();
                $trainingAssignModel->createdBy = $this->employeeId;
                $trainingAssignModel->status = 'E';
                $trainingAssignRepo->add($trainingAssignModel);
            }
            try {
                HeadNotification::pushNotification(NotificationEvents::TRAINING_ASSIGNED, $trainingAssignModel, $this->adapter, $this);
            } catch (Exception $e) {
                return new JsonModel([
                    "success" => true,
                    "data" => null,
                    "message" => "Training assigned successfully with following error : " . $e->getMessage()
                ]);
            }
            return new JsonModel(['success' => true, 'data' => null, 'message' => "Training assigned successfully."]);
        } catch (Exception $e) {
            return new JsonModel(['success' => false, 'data' => null, 'message' => $e->getMessage()]);
        }
    }

    public function pullEmployeeForTrainingAssignAction() {
        try {
            $request = $this->getRequest();
            $data = $request->getPost();


            $employeeId = $data['employeeId'];
            $branchId = $data['branchId'];
            $departmentId = $data['departmentId'];
            $designationId = $data['designationId'];
            $positionId = $data['positionId'];
            $serviceTypeId = $data['serviceTypeId'];
            $trainingId = (int) $data['trainingId'];
            $companyId = $data['companyId'];
            $employeeRepository = new EmployeeRepository($this->adapter);
            $trainingAssignRepo = new TrainingAssignRepository($this->adapter);
            $employeeTypeId = $data['employeeTypeId'];

            $employeeResult = $employeeRepository->filterRecords($employeeId, $branchId, $departmentId, $designationId, $positionId, $serviceTypeId, -1, 1, $companyId, $employeeTypeId);

            $employeeList = [];
            foreach ($employeeResult as $employeeRow) {
                $employeeId = $employeeRow['EMPLOYEE_ID'];
                if ($trainingId != -1) {
                    $trainingAssignList = $trainingAssignRepo->getDetailByEmployeeID($employeeId, $trainingId);
                } else {
                    $trainingAssignList = null;
                }
                if ($trainingAssignList != null && $trainingAssignList['STATUS'] == 'E') {
                    $employeeRow['TRAINING_NAME'] = $trainingAssignList['TRAINING_NAME'];
                    $employeeRow['TRAINING_ID'] = $trainingAssignList['TRAINING_ID'];
                    $employeeRow['START_DATE'] = $trainingAssignList['START_DATE'];
                    $employeeRow['END_DATE'] = $trainingAssignList['END_DATE'];
                    $employeeRow['INSTITUTE_NAME'] = $trainingAssignList['INSTITUTE_NAME'];
                    $employeeRow['LOCATION'] = $trainingAssignList['LOCATION'];
                } else {
                    $employeeRow['TRAINING_NAME'] = "";
                    $employeeRow['TRAINING_ID'] = "";
                    $employeeRow['START_DATE'] = "";
                    $employeeRow['END_DATE'] = "";
                    $employeeRow['INSTITUTE_NAME'] = "";
                    $employeeRow['LOCATION'] = "";
                }
                array_push($employeeList, $employeeRow);
            }

            return new JsonModel(['success' => true, 'data' => $employeeList, 'message' => null]);
        } catch (Exception $e) {
            return new JsonModel(['success' => false, 'data' => null, 'message' => $e->getMessage()]);
        }
    }

}
