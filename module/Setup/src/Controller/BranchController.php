<?php

namespace Setup\Controller;

use Application\Custom\CustomViewModel;
use Application\Helper\ACLHelper;
use Application\Helper\Helper;
use Application\Model\HrisQuery;
use Exception;
use Setup\Form\BranchForm;
use Setup\Model\Branch;
use Setup\Model\Company;
use Setup\Repository\BranchRepository;
use Zend\Authentication\Storage\StorageInterface;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Form\Annotation\AnnotationBuilder;
use Zend\Mvc\Controller\AbstractActionController;
use Setup\Model\HrEmployees;
use Application\Helper\EntityHelper;

class BranchController extends AbstractActionController
{

    private $form;
    private $repository;
    private $adapter;
    private $employeeId;
    private $storageData;
    private $acl;

    function __construct(AdapterInterface $adapter, StorageInterface $storage)
    {
        $this->adapter = $adapter;
        $this->repository = new BranchRepository($adapter);
        $this->storageData = $storage->read();
        $this->employeeId = $this->storageData['employee_id'];
        $this->acl = $this->storageData['acl'];
    }

    public function initializeForm()
    {
        $branchForm = new BranchForm();
        $builder = new AnnotationBuilder();
        if (!$this->form) {
            $this->form = $builder->createForm($branchForm);
        }
    }

    public function indexAction()
    {
        $request = $this->getRequest();
        if ($request->isPost()) {
            try {
                //                $result = $this->repository->fetchAllWithCompany();
                $result = $this->repository->fetchAllWithBranchManager(); //use where BranchManager is required
                $branchList = Helper::extractDbData($result);
                return new CustomViewModel(['success' => true, 'data' => $branchList, 'error' => '']);
            } catch (Exception $e) {
                return new CustomViewModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
            }
        }
        return Helper::addFlashMessagesToArray($this, ['acl' => $this->acl]);
    }

    public function addAction()
    {
        ACLHelper::checkFor(ACLHelper::ADD, $this->acl, $this);
        $this->initializeForm();
        $request = $this->getRequest();
        if ($request->isPost()) {
            $this->form->setData($request->getPost());
            if ($this->form->isValid()) {
                $branch = new Branch();
                $branch->exchangeArrayFromForm($this->form->getData());
                $branch->branchId = ((int) Helper::getMaxId($this->adapter, "HRIS_BRANCHES", "BRANCH_ID")) + 1;
                $branch->createdDt = Helper::getcurrentExpressionDate();
                $branch->createdBy = $this->employeeId;
                $branch->status = 'E';

                $this->repository->add($branch);

                $this->flashmessenger()->addMessage("Branch Successfully Added!!!");
                return $this->redirect()->toRoute("branch");
            }
        }
        $countryKV = HrisQuery::singleton()
            ->setAdapter($this->adapter)
            ->setTableName("HRIS_COUNTRIES")
            ->setColumnList(["COUNTRY_ID", "COUNTRY_NAME"])
            ->setKeyValue("COUNTRY_ID", "COUNTRY_NAME")
            ->setIncludeEmptyRow(true)
            ->result();
        $companyKV = HrisQuery::singleton()
            ->setAdapter($this->adapter)
            ->setTableName(Company::TABLE_NAME)
            ->setColumnList([Company::COMPANY_ID, Company::COMPANY_NAME])
            ->setWhere([Company::STATUS => 'E'])
            ->setKeyValue(Company::COMPANY_ID, Company::COMPANY_NAME)
            ->setIncludeEmptyRow(true)
            ->result();
        $employeeKV = HrisQuery::singleton()
            ->setAdapter($this->adapter)
            ->setTableName(HrEmployees::TABLE_NAME)
            ->setColumnList([HrEmployees::EMPLOYEE_ID, HrEmployees::FULL_NAME])
            ->setWhere([HrEmployees::STATUS => 'E'])
            ->setKeyValue(HrEmployees::EMPLOYEE_ID, HrEmployees::FULL_NAME)
            ->setIncludeEmptyRow(TRUE)
            ->result();

        $provinces = EntityHelper::getTableKVListWithSortOption($this->adapter, "HRIS_PROVINCES", "PROVINCE_ID", ["PROVINCE_NAME"], null, "PROVINCE_ID", "ASC", "-", true, true, null);

        return Helper::addFlashMessagesToArray(
            $this,
            [
                'form' => $this->form,
                'countries' => $countryKV,
                'companies' => $companyKV,
                'employees' => $employeeKV,
                'customRenderer' => Helper::renderCustomView(),
                'provinces' => $provinces
            ]
        );
    }

