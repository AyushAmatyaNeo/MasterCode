<?php

namespace Setup\Controller;

use Application\Custom\CustomViewModel;
use Application\Helper\ACLHelper;
use Application\Helper\EntityHelper;
use Application\Helper\Helper;
use Exception;
use Setup\Model\TravelExpenseClass;
use Setup\Model\TravelExpenseClass as TravelExpenseClassModel;
use Setup\Repository\TravelExpenseClassRepository;
use Zend\Authentication\Storage\StorageInterface;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Form\Annotation\AnnotationBuilder;
use Zend\Mvc\Controller\AbstractActionController;

class TravelExpClassController extends AbstractActionController {

    private $form;
    private $adapter;
    private $employeeId;
    private $repository;
    private $storageData;
    private $acl;

  

    public function __construct(AdapterInterface $adapter, StorageInterface $storage) {
        $this->adapter = $adapter;
        $this->repository = new TravelExpenseClassRepository($adapter);
        $this->storageData = $storage->read();
        $this->employeeId = $this->storageData['employee_id'];
        $this->acl = $this->storageData['acl'];
    }


    public function indexAction() {
        $request = $this->getRequest();
        if ($request->isPost()) {
            try {
                $result = $this->repository->fetchAll();
                //echo '<pre>';print_r($result);die;
                $travelClassList = Helper::extractDbData($result);
                return new CustomViewModel(['success' => true, 'data' => $travelClassList, 'error' => '']);
            } catch (Exception $e) {
                return new CustomViewModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
            }
        }
        return Helper::addFlashMessagesToArray($this, ['acl' => $this->acl]);
    }
    
    public function addAction() {
       
        $request = $this->getRequest();
    //  echo '<pre>';print_r($request);die;

        if ($request->isPost()) {
            $travelExpenseClassModel = new TravelExpenseClass();
            $data = $request->getPost();
          // echo '<pre>'; print_r('$data');die
                 $travelExpenseClassModel->id = ((int) Helper::getMaxId($this->adapter, TravelExpenseClass::TABLE_NAME, TravelExpenseClass::ID)) + 1;
                 $travelExpenseClassModel->createdBy = $this->employeeId;
                 $travelExpenseClassModel->createdDt= Helper::getcurrentExpressionDate();
                 $travelExpenseClassModel->status = 'E';
                 $travelExpenseClassModel->categoryName = $data['categoryName'];
                 $travelExpenseClassModel->allowancePercentage = $data['allowancePercentage'];

                 $this->repository->add($travelExpenseClassModel);
               
                 return new CustomViewModel(['success' => true,'error' => '']);
            
        return Helper::addFlashMessagesToArray($this, [
                    'customRenderer' => Helper::renderCustomView()
        ]);
    }

  }

public function editAction(){
        $id = (int) $this->params()->fromRoute("id");
        if ($id === 0) {
            return $this->redirect()->toRoute('travelExpenseClass');
        }
        $request = $this->getRequest();
        // echo '<pre>'; print_r($request);die;

        if($request->isPost()){
            $travelExpClass = new TravelExpenseClass();
            $data = $request->getPost();
            $travelExpClass->categoryName = $data['categoryName'];
            $travelExpClass->allowancePercentage = $data['allowancePercentage'];
            $travelExpClass->createdBy = $this->employeeId;
            $travelExpClass->createdDt= Helper::getcurrentExpressionDate();
            $travelExpClass->modifiedBy = $this->employeeId;
            $travelExpClass->modifiedDt= Helper::getcurrentExpressionDate();
            $travelExpClass->status = 'E';

            // echo '<pre>'; print_r($id);die;

            $this->repository->edit($travelExpClass,$id);
            $this->flashmessenger()->addMessage("Travel  Successfully Updated!!!");
            return $this->redirect()->toRoute('travelExpenseClass');
            return new CustomViewModel(['success' => true,'error' => '']);

        }
        $detail=$this->repository->fetchById($id);
        // echo '<pre>';print_r($detail);die;
        return Helper::addFlashMessagesToArray($this, [
            'acl' => $this->acl,
            'detail' => $detail,
            'id' => $id

        ]);
     }

public function deleteAction(){
   
if (!ACLHelper::checkFor(ACLHelper::DELETE, $this->acl, $this)) {
    return;
}
$id = (int) $this->params()->fromRoute("id");
if (!$id) {
    return $this->redirect()->toRoute('travelExpenseClass');
}
$this->repository->delete($id);
$this->flashmessenger()->addMessage("Travel  Successfully Deleted!!!");
return $this->redirect()->toRoute('travelExpenseClass');
}
  
    
}

