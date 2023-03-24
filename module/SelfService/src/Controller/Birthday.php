<?php

namespace SelfService\Controller;

use Application\Helper\Helper;
use Exception;
use Notification\Controller\HeadNotification;
use Notification\Model\NotificationEvents;
use SelfService\Model\BirthdayModel;
use SelfService\Repository\BirthdayRepository;
use Zend\Authentication\AuthenticationService;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Mvc\Controller\AbstractActionController;

class Birthday extends AbstractActionController {

    private $adapter;
    private $repository;
    private $employeeId;

    function __construct(AdapterInterface $adapter) {
        $this->adapter = $adapter;
        $this->repository = new BirthdayRepository($adapter);
        $auth = new AuthenticationService();
        $this->employeeId = $auth->getStorage()->read()['employee_id'];
    }

    public function indexAction() {
        $birthdays = $this->repository->getBirthdays();
        return Helper::addFlashMessagesToArray($this, [
                    'employeesBirthday' => $birthdays,
                    'currentEmployeeId' => $this->employeeId
        ]);
    }

    public function wishAction() {
        $id = (int) $this->params()->fromRoute("id");
        if (!$id) {
            return $this->redirect()->toRoute('birthday');
        }

        $empDetails = $this->repository->getBirthdayEmpDet($id);
		//print_r($empDetails);die;
        $wishEmpDetail = $this->repository->getBirthdayEmpDet($this->employeeId);
        $request = $this->getRequest();

        $birthdayMessage = $this->repository->getBirthdayMessage($id);

        if ($request->isPost()) {
            $birthdayModel = new BirthdayModel();
            $data = $request->getPost();
            $message = $data['birthdayMessage'];

            $birthdayModel->birthdayId = ((int) Helper::getMaxId($this->adapter, BirthdayModel::TABLE_NAME, BirthdayModel::BIRTHDAY_ID)) + 1;
            $birthdayModel->birthdayDate = $empDetails['BIRTH_DATE'];
            $birthdayModel->fromEmployee = $this->employeeId;
            $birthdayModel->toEmployee = $id;
            $birthdayModel->message = $message;
            $birthdayModel->createdDt = Helper::getcurrentExpressionDateTime();
            $birthdayModel->status = 'E';

            $this->repository->add($birthdayModel);
            try {
                HeadNotification::pushNotification(NotificationEvents::BIRTHDAY_WISHED, $birthdayModel, $this->adapter, $this);
            } catch (Exception $e) {
                
            }

            $this->flashmessenger()->addMessage("Birthday message created sucessfully");
            return $this->redirect()->toRoute("birthday", ['action' => 'wish', 'id' => $id]);
        }

        $messagePosted = $this->repository->checkMessagePosted($this->employeeId, $id);

        $showMessageField = true;
        if ($id == $this->employeeId) {
            $showMessageField = false;
        }
        if ($messagePosted['C'] > 0) {
            $showMessageField = false;
        }
        return Helper::addFlashMessagesToArray($this, [
                    'brithdayEmpDtl' => $empDetails,
                    'wishEmpDetail' => $wishEmpDetail,
                    'birthdayMessage' => $birthdayMessage,
                    'showMessageField' => $showMessageField,
        ]);
    }

}
