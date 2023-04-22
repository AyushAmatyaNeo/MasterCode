<?php

namespace SelfService\Controller;

use Application\Controller\HrisController;
use Application\Helper\Helper;
use Exception;
use Notification\Controller\HeadNotification;
use Notification\Model\NotificationEvents;
use SelfService\Form\OvertimeRequestForm;
use SelfService\Model\Overtime;
use SelfService\Model\OvertimeDetail;
use SelfService\Repository\OvertimeDetailRepository;
use SelfService\Repository\OvertimeRepository;
use Zend\Authentication\Storage\StorageInterface;
use Zend\Db\Adapter\AdapterInterface;
use Zend\View\Model\JsonModel;
use Zend\View\Model\ViewModel;

class OvertimeRequest extends HrisController {

    private $detailRepository;

    public function __construct(AdapterInterface $adapter, StorageInterface $storage) {
        parent::__construct($adapter, $storage);
        $this->initializeForm(OvertimeRequestForm::class);
        $this->repository = new OvertimeRepository($adapter);
        $this->detailRepository = new OvertimeDetailRepository($adapter);
    }

    public function overtimeDetail($overtimeId) {
        $rawList = $this->detailRepository->fetchByOvertimeId($overtimeId);
        // echo '<pre>';print_r($rawList );die;
        return Helper::extractDbData($rawList);
    }

    public function indexAction() {
        $request = $this->getRequest();
        if ($request->isPost()) {
            try {
                $rawList = $this->repository->getAllByEmployeeId($this->employeeId);
                $list = [];
                foreach ($rawList as $item) {
                    $detail = $this->overtimeDetail($item['OVERTIME_ID']);
                    $item['DETAILS'] = $detail;
                    array_push($list, $item);
                }
                // echo '<pre>';print_r($list);die;
                return new JsonModel(['success' => true, 'data' => $list, 'error' => '']);
            } catch (Exception $e) {
                return new JsonModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
            }
        }
        return new ViewModel();
    }

    public function addAction() {
        $request = $this->getRequest();

        $model = new Overtime();
        if ($request->isPost()) {
            $postData = $request->getPost();
            $this->form->setData($postData);
            if ($this->form->isValid()) {
                $postDataArray = $postData->getArrayCopy();
                $model->exchangeArrayFromForm($this->form->getData());
                $model->overtimeId = ((int) Helper::getMaxId($this->adapter, Overtime::TABLE_NAME, Overtime::OVERTIME_ID)) + 1;
                $model->employeeId = $this->employeeId;
                $model->requestedDate = Helper::getcurrentExpressionDate();
                $model->status = 'RQ';
                $model->allTotalHour = Helper::hoursToMinutes($postDataArray['allTotalHour']);
                $this->repository->add($model);

                $overtimeDetailModel = new OvertimeDetail();
                for ($i = 0; $i < sizeof($postDataArray['startTime']); $i++) {
                    $startTime = $postDataArray['startTime'][$i];
                    $endTime = $postDataArray['endTime'][$i];
                    $totalHour = $postDataArray['totalHour'][$i];
                    $overtimeDetailModel->overtimeId = $model->overtimeId;
                    $overtimeDetailModel->detailId = ((int) Helper::getMaxId($this->adapter, OvertimeDetail::TABLE_NAME, OvertimeDetail::DETAIL_ID)) + 1;
                    $overtimeDetailModel->startTime = Helper::getExpressionTime($startTime);
                    $overtimeDetailModel->endTime = Helper::getExpressionTime($endTime);
                    $overtimeDetailModel->totalHour = Helper::hoursToMinutes($totalHour);
                    $overtimeDetailModel->status = 'E';
                    $overtimeDetailModel->createdBy = $this->employeeId;
                    $overtimeDetailModel->createdDate = Helper::getcurrentExpressionDate();
                    $this->detailRepository->add($overtimeDetailModel);
                }
                $this->flashmessenger()->addMessage("Overtime Request Successfully added!!!");
                try {
                    HeadNotification::pushNotification(NotificationEvents::OVERTIME_APPLIED, $model, $this->adapter, $this);
                } catch (Exception $e) {
                    $this->flashmessenger()->addMessage($e->getMessage());
                }
                return $this->redirect()->toRoute("overtimeRequest");
            }
        }

        return Helper::addFlashMessagesToArray($this, [
                    'form' => $this->form,
                    'employeeId' => $this->employeeId
        ]);
    }

