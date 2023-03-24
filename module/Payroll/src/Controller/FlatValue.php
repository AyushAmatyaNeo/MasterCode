<?php

namespace Payroll\Controller;

use Application\Controller\HrisController;
use Application\Helper\EntityHelper;
use Application\Helper\Helper;
use Application\Model\FiscalYear;
use Exception;
use Payroll\Form\FlatValue as FlatValueForm;
use Payroll\Model\FlatValue as FlatValueModel;
use Payroll\Repository\FlatValueDetailRepo;
use Payroll\Repository\FlatValueRepository;
use Payroll\Repository\PositionFlatValueRepo;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use Setup\Model\Position;
use Zend\Authentication\Storage\StorageInterface;
use Zend\Db\Adapter\AdapterInterface;
use Zend\View\Model\JsonModel;

class FlatValue extends HrisController {

    public function __construct(AdapterInterface $adapter, StorageInterface $storage) {
        parent::__construct($adapter, $storage);
        $this->initializeRepository(FlatValueRepository::class);
        $this->initializeForm(FlatValueForm::class);
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
            $this->form->setData($request->getPost());
            if ($this->form->isValid()) {
                $flatValue = new FlatValueModel();
                $flatValue->exchangeArrayFromForm($this->form->getData());
                $flatValue->flatId = ((int) Helper::getMaxId($this->adapter, FlatValueModel::TABLE_NAME, FlatValueModel::FLAT_ID)) + 1;
                $flatValue->createdDt = Helper::getcurrentExpressionDate();
                $flatValue->status = 'E';

                $this->repository->add($flatValue);
                $this->flashmessenger()->addMessage("Flat Value added Successfully.");
                return $this->redirect()->toRoute("flatValue");
            }
        }
        return [
            'form' => $this->form,
            'customRenderer' => Helper::renderCustomView()
        ];
    }

    public function editAction() {
        $id = (int) $this->params()->fromRoute("id");
        $request = $this->getRequest();

        $flatValueModel = new FlatValueModel();
        if (!$request->isPost()) {
            $flatValueModel->exchangeArrayFromDB($this->repository->fetchById($id)->getArrayCopy());
            $this->form->bind($flatValueModel);
        } else {
            $this->form->setData($request->getPost());
            if ($this->form->isValid()) {
                $flatValueModel->exchangeArrayFromForm($this->form->getData());
                $flatValueModel->modifiedDt = Helper::getcurrentExpressionDate();
                unset($flatValueModel->createdDt);
                unset($flatValueModel->flatId);
                unset($flatValueModel->status);
                $this->repository->edit($flatValueModel, $id);
                $this->flashmessenger()->addMessage("Flat Value updated successfully.");
                return $this->redirect()->toRoute("flatValue");
            }
        }
        return [
            'form' => $this->form,
            'id' => $id,
            'customRenderer' => Helper::renderCustomView()
        ];
    }

    public function deleteAction() {
        $id = (int) $this->params()->fromRoute("id");

        if (!$id) {
            return $this->redirect()->toRoute('flatValue');
        }
        $this->repository->delete($id);
        $this->flashmessenger()->addMessage("Flat Value Successfully Deleted!!!");
        return $this->redirect()->toRoute('flatValue');
    }

    public function detailAction() {
        $flatValues = EntityHelper::getTableList($this->adapter, FlatValueModel::TABLE_NAME, [FlatValueModel::FLAT_ID, FlatValueModel::FLAT_EDESC], [FlatValueModel::STATUS => EntityHelper::STATUS_ENABLED]);
        $fiscalYears = EntityHelper::getTableList($this->adapter, FiscalYear::TABLE_NAME, [FiscalYear::FISCAL_YEAR_ID, FiscalYear::FISCAL_YEAR_NAME]);
        return $this->stickFlashMessagesTo([
                    'searchValues' => EntityHelper::getSearchData($this->adapter),
                    'flatValues' => $flatValues,
                    'fiscalYears' => $fiscalYears,
                    'acl' => $this->acl,
					'employeeDetail' => $this->storageData['employee_detail'],
        ]);
    }

    public function getFlatValueDetailAction() {
        try {
            $request = $this->getRequest();
            if (!$request->isPost()) {
                throw new Exception("The request should be of type post");
            }
            $postedData = $request->getPost();
            $flatId = $postedData['flatId'];
            $fiscalYearId = $postedData['fiscalYearId'];
            $employeeFilter = $postedData['employeeFilter'];
            $detailRepo = new FlatValueDetailRepo($this->adapter);
            $result = $detailRepo->getFlatValuesDetailById($flatId, $fiscalYearId, $employeeFilter);
            //echo '<pre>'; print_r(Helper::extractDbData($result)); die;
            return new JsonModel(['success' => true, 'data' => Helper::extractDbData($result), 'error' => '']);
        } catch (Exception $e) {
            return new JsonModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
        }
    }

    public function postFlatValueDetailAction() {
        try {
            $request = $this->getRequest();
            if (!$request->isPost()) {
                throw new Exception("The request should be of type post");
            }
            $postedData = $request->getPost();
            $data = $postedData['data'];
            $detailRepo = new FlatValueDetailRepo($this->adapter);
            $detailRepo->postFlatValuesDetail($data);

            return new JsonModel(['success' => true, 'data' => $data, 'error' => '']);
        } catch (Exception $e) {
            return new JsonModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
        }
    }

    public function positionWiseAction() {
        $fiscalYears = EntityHelper::getTableList($this->adapter, FiscalYear::TABLE_NAME, [FiscalYear::FISCAL_YEAR_ID, FiscalYear::FISCAL_YEAR_NAME]);
        $flatValues = EntityHelper::getTableList($this->adapter, FlatValueModel::TABLE_NAME, [FlatValueModel::FLAT_ID, FlatValueModel::FLAT_EDESC], [FlatValueModel::STATUS => EntityHelper::STATUS_ENABLED, FlatValueModel::ASSIGN_TYPE => 'P']);
        $positions = EntityHelper::getTableList($this->adapter, Position::TABLE_NAME, [Position::POSITION_ID, Position::POSITION_NAME, Position::LEVEL_NO]);
        return $this->stickFlashMessagesTo([
                    'fiscalYears' => $fiscalYears,
                    'flatValues' => $flatValues,
                    'positions' => $positions,
					'acl' => $this->acl,
					'employeeDetail' => $this->storageData['employee_detail'],
        ]);
    }

    public function getPositionFlatValueAction() {
        try {
            $request = $this->getRequest();
            $postedData = $request->getPost();
            $fiscalYearId = $postedData['fiscalYearId'];

            $detailRepo = new PositionFlatValueRepo($this->adapter);
            $result = $detailRepo->getPositionFlatValue($fiscalYearId);

            return new JsonModel(['success' => true, 'data' => Helper::extractDbData($result), 'error' => '']);
        } catch (Exception $e) {
            return new JsonModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
        }
    }

    public function setPositionFlatValueAction() {
        try {
            $request = $this->getRequest();
            $postedData = $request->getPost();
            $fiscalYearId = $postedData['fiscalYearId'];
            $positionId = $postedData['positionId'];
            $flatId = $postedData['flatId'];
            $assignedValue = $postedData['assignedValue'];

            $detailRepo = new PositionFlatValueRepo($this->adapter);
            $detailRepo->setPositionFlatValue($fiscalYearId, $positionId, $flatId, $assignedValue);

            return new JsonModel(['success' => true, 'data' => [], 'error' => '']);
        } catch (Exception $e) {
            return new JsonModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
        }
    }

    public function uploadFlatValueEmpWiseAction() {
        try {
            $request = $this->getRequest();
            $files = $request->getFiles()->toArray();

            if (sizeof($files) > 0) {
                $ext = pathinfo($files['excel_file']['name'], PATHINFO_EXTENSION);
                $fileName = pathinfo($files['excel_file']['name'], PATHINFO_FILENAME);
                $unique = Helper::generateUniqueName();
                $newFileName = $unique . "." . $ext;
                $uploadPath = Helper::UPLOAD_DIR . "/payroll/" . $newFileName;


                if ($ext != 'xlsx') {
                    throw new Exception("Please upload a xlsx file");
                }



                if (!empty($_FILES["excel_file"])) {
                    $success = move_uploaded_file($files['excel_file']['tmp_name'], $uploadPath);
                    if (!$success) {
                        throw new Exception("Upload unsuccessful.");
                    }

                    $reader = new Xlsx();
                    $spreadsheet = $reader->load($uploadPath);
                    $sheetData = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);
                    $i = 0;
                    $detailRepo = new FlatValueDetailRepo($this->adapter);
                    foreach ($sheetData as $importDetails) {
                        $data = [];

                        if ($i > 0) {
                            $data['employeeId'] = $importDetails['A'];
                            $data['fiscalYearId'] = $importDetails['D'];
                            $data['flatId'] = $importDetails['E'];
                            $data['flatValue'] = $importDetails['F'];

                            if ($data['employeeId']) {
                                $detailRepo->postFlatValuesDetail($data);
                            }
                        }

                        $i++;
                    }
                }
            } else {
                throw new Exception('Error Reading File');
            }

            $this->flashmessenger()->addMessage("Sucessfully Uploaded.");
            return $this->redirect()->toRoute("flatValue", ["action" => "detail"]);
        } catch (Exception $e) {
            $errorMSg = $e->getMessage();
            $this->flashmessenger()->addMessage("Error !!" . $errorMSg);
            return $this->redirect()->toRoute("flatValue", ["action" => "detail"]);
        }
    }

    public function bulkDetailAction() {
        $flatValues = EntityHelper::getTableList($this->adapter, FlatValueModel::TABLE_NAME, [FlatValueModel::FLAT_ID, FlatValueModel::FLAT_EDESC], [FlatValueModel::STATUS => EntityHelper::STATUS_ENABLED]);
        $fiscalYears = EntityHelper::getTableList($this->adapter, FiscalYear::TABLE_NAME, [FiscalYear::FISCAL_YEAR_ID, FiscalYear::FISCAL_YEAR_NAME]);
        return $this->stickFlashMessagesTo([
                    'searchValues' => EntityHelper::getSearchData($this->adapter),
                    'flatValues' => $flatValues,
                    'fiscalYears' => $fiscalYears,
                    'acl' => $this->acl,
        ]);
    }

    //From here flat value assign changes added
    public function getBulkFlatValueDetailAction() {
        try {
            $request = $this->getRequest();
            if (!$request->isPost()) {
                throw new Exception("The request should be of type post");
            }
            $postedData = $request->getPost();
            $flatId = $postedData['flatId'];
            $pivotString = '';
            for($i = 0; $i < count($flatId); $i++){
                if($i != 0){ $pivotString.=','; }
                $pivotString.= $flatId[$i].' AS F_'.$flatId[$i];
            }
            $fiscalYearId = $postedData['fiscalYearId'];
            $employeeFilter = $postedData['employeeFilter'];
            $detailRepo = new FlatValueDetailRepo($this->adapter);
            $result = $detailRepo->getBulkFlatValuesDetailById($pivotString, $fiscalYearId, $employeeFilter);
            $columns = $detailRepo->getColumns($flatId);
            return new JsonModel(['success' => true, 'data' => Helper::extractDbData($result), 'error' => '', 'columns' => Helper::extractDbData($columns)]);
        } catch (Exception $e) {
            return new JsonModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
        }
    }

    public function postBulkFlatValueDetailAction() {
        try {
            $request = $this->getRequest();
            if (!$request->isPost()) {
                throw new Exception("The request should be of type post");
            }
            $postedData = $request->getPost();
            $data = $postedData['data'];
            $fiscalYearId = $postedData['fiscalYearId'];
            $detailRepo = new FlatValueDetailRepo($this->adapter);
            foreach($data as $d){
                if($d['employeeId'] == null || $d['employeeId'] == ''){
                    continue;
                }
                $detailRepo->postBulkFlatValuesDetail($d, $fiscalYearId);
            }
            return new JsonModel(['success' => true, 'error' => '']);
        } catch (Exception $e) {
            return new JsonModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
        }
    }

    public function positionWiseFlatValueAction() {
        $fiscalYears = EntityHelper::getTableList($this->adapter, FiscalYear::TABLE_NAME, [FiscalYear::FISCAL_YEAR_ID, FiscalYear::FISCAL_YEAR_NAME]);
        $flatValues = EntityHelper::getTableList($this->adapter, FlatValueModel::TABLE_NAME, [FlatValueModel::FLAT_ID, FlatValueModel::FLAT_EDESC], [FlatValueModel::STATUS => EntityHelper::STATUS_ENABLED, FlatValueModel::ASSIGN_TYPE => 'P']);
        $positions = EntityHelper::getTableList($this->adapter, Position::TABLE_NAME, [Position::POSITION_ID, Position::POSITION_NAME, Position::LEVEL_NO],["STATUS='E'"]);
        return $this->stickFlashMessagesTo([
                    'fiscalYears' => $fiscalYears,
                    'flatValues' => $flatValues,
                    'positions' => $positions,
                    'acl' => $this->acl,
					'employeeDetail' => $this->storageData['employee_detail'],
        ]);
    }

    public function getPositionWiseFlatValueAction() {
        try {
            $request = $this->getRequest();
            if (!$request->isPost()) {
                throw new Exception("The request should be of type post");
            }
            $postedData = $request->getPost();
            $flatId = $postedData['flatId'];
            $positionId = $postedData['positionId'];
            $pivotString = '';
            for($i = 0; $i < count($flatId); $i++){
                if($i != 0){ $pivotString.=','; }
                $pivotString.= $flatId[$i].' AS F_'.$flatId[$i];
            }
            $fiscalYearId = $postedData['fiscalYearId'];
            $detailRepo = new FlatValueDetailRepo($this->adapter);
            $result = $detailRepo->getPositionWiseFlatValue($pivotString, $fiscalYearId, $positionId);
            $columns = $detailRepo->getColumns($flatId);
            return new JsonModel(['success' => true, 'data' => Helper::extractDbData($result), 'error' => '', 'columns' => Helper::extractDbData($columns)]);
        } catch (Exception $e) {
            return new JsonModel(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    public function setPositionWiseFlatValueAction() {
        try {
            $request = $this->getRequest();
            if (!$request->isPost()) {
                throw new Exception("The request should be of type post");
            }
            $postedData = $request->getPost();
            $data = $postedData['data'];
            $fiscalYearId = $postedData['fiscalYearId'];
            $detailRepo = new FlatValueDetailRepo($this->adapter);
            foreach($data as $d){
                if($d['positionId'] == null || $d['positionId'] == ''){
                    continue;
                }
                $detailRepo->setPositionWiseFlatValue($d, $fiscalYearId);
            } 
            return new JsonModel(['success' => true, 'error' => '']);
        } catch (Exception $e) {
            return new JsonModel(['success' => false, 'error' => $e->getMessage()]);
        }
    }
}
