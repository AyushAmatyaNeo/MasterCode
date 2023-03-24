<?php

namespace Payroll\Controller;

use Application\Controller\HrisController;
use Application\Helper\EntityHelper;
use Application\Helper\Helper;
use Application\Model\FiscalYear;
use Exception;
use Payroll\Form\Variance;
use Payroll\Model\FlatValue as FlatValueModel;
use Payroll\Model\Rules;
use Payroll\Model\VariancePayhead;
use Payroll\Model\VarianceSetup;
use Payroll\Repository\VariancePayHeadRepo;
use Payroll\Repository\VarianceRepo;
use Zend\Authentication\Storage\StorageInterface;
use Zend\Db\Adapter\AdapterInterface;
use Zend\View\Model\JsonModel;

class VarianceSetupController extends HrisController {
    
    private $variableTypeList=[
    'S'=>'Salary Group',
    'V'=>'Variance',
    'O'=>'OT',
    'T'=>'Tax Group',
    'B'=>'Basic', // for baisc
    'C'=>'Grade', // for Grade
    'A'=>'Allowance', // for Allowance
    'G'=>'Gross', // for Gross
    'Y'=>'Tax Yearly', // for Gross
    'N'=>'Annual Salary', // for annual salary sheet
    ];
    
    
    private $vHeadsList=[
    'NO'=>'None',
    'IN'=>'Incomes',
    'TE'=>'Tax Excemptions',
    'OT'=>'Other Tax',
    'MI'=>'Miscellaneous',
    'BM'=>'B-Miscellaneous',
    'CM'=>'C-Miscellaneous',
    'SE'=>'Sum Of Exemptions',
    'ST'=>'A Sum of Other Tax',
    ];

    public function __construct(AdapterInterface $adapter, StorageInterface $storage) {
        parent::__construct($adapter, $storage);
        $this->initializeRepository(VarianceRepo::class);
        $this->initializeForm(Variance::class);
    }

    public function indexAction() {
        $request = $this->getRequest();
        if ($request->isPost()) {
            try {
                $rawList = $this->repository->fetchAll();
                $list = Helper::extractDbData($rawList);
                return new JsonModel(['success' => true, 'data' => $list, 'error' => '']);
            } catch (Exception $e) {
                return new JsonModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
            }
        }

        return $this->stickFlashMessagesTo(['acl' => $this->acl]);
    }

    public function addAction() {
        $request = $this->getRequest();
        if ($request->isPost()) {
            $postData = $request->getPost();
            $payId = $request->getPost('payId');
            $this->form->setData($request->getPost());
            if ($this->form->isValid()) {
                $variance = new VarianceSetup();
                $variance->exchangeArrayFromForm($this->form->getData());
                $variance->varianceId = ((int) Helper::getMaxId($this->adapter, VarianceSetup::TABLE_NAME, VarianceSetup::VARIANCE_ID)) + 1;
                $variance->status = 'E';
                $variance->createdBy = $this->employeeId;
                $this->repository->add($variance);

                foreach ($payId as $pay) {
                    $this->addVariancePayHead($variance->varianceId, $pay);
                }
                $this->flashmessenger()->addMessage("Varience Variable Sucessfully created");
                return $this->redirect()->toRoute("varianceSetup");
            }
        }
        return [
            'variableTypeList' => $this->variableTypeList,
            'vHeadsList' => $this->vHeadsList,
            'form' => $this->form,
            'customRenderer' => Helper::renderCustomView(),
            'payHeads' => EntityHelper::getTableList($this->adapter, Rules::TABLE_NAME, [Rules::PAY_ID, Rules::PAY_EDESC], [Rules::STATUS => "E"]),
			'acl' => $this->acl,
					'employeeDetail' => $this->storageData['employee_detail'],
        ];
    }

    public function editAction() {
        $id = (int) $this->params()->fromRoute("id");
        $request = $this->getRequest();
        $variance = new VarianceSetup();

        $details = $this->repository->fetchById($id);

        if (!$request->isPost()) {
            $variance->exchangeArrayFromDB($details);
            $this->form->bind($variance);
        } else {
            $postData = $request->getPost();
            $payId = $request->getPost('payId');
            $this->form->setData($request->getPost());
            if ($this->form->isValid()) {
                $variance->exchangeArrayFromForm($this->form->getData());
                $variance->modifiedDt = Helper::getcurrentExpressionDate();
                $variance->modifiedBy = $this->employeeId;
                $this->repository->edit($variance, $id);
                $variancePayHeadRepo = new VariancePayHeadRepo($this->adapter);
                $variancePayHeadRepo->delete($id);
                foreach ($payId as $pay) {
                    $this->addVariancePayHead($id, $pay);
                }
                $this->flashmessenger()->addMessage("Varience Variable Sucessfully Edited");
                return $this->redirect()->toRoute("varianceSetup");
            }
        }

        return [
            'id' => $id,
            'variableTypeList' => $this->variableTypeList,
            'vHeadsList' => $this->vHeadsList,
            'form' => $this->form,
            'customRenderer' => Helper::renderCustomView(),
            'details' => $details,
            'payHeads' => EntityHelper::getTableList($this->adapter, Rules::TABLE_NAME, [Rules::PAY_ID, Rules::PAY_EDESC], [Rules::STATUS => "E"]),
			'acl' => $this->acl,
					'employeeDetail' => $this->storageData['employee_detail'],
        ];
    }

    public function deleteAction() {
        $id = (int) $this->params()->fromRoute("id");

        if (!$id) {
            return $this->redirect()->toRoute('varianceSetup');
        }
        $this->repository->delete($id);
        $this->flashmessenger()->addMessage("Varience Variable Successfully Deleted!!!");
        return $this->redirect()->toRoute('varianceSetup');
    }


    public function addVariancePayHead($varianceId, $payId) {
        $variancePayHeadRepo = new VariancePayHeadRepo($this->adapter);
        $variancePayHead = new VariancePayhead();
        $variancePayHead->varianceId = $varianceId;
        $variancePayHead->payId = $payId;
        $variancePayHeadRepo->add($variancePayHead);
    }

}
