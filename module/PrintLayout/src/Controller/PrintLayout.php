<?php

namespace PrintLayout\Controller;

use Application\Controller\HrisController;
use Zend\Authentication\Storage\StorageInterface;
use Zend\Db\Adapter\AdapterInterface;
use Zend\View\Model\ViewModel;
use Application\Helper\Helper;
use Zend\View\Model\JsonModel;
use Exception;
use Zend\Db\Sql\Expression;
use KioskApi\Controller\Authentication;
use Zend\Authentication\AuthenticationService;
use Zend\Mvc\Controller\AbstractActionController;
use Application\Helper\EntityHelper;
use PrintLayout\Repository\PrintLayoutRepo;
use PrintLayout\Model\PrintLayoutTemplate;
use PrintLayout\Model\ServiceEventModel;
use PrintLayout\Model\SwearingModel;
use PrintLayout\Model\AppointmentModel;
use PrintLayout\Model\DepartureLetterModel;
use PrintLayout\Model\TravelFormModel;



class PrintLayout extends AbstractActionController {

    private $templateRepo;
   

    private function getVariables($id) {
        $type = new ServiceEventModel();
        $type1 = new TravelFormModel();
        $vars = $type->getObjectAttrs();
        $type1PbjVars = $type1->getObjectAttrs();
        return [
            // 1=> $vars,
            2=> $type1PbjVars
        ];
    }

    public function __construct(AdapterInterface $adapter) {
        $this->adapter = $adapter;
        $this->templateRepo = new PrintLayoutRepo($adapter);
        //$this->
        $auth = new AuthenticationService();
        $this->employeeId = $auth->getStorage()->read()['employee_id'];
    }

    public function indexAction()
    {
        return new ViewModel();
    }

    public function getReportsTableDataAction(){
        try {
            $recordList = $this->templateRepo->getReportsTable();
            return new JsonModel([
                "success" => "true",
                "data" => $recordList
            ]);
        } catch (Exception $e) {
            return new JsonModel(['success' => false, 'data' => null, 'message' => $e->getMessage()]);
        }
    }
    public function deleteAction()
    {
        $id = $this->params()->fromRoute('id');

        if (!$id) {
            return $this->redirect()->toRoute('printlayout');
        }
        $this->templateRepo->delete($id);
        $this->flashmessenger()->addMessage("Report Successfully Deleted!!!");
        return $this->redirect()->toRoute('printlayout');
    }

    public function editAction()
    {
        $id = $this->params()->fromRoute('id');
        $request = $this->getRequest();
        if ($request->isPost()) {
            $id = $this->params()->fromRoute('id');
            $postedData = json_decode(json_encode($request->getPost()), true);
            //print_r($postedData); die;
            $template = new PrintLayoutTemplate();
            $template->exchangeArrayFromForm($postedData);
            $template->modifiedBy=$this->employeeId;
            $template->modifiedDt = new Expression("SYSDATE");;
            $this->templateRepo->edit($template,$id);


            return $this->redirect()->toRoute('printlayout');
        }else{
            $data = $this->templateRepo->fetchById($id);
            $data['BODY'] = $data['BODY']->load();
            return Helper::addFlashMessagesToArray($this, [
                //'form' => $this->form,
                'data' => $data,
                'id' => $id,
                'variables' => $this->getVariables($id)
            ]);
        }
        

    }

}
