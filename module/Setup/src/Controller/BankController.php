<?php

namespace Setup\Controller;

use Application\Custom\CustomViewModel;
use Application\Helper\ACLHelper;
use Application\Helper\EntityHelper;
use Application\Helper\Helper;
use Exception;
use Setup\Form\BankForm;
use Setup\Model\Bank;
use Setup\Model\Company;
use Setup\Model\Position;
use Setup\Repository\BankRepository;
use Zend\Authentication\Storage\StorageInterface;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Form\Annotation\AnnotationBuilder;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class BankController extends AbstractActionController
{

    private $repository;
    private $form;
    private $adapter;
    private $employeeId;
    private $storageData;
    private $acl;

    public function __construct(AdapterInterface $adapter, StorageInterface $storage)
    {
        $this->adapter = $adapter;
        $this->repository = new BankRepository($adapter);
        $this->storageData = $storage->read();
        $this->employeeId = $this->storageData['employee_id'];
        $this->acl = $this->storageData['acl'];
    }

    public function initializeForm()
    {
        $bankForm = new BankForm();
        $builder = new AnnotationBuilder();
        if (!$this->form) {
            $this->form = $builder->createForm($bankForm);
        }
    }

    public function indexAction()
    {
        $request = $this->getRequest();
        if ($request->isPost()) {
            try {
                $result = $this->repository->fetchBankDetails();
                // echo '<pre>';print_r($result);die;

                $bankList = Helper::extractDbData($result);
                return new CustomViewModel(['success' => true, 'data' => $bankList, 'error' => '']);
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
            // echo '<pre>';print_r($this->form->isValid());die;

            if ($this->form->isValid()) {
                $bank = new Bank();
                $bank->exchangeArrayFromForm($this->form->getData());
                $bank->bankId = ((int) Helper::getMaxId($this->adapter, Bank::TABLE_NAME, Bank::BANK_ID)) + 1;
                $bank->createdDt = Helper::getcurrentExpressionDate();
                $bank->createdBy = $this->employeeId;
                $bank->comapnyAccNo = null;
                $bank->branchName = null;
                $bank->status = 'E';
                $this->repository->add($bank);

                $this->flashmessenger()->addMessage("Bank Successfully added!!!");
                return $this->redirect()->toRoute("bank");
            }
        }
        return new ViewModel(
            Helper::addFlashMessagesToArray(
                $this,
                [
                    'customRenderer' => Helper::renderCustomView(),
                    'form' => $this->form,
                    'messages' => $this->flashmessenger()->getMessages()
                ]
            )
        );
    }

    public function editAction()
    {
        ACLHelper::checkFor(ACLHelper::UPDATE, $this->acl, $this);
        $id = (int) $this->params()->fromRoute("id");
        if ($id === 0) {
            return $this->redirect()->toRoute('bank');
        }

        $this->initializeForm();
        $request = $this->getRequest();

        $bank = new Bank();
        if (!$request->isPost()) {

            $bank->exchangeArrayFromDB($this->repository->fetchById($id)->getArrayCopy());
            $this->form->bind($bank);
        } else {
            $this->form->setData($request->getPost());
            if ($this->form->isValid()) {
                $bank->exchangeArrayFromForm($this->form->getData());
                $bank->createdBy = $this->employeeId;
                $this->repository->edit($bank, $id);
                $this->flashmessenger()->addMessage("Bank Successfully Updated!!!");
                return $this->redirect()->toRoute("bank");
            }
        }
        return Helper::addFlashMessagesToArray(
            $this,
            [
                'customRenderer' => Helper::renderCustomView(),
                'form' => $this->form,
                'id' => $id
            ]
        );
    }

    public function deleteAction()
    {
        if (!ACLHelper::checkFor(ACLHelper::DELETE, $this->acl, $this)) {
            return;
        };
        $id = (int) $this->params()->fromRoute("id");
        if (!$id) {
            return $this->redirect()->toRoute('bank');
        }
        // echo '<pre>';print_r($id);die;
        $this->repository->deleteBank($id, $this->employeeId);
        $this->flashmessenger()->addMessage("Bank Successfully Deleted!!!");
        return $this->redirect()->toRoute('bank');
    }
}
