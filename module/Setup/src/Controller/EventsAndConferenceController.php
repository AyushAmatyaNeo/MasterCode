<?php

namespace Setup\Controller;

use Application\Custom\CustomViewModel;
use Application\Helper\ACLHelper;
use Application\Helper\EntityHelper;
use Application\Helper\Helper;
use Exception;
use Setup\Form\EventsForm;
use Setup\Model\Company;
use Setup\Model\Institute;
use Setup\Model\Events;
use Setup\Repository\EventsRepository;
use Zend\Authentication\Storage\StorageInterface;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Form\Annotation\AnnotationBuilder;
use Zend\Mvc\Controller\AbstractActionController;

class EventsAndConferenceController extends AbstractActionController {

    private $form;
    private $adapter;
    private $employeeId;
    private $repository;
    private $storageData;
    private $acl;

    const EVENT_TYPES = [
        'CP' => 'Personal',
        'CC' => 'Company Contribution'
    ];

    public function __construct(AdapterInterface $adapter, StorageInterface $storage) {
        $this->adapter = $adapter;
        $this->repository = new EventsRepository($adapter);
        $this->storageData = $storage->read();
        $this->employeeId = $this->storageData['employee_id'];
        $this->acl = $this->storageData['acl'];
    }

    public function initializeForm() {
        $builder = new AnnotationBuilder();
        $form = new EventsForm();
        $this->form = $builder->createForm($form);
    }

    public function indexAction() {
        $request = $this->getRequest();
        if ($request->isPost()) {
            try {
                $result = $this->repository->fetchAll();
                $eventList = Helper::extractDbData($result);
                        // echo '<pre>';print_r($eventList);die;
                return new CustomViewModel(['success' => true, 'data' => $eventList, 'error' => '']);
            } catch (Exception $e) {
                return new CustomViewModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
            }
        }
        return Helper::addFlashMessagesToArray($this, ['acl' => $this->acl]);
    }

    public function addAction() {
        
        ACLHelper::checkFor(ACLHelper::ADD, $this->acl, $this);
        $this->initializeForm();
        $request = $this->getRequest();

        if ($request->isPost()) {
            $eventsModel = new Events();
            $this->form->setData($request->getPost());
            if ($this->form->isValid()) {
                $eventsModel->exchangeArrayFromForm($this->form->getData());
                                                // echo '<pre>';print_r($this->form->setData($request->getPost()));die;
                $eventsModel->eventId = ((int) Helper::getMaxId($this->adapter, Events::TABLE_NAME, Events::EVENT_ID)) + 1;
                $eventsModel->createdBy = $this->employeeId;
                $eventsModel->createdDate = Helper::getcurrentExpressionDate();
                $eventsModel->status = 'E';
                $this->repository->add($eventsModel);
                $this->flashmessenger()->addMessage("Events Successfully added!!!");
                return $this->redirect()->toRoute('events');
            }
        }
        return Helper::addFlashMessagesToArray($this, [
                    'form' => $this->form,
                    'instituteNameList' => EntityHelper::getTableKVListWithSortOption($this->adapter, Institute::TABLE_NAME, Institute::INSTITUTE_ID, [Institute::INSTITUTE_NAME], [Institute::STATUS => 'E'], Institute::INSTITUTE_NAME, "ASC", null, [null => '---'], true),
                    'companies' => EntityHelper::getTableKVListWithSortOption($this->adapter, Company::TABLE_NAME, Company::COMPANY_ID, [Company::COMPANY_NAME], ["STATUS" => "E"], Company::COMPANY_NAME, "ASC", null, [null => '---'], true),
                    'eventTypeList' => self::EVENT_TYPES,
                    'customRenderer' => Helper::renderCustomView()
        ]);
    }

    public function editAction() {
        ACLHelper::checkFor(ACLHelper::UPDATE, $this->acl, $this);
        $id = (int) $this->params()->fromRoute("id");
        if ($id === 0) {
            return $this->redirect()->toRoute('events');
        }

        $this->initializeForm();
        $request = $this->getRequest();

        $eventsModel = new Events();
        if (!$request->isPost()) {
            $eventsModel->exchangeArrayFromDB($this->repository->fetchById($id));
            $this->form->bind($eventsModel);
        } else {

            $this->form->setData($request->getPost());
            // echo '<pre>';print_r($this->form->setData($request->getPost()));die;
            if ($this->form->isValid()) {
                $eventsModel->exchangeArrayFromForm($this->form->getData());
                $eventsModel->modifiedDate = Helper::getcurrentExpressionDate();
                $eventsModel->modifiedBy = $this->employeeId;
                $this->repository->edit($eventsModel, $id);
                $this->flashmessenger()->addMessage("Events Successfully Updated!!!");
                return $this->redirect()->toRoute("events");
            }
        }
        return Helper::addFlashMessagesToArray(
                        $this, [
                    'form' => $this->form,
                    'id' => $id,
                    'instituteNameList' => EntityHelper::getTableKVListWithSortOption($this->adapter, Institute::TABLE_NAME, Institute::INSTITUTE_ID, [Institute::INSTITUTE_NAME], [Institute::STATUS => 'E'], Institute::INSTITUTE_NAME, "ASC", null, [null => '---'], true),
                    'companies' => EntityHelper::getTableKVListWithSortOption($this->adapter, Company::TABLE_NAME, Company::COMPANY_ID, [Company::COMPANY_NAME], ["STATUS" => "E"], Company::COMPANY_NAME, "ASC", null, [null => '---'], true),
                    'eventTypeList' => self::EVENT_TYPES,
                    'customRenderer' => Helper::renderCustomView()
                        ]
        );
    }

    public function deleteAction() {
        if (!ACLHelper::checkFor(ACLHelper::DELETE, $this->acl, $this)) {
            return;
        };
        $id = (int) $this->params()->fromRoute("id");
        if (!$id) {
            return $this->redirect()->toRoute('events');
        }
        $this->repository->delete($id);
        $this->flashmessenger()->addMessage("Events Successfully Deleted!!!");
        return $this->redirect()->toRoute('events');
    }

}
