<?php

namespace Setup\Controller;

use Application\Controller\HrisController;
use Application\Custom\CustomViewModel;
use Application\Helper\ACLHelper;
use Application\Helper\Helper;
use Exception;
use Setup\Form\CompanyForm;
use Setup\Model\Company;
use Setup\Model\EmployeeFile as EmployeeFile2;
use Setup\Repository\CompanyRepository;
use Setup\Repository\EmployeeFile;
use System\Repository\SynergyRepository;
use Zend\Authentication\Storage\StorageInterface;
use Zend\Db\Adapter\AdapterInterface;
use Zend\View\Model\JsonModel;
use Zend\View\Model\ViewModel;

class CompanyController extends HrisController {

    private $synergyRepo;

    public function __construct(AdapterInterface $adapter, StorageInterface $storage) {
        parent::__construct($adapter, $storage);
        $this->initializeRepository(CompanyRepository::class);
        $this->initializeForm(CompanyForm::class);
        $this->synergyRepo = new SynergyRepository($adapter);
    }

    public function indexAction() {
        $request = $this->getRequest();
        if ($request->isPost()) {
            try {
                $result = $this->repository->fetchAll();
                $companyList = Helper::extractDbData($result);
                return new JsonModel(['success' => true, 'data' => $companyList, 'error' => '']);
            } catch (Exception $e) {
                return new JsonModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
            }
        }
        return Helper::addFlashMessagesToArray($this, ['acl' => $this->acl]);
    }

    public function addAction() {
        $request = $this->getRequest();
        $imageData = null;
        if ($request->isPost()) {
            $postedData = $request->getPost();
            $this->form->setData($postedData);
            if ($this->form->isValid()) {
                $company = new Company();
                $company->exchangeArrayFromForm($this->form->getData());
                $company->createdDt = Helper::getcurrentExpressionDate();
                $company->createdBy = $this->employeeId;
                $company->companyId = ((int) Helper::getMaxId($this->adapter, Company::TABLE_NAME, Company::COMPANY_ID)) + 1;
                $company->logo = $postedData['logo'];
                $company->status = 'E';
                $this->repository->add($company);
                $this->flashmessenger()->addMessage("Company Successfully added.");
                return $this->redirect()->toRoute("company");
            } else {
                $imageData = $this->getFileInfo($this->adapter, $postedData['logo']);
            }
        }
        return new ViewModel(Helper::addFlashMessagesToArray(
                        $this, [
                    'form' => $this->form,
                    'messages' => $this->flashmessenger()->getMessages(),
                    'imageData' => $imageData
                        ]
                )
        );
    }

    private function getFileInfo(AdapterInterface $adapter, $fileId) {
        $fileRepo = new EmployeeFile($adapter);
        $fileDetail = $fileRepo->fetchById($fileId);

        if ($fileDetail == null) {
            $imageData = [
                'fileCode' => null,
                'fileName' => null,
                'oldFileName' => null
            ];
        } else {
            $imageData = [
                'fileCode' => $fileDetail['FILE_CODE'],
                'oldFileName' => $fileDetail['FILE_NAME'],
                'fileName' => $fileDetail['FILE_PATH']
            ];
        }
        return $imageData;
    }

    public function editAction() {
        $id = (int) $this->params()->fromRoute("id");
        if ($id === 0) {
            return $this->redirect()->toRoute('company');
        }
        $request = $this->getRequest();

        $company = new Company();
        if (!$request->isPost()) {
            $company->exchangeArrayFromDB($this->repository->fetchById($id)->getArrayCopy());
            $this->prepareForm($company->companyCode);
            $this->form->bind($company);
        } else {
            $postedData = $request->getPost();
            $this->form->setData($postedData);
            if ($this->form->isValid()) {
                $company->exchangeArrayFromForm($this->form->getData());
                $company->modifiedDt = Helper::getcurrentExpressionDate();
                $company->modifiedBy = $this->employeeId;
                $company->logo = $postedData['logo'];
                $this->repository->edit($company, $id);
                $this->flashmessenger()->addMessage("Company Successfully Updated!!!");
                return $this->redirect()->toRoute("company");
            }
        }

        $imageData = $this->getFileInfo($this->adapter, $company->logo);

        return Helper::addFlashMessagesToArray(
                        $this, [
                    'form' => $this->form,
                    'id' => $id,
                    'imageData' => $imageData,
                    'customRenderer' => Helper::renderCustomView(),
                        ]
        );
    }

