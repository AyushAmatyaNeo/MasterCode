<?php

namespace Application\Controller;

use Application\Helper\EntityHelper;
use Application\Helper\Helper;
use Application\Model\Files;
use Application\Model\FiscalYear;
use Application\Repository\FileRepository;
use Application\Repository\MonthRepository;
use Exception;
use ReflectionClass;
use Zend\Authentication\Storage\StorageInterface;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Form\Annotation\AnnotationBuilder;
use Zend\Form\Element\Select;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\JsonModel;

class HrisController extends AbstractActionController {

    protected $adapter;
    protected $employeeId;
    protected $storageData;
    protected $acl;
    protected $preference;
    protected $form;
    protected $repository;
    protected $status = [
        '-1' => 'All Status',
        'RQ' => 'Pending',
        'RC' => 'Recommended',
        'AP' => 'Approved',
        'R' => 'Rejected',
        'C' => 'Cancelled',
        'CP' => 'Cancel Pending',
        'CR' => 'Cancel Recommended'
    ];

    function __construct(AdapterInterface $adapter, StorageInterface $storage) {
        $this->adapter = $adapter;
        $this->storageData = $storage->read();
        $this->acl = $this->storageData['acl'];
        $this->preference = $this->storageData['preference'];
        $this->employeeId = $this->storageData['employee_id'];
    }

    protected function stickFlashMessagesTo($return) {
        $flashMessenger = $this->flashMessenger();
        if ($flashMessenger->hasMessages()) {
            $return['messages'] = $flashMessenger->getMessages();
        }
        return $return;
    }

    protected function initializeForm(string $formClass) {
        $builder = new AnnotationBuilder();
        $refl = new ReflectionClass($formClass);
        $formObject = $refl->newInstanceArgs();
        $this->form = $builder->createForm($formObject);
    }

    protected function initializeRepository(string $repositoryClass) {
        $refl = new ReflectionClass($repositoryClass);
        $this->repository = $refl->newInstanceArgs([$this->adapter]);
    }

    protected function getStatusSelectElement(array $config) {
        return $this->getSelectElement($config, $this->status);
    }

    protected function getSelectElement($config, $options) {
        $selectFE = new Select();
        $selectFE->setName($config['name']);
        $selectFE->setValueOptions($options);
        $selectFE->setAttributes(["id" => $config['id'], "class" => $config['class']]);
        $selectFE->setLabel($config['label']);
        return $selectFE;
    }

    protected function listValueToKV($list, $key, $value, $optional = false) {
        $output = [];
        if ($optional) {
            $output[-1] = '---------';
        }
        foreach ($list as $item) {
            $output[$item[$key]] = $item[$value];
        }
        return $output;
    }

    protected function getACLFilter() {
        $filter = [];
        switch ($this->acl['CONTROL']) {
            case 'C':
                $filter['companyId'] = $this->storageData['employee_detail']['COMPANY_ID'];
                break;
            case 'B':
                $filter['branchId'] = $this->storageData['employee_detail']['BRANCH_ID'];
                break;
            case 'U':
                $filter['employeeId'] = $this->storageData['employee_detail']['EMPLOYEE_ID'];
                break;
        }
        return $filter;
    }

    public function uploadFileAction() {
        try {
            $request = $this->getRequest();
            $files = $request->getFiles()->toArray();
            if (sizeof($files) <= 0) {
                throw new Exception("No file is uploaded");
            }
            $ext = pathinfo($files['file']['name'], PATHINFO_EXTENSION);
            $fileName = pathinfo($files['file']['name'], PATHINFO_FILENAME);
            $unique = Helper::generateUniqueName();
            $newFileName = $unique . "." . $ext;
            $success = move_uploaded_file($files['file']['tmp_name'], Helper::UPLOAD_DIR . "/" . $newFileName);
            if (!$success) {
                throw new Exception("Moving uploaded file failed");
            }
            $fileRepository = new FileRepository($this->adapter);
            $file = new Files();
            $file->fileId = ((int) Helper::getMaxId($this->adapter, Files::TABLE_NAME, Files::FILE_ID)) + 1;
            $file->fileName = $fileName . "." . $ext;
            $file->fileInDirName = $newFileName;
            $file->uploadedDate = Helper::getcurrentExpressionDate();
            $file->uploadedBy = $this->employeeId;
            $fileRepository->add($file);
            return new JsonModel(['success' => true, 'data' => (array) $file, 'error' => '']);
        } catch (Exception $e) {
            return new JsonModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
        }
    }

