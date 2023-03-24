<?php

namespace Payroll\Controller;

use Application\Controller\HrisController;
use Application\Helper\EntityHelper;
use Application\Helper\Helper;
use Application\Model\FiscalYear;
use Application\Model\Months;
use Exception;
use Payroll\Form\MonthlyValue as MonthlyValueForm;
use Payroll\Model\MonthlyValue as MonthlyValueModel;
use Payroll\Repository\MonthlyValueDetailRepo;
use Payroll\Repository\MonthlyValueRepository;
use Payroll\Repository\PositionMonthlyValueRepo;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use Setup\Model\Position;
use Zend\Authentication\Storage\StorageInterface;
use Zend\Db\Adapter\AdapterInterface;
use Zend\View\Model\JsonModel;

class MonthlyValue extends HrisController {

    public function __construct(AdapterInterface $adapter, StorageInterface $storage) {
        parent::__construct($adapter, $storage);
        $this->initializeRepository(MonthlyValueRepository::class);
        $this->initializeForm(MonthlyValueForm::class);
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
                $monthlyValue = new MonthlyValueModel();
                $monthlyValue->exchangeArrayFromForm($this->form->getData());
                $monthlyValue->mthId = ((int) Helper::getMaxId($this->adapter, MonthlyValueModel::TABLE_NAME, MonthlyValueModel::MTH_ID)) + 1;
                $monthlyValue->createdDt = Helper::getcurrentExpressionDate();
                $monthlyValue->status = 'E';
                $this->repository->add($monthlyValue);
                $this->flashmessenger()->addMessage("Monthly Value added Successfully.");
                return $this->redirect()->toRoute("monthlyValue");
            }
        }
        return [
            'form' => $this->form,
            'customRenderer' => Helper::renderCustomView(),
			'acl' => $this->acl,
					'employeeDetail' => $this->storageData['employee_detail'],
        ];
    }

    public function editAction() {
        $id = (int) $this->params()->fromRoute("id");
        $request = $this->getRequest();

        $monthlyValueMode = new MonthlyValueModel();
        if (!$request->isPost()) {
            $monthlyValueMode->exchangeArrayFromDB($this->repository->fetchById($id)->getArrayCopy());
            $this->form->bind($monthlyValueMode);
        } else {
            $this->form->setData($request->getPost());
            if ($this->form->isValid()) {
                $monthlyValueMode->exchangeArrayFromForm($this->form->getData());
                $monthlyValueMode->modifiedDt = Helper::getcurrentExpressionDate();
                unset($monthlyValueMode->createdDt);
                unset($monthlyValueMode->mthId);
                unset($monthlyValueMode->status);
                $this->repository->edit($monthlyValueMode, $id);
                $this->flashmessenger()->addMessage("Monthly Value updated successfully.");
                return $this->redirect()->toRoute("monthlyValue");
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
            return $this->redirect()->toRoute('monthlyValue');
        }
        $this->repository->delete($id);
        $this->flashmessenger()->addMessage("Monthly Value Successfully Deleted!!!");
        return $this->redirect()->toRoute('monthlyValue');
    }

    public function detailAction() {
        $monthlyValues = EntityHelper::getTableList($this->adapter, MonthlyValueModel::TABLE_NAME, [MonthlyValueModel::MTH_ID, MonthlyValueModel::MTH_EDESC]);
        $fiscalYears = EntityHelper::getTableList($this->adapter, FiscalYear::TABLE_NAME, [FiscalYear::FISCAL_YEAR_ID, FiscalYear::FISCAL_YEAR_NAME]);
        $months = EntityHelper::getTableList($this->adapter, Months::TABLE_NAME, [Months::MONTH_ID, Months::MONTH_EDESC, Months::FISCAL_YEAR_ID],null,'','FISCAL_YEAR_MONTH_NO');
        return Helper::addFlashMessagesToArray($this, [
                    'searchValues' => EntityHelper::getSearchData($this->adapter),
                    'monthlyValues' => $monthlyValues,
                    'fiscalYears' => $fiscalYears,
                    'months' => $months,
                    'acl' => $this->acl,
					'employeeDetail' => $this->storageData['employee_detail'],
        ]);
    }

    public function getMonthlyValueDetailAction() {
        try {
            $request = $this->getRequest();
            if (!$request->isPost()) {
                throw new Exception("The request should be of type post");
            }
            $postedData = $request->getPost();
            $mthId = $postedData['mthId'];
            $fiscalYearId = $postedData['fiscalYearId'];
            $employeeFilter = $postedData['employeeFilter'];

            $detailRepo = new MonthlyValueDetailRepo($this->adapter);
            $result = $detailRepo->getMonthlyValuesDetailById($mthId, $fiscalYearId, $employeeFilter);
            $employeeList=$this->getEmpListFromSearchValues($employeeFilter);
            return new JsonModel(['success' => true, 'data' => Helper::extractDbData($result),'employeeList'=>$employeeList , 'error' => '']);
        } catch (Exception $e) {
            return new JsonModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
        }
    }

    public function postMonthlyValueDetailAction() {
        try {
            $request = $this->getRequest();
            if (!$request->isPost()) {
                throw new Exception("The request should be of type post");
            }
            $postedData = $request->getPost();
            $data = $postedData['data'];
            $detailRepo = new MonthlyValueDetailRepo($this->adapter);
            $detailRepo->postMonthlyValuesDetail($data);

            return new JsonModel(['success' => true, 'data' => $data, 'error' => '']);
        } catch (Exception $e) {
            return new JsonModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
        }
    }

    public function positionWiseAction() {
        $fiscalYears = EntityHelper::getTableList($this->adapter, FiscalYear::TABLE_NAME, [FiscalYear::FISCAL_YEAR_ID, FiscalYear::FISCAL_YEAR_NAME]);
        $months = EntityHelper::getTableList($this->adapter, Months::TABLE_NAME, [Months::MONTH_ID, Months::MONTH_EDESC, Months::FISCAL_YEAR_ID]);
        $monthlyValues = EntityHelper::getTableList($this->adapter, MonthlyValueModel::TABLE_NAME, [MonthlyValueModel::MTH_ID, MonthlyValueModel::MTH_EDESC]);
        $positions = EntityHelper::getTableList($this->adapter, Position::TABLE_NAME, [Position::POSITION_ID, Position::POSITION_NAME, Position::LEVEL_NO],["STATUS='E'"],"","LEVEL_NO ASC");
        return Helper::addFlashMessagesToArray($this, [
                    'fiscalYears' => $fiscalYears,
                    'months' => $months,
                    'monthlyValues' => $monthlyValues,
                    'positions' => $positions,
					'acl' => $this->acl,
					'employeeDetail' => $this->storageData['employee_detail'],
        ]);
    }

    public function getPositionMonthlyValueAction() {
        try {
            $request = $this->getRequest();
            if (!$request->isPost()) {
                throw new Exception("The request should be of type post");
            }
            $postedData = $request->getPost();
            $monthId = $postedData['monthId'];

            $detailRepo = new PositionMonthlyValueRepo($this->adapter);
            $result = $detailRepo->getPositionMonthlyValue($monthId);

            return new JsonModel(['success' => true, 'data' => Helper::extractDbData($result), 'error' => '']);
        } catch (Exception $e) {
            return new JsonModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
        }
    }

    public function setPositionMonthlyValueAction() {
        try {
            $request = $this->getRequest();
            if (!$request->isPost()) {
                throw new Exception("The request should be of type post");
            }
            $postedData = $request->getPost();
            $monthId = $postedData['monthId'];
            $positionId = $postedData['positionId'];
            $mthId = $postedData['mthId'];
            $assignedValue = $postedData['assignedValue'];

            $detailRepo = new PositionMonthlyValueRepo($this->adapter);
            $detailRepo->setPositionMonthlyValue($monthId, $positionId, $mthId, $assignedValue);

            return new JsonModel(['success' => true, 'data' => [], 'error' => '']);
        } catch (Exception $e) {
            return new JsonModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
        }
    }

    public function uploadMonthlyValueEmpWiseAction() {
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
                    foreach ($sheetData as $importDetails) {
                        $data = [];

                        if ($i > 0) {
                            $data['employeeId'] = (int) $importDetails['A'];
                            $data['fiscalYearId'] = (int) $importDetails['D'];
                            $data['mthId'] = (int) $importDetails['E'];


                            $detailRepo = new MonthlyValueDetailRepo($this->adapter);
                            $monthDetails = $detailRepo->getMonthDeatilByFiscalYear($data['fiscalYearId']);
                            $monthCounter = 'G';
                            foreach ($monthDetails as $month) {
                                $data['monthId'] = $month['MONTH_ID'];
                                $data['mthValue'] = $importDetails[$monthCounter];
                                if ($data['mthValue']) {
                                    $detailRepo->postMonthlyValuesDetail($data);
                                }
                                $monthCounter++;
                            }
                        }

                        $i++;
                    }
                }
            } else {
                throw new Exception('Error Reading File');
            }

            $this->flashmessenger()->addMessage("Sucessfully Uploaded.");
            return $this->redirect()->toRoute("monthlyValue", ["action" => "detail"]);
        } catch (Exception $e) {
            $errorMSg = $e->getMessage();
            $this->flashmessenger()->addMessage("Error !!" . $errorMSg);
            return $this->redirect()->toRoute("monthlyValue", ["action" => "detail"]);
        }
    }

}
