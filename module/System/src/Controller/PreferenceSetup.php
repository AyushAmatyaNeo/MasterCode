<?php
namespace System\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\Db\Adapter\AdapterInterface;
use Application\Helper\Helper;
use Application\Helper\EntityHelper;
use System\Form\PreferenceSetupForm;
use Zend\Form\Annotation\AnnotationBuilder;
use Setup\Model\Company;
use Zend\Authentication\AuthenticationService;
use Zend\Db\Sql\Select;
use System\Model\PreferenceSetup as PreferenceSetupModel;
use System\Repository\PreferenceSetupRepo;

class PreferenceSetup extends AbstractActionController{
    private $form;
    private $repository;
    private $employeeId;
    public function __construct(AdapterInterface $adapter) {
        $this->adapter = $adapter;
        $auth = new AuthenticationService();
        $this->employeeId = $auth->getStorage()->read()['employee_id'];
        $this->repository = new PreferenceSetupRepo($adapter);
    }
    public function indexAction() {
        $result = $this->repository->fetchAll();
        $list = [];
        foreach ($result as $row){
            $row['PREFERENCE_NAME'] = PreferenceSetupModel::PREFERENCE_NAME_LIST[$row['PREFERENCE_NAME']];
            $row['PREFERENCE_CONSTRAINT'] = PreferenceSetupModel::PREFERENCE_CONSTRAINT_LIST[$row['PREFERENCE_CONSTRAINT']];
            $row['PREFERENCE_CONDITION'] = PreferenceSetupModel::PREFERENCE_CONDITION_LIST[$row['PREFERENCE_CONDITION']];
            $row['REQUEST_TYPE']=PreferenceSetupModel::REQUEST_TYPE_LIST[$row['REQUEST_TYPE']];
            array_push($list, $row);
        }
//        print_r($list);die();
        return Helper::addFlashMessagesToArray($this, ['list'=>$list]);
    }
    public function initializeForm(){
        $preferenceForm = new PreferenceSetupForm();
        $builder = new AnnotationBuilder();
        $this->form = $builder->createForm($preferenceForm);
    }
    public function addAction(){
        $this->initializeForm();
        $request = $this->getRequest();
        if($request->isPost()){
            $postData = $request->getPost();
            $this->form->setData($postData);
            if($this->form->isValid()){
                $preferenceSetup = new PreferenceSetupModel();
                $preferenceSetup->exchangeArrayFromForm($this->form->getData());
                $preferenceSetup->preferenceId = ((int) Helper::getMaxId($this->adapter, "HRIS_PREFERENCE_SETUP", "PREFERENCE_ID")) + 1;
                $preferenceSetup->createdDate = Helper::getcurrentExpressionDate();
                $preferenceSetup->createdBy = $this->employeeId;
                $preferenceSetup->constraintValue = Helper::hoursToMinutes($preferenceSetup->constraintValue);
                $preferenceSetup->status = 'E';
                $this->repository->add($preferenceSetup);

                $this->flashmessenger()->addMessage("Preference Detail Successfully Added!!!");
                return $this->redirect()->toRoute("preferenceSetup");
            }
        }
        return Helper::addFlashMessagesToArray($this, [
            'form'=> $this->form,
            'companies' => EntityHelper::getTableKVListWithSortOption($this->adapter, Company::TABLE_NAME, Company::COMPANY_ID, [Company::COMPANY_NAME], ["STATUS" => "E"], Company::COMPANY_NAME, Select::ORDER_ASCENDING, null, false, true),
            'preferenceNameList'=>PreferenceSetupModel::PREFERENCE_NAME_LIST,
            'preferenceConstraintList'=>PreferenceSetupModel::PREFERENCE_CONSTRAINT_LIST,
            "preferenceConditionList"=>PreferenceSetupModel::PREFERENCE_CONDITION_LIST,
            'requestTypeList'=>PreferenceSetupModel::REQUEST_TYPE_LIST,
            'employeeTypeList'=>PreferenceSetupModel::EMPLOYEE_TYPE_LIST
        ]);
    }
    public function editAction(){
        $this->initializeForm();
        $id = $this->params()->fromRoute('id');
        if($id===0){
            $this->redirect()->toRoute('preferenceSetup');
        }
        $request = $this->getRequest();
        $preferenceSetupModel = new PreferenceSetupModel();
        if(!$request->isPost()){
           $detail = $this->repository->fetchById($id)->getArrayCopy();
           $preferenceSetupModel->exchangeArrayFromDB($detail);
           $this->form->bind($preferenceSetupModel);
        }else{
            $postData = $request->getPost();
            $this->form->setData($postData);
            if($this->form->isValid()){
                $preferenceSetup = new PreferenceSetupModel();
                $preferenceSetup->exchangeArrayFromForm($this->form->getData());
                $preferenceSetup->modifiedDate = Helper::getcurrentExpressionDate();
                $preferenceSetup->modifiedBy = $this->employeeId;
                $preferenceSetup->constraintValue = Helper::hoursToMinutes($preferenceSetup->constraintValue);

                $this->repository->edit($preferenceSetup,$id);

                $this->flashmessenger()->addMessage("Preference Detail Successfully Updated!!!");
                return $this->redirect()->toRoute("preferenceSetup");
            }
        }
        return Helper::addFlashMessagesToArray($this, [
            'form'=> $this->form,
            'id'=>$id,
            'companies' => EntityHelper::getTableKVListWithSortOption($this->adapter, Company::TABLE_NAME, Company::COMPANY_ID, [Company::COMPANY_NAME], ["STATUS" => "E"], Company::COMPANY_NAME, Select::ORDER_ASCENDING, null, false, true),
            'preferenceNameList'=>PreferenceSetupModel::PREFERENCE_NAME_LIST,
            'preferenceConstraintList'=>PreferenceSetupModel::PREFERENCE_CONSTRAINT_LIST,
            "preferenceConditionList"=>PreferenceSetupModel::PREFERENCE_CONDITION_LIST,
            'requestTypeList'=>PreferenceSetupModel::REQUEST_TYPE_LIST,
            'employeeTypeList'=>PreferenceSetupModel::EMPLOYEE_TYPE_LIST
        ]);
    }
}
