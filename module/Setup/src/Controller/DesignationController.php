<?php
namespace Setup\Controller;

use Application\Controller\HrisController;
use Application\Helper\Helper;
use Application\Model\HrisQuery;
use Exception;
use Setup\Form\DesignationForm;
use Setup\Model\Company;
use Setup\Model\Designation;
use Setup\Repository\DesignationRepository;
use Zend\Authentication\Storage\StorageInterface;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Db\Sql\Select;
use Zend\View\Model\JsonModel;

class DesignationController extends HrisController {

    public function __construct(AdapterInterface $adapter, StorageInterface $storage, DesignationRepository $repository) {
        parent::__construct($adapter, $storage);
        $this->repository = $repository;
        $this->initializeForm(DesignationForm::class);
    }

    public function indexAction() {
        $request = $this->getRequest();
        if ($request->isPost()) {
            try {
                $result = $this->repository->fetchAll();
                $designationList = Helper::extractDbData($result);
                return new JsonModel(['success' => true, 'data' => $designationList, 'error' => '']);
            } catch (Exception $e) {
                return new JsonModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
            }
        }
        return $this->stickFlashMessagesTo(['acl' => $this->acl]);
    }

    private function prepareForm($id = null) {
        $companyId = $this->form->get('companyId');
        $parentDesignation = $this->form->get("parentDesignation");

        $companyKV = HrisQuery::singleton()
            ->setAdapter($this->adapter)
            ->setTableName(Company::TABLE_NAME)
            ->setColumnList([Company::COMPANY_ID, Company::COMPANY_NAME])
            ->setWhere([Company::STATUS => 'E'])
            ->setKeyValue(Company::COMPANY_ID, Company::COMPANY_NAME)
            ->setIncludeEmptyRow(true)
            ->result();
        $depWhere = [Designation::STATUS => 'E'];
        if ($id != null) {
            $depWhere[] = Designation::DESIGNATION_ID . " != {$id}";
        }
        $designationKV = HrisQuery::singleton()
            ->setAdapter($this->adapter)
            ->setTableName(Designation::TABLE_NAME)
            ->setColumnList([Designation::DESIGNATION_ID, Designation::DESIGNATION_TITLE])
            ->setWhere($depWhere)
            ->setOrder([Designation::DESIGNATION_TITLE => Select::ORDER_ASCENDING])
            ->setKeyValue(Designation::DESIGNATION_ID, Designation::DESIGNATION_TITLE)
            ->setIncludeEmptyRow(true)
            ->result();
        $companyId->setValueOptions($companyKV);
        $parentDesignation->setValueOptions($designationKV);
    }

    public function addAction() {
        $request = $this->getRequest();
        if ($request->isPost()) {
            $this->form->setData($request->getPost());
            if ($this->form->isValid()) {
                $designation = new Designation();
                $designation->exchangeArrayFromForm($this->form->getData());
                $designation->createdDt = Helper::getcurrentExpressionDate();
                $designation->createdBy = $this->employeeId;
                $designation->designationId = ((int) Helper::getMaxId($this->adapter, "HRIS_DESIGNATIONS", "DESIGNATION_ID")) + 1;
                $designation->status = 'E';
                $this->repository->add($designation);

                $this->flashmessenger()->addMessage("Designation Successfully added!!!");
                return $this->redirect()->toRoute("designation");
            }
        }
        $this->prepareForm();
        return [
            'form' => $this->form,
            'customRender' => Helper::renderCustomView(),
        ];
    }

    public function editAction() {
        $id = (int) $this->params()->fromRoute("id");
        if ($id === 0) {
            return $this->redirect()->toRoute('designation');
        }
        $request = $this->getRequest();
        $designation = new Designation();
        if ($request->isPost()) {
            $this->form->setData($request->getPost());
            if ($this->form->isValid()) {
                $designation->exchangeArrayFromForm($this->form->getData());
                $designation->modifiedDt = Helper::getcurrentExpressionDate();
                $designation->modifiedBy = $this->employeeId;
                $this->repository->edit($designation, $id);

                $this->flashmessenger()->addMessage("Designation Successfully Updated!!!");
                return $this->redirect()->toRoute("designation");
            }
        }
        $fetchData = $this->repository->fetchById($id)->getArrayCopy();
        $designation->exchangeArrayFromDB($fetchData);
        $this->form->bind($designation);
        $this->prepareForm();
        return [
            'form' => $this->form,
            'id' => $id,
            'customRender' => Helper::renderCustomView(),
        ];
    }

    public function deleteAction() {
        $id = (int) $this->params()->fromRoute("id");
        if (!$id) {
            return $this->redirect()->toRoute('designation');
        }
        $this->repository->delete($id);
        $this->flashmessenger()->addMessage("Designation Successfully Deleted!!!");
        return $this->redirect()->toRoute('designation');
    }
}