    public function getFileDetailAction() {
        try {
            $request = $this->getRequest();
            $postedData = (array) $request->getPost();
            $fileRepository = new FileRepository($this->adapter);
            $fileDetail = $fileRepository->fetchById($postedData['fileId']);
            return new JsonModel(['success' => true, 'data' => $fileDetail, 'error' => '']);
        } catch (Exception $e) {
            return new JsonModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
        }
    }

    public function getFiscalYearMonthAction() {
        try {
            $data['years'] = EntityHelper::getTableList($this->adapter, FiscalYear::TABLE_NAME, [FiscalYear::FISCAL_YEAR_ID, FiscalYear::FISCAL_YEAR_NAME]);
            $monthRepo = new MonthRepository($this->adapter);
            $data['months'] = iterator_to_array($monthRepo->fetchAll(), false);
            $data['currentMonth'] = $monthRepo->getCurrentMonth();
            return new JsonModel(['success' => true, 'data' => $data, 'error' => '']);
        } catch (Exception $e) {
            return new JsonModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
        }
    }

    public function getSearchDataAction() {
        try {
            $data = EntityHelper::getSearchData($this->adapter);
            return new JsonModel(['success' => true, 'data' => $data, 'error' => '']);
        } catch (Exception $e) {
            return new JsonModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
        }
    }

    public function regenAttendanceAction() {
        try {
            $request = $this->getRequest();
            $data = $request->getPost();
            $boundedParameter = [];
            $boundedParameter['employeeId']=$data['EMPLOYEE_ID'];
            $boundedParameter['fromDate']=$data['FROM_DATE'];
            $toDateQuery = "";
            if (isset($data['TO_DATE'])) {
            $boundedParameter['toDate']=$data['TO_DATE'];
            }
            EntityHelper::rawQueryResult($this->adapter, "BEGIN HRIS_REATTENDANCE(:fromDate,:employeeId,:toDate); END;",$boundedParameter);
            return new JsonModel(['success' => true, 'data' => null, 'message' => "Reattendance successful."]);
        } catch (Exception $e) {
            return new JsonModel(['success' => false, 'data' => null, 'message' => $e->getMessage()]);
        }
    }

    public function checkUniqueAction() {
//        note
//        dont use this function security Concern
//        
//        
//        try {
//            $request = $this->getRequest();
//            $postedData = $request->getPost();
//            $sql = "SELECT {$postedData['columnName']}
//                    FROM {$postedData['tableName']}
//                    WHERE {$postedData['columnName']} = {$postedData['columnValue']}
//                    AND ( {$postedData['columnName']}  !=
//                      (SELECT {$postedData['columnName']}
//                      FROM {$postedData['tableName']}
//                      WHERE {$postedData['pkName']} = {$postedData['pkValue']} ) or 1=1)";
//            $result = EntityHelper::rawQueryResult($this->adapter, $sql);
//            $data['notUnique'] = count($result) > 0;
//            $data['message'] = "Already Reserved";
//            return new JsonModel(['success' => true, 'data' => $data, 'error' => '']);
//        } catch (Exception $e) {
//            return new JsonModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
//        }
    }

    public function getEmpListFromSearchValues($data) {
        $companyId = isset($data['companyId']) ? $data['companyId'] : -1;
        $branchId = isset($data['branchId']) ? $data['branchId'] : -1;
        $departmentId = isset($data['departmentId']) ? $data['departmentId'] : -1;
        $designationId = isset($data['designationId']) ? $data['designationId'] : -1;
        $positionId = isset($data['positionId']) ? $data['positionId'] : -1;
        $serviceTypeId = isset($data['serviceTypeId']) ? $data['serviceTypeId'] : -1;
        $serviceEventTypeId = isset($data['serviceEventTypeId']) ? $data['serviceEventTypeId'] : -1;
        $employeeTypeId = isset($data['employeeTypeId']) ? $data['employeeTypeId'] : -1;
        $genderId = isset($data['genderId']) ? $data['genderId'] : -1;
        $functionalTypeId = isset($data['functionalTypeId']) ? $data['functionalTypeId'] : -1;
        $employeeId = isset($data['employeeId']) ? $data['employeeId'] : -1;

        $searchConditon = EntityHelper::getSearchConditon($companyId, $branchId, $departmentId, $positionId, $designationId, $serviceTypeId, $serviceEventTypeId, $employeeTypeId, $employeeId, $genderId, null, $functionalTypeId);

        $sql = "SELECT 
                    E.EMPLOYEE_ID
                    ,E.EMPLOYEE_CODE
                    ,E.FULL_NAME
                    FROM HRIS_EMPLOYEES E
                    WHERE E.STATUS='E' 
                {$searchConditon}
                 ORDER BY SENIORITY_LEVEL ASC";
        $list = EntityHelper::rawQueryResult($this->adapter, $sql);
        return iterator_to_array($list, false);
    }

}
