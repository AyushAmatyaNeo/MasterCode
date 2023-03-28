<?php

namespace SelfService\Controller;

use Application\Controller\HrisController;
use Application\Helper\Helper;
use Exception;
use Notification\Controller\HeadNotification;
use Notification\Model\NotificationEvents;
use SelfService\Form\TrainingRequestForm;
use SelfService\Model\TrainingRequest as TrainingRequestModel;
use SelfService\Repository\TrainingRequestRepository;
use Setup\Repository\TrainingRepository;
use Zend\Authentication\Storage\StorageInterface;
use Zend\Db\Adapter\AdapterInterface;
use Zend\View\Model\JsonModel;

class TrainingRequest extends HrisController {

    public function __construct(AdapterInterface $adapter, StorageInterface $storage) {
        parent::__construct($adapter, $storage);
        $this->initializeRepository(TrainingRequestRepository::class);
        $this->initializeForm(TrainingRequestForm::class);
    }

    public function indexAction() {
        $request = $this->getRequest();
        if ($request->isPost()) {
            try {
                $list = $this->repository->getAllByEmployeeId($this->employeeId);
                return new JsonModel(['success' => true, 'data' => $list, 'error' => '']);
            } catch (Exception $e) {
                return new JsonModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
            }
        }
        return $this->stickFlashMessagesTo([]);
    }

    public function prepareForm() {
        $trainingList = $this->getTrainingList($this->employeeId);

        $trainingId = $this->form->get('trainingId');
        $trainingId->setValueOptions($trainingList['trainingKVList']);

        $trainingType = $this->form->get('trainingType');
        $trainingType->setValueOptions($this->trainingTypes);
    }

    public function addAction() {
        $request = $this->getRequest();
        $model = new TrainingRequestModel();
        if ($request->isPost()) {
            $postData = $request->getPost();
            $this->form->setData($postData);
            if ($this->form->isValid()) {
                $model->exchangeArrayFromForm($this->form->getData());
                if ($postData['trainingId'] == -1) {
                    $model->trainingId = null;
                }
                $model->requestId = ((int) Helper::getMaxId($this->adapter, TrainingRequestModel::TABLE_NAME, TrainingRequestModel::REQUEST_ID)) + 1;
                $model->employeeId = $this->employeeId;
                $model->requestedDate = Helper::getcurrentExpressionDate();
                $model->status = 'RQ';
                $this->repository->add($model);
                $this->flashmessenger()->addMessage("Training Request Successfully added!!!");
                try {
                    HeadNotification::pushNotification(NotificationEvents::TRAINING_APPLIED, $model, $this->adapter, $this);
                } catch (Exception $e) {
                    $this->flashmessenger()->addMessage($e->getMessage());
                }
                return $this->redirect()->toRoute("trainingRequest");
            }
        }
        $this->prepareForm();
        $trainings = $this->getTrainingList($this->employeeId);
        // echo '<pre>';print_r($trainings);die;
        return Helper::addFlashMessagesToArray($this, [
                    'form' => $this->form,
                    'trainingList' => $trainings['trainingList'],
                    'customRenderer' => Helper::renderCustomView()
        ]);
    }

    public function viewAction() {
        $id = (int) $this->params()->fromRoute('id');
        if ($id === 0) {
            return $this->redirect()->toRoute("trainingRequest");
        }
        $detail = $this->repository->fetchById($id);
        return Helper::addFlashMessagesToArray($this, [
                    'form' => $this->form,
                    'detail' => $detail,
                    'recommender' => $detail['RECOMMENDED_BY_NAME'] == null ? $detail['RECOMMENDER_NAME'] : $detail['RECOMMENDED_BY_NAME'],
                    'approver' => $detail['APPROVED_BY_NAME'] == null ? $detail['APPROVER_NAME'] : $detail['APPROVED_BY_NAME'],
        ]);
    }

    public function deleteAction() {
        $id = (int) $this->params()->fromRoute("id");
        if (!$id) {
            return $this->redirect()->toRoute('trainingRequest');
        }
        $this->repository->delete($id);
        $this->flashmessenger()->addMessage("Training Request Successfully Cancelled.");
        return $this->redirect()->toRoute('trainingRequest');
    }

    private $trainingList = null;
    private $trainingTypes = array(
        'CP' => 'Personal',
        'CC' => 'Company Contribution'
    );

    private function getTrainingList($employeeId) {
        if ($this->trainingList === null) {
            $trainingRepo = new TrainingRepository($this->adapter);
            $trainingResult = $trainingRepo->selectAll($employeeId);
            $trainingList = [];
            $allTrainings = [];
            $trainingList[''] = "---";
            foreach ($trainingResult as $trainingRow) {
                $trainingList[$trainingRow['TRAINING_ID']] = $trainingRow['TRAINING_NAME'] . " (" . $trainingRow['START_DATE'] . " to " . $trainingRow['END_DATE'] . ")";
                $allTrainings[$trainingRow['TRAINING_ID']] = $trainingRow;
            }
            $this->trainingList = ['trainingKVList' => $trainingList, 'trainingList' => $allTrainings];
        }
        return $this->trainingList;
    }

}
