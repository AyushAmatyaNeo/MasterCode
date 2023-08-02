<?php

namespace System\Controller;

use Application\Helper\EntityHelper;
use Application\Helper\Helper;
use Exception;
use System\Form\RoleSetupForm;
use System\Model\RoleControl;
use System\Model\RoleSetup;
use System\Repository\RoleControlRepository;
use System\Repository\RoleSetupRepository;
use Zend\Authentication\Storage\StorageInterface;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Form\Annotation\AnnotationBuilder;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\JsonModel;

class RoleSetupController extends AbstractActionController {

    private $form;
    private $repository;
    private $adapter;
    private $employeeId;
    private $storageData;
    private $acl;

    public function __construct(AdapterInterface $adapter, StorageInterface $storage) {
        $this->repository = new RoleSetupRepository($adapter);
        $this->adapter = $adapter;
        $this->storageData = $storage->read();
        $this->employeeId = $this->storageData['employee_id'];
        $this->acl = $this->storageData['acl'];
    }

    public function initializeForm() {
        $roleSetupForm = new RoleSetupForm();
        $builder = new AnnotationBuilder();
        $this->form = $builder->createForm($roleSetupForm);
    }

    public function indexAction() {
        $request = $this->getRequest();
        if ($request->isPost()) {
            try {
                $result = $this->repository->fetchAll();
                $list = Helper::extractDbData($result);
                return new JsonModel(['success' => true, 'data' => $list, 'error' => '']);
            } catch (Exception $e) {
                return new JsonModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
            }
        }
        return Helper::addFlashMessagesToArray($this, [
                    'acl' => $this->acl
        ]);
    }

    public function addAction() {
        $request = $this->getRequest();
        $this->initializeForm();

        if ($request->isPost()) {
            $this->form->setData($request->getPost());
            if ($this->form->isValid()) {
                $roleSetup = new RoleSetup();
                
                
                $roleSetup->exchangeArrayFromForm($this->form->getData());
                $roleSetup->roleId = ((int) Helper::getMaxId($this->adapter, RoleSetup::TABLE_NAME, RoleSetup::ROLE_ID)) + 1;
                $roleSetup->createdDt = Helper::getcurrentExpressionDate();
                $roleSetup->createdBy = $this->employeeId;
                $roleSetup->status = 'E';

                //to insert data into roleControl start
                $roleControlModel = new RoleControl();
                $roleControlRepo = new RoleControlRepository($this->adapter);
                $roleControlModel->roleId = $roleSetup->roleId;
                foreach ($roleSetup->control as $value) {
                    $roleControlModel->control = $value;
                    foreach($request->getPost('selectOptions'.$value) as $val){
                        $roleControlModel->val = $val;
                        $roleControlRepo->add($roleControlModel);
                    }
                }
                //to insert data into roleControl end
                $roleSetup->control = implode(',', $roleSetup->control);
                if($roleSetup->control == null){ $roleSetup->control = 'F'; }
                $this->repository->add($roleSetup);

                $this->flashmessenger()->addMessage("Role Successfully Added!!!");
                return $this->redirect()->toRoute("rolesetup");
            }
        }
        return Helper::addFlashMessagesToArray($this, [
                    'form' => $this->form,
                    'customRenderer' => Helper::renderCustomView(),
                    'searchValues' => EntityHelper::getSearchData($this->adapter)
        ]);
    }

    public function editAction() {
        $id = (int) $this->params()->fromRoute("id");
        $this->initializeForm();
        $request = $this->getRequest();

        $roleSetup = new RoleSetup();
        $roleControlRepo = new RoleControlRepository($this->adapter);
        if (!$request->isPost()) {
            $roleSetup->exchangeArrayFromDB($this->repository->fetchById($id)->getArrayCopy());
            $this->form->bind($roleSetup);
        } else {
            $this->form->setData($request->getPost());
            if ($this->form->isValid()) {
                $roleSetup->exchangeArrayFromForm($this->form->getData());
                //to insert data into roleControl start
                $roleControlModel = new RoleControl();
                $roleControlModel->roleId = $id;
                $roleControlRepo->delete($id);
                foreach ($roleSetup->control as $value) {
                    $roleControlModel->control = $value;
                    foreach($request->getPost('selectOptions'.$value) as $val){
                        $roleControlModel->val = $val;
                        $roleControlRepo->add($roleControlModel);
                    }
                }
                //to insert data into roleControl end
                $roleSetup->modifiedDt = Helper::getcurrentExpressionDate();
                $roleSetup->modifiedBy = $this->employeeId;
                unset($roleSetup->createdDt);
                unset($roleSetup->roleId);
                unset($roleSetup->status);
                $roleSetup->control = implode(',', $roleSetup->control);
                if($roleSetup->control == null){ $roleSetup->control = 'F'; }
                $this->repository->edit($roleSetup, $id);
                $this->flashmessenger()->addMessage("Role Successfully Updated!!!");
                return $this->redirect()->toRoute("rolesetup");
            }
        }
        $controls = explode(',',$this->repository->fetchById($id)['CONTROL']);
        return Helper::addFlashMessagesToArray($this, [
                    'form' => $this->form,
                    'id' => $id,
                    'customRenderer' => Helper::renderCustomView(),
                    'searchValues' => EntityHelper::getSearchData($this->adapter),
                    'selectedValues' => $roleControlRepo->fetchById($id),
                    'controls' => $controls
        ]);
    }

    public function deleteAction() {
        $id = (int) $this->params()->fromRoute("id");

        if (!$id) {
            return $this->redirect()->toRoute('rolesetup');
        }
        $this->repository->delete($id);
        $this->flashmessenger()->addMessage("Role Successfully Deleted!!!");
        return $this->redirect()->toRoute('rolesetup');
    }

    public function fetchRuleControls() {
        
    }

}
