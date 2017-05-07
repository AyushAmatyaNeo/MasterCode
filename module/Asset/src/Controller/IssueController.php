<?php

namespace Asset\Controller;

use Application\Helper\EntityHelper as ApplicationEntityHelper;
use Application\Helper\Helper;
use Asset\Form\IssueForm;
use Asset\Model\Issue;
use Asset\Model\Setup;
use Asset\Repository\IssueRepository;
use Asset\Repository\SetupRepository;
use Setup\Model\HrEmployees;
use Setup\Repository\EmployeeRepository;
use Zend\Authentication\AuthenticationService;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Form\Annotation\AnnotationBuilder;
use Zend\Mvc\Controller\AbstractActionController;

class IssueController extends AbstractActionController {

    private $adapter;
    private $form;
    private $employeeId;
    private $repository;

    public function __construct(AdapterInterface $adapter) {
        $this->adapter = $adapter;
        $this->repository = new IssueRepository($adapter);
        $auth = new AuthenticationService();
        $this->employeeId = $auth->getStorage()->read()['employee_id'];
    }

    public function initializeForm() {
        $form = new IssueForm();
        $builder = new AnnotationBuilder();
        $this->form = $builder->createForm($form);
    }

    public function indexAction() {
        $result = $this->repository->fetchAll();
        $list = [];
        foreach ($result as $row) {
            array_push($list, $row);
        }
        return Helper::addFlashMessagesToArray($this, [
                    'issue' => $list
        ]);
    }

    public function addAction() {
        $this->initializeForm();
        $employeeRepo = new EmployeeRepository($this->adapter);
        $employeeDetail = $employeeRepo->fetchById($this->employeeId);
        $asset = $this->repository->fetchallIssuableAsset();
        $request = $this->getRequest();
        if ($request->isPost()) {
            $this->form->setData($request->getPost());

            if ($this->form->isValid()) {

                $issue = new Issue();
                $issue->exchangeArrayFromForm($this->form->getData());
                $issue->issueId = ((int) Helper::getMaxId($this->adapter, $issue::TABLE_NAME, $issue::ISSUE_ID)) + 1;
                $issue->sno = ((int) Helper::getMaxId($this->adapter, $issue::TABLE_NAME, $issue::SNO)) + 1;
                $issue->createdBy = $this->employeeId;
                $issue->autorizedBy = $this->employeeId;
                $issue->companyId = $employeeDetail['COMPANY_ID'];
                $issue->branchId = $employeeDetail['BRANCH_ID'];
                $issue->status = 'E';


                $remQty = $request->getPost()['balance'];
                $newRemQty = $remQty - $issue->quantity;

                $a = $this->repository->add($issue);
                if ($a) {
                    $setupModel = new Setup();
                    $setupModel->quantityBalance = $newRemQty;
                    $setupRepo = new SetupRepository($this->adapter);
                    $setupRepo->updateRemainingAssetBalance($setupModel, $issue->assetId);
                }

                $this->flashmessenger()->addMessage("Asset Successfully issued!!!");
                return $this->redirect()->toRoute("assetSetup");
            }
        }

        return Helper::addFlashMessagesToArray($this, [
                    'form' => $this->form,
//                    'asset' => ApplicationEntityHelper::getTableKVListWithSortOption($this->adapter, Setup::TABLE_NAME, Setup::ASSET_ID, [Setup::ASSET_EDESC], ["STATUS" => "E"], Setup::ASSET_EDESC, "ASC", NULL, FALSE, TRUE),
                    'asset' => $asset['B'],
                    'employee' => ApplicationEntityHelper::getTableKVListWithSortOption($this->adapter, HrEmployees::TABLE_NAME, "EMPLOYEE_ID", ["FIRST_NAME", "MIDDLE_NAME", "LAST_NAME"], ["STATUS" => 'E', 'RETIRED_FLAG' => 'N'], "FIRST_NAME", "ASC", " ", FALSE, TRUE),
        ]);
    }

    public function editAction() {
        $id = $this->params()->fromRoute('id');
        if ($id == 0) {
            $this->redirect()->toRoute('assetIssue');
        }

        $this->initializeForm();
        $request = $this->getRequest();
        $issue = new Issue();
        if (!$request->isPost()) {
            $issue->exchangeArrayFromDB($this->repository->fetchById($id));
            $this->form->bind($issue);
        } else {
            $this->form->setData($request->getPost());
            if ($this->form->isValid()) {
                $issue->exchangeArrayFromForm($this->form->getData());
                $issue->modifiedDate = Helper::getcurrentExpressionDate();
                $issue->modifiedBy = $this->employeeId;

//                echo '<pre>';
//                print_r($issue);
//                die();
                $this->repository->edit($issue, $id);
                $this->flashmessenger()->addMessage("Asset issue Sucessfully updated");
                return $this->redirect()->toRoute("assetIssue");
            }
        }

        return Helper::addFlashMessagesToArray($this, [
                    'form' => $this->form,
                    'editVal' => $issue,
                    'asset' => ApplicationEntityHelper::getTableKVListWithSortOption($this->adapter, Setup::TABLE_NAME, Setup::ASSET_ID, [Setup::ASSET_EDESC], ["STATUS" => "E"], Setup::ASSET_EDESC, "ASC", NULL, FALSE, TRUE),
//            'asset' => $asset['B'],
                    'employee' => ApplicationEntityHelper::getTableKVListWithSortOption($this->adapter, HrEmployees::TABLE_NAME, "EMPLOYEE_ID", ["FIRST_NAME", "MIDDLE_NAME", "LAST_NAME"], ["STATUS" => 'E', 'RETIRED_FLAG' => 'N'], "FIRST_NAME", "ASC", " ", FALSE, TRUE),
                    'id' => $id
        ]);

        echo "<pre>";
        print_r($issue);
        die();
    }

    public function viewAction() {
        $id = $this->params()->fromRoute('id');
        if ($id == 0) {
            $this->redirect()->toRoute('assetSetup');
        }

        $result = $this->repository->fetchAllById($id);
        $list = [];
        foreach ($result as $row) {
            array_push($list, $row);
        }
        return Helper::addFlashMessagesToArray($this, [
                    'issue' => $list,
                    'id' => $id
        ]);
    }

    public function returnAssetAction() {
        $id = $this->params()->fromRoute('id');
        if ($id == 0) {
            $this->redirect()->toRoute('assetIssue', ['action' => 'view', 'id' => $id]);
        }
        $this->initializeForm();
        $request = $this->getRequest();
        $issue = new Issue();
        if ($request->isPost()) {

            $postdata = $request->getPost();
            $assetId = $postdata['assetId'];
            $issueId = $postdata['issueId'];
            $returnedDate = $postdata['returndeDate'];

            if (!empty($assetId) && !empty($assetId) && !empty($issueId)) {
                
                $issue->modifiedDate = Helper::getcurrentExpressionDate();
                $issue->modifiedBy = $this->employeeId;
                $issue->assetId = $assetId;
//                $issue->assetId = ;

                echo '<pre>';
                print_r($issue);
                die();
                
//                $this->repository->edit($issue, $id);
//                $this->flashmessenger()->addMessage("Asset issue Sucessfully updated");
//                return $this->redirect()->toRoute("assetIssue");
            }
                
                
                die();
                
                
            } else {
                $this->redirect()->toRoute('assetIssue', ['action' => 'view', 'id' => $id]);
            }
        } else {
            $this->redirect()->toRoute('assetIssue', ['action' => 'view', 'id' => $id]);
        }

//        print_r('return asset');
        die();
    }

}
