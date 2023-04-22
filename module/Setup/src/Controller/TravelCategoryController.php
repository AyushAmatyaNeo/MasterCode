<?php

namespace Setup\Controller;

use Zend\Helper\EntityHelper;
use Application\Helper\Helper;
use Exception;
use Setup\Form\TravelCategoryForm;
use Setup\Model\TravelCategory as TravelCategoryModel;
use Setup\Repository\TravelCategoryRepository;
use Zend\Authentication\AuthenticationService;
use Zend\Authentication\Storage\StorageInterface;
use Zend\Db\Adapter\AdapterInterface;
use Application\Custom\CustomViewModel;
use Setup\Model\TravelCategory;
use Zend\Form\Annotation\AnnotationBuilder;
use Zend\Mvc\Controller\AbstractActionController;

class TravelCategoryController extends AbstractActionController{
    protected $adapter;
    protected $employeeId;
    protected $repository;
    protected $storageData;
    protected $acl;

  

    public function __construct(AdapterInterface $adapter,StorageInterface $storage){
        $this->adapter=$adapter;
        $this->travelCategoryRepository=new TravelCategoryRepository($adapter);
        $this->storageData = $storage->read();
        $auth=new AuthenticationService();
        $this->employeeId = $this->storageData['employee_id'];
        $this->acl = $this->storageData['acl'];
    }


    public function indexAction(){
        $request = $this->getRequest();
    if ($request->isPost()) {
        try {
            $result = $this->travelCategoryRepository->fetchAll();
            $travelCategoryList = Helper::extractDbData($result);
            return new CustomViewModel(['success' => true, 'data' => $travelCategoryList, 'error' => '']);
        } catch (Exception $e) {
            return new CustomViewModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
        }
    }
    return Helper::addFlashMessagesToArray($this, ['acl' => $this->acl]);
    }

    
    public function addAction() {
        $request = $this->getRequest();
        // echo '<pre>';print_r($request);die;

        if ($request->isPost()) {
            $travelCategoryModel = new TravelCategory();
            $data = $request->getPost();
                $travelCategoryModel->id = ((int) Helper::getMaxId($this->adapter, TravelCategory::TABLE_NAME, TravelCategory::ID)) + 1;
                $travelCategoryModel->createdBy = $this->employeeId;
                $travelCategoryModel->createdDt= Helper::getcurrentExpressionDate();
                $travelCategoryModel->modifiedBy = $this->employeeId;
                $travelCategoryModel->modifiedDt= Helper::getcurrentExpressionDate();
                $travelCategoryModel->deletedBy = $this->employeeId;
                $travelCategoryModel->deletedDt= Helper::getcurrentExpressionDate();
                $travelCategoryModel->status = 'E';
                $travelCategoryModel->positionId = $data['positionId'];
                $travelCategoryModel->dailyAllowance = $data['dailyAllowance'];
                $travelCategoryModel->advanceAmount = $data['advanceAmount'];

                $this->travelCategoryRepository->add($travelCategoryModel);
                return new CustomViewModel(['success' => true,'error' => '']);
            
        return Helper::addFlashMessagesToArray($this, [
                    'customRenderer' => Helper::renderCustomView()
        ]);
    }

  }

  public function deleteAction(){
    $id=(int) $this->params()->fromRoute("id");
    if(!$id){
        return $this->redirect()->toRoute('travelCategory');
    }
    $this->travelCategoryRepository->delete($id);
    $this->flashmessenger()->addMessage("Travel Category Successfully Deleted!!!");
    return $this->redirect()->toRoute('travelCategory');
    }


  public function editAction(){
        $id = (int) $this->params()->fromRoute("id");
        if ($id === 0) {
            return $this->redirect()->toRoute('travelCategory');
        }
        $request = $this->getRequest();

        if($request->isPost()){
            $travelCategoryModel = new TravelCategory();
            $data = $request->getPost();
            // echo '<pre>';print_r($data);die;
            $travelCategoryModel->id = ((int) Helper::getMaxId($this->adapter, TravelCategory::TABLE_NAME, TravelCategory::ID)) + 1;
            $travelCategoryModel->createdBy = $this->employeeId;
            $travelCategoryModel->createdDt= Helper::getcurrentExpressionDate();
            $travelCategoryModel->modifiedBy = $this->employeeId;
            $travelCategoryModel->modifiedDt= Helper::getcurrentExpressionDate();
            $travelCategoryModel->deletedBy = $this->employeeId;
            $travelCategoryModel->deletedDt= Helper::getcurrentExpressionDate();
            $travelCategoryModel->status = 'E';
            $travelCategoryModel->positionId = $data['positionId'];
            $travelCategoryModel->dailyAllowance = $data['dailyAllowance'];
            $travelCategoryModel->advanceAmount = $data['advanceAmount'];
            // echo '<pre>'; print_r($travelCategoryModel);die;

            $this->travelCategoryRepository->edit($travelCategoryModel,$id);
            return new CustomViewModel(['success' => true,'error' => '']);

        }
        $detail=$this->travelCategoryRepository->fetchById($id);
        // print_r($detail);die;
        return Helper::addFlashMessagesToArray($this, [
            'acl' => $this->acl,
            'detail' => $detail,
            'id' => $id

        ]);
     }

    public function viewAction(){
        $id = (int) $this->params()->fromRoute("id");
        if ($id === 0) {
            return $this->redirect()->toRoute('travelCategory');
        }
        $detail=$this->travelCategoryRepository->fetchById($id);
        return Helper::addFlashMessagesToArray($this, [
            'acl' => $this->acl,
            'detail' => $detail,
            'id' => $id

        ]);
     }
}