    public function editAction()
    {
        ACLHelper::checkFor(ACLHelper::UPDATE, $this->acl, $this);
        $id = (int) $this->params()->fromRoute("id");
        $this->initializeForm();
        $request = $this->getRequest();

        $branch = new Branch();
        if (!$request->isPost()) {
            $branch->exchangeArrayFromDB($this->repository->fetchById($id)->getArrayCopy());
            $this->form->bind($branch);
        } else {
            $modifiedDt = date('d-M-y');
            $this->form->setData($request->getPost());
            if ($this->form->isValid()) {
                $branch->exchangeArrayFromForm($this->form->getData());
                $branch->modifiedDt = Helper::getcurrentExpressionDate();
                $branch->modifiedBy = $this->employeeId;
                $this->repository->edit($branch, $id);
                $this->flashmessenger()->addMessage("Branch Successfully Updated!!!");
                return $this->redirect()->toRoute("branch");
            }
        }
        $countryKV = HrisQuery::singleton()
            ->setAdapter($this->adapter)
            ->setTableName("HRIS_COUNTRIES")
            ->setColumnList(["COUNTRY_ID", "COUNTRY_NAME"])
            ->setKeyValue("COUNTRY_ID", "COUNTRY_NAME")
            ->setIncludeEmptyRow(true)
            ->result();
        $companyKV = HrisQuery::singleton()
            ->setAdapter($this->adapter)
            ->setTableName(Company::TABLE_NAME)
            ->setColumnList([Company::COMPANY_ID, Company::COMPANY_NAME])
            ->setWhere([Company::STATUS => 'E'])
            ->setKeyValue(Company::COMPANY_ID, Company::COMPANY_NAME)
            ->setIncludeEmptyRow(true)
            ->result();
        $employeeKV = HrisQuery::singleton()
            ->setAdapter($this->adapter)
            ->setTableName(HrEmployees::TABLE_NAME)
            ->setColumnList([HrEmployees::EMPLOYEE_ID, HrEmployees::FULL_NAME])
            ->setWhere([HrEmployees::STATUS => 'E'])
            ->setKeyValue(HrEmployees::EMPLOYEE_ID, HrEmployees::FULL_NAME)
            ->setIncludeEmptyRow(true)
            ->result();

        $provinces = EntityHelper::getTableKVListWithSortOption($this->adapter, "HRIS_PROVINCES", "PROVINCE_ID", ["PROVINCE_NAME"], null, "PROVINCE_ID", "ASC", "-", true, true, null);

        return Helper::addFlashMessagesToArray($this, [
            'form' => $this->form,
            'id' => $id,
            'countries' => $countryKV,
            'companies' => $companyKV,
            'employees' => $employeeKV,
            'customRenderer' => Helper::renderCustomView(),
            'provinces' => $provinces
        ]);
    }

    public function deleteAction()
    {
        if (!ACLHelper::checkFor(ACLHelper::DELETE, $this->acl, $this)) {
            return;
        };
        $id = (int) $this->params()->fromRoute("id");
        if (!$id) {
            return $this->redirect()->toRoute('branch');
        }
        $this->repository->deleteBranch($id, $this->employeeId);
        $this->flashmessenger()->addMessage("Branch Successfully Deleted!!!");
        return $this->redirect()->toRoute('branch');
    }
}
