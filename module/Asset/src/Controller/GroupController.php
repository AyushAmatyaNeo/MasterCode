<?php
namespace Asset\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\Db\Adapter\AdapterInterface;
use Asset\Form\GroupForm;
use Asset\Model\Group;
use Asset\Repository\GroupRepository;
use Zend\Form\Annotation\AnnotationBuilder;
use Zend\Authentication\AuthenticationService;
use Application\Helper\Helper;
use Setup\Repository\EmployeeRepository;

class GroupController extends AbstractActionController{
    private $adapter;
    private $repository;
    private $form;
    private $employeeId;

    public function __construct(AdapterInterface $adapter) {
        $this->adapter = $adapter;
        $this->repository = new GroupRepository($adapter);
        $auth = new AuthenticationService();
        $this->employeeId = $auth->getStorage()->read()['employee_id'];
    }

    public function initializeForm() {
        $form = new GroupForm();
        $builder = new AnnotationBuilder();
        $this->form = $builder->createForm($form);
    }

    public function indexAction() {
        $result = $this->repository->fetchAll();
        $list = [];
        foreach ($result as $row) {
            array_push($list, $row);
        }
//        echo '<pre>';
//        print_r($list); die();
//        echo '</pre>';
        return Helper::addFlashMessagesToArray($this, [
                    'group' => $list
        ]);
    }
    
    public function addAction(){
        $this->initializeForm();
        $employeeRepo = new EmployeeRepository($this->adapter);
        $employeeDetail = $employeeRepo->fetchById($this->employeeId);

        $request = $this->getRequest();
        if ($request->isPost()) {
            $this->form->setData($request->getPost());
            if ($this->form->isValid()) {

                $group = new Group();
                $group->exchangeArrayFromForm($this->form->getData());
                $group->createdBy = $this->employeeId;
                $group->createdDate = Helper::getcurrentExpressionDate();
                $group->approveDate = Helper::getcurrentExpressionDate();
                $group->companyId = $employeeDetail['COMPANY_ID'];
                $group->branchId = $employeeDetail['BRANCH_ID'];
                $group->assetGroupId = ((int) Helper::getMaxId($this->adapter, $group::TABLE_NAME, $group::ASSET_GROUP_ID)) + 1;
                $group->status = 'E';
                $this->repository->add($group);
                $this->flashmessenger()->addMessage("Asset Group Successfully added!!!");
                return $this->redirect()->toRoute("assetGroup");
            }

        }
        return Helper::addFlashMessagesToArray($this, [
            'form'=>$this->form
        ]);
    }


    public function deleteAction() {
        $id = $this->params()->fromRoute('id');
        if ($id == 0) {
            $this->redirect()->toRoute('assetGroup');
        }
        $this->repository->delete($id);
        $this->flashmessenger()->addMessage("Asset Group Successfully Deleted!!!");
        return $this->redirect()->toRoute("assetGroup");
    }
    
    public function editAction(){
        $id = $this->params()->fromRoute('id');
        if($id==0){
            $this->redirect()->toRoute('assetGroup');
        }
        $this->initializeForm();
        
        $request = $this->getRequest();
        $group= new Group();
        if(!$request->isPost()){
            $group->exchangeArrayFromDB($this->repository->fetchById($id)->getArrayCopy());
            $this->form->bind($group);
        }else{
            $this->form->setData($request->getPost());
            if($this->form->isValid()){
                $group->exchangeArrayFromForm($this->form->getData());
                
                $group->modifiedDate = Helper::getcurrentExpressionDate();
                $group->modifiedBy = $this->employeeId;
                
                $this->repository->edit($group, $id);
                $this->flashmessenger()->addMessage("Asset Group Successfully Updated!!!");
                return $this->redirect()->toRoute("assetGroup");
            }
        }
        return Helper::addFlashMessagesToArray($this, [
            'form'=>$this->form,
            'id'=>$id
        ]);
    }

}


