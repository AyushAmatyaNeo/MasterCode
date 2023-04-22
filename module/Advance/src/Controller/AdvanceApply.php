<?php

namespace Advance\Controller;

use Advance\Form\AdvanceRequestForm;
use Advance\Model\AdvanceRequestModel;
use Advance\Model\AdvanceSetupModel;
use Advance\Repository\AdvanceRequestRepository;
use Application\Controller\HrisController;
use Application\Helper\EntityHelper;
use Application\Helper\Helper;
use Exception;
use Notification\Controller\HeadNotification;
use Notification\Model\NotificationEvents;
use Setup\Model\HrEmployees;
use Zend\Authentication\Storage\StorageInterface;
use Zend\Db\Adapter\AdapterInterface;
use Zend\View\Model\JsonModel;

class AdvanceApply extends HrisController {

    public function __construct(AdapterInterface $adapter, StorageInterface $storage) {
        parent::__construct($adapter, $storage);
        $this->initializeRepository(AdvanceRequestRepository::class);
        $this->initializeForm(AdvanceRequestForm::class);
    }

    public function indexAction() {
        return $this->redirect()->toRoute("advanceStatus");
    }

    public function addAction() {
        $request = $this->getRequest();

        if ($request->isPost()) {
            $postData = $request->getPost();
            $this->form->setData($postData);


            if ($this->form->isValid()) {
                $advanceRequestModel = new AdvanceRequestModel();
                $advanceRequestModel->exchangeArrayFromForm($this->form->getData());

                $advanceRequestModel->deductionType = $postData['deductionType'];
                $advanceRequestModel->advanceRequestId = (int) Helper::getMaxId($this->adapter, AdvanceRequestModel::TABLE_NAME, AdvanceRequestModel::ADVANCE_REQUEST_ID) + 1;
                $advanceRequestModel->status = "RQ";
                // echo '<pre>';print_r($advanceRequestModel);die;

                $this->repository->add($advanceRequestModel);
                $this->flashmessenger()->addMessage("Advance Request Successfully added!!!");
                try {
                    HeadNotification::pushNotification(NotificationEvents::ADVANCE_APPLIED, $advanceRequestModel, $this->adapter, $this);
                } catch (Exception $e) {
                    $this->flashmessenger()->addMessage($e->getMessage());
                }

                return $this->redirect()->toRoute("advanceStatus");
            }
        }
        $advanceRepo = new AdvanceRequestRepository($this->adapter);
        $advanceList = $advanceRepo->fetchAvailableAdvacenList($this->employeeId);

        $employeeListWithCode= EntityHelper::rawQueryResult($this->adapter, "SELECT EMPLOYEE_ID,EMPLOYEE_CODE||'-'||FULL_NAME AS FULL_NAME,SALARY FROM HRIS_EMPLOYEES WHERE STATUS='E' AND RETIRED_FLAG='N'"); 
        return Helper::addFlashMessagesToArray($this, [
                    'form' => $this->form,
                    'advance' => $advanceList,
                    // 'advance' => EntityHelper::getTableList($this->adapter, AdvanceSetupModel::TABLE_NAME, ['*'], [AdvanceSetupModel::STATUS => 'E']),
                    'customRenderer' => Helper::renderCustomView(),
                    'employeeList' => Helper::extractDbData($employeeListWithCode),
        ]);
    }

    public function pullEmployeeAdvanceAction() {
        try {
            $request = $this->getRequest();
            $employeeId = $request->getPost('employeeId');
            $advanceList = $this->repository->fetchAvailableAdvacenList($employeeId);
            return new JsonModel(['success' => true, 'data' => $advanceList, 'error' => '']);
        } catch (Exception $e) {
            return new JsonModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
        }
    }

}
