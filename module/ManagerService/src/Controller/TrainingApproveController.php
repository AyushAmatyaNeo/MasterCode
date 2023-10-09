<?php

namespace ManagerService\Controller;

use Application\Controller\HrisController;
use Application\Helper\EntityHelper;
use Application\Helper\Helper;
use Exception;
use ManagerService\Repository\TrainingApproveRepository;
use Notification\Controller\HeadNotification;
use Notification\Model\NotificationEvents;
use SelfService\Form\TrainingRequestForm;
use SelfService\Model\TrainingRequest;
use Setup\Repository\TrainingRepository;
use ManagerService\Repository\ManagerReportRepo;
use Zend\Authentication\Storage\StorageInterface;
use Zend\Db\Adapter\AdapterInterface;
use Zend\View\Model\JsonModel;

class TrainingApproveController extends HrisController
{

    public function __construct(AdapterInterface $adapter, StorageInterface $storage)
    {
        parent::__construct($adapter, $storage);
        $this->initializeRepository(TrainingApproveRepository::class);
        $this->initializeForm(TrainingRequestForm::class);
    }

    public function indexAction()
    {
        $request = $this->getRequest();
        if ($request->isPost()) {
            try {
                $search['userId'] = $this->employeeId;
                $rawList = $this->repository->getPendingList($search);
                $list = Helper::extractDbData($rawList);
                return new JsonModel(['success' => true, 'data' => $list, 'error' => '']);
            } catch (Exception $e) {
                return new JsonModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
            }
        }
        return $this->stickFlashMessagesTo([]);
    }

    public function viewAction()
    {
        $id = (int) $this->params()->fromRoute('id');
        $role = $this->params()->fromRoute('role');

        if ($id === 0) {
            return $this->redirect()->toRoute("trainingApprove");
        }
        $trainingRequestModel = new TrainingRequest();
        $request = $this->getRequest();

        $detail = $this->repository->fetchById($id);
        if ($request->isPost()) {
            $postedData = (array) $request->getPost();
            $action = $postedData['submit'];
            $this->makeDecision($id, $role, $action == 'Approve', $postedData[$role == 2 ? 'recommendedRemarks' : 'approvedRemarks'], true);
            return $this->redirect()->toRoute("trainingApprove");
        }
        $trainingRequestModel->exchangeArrayFromDB($detail);
        $this->form->bind($trainingRequestModel);
        return Helper::addFlashMessagesToArray($this, [
            'form' => $this->form,
            'id' => $id,
            'role' => $role,
            'detail' => $detail,
            'customRenderer' => Helper::renderCustomView()
        ]);
    }

    public function statusAction()
    {
        $request = $this->getRequest();
        if ($request->isPost()) {
            try {
                $searchQuery = $request->getPost();
                $searchQuery['userId'] = $this->employeeId;
                $list = $this->repository->getAllList((array) $searchQuery);
                return new JsonModel(['success' => true, 'data' => $list, 'error' => '']);
            } catch (Exception $e) {
                return new JsonModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
            }
        }
        $managerRepo = new ManagerReportRepo($this->adapter);
        $employee = $managerRepo->fetchAllEmployee($this->employeeId);
        $employees = [];
        foreach ($employee as $key => $value) {
            array_push($employees, ["id" => $key, "name" => $value]);
        }
        $statusSE = $this->getStatusSelectElement(['name' => 'status', 'id' => 'status', 'class' => 'form-control reset-field', 'label' => 'Status']);
        return $this->stickFlashMessagesTo([
            'status' => $statusSE,
            'employees' => $employees,
            'recomApproveId' => $this->employeeId,
            'searchValues' => EntityHelper::getSearchData($this->adapter),
        ]);
    }

    public function batchApproveRejectAction()
    {
        $request = $this->getRequest();
        try {
            $postData = $request->getPost();
            $this->makeDecision($postData['id'], $postData['role'], $postData['btnAction'] == "btnApprove");
            return new JsonModel(['success' => true, 'data' => null]);
        } catch (Exception $e) {
            return new JsonModel(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    private function makeDecision($id, $role, $approve, $remarks = null, $enableFlashNotification = false)
    {
        $notificationEvent = null;
        $message = null;
        $model = new TrainingRequest();
        $model->requestId = $id;
        switch ($role) {
            case 2:
                $model->recommendedRemarks = $remarks;
                $model->recommendedDate = Helper::getcurrentExpressionDate();
                $model->recommendedBy = $this->employeeId;
                $model->status = $approve ? "RC" : "R";
                $message = $approve ? "Training Request Recommended" : "Training Request Rejected";
                $notificationEvent = $approve ? NotificationEvents::TRAINING_RECOMMEND_ACCEPTED : NotificationEvents::TRAINING_RECOMMEND_REJECTED;
                break;
            case 4:
                $model->recommendedDate = Helper::getcurrentExpressionDate();
                $model->recommendedBy = $this->employeeId;
            case 3:
                $model->approvedRemarks = $remarks;
                $model->approvedDate = Helper::getcurrentExpressionDate();
                $model->approvedBy = $this->employeeId;
                $model->status = $approve ? "AP" : "R";
                $message = $approve ? "Training Request Approved" : "Training Request Rejected";
                $notificationEvent = $approve ? NotificationEvents::TRAINING_APPROVE_ACCEPTED : NotificationEvents::TRAINING_APPROVE_REJECTED;
                break;
        }
        $this->repository->edit($model, $id);
        if ($enableFlashNotification) {
            $this->flashmessenger()->addMessage($message);
        }
        try {
            HeadNotification::pushNotification($notificationEvent, $model, $this->adapter, $this);
        } catch (\Exception $e) {
            $this->flashmessenger()->addMessage($e->getMessage());
        }
    }

    private $trainingList = null;
    private $trainingTypes = array(
        'CP' => 'Personal',
        'CC' => 'Company Contribution'
    );

    private function getTrainingList($employeeId)
    {
        if ($this->trainingList === null) {
            $trainingRepo = new TrainingRepository($this->adapter);
            $trainingResult = $trainingRepo->selectAll($employeeId);
            $trainingList = [];
            $allTrainings = [];
            $trainingList[-1] = "---";
            foreach ($trainingResult as $trainingRow) {
                $trainingList[$trainingRow['TRAINING_ID']] = $trainingRow['TRAINING_NAME'] . " (" . $trainingRow['START_DATE'] . " to " . $trainingRow['END_DATE'] . ")";
                $allTrainings[$trainingRow['TRAINING_ID']] = $trainingRow;
            }
            $this->trainingList = ['trainingKVList' => $trainingList, 'trainingList' => $allTrainings];
        }
        return $this->trainingList;
    }
}
