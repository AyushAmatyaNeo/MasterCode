<?php

namespace Advance\Controller;

use Advance\Form\AdvanceSetupForm;
use Advance\Model\AdvanceSetupModel;
use Advance\Repository\AdvanceSetupRepository;
use Application\Helper\Helper;
use Exception;
use Zend\Authentication\Storage\StorageInterface;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Form\Annotation\AnnotationBuilder;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\JsonModel;
use Zend\View\Model\ViewModel;

class AdvanceSetup extends AbstractActionController {

    private $form;
    private $repository;
    private $adapter;
    private $employeeId;
    private $storageData;
    private $acl;

    function __construct(AdapterInterface $adapter, StorageInterface $storage) {
        $this->repository = new AdvanceSetupRepository($adapter);
        $this->adapter = $adapter;
        $this->storageData = $storage->read();
        $this->employeeId = $this->storageData['employee_id'];
        $this->acl = $this->storageData['acl'];
    }

    private function getForm() {
        if (!$this->form) {
            $form = new AdvanceSetupForm();
            $builder = new AnnotationBuilder();
            $this->form = $builder->createForm($form);
        }
        return $this->form;
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
        $form = $this->getForm();

        if ($request->isPost()) {
            $form->setData($request->getPost());
            if ($form->isValid()) {
                $advanceSetupModel = new AdvanceSetupModel();
                $advanceSetupModel->exchangeArrayFromForm($form->getData());
                $advanceSetupModel->status = "E";
                $advanceSetupModel->advanceId = ((int) Helper::getMaxId($this->adapter, AdvanceSetupModel::TABLE_NAME, AdvanceSetupModel::ADVANCE_ID)) + 1;
                $advanceSetupModel->createdBy = $this->employeeId;
                $this->repository->add($advanceSetupModel);
                $this->flashmessenger()->addMessage("Advance added successfully.");
                return $this->redirect()->toRoute("advance-setup");
            }
        }

        return new ViewModel([
            'form' => $this->form,
            'customRenderer' => Helper::renderCustomView(),
        ]);
    }

    public function editAction() {
        $id = (int) $this->params()->fromRoute("id", -1);
        if ($id === -1) {
            return $this->redirect()->toRoute('advance-setup');
        }
        $request = $this->getRequest();
        $form = $this->getForm();

        if ($request->isPost()) {
            $form->setData($request->getPost());
            if ($form->isValid()) {
//                echo '<pre>';
//                print_r($request->getPost());
//                die();
                $advanceSetupModel = new AdvanceSetupModel();
                $advanceSetupModel->exchangeArrayFromForm($form->getData());
                $advanceSetupModel->modifiedBy = $this->employeeId;
                $advanceSetupModel->modifiedDate = Helper::getcurrentExpressionDate();
                $this->repository->edit($advanceSetupModel, $id);
                $this->flashmessenger()->addMessage("advance edited successfully.");
                return $this->redirect()->toRoute("advance-setup");
            }
        }

        $advanceSetupModel = new AdvanceSetupModel();
        $detail = $this->repository->fetchById($id)->getArrayCopy();
        $advanceSetupModel->exchangeArrayFromDB($detail);
        $form->bind($advanceSetupModel);
        return new ViewModel([
            'form' => $this->form,
            'customRenderer' => Helper::renderCustomView(),
            "id" => $id
        ]);
    }

}