    public function deleteAction() {
        $id = (int) $this->params()->fromRoute("id");
        if (!$id) {
            return $this->redirect()->toRoute('overtimeRequest');
        }
        $this->repository->delete($id);
        $this->flashmessenger()->addMessage("Overtime Request Successfully Cancelled!!!");
        return $this->redirect()->toRoute('overtimeRequest');
    }

    public function viewAction() {
        $id = (int) $this->params()->fromRoute('id', 0);
        if ($id === 0) {
            return $this->redirect()->toRoute("overtimeRequest");
        }

        $overtimeModel = new Overtime();
        $detail = $this->repository->fetchById($id);

        $overtimeModel->exchangeArrayFromDB($detail);
        $this->form->bind($overtimeModel);

        $overtimeDetailResult = $this->detailRepository->fetchByOvertimeId($detail['OVERTIME_ID']);
        $overtimeDetails = Helper::extractDbData($overtimeDetailResult);
        // echo '<pre>';print_r($detail);die;
        return Helper::addFlashMessagesToArray($this, [
                    'form' => $this->form,
                    'employeeName' => $detail['FULL_NAME'],
                    'status' => $detail['STATUS'],
                    'statusDetail' => $detail['STATUS_DETAIL'],
                    'requestedDate' => $detail['REQUESTED_DATE'],
                    'recommender' => $detail['RECOMMENDED_BY_NAME'] ? $detail['RECOMMENDED_BY_NAME'] : $detail['RECOMMENDER_NAME'],
                    'approver' => $detail['APPROVED_BY_NAME'] ? $detail['APPROVED_BY_NAME'] : $detail['APPROVER_NAME'],
                    'overtimeDetails' => $overtimeDetails,
                    'totalHour' => $detail['TOTAL_HOUR_DETAIL']
        ]);
    }

    public function validateAttendanceAction(){
        $date = date_format(date_create($_POST['date']), "d-M-y");
        $employeeId = $_POST['employeeId'];
        $result = $this->detailRepository->getAttendanceOvertimeValidation($employeeId, $date);
        return new JSONModel(["validation" => $result["VALIDATION"]]);
    }

    public function editAction() {
        $id = (int) $this->params()->fromRoute("id");
        if ($id === 0) {
            return $this->redirect()->toRoute('overtimeRequest');
        }
        $request = $this->getRequest();
        $overtimeDetailModel = new OvertimeDetail();
        if ($request->isPost()) {
            $this->form->setData($request->getPost());
            if ($this->form->isValid()) {
                $overtimeDetailModel->exchangeArrayFromForm($this->form->getData());
                $overtimeDetailModel->modifiedDate = Helper::getcurrentExpressionDate();
                $overtimeDetailModel->status='RQ';
                $overtimeDetailModel->modifiedBy = $this->employeeId;
                $this->repository->edit($overtimeDetailModel, $id);
                $this->flashmessenger()->addMessage("Overtime Request Successfully Updated!!!");
                return $this->redirect()->toRoute("overtimeRequest");
            }
        }
        $overtimeModel = new Overtime();
        $detail = $this->repository->fetchById($id);

        $overtimeModel->exchangeArrayFromDB($detail);
        $this->form->bind($overtimeModel);

        $overtimeDetailResult = $this->detailRepository->fetchByOvertimeId($detail['OVERTIME_ID']);
        $overtimeDetails = Helper::extractDbData($overtimeDetailResult);
        // echo '<pre>';print_r($overtimeDetails);die;
        return Helper::addFlashMessagesToArray($this, [
            'form' => $this->form,
            'id'=>$id,
            'overtimeDetails' => $overtimeDetails,
            'totalHour' => $detail['TOTAL_HOUR_DETAIL']
]);
    }

    public function overtimeDetailsAction() {
        $request = $this->getRequest();
        if ($request->isPost()) {
            try {
                $postData=$request->getPost();
                // echo '<pre>';print_r($postData['overtimeId']);die;
                $overtimeDetailResult = $this->detailRepository->fetchByOvertimeId($postData['overtimeId']);
                return new JsonModel(['success' => true, 'data' => $overtimeDetailResult, 'error' => '']);
            } catch (Exception $e) {
                return new JsonModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
            }
        }
        return new ViewModel();
    }

}
