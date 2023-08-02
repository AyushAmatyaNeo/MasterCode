<?php

namespace Setup\Controller;

use Application\Controller\HrisController;
use Application\Helper\ACLHelper;
use Application\Helper\Helper;
use Exception;
use Setup\Form\LocationForm;
use Setup\Model\Location;
use Setup\Repository\LocationRepository;
use Zend\Authentication\Storage\StorageInterface;
use Zend\Db\Adapter\AdapterInterface;
use Zend\View\Model\JsonModel;
use Zend\View\Model\ViewModel;

class LocationController extends HrisController {

    public function __construct(AdapterInterface $adapter, StorageInterface $storage) {
        parent::__construct($adapter, $storage);
        $this->initializeRepository(LocationRepository::class);
        $this->initializeForm(LocationForm::class);
    }

    public function indexAction() {
        $request = $this->getRequest();
        if ($request->isPost()) {
            try {
                $result = $this->repository->fetchAll();
                $locationList = iterator_to_array($result, FALSE);
                return new JsonModel(['success' => true, 'data' => $locationList, 'error' => '']);
            } catch (Exception $e) {
                return new JsonModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
            }
        }
        return $this->stickFlashMessagesTo([
                    'acl' => $this->acl
        ]);
    }

    private function prepareForm($id = 0) {
        $locationList = iterator_to_array($this->repository->fetchParentList($id), FALSE);
        $parentLocationId = $this->form->get('parentLocationId');
        $parentLocationId->setValueOptions($this->listValueToKV($locationList, "LOCATION_ID", "LOCATION_EDESC"));
    }

    public function addAction() {
        $this->prepareForm();
        $request = $this->getRequest();
        if ($request->isPost()) {
            $this->form->setData($request->getPost());
            if ($this->form->isValid()) {
                $location = new Location();
                $location->exchangeArrayFromForm($this->form->getData());
                $location->createdDt = Helper::getcurrentExpressionDate();
                $location->createdBy = $this->employeeId;
                $location->locationId = ((int) Helper::getMaxId($this->adapter, "HRIS_LOCATIONS", "LOCATION_ID")) + 1;
                $location->status = 'E';
                $this->repository->add($location);
                $this->flashmessenger()->addMessage("Location Successfully added.");
                return $this->redirect()->toRoute("location");
            }
        }

        return $this->stickFlashMessagesTo([
                    'form' => $this->form,
                    'customRender' => Helper::renderCustomView(),]);
    }

    public function editAction() {
        $id = (int) $this->params()->fromRoute("id");
        if ($id === 0) {
            return $this->redirect()->toRoute('location');
        }
        $this->prepareForm($id);
        $request = $this->getRequest();
        $location = new Location();
        if ($request->isPost()) {
            $this->form->setData($request->getPost());
            if ($this->form->isValid()) {

                $location->exchangeArrayFromForm($this->form->getData());
                $location->modifiedDt = Helper::getcurrentExpressionDate();
                $location->modifiedBy = $this->employeeId;
                $this->repository->edit($location, $id);

                $this->flashmessenger()->addMessage("Location Successfully Updated.");
                return $this->redirect()->toRoute("location");
            }
        }
        $fetchData = $this->repository->fetchById($id)->getArrayCopy();
        $location->exchangeArrayFromDB($fetchData);
        $this->form->bind($location);
        return new ViewModel(Helper::addFlashMessagesToArray(
                        $this, [
                    'form' => $this->form,
                    'customRender' => Helper::renderCustomView(),
                    'id' => $id
                ])
        );
    }

    public function deleteAction() {
        if (!ACLHelper::checkFor(ACLHelper::DELETE, $this->acl, $this)) {
            return;
        };
        $id = (int) $this->params()->fromRoute("id");
        if (!$id) {
            return $this->redirect()->toRoute('location');
        }
        $this->repository->delete($id);
        $this->flashmessenger()->addMessage("Location Successfully Deleted!!!");
        return $this->redirect()->toRoute('location');
    }

}
