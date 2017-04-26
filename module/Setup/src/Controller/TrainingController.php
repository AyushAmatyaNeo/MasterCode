<?php

namespace Setup\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Application\Helper\Helper;
use Application\Helper\EntityHelper;
use Zend\Form\Annotation\AnnotationBuilder;
use Setup\Form\TrainingForm;
use Setup\Model\Training;
use Zend\Authentication\AuthenticationService;
use Setup\Repository\TrainingRepository;
use Setup\Model\Institute;
use Zend\Db\Adapter\AdapterInterface;
use Setup\Model\Company;

class TrainingController extends AbstractActionController {

    private $form;
    private $adapter;
    private $employeeId;
    private $repository;

    const TRAINING_TYPES = [
        'CP' => 'Personal',
        'CC' => 'Company Contribution'
    ];

    public function __construct(AdapterInterface $adapter) {
        $this->adapter = $adapter;
        $this->repository = new TrainingRepository($adapter);
        $auth = new AuthenticationService();
        $this->employeeId = $auth->getStorage()->read()['employee_id'];
    }

    public function initializeForm() {
        $builder = new AnnotationBuilder();
        $form = new TrainingForm();
        $this->form = $builder->createForm($form);
    }

    public function indexAction() {
        $list = $this->repository->fetchAll();
        return Helper::addFlashMessagesToArray($this, ['list' => $list]);
    }

    public function addAction() {
        $this->initializeForm();
        $request = $this->getRequest();


        if ($request->isPost()) {
            $trainingModel = new Training();
            $this->form->setData($request->getPost());
            if ($this->form->isValid()) {
                $trainingModel->exchangeArrayFromForm($this->form->getData());
                $trainingModel->trainingId = ((int) Helper::getMaxId($this->adapter, Training::TABLE_NAME, Training::TRAINING_ID)) + 1;
                $trainingModel->createdBy = $this->employeeId;
                $trainingModel->createdDate = Helper::getcurrentExpressionDate();
                $trainingModel->status = 'E';

                $this->repository->add($trainingModel);
                $this->flashmessenger()->addMessage("Training Successfully added!!!");
                return $this->redirect()->toRoute('training');
            }
        }
        return Helper::addFlashMessagesToArray($this, [
                    'form' => $this->form,
                    'instituteNameList' => EntityHelper::getTableKVListWithSortOption($this->adapter, Institute::TABLE_NAME, Institute::INSTITUTE_ID, [Institute::INSTITUTE_NAME], [Institute::STATUS => 'E'], Institute::INSTITUTE_NAME, "ASC", null, true, true),
                    'companies' => EntityHelper::getTableKVListWithSortOption($this->adapter, Company::TABLE_NAME, Company::COMPANY_ID, [Company::COMPANY_NAME], ["STATUS" => "E"], Company::COMPANY_NAME, "ASC", null, false, true),
                    'trainingTypeList' => self::TRAINING_TYPES
        ]);
    }

    public function editAction() {
        $id = (int) $this->params()->fromRoute("id");
        if ($id === 0) {
            return $this->redirect()->toRoute('training');
        }

        $this->initializeForm();
        $request = $this->getRequest();

        $trainingModel = new Training();
        if (!$request->isPost()) {
            $trainingModel->exchangeArrayFromDB($this->repository->fetchById($id));
            $this->form->bind($trainingModel);
        } else {

            $this->form->setData($request->getPost());
            if ($this->form->isValid()) {
                $trainingModel->exchangeArrayFromForm($this->form->getData());
                $trainingModel->modifiedDate = Helper::getcurrentExpressionDate();
                $trainingModel->modifiedBy = $this->employeeId;
                $this->repository->edit($trainingModel, $id);
                $this->flashmessenger()->addMessage("Training Successfully Updated!!!");
                return $this->redirect()->toRoute("training");
            }
        }
        return Helper::addFlashMessagesToArray(
                        $this, [
                    'form' => $this->form,
                    'id' => $id,
                    'instituteNameList' => EntityHelper::getTableKVListWithSortOption($this->adapter, Institute::TABLE_NAME, Institute::INSTITUTE_ID, [Institute::INSTITUTE_NAME], [Institute::STATUS => 'E'], Institute::INSTITUTE_NAME, "ASC", null, true, true),
                    'companies' => EntityHelper::getTableKVListWithSortOption($this->adapter, Company::TABLE_NAME, Company::COMPANY_ID, [Company::COMPANY_NAME], ["STATUS" => "E"], Company::COMPANY_NAME, "ASC", null, false, true),
                    'trainingTypeList' => self::TRAINING_TYPES
                        ]
        );
    }

    public function deleteAction() {
        $id = (int) $this->params()->fromRoute("id");
        if (!$id) {
            return $this->redirect()->toRoute('training');
        }
        $this->repository->delete($id);
        $this->flashmessenger()->addMessage("Training Successfully Deleted!!!");
        return $this->redirect()->toRoute('training');
    }

}