    private function prepareForm($companyCode=null) {
        $formCode = $this->form->get('formCode');
        $drAccCode = $this->form->get('drAccCode');
        $crAccCode = $this->form->get('crAccCode');
        $excessCrAccCode = $this->form->get('excessCrAccCode');
        $lessDrAccCode = $this->form->get('lessDrAccCode');
        $equalCrAccCode = $this->form->get('equalCrAccCode');
        $advanceDrAccCode = $this->form->get('advanceDrAccCode');
        $advanceCrAccCode = $this->form->get('advanceCrAccCode');
        $formCodeFac = $this->form->get('formCodeFac');
        $drAccCodeFac = $this->form->get('drAccCodeFac');
        $crAccCodeFac = $this->form->get('crAccCodeFac');
        $excessCrAccCodeFac = $this->form->get('excessCrAccCodeFac');
        $lessDrAccCodeFac = $this->form->get('lessDrAccCodeFac');
        $equalCrAccCodeFac = $this->form->get('equalCrAccCodeFac');
        $advanceDrAccCodeFac = $this->form->get('advanceDrAccCodeFac');
        $advanceCrAccCodeFac = $this->form->get('advanceCrAccCodeFac');
        
        $companyCode=($companyCode!=null)?$companyCode:$this->storageData['company_detail']['COMPANY_CODE'];
        $formCodeList = $this->synergyRepo->getFormList($companyCode);
        $accCodeList = $this->synergyRepo->getAccountList($companyCode);
        $formCode->setValueOptions($this->listValueToKV($formCodeList, "FORM_CODE", "FORM_EDESC"));

        $drAccCode->setValueOptions($this->listValueToKV($accCodeList, "ACC_CODE", "ACC_EDESC"));
        $crAccCode->setValueOptions($this->listValueToKV($accCodeList, "ACC_CODE", "ACC_EDESC"));
        $excessCrAccCode->setValueOptions($this->listValueToKV($accCodeList, "ACC_CODE", "ACC_EDESC"));
        $lessDrAccCode->setValueOptions($this->listValueToKV($accCodeList, "ACC_CODE", "ACC_EDESC"));
        $equalCrAccCode->setValueOptions($this->listValueToKV($accCodeList, "ACC_CODE", "ACC_EDESC"));
        $advanceDrAccCode->setValueOptions($this->listValueToKV($accCodeList, "ACC_CODE", "ACC_EDESC"));
        $advanceCrAccCode->setValueOptions($this->listValueToKV($accCodeList, "ACC_CODE", "ACC_EDESC"));
        $formCode->setValueOptions($this->listValueToKV($formCodeList, "FORM_CODE", "FORM_EDESC"));
        
        $formCodeFac->setValueOptions($this->listValueToKV($formCodeList, "FORM_CODE", "FORM_EDESC"));

        $drAccCodeFac->setValueOptions($this->listValueToKV($accCodeList, "ACC_CODE", "ACC_EDESC"));
        $crAccCodeFac->setValueOptions($this->listValueToKV($accCodeList, "ACC_CODE", "ACC_EDESC"));
        $excessCrAccCodeFac->setValueOptions($this->listValueToKV($accCodeList, "ACC_CODE", "ACC_EDESC"));
        $lessDrAccCodeFac->setValueOptions($this->listValueToKV($accCodeList, "ACC_CODE", "ACC_EDESC"));
        $equalCrAccCodeFac->setValueOptions($this->listValueToKV($accCodeList, "ACC_CODE", "ACC_EDESC"));
        $advanceDrAccCodeFac->setValueOptions($this->listValueToKV($accCodeList, "ACC_CODE", "ACC_EDESC"));
        $advanceCrAccCodeFac->setValueOptions($this->listValueToKV($accCodeList, "ACC_CODE", "ACC_EDESC"));
        $formCodeFac->setValueOptions($this->listValueToKV($formCodeList, "FORM_CODE", "FORM_EDESC"));

    }

    public function deleteAction() {
        if (!ACLHelper::checkFor(ACLHelper::DELETE, $this->acl, $this)) {
            return;
        };
        $id = (int) $this->params()->fromRoute("id");
        $this->repository->delete($id);
        $this->flashmessenger()->addMessage("Company Successfully Deleted!!!");
        return $this->redirect()->toRoute('company');
    }

    public function fileUploadAction() {
        try {
            $request = $this->getRequest();
            $files = $request->getFiles()->toArray();
            if (sizeof($files) > 0) {
                $ext = pathinfo($files['file']['name'], PATHINFO_EXTENSION);
                $fileName = pathinfo($files['file']['name'], PATHINFO_FILENAME);
                $unique = Helper::generateUniqueName();
                $newFileName = $unique . "." . $ext;
                $success = move_uploaded_file($files['file']['tmp_name'], Helper::UPLOAD_DIR . "/" . $newFileName);
                if ($success) {
                    $fileRepository = new EmployeeFile($this->adapter);
                    $file = new EmployeeFile2();
                    $file->fileCode = ((int) Helper::getMaxId($this->adapter, 'HRIS_EMPLOYEE_FILE', 'FILE_CODE')) + 1;
                    $file->filePath = $newFileName;
                    $file->fileName = $fileName . "." . $ext;
                    $file->status = 'E';
                    $file->createdDt = Helper::getcurrentExpressionDate();
                    $fileRepository->add($file);
                    return new CustomViewModel(['success' => true, 'data' => ["fileName" => $newFileName, "oldFileName" => $fileName . "." . $ext, 'fileCode' => $file->fileCode], 'error' => '']);
                } else {
                    throw new Exception("Moving uploaded file failed");
                }
            } else {
                throw new Exception("No file is uploaded");
            }
        } catch (Exception $e) {
            return new CustomViewModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
        }
    }

}

?>