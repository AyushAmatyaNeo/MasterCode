<?php

namespace System\Controller;

use Application\Controller\HrisController;
use Application\Helper\EntityHelper;
use Application\Helper\Helper;
use Exception;
use System\Form\UserSetupForm;
use Notification\Model\NotificationEvents;
use Notification\Controller\HeadNotification;
use System\Model\UserSetup;
use System\Repository\UserSetupRepository;
use Zend\Authentication\Storage\StorageInterface;
use Zend\Db\Adapter\AdapterInterface;
use Zend\View\Model\JsonModel;

class UserSetupController extends HrisController {

    public function __construct(AdapterInterface $adapter, StorageInterface $storage) {
        parent::__construct($adapter, $storage);
        $this->initializeRepository(UserSetupRepository::class);
        $this->initializeForm(UserSetupForm::class);
    }

    public function indexAction() {
        $request = $this->getRequest();
        if ($request->isPost()) {
            try {
                $list = $this->repository->fetchFiltered($this->getACLFilter());
                return new JsonModel(['success' => true, 'data' => $list, 'error' => '']);
            } catch (Exception $e) {
                return new JsonModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
            }
        }
        return $this->stickFlashMessagesTo([
                    'acl' => $this->acl
        ]);
    }

    public function addAction() {
        $request = $this->getRequest();
        if ($request->isPost()) {
            $this->form->setData($request->getPost());
            if ($this->form->isValid()) {
                $userSetup = new UserSetup();
                $userSetup->exchangeArrayFromForm($this->form->getData());
                $userSetup->userId = ((int) Helper::getMaxId($this->adapter, UserSetup::TABLE_NAME, UserSetup::USER_ID)) + 1;
                $userSetup->createdDt = Helper::getcurrentExpressionDate();
                $userSetup->createdBy = $this->employeeId;

                $userSetup->password = Helper::encryptPassword($userSetup->password);


                $this->repository->add($userSetup);
                try {
                    HeadNotification::pushNotification(NotificationEvents::NEW_USER_CREATED, $userSetup, $this->adapter, $this);
                } catch (Exception $e) {
                    $this->flashmessenger()->addMessage($e->getMessage());
                }
                $this->flashmessenger()->addMessage("User Successfully Added!!!");
                return $this->redirect()->toRoute("usersetup");
            }
        }
        return $this->stickFlashMessagesTo([
                    'form' => $this->form,
                    'employeeList' => $this->repository->getEmployeeList(),
                    'roleList' => EntityHelper::getTableKVListWithSortOption($this->adapter, "HRIS_ROLES", "ROLE_ID", ["ROLE_NAME"], ["STATUS" => "E"], "ROLE_NAME", "ASC", null, false, true),
                    'customRenderer' => Helper::renderCustomView(),
        ]);
    }

    public function editAction() {
        $id = (int) $this->params()->fromRoute("id");
        $request = $this->getRequest();

        $userSetup = new UserSetup();
        $detail = $this->repository->fetchById($id);
        if ($request->isPost()) {
            $this->form->setData($request->getPost());
            if ($this->form->isValid()) {
                $userSetup->exchangeArrayFromForm($this->form->getData());
                $userSetup->modifiedDt = Helper::getcurrentExpressionDate();
                $userSetup->modifiedBy = $this->employeeId;
                $userSetup->password = Helper::encryptPassword($userSetup->password);
                // echo '<pre>';print_r($userSetup);die;
                $this->repository->edit($userSetup, $id);
                $this->flashmessenger()->addMessage("User Successfully Updated!!!");
                return $this->redirect()->toRoute("usersetup");
            }
        }
        $userSetup->exchangeArrayFromDB($detail);
        $this->form->bind($userSetup);
        // echo '<pre>';print_r($this->form->bind($userSetup));die;

        return $this->stickFlashMessagesTo([
                    'form' => $this->form,
                    'id' => $id,
                    'status' => $detail['STATUS'],
                    'passwordDtl' => $detail['PASSWORD'],
                    'employeeList' => $this->repository->getEmployeeList($detail['EMPLOYEE_ID']),
                    'roleList' => EntityHelper::getTableKVListWithSortOption($this->adapter, "HRIS_ROLES", "ROLE_ID", ["ROLE_NAME"], ["STATUS" => "E"], "ROLE_NAME", "ASC", null, false, true),
                    'customRenderer' => Helper::renderCustomView(),
        ]);
    }

    public function deleteAction() {
        $id = (int) $this->params()->fromRoute("id");

        if (!$id) {
            return $this->redirect()->toRoute('usersetup');
        }
        $this->repository->delete($id);
        $this->flashmessenger()->addMessage("User Successfully Deleted!!!");
        return $this->redirect()->toRoute('usersetup');
    }

    public function checkUserNameAction() {
        try {
            $request = $this->getRequest();
            $userName = $request->getPost('userName');
            $userId = $request->getPost('userId');
            $returnData = $this->repository->checkUserNameAvailability($userName,$userId);
                        // echo '<pre>';print_r($returnData);die;
            $availability = 'YES';
            if ($returnData) {
                $availability = 'NO';
            }
            return new JsonModel(['success' => true, 'data' => $availability, 'error' => '']);
        } catch (Exception $e) {
            return new JsonModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
        }
    }

}
