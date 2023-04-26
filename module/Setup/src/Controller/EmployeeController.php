<?php
namespace Setup\Controller;

use Application\Controller\HrisController;
use Application\Factory\ConfigInterface;
use Application\Helper\EntityHelper;
use Application\Helper\EntityHelper as ApplicationHelper;
use Application\Helper\Helper;
use Application\Model\HrisQuery;
use Asset\Repository\IssueRepository;
use AttendanceManagement\Model\ShiftSetup;
use AttendanceManagement\Repository\ShiftAssignRepository;
use Exception;
use LeaveManagement\Model\LeaveMaster;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Setup\Form\HrEmployeesFormTabEight;
use Setup\Form\HrEmployeesFormTabFive;
use Setup\Form\HrEmployeesFormTabFour;
use Setup\Form\HrEmployeesFormTabNine;
use Setup\Form\HrEmployeesFormTabOne;
use Setup\Form\HrEmployeesFormTabSeven;
use Setup\Form\HrEmployeesFormTabSix;
use Setup\Form\HrEmployeesFormTabThree;
use Setup\Form\HrEmployeesFormTabTwo;
use Setup\Model\AcademicCourse;
use Setup\Model\AcademicDegree;
use Setup\Model\AcademicProgram;
use Setup\Model\AcademicUniversity;
use Setup\Model\Branch;
use Setup\Model\Company;
use Setup\Model\Department;
use Setup\Model\Designation;
use Setup\Model\EmployeeExperience;
use Setup\Model\EmployeeFile as EmployeeFileModel;
use Setup\Model\EmployeeQualification;
use Setup\Model\EmployeeTraining;
use Setup\Model\FunctionalTypes;
use Setup\Model\Gender;
use Setup\Model\HrEmployees;
use Setup\Model\Location;
use Setup\Model\Position;
use Setup\Model\RecommendApprove;
use Setup\Model\ServiceEventType;
use Setup\Model\ServiceType;
use Setup\Repository\AcademicCourseRepository;
use Setup\Repository\AcademicDegreeRepository;
use Setup\Repository\AcademicProgramRepository;
use Setup\Repository\AcademicUniversityRepository;
use Setup\Repository\EmployeeExperienceRepository;
use Setup\Repository\EmployeeFile;
use Setup\Repository\EmployeeQualificationRepository;
use Setup\Repository\EmployeeRepository;
use Setup\Repository\EmployeeTrainingRepository;
use Setup\Repository\JobHistoryRepository;
use Setup\Repository\RecommendApproveRepository;
use Zend\Authentication\Storage\StorageInterface;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Db\Sql\Expression;
use Zend\Form\Annotation\AnnotationBuilder;
use Zend\View\Model\JsonModel;

class EmployeeController extends HrisController {

    private $config;
    private $employeeFileRepo;
    private $jobHistoryRepo;
    private $formOne;
    private $formTwo;
    private $formThree;
    private $formFour;
    private $formSix;
    private $formSeven;
    private $formEight;
    private $formNine;
    private $countryList;
    private $erpCompanyCode;

    public function __construct(AdapterInterface $adapter, StorageInterface $storage, ConfigInterface $config) {
        parent::__construct($adapter, $storage);
        $this->repository = new EmployeeRepository($adapter);
        $this->employeeFileRepo = new EmployeeFile($this->adapter);
        $this->jobHistoryRepo = new JobHistoryRepository($this->adapter);
        $this->config = $config;
    }

    public function getCountryList() {
        if (!isset($this->countryList)) {
            $this->countryList = ApplicationHelper::getTableKVList($this->adapter, 'HRIS_COUNTRIES', 'COUNTRY_ID', ['COUNTRY_NAME'], null, null, true);
        }
        return $this->countryList;
    }

    public function indexAction() {
        return $this->stickFlashMessagesTo([
                'searchValues' => ApplicationHelper::getSearchData($this->adapter),
                'acl' => $this->acl,
                'employeeDetail' => $this->storageData['employee_detail'],
                'preference' => $this->preference,
                'provinces' => ApplicationHelper::getProvinceList($this->adapter),
                'braProv' => ApplicationHelper::getBranchFromProvince($this->adapter),
        ]);
    }

    public function contactAction() {
        return $this->stickFlashMessagesTo([
                'searchValues' => ApplicationHelper::getSearchData($this->adapter),
                'acl' => $this->acl,
                'employeeDetail' => $this->storageData['employee_detail'],
        ]);
    }

    public function initializeMultipleForm() {
        $builder = new AnnotationBuilder();
        $formTabOne = new HrEmployeesFormTabOne();
        $formTabTwo = new HrEmployeesFormTabTwo();
        $formTabThree = new HrEmployeesFormTabThree();
        $formTabFour = new HrEmployeesFormTabFour();
        $formTabFive = new HrEmployeesFormTabFive();
        $formTabSix = new HrEmployeesFormTabSix();
        $formTabSeven = new HrEmployeesFormTabSeven();
        $formTabEight = new HrEmployeesFormTabEight();
        $formTabNine = new HrEmployeesFormTabNine();

        if (!$this->formOne) {
            $this->formOne = $builder->createForm($formTabOne);
            $genderId = $this->formOne->get('genderId');
            $bloodGroupId = $this->formOne->get('bloodGroupId');
            $religionId = $this->formOne->get('religionId');
            $addrPermZoneId = $this->formOne->get('addrPermZoneId');
            $addrTempZoneId = $this->formOne->get('addrTempZoneId');
            $companyId = $this->formOne->get('companyId');
            $countryId = $this->formOne->get('countryId');
            $addrPermProvinceId = $this->formOne->get('addrPermProvinceId');
            $addrTempProvinceId = $this->formOne->get('addrTempProvinceId');

            $genderList = ApplicationHelper::getTableKVList($this->adapter, \Setup\Model\Gender::TABLE_NAME, \Setup\Model\Gender::GENDER_ID, [\Setup\Model\Gender::GENDER_NAME], null, null, true);
            $bloodGroupList = ApplicationHelper::getTableKVList($this->adapter, 'HRIS_BLOOD_GROUPS', 'BLOOD_GROUP_ID', ['BLOOD_GROUP_CODE'], NULL, NULL, TRUE);
            $zoneList = ApplicationHelper::getTableKVList($this->adapter, \Setup\Model\Zones::TABLE_NAME, \Setup\Model\Zones::ZONE_ID, [\Setup\Model\Zones::ZONE_NAME], null, null, true);
            $religionList = ApplicationHelper::getTableKVList($this->adapter, 'HRIS_RELIGIONS', 'RELIGION_ID', ['RELIGION_NAME'], null, null, true);
            $companyList = ApplicationHelper::getTableKVListWithSortOption($this->adapter, "HRIS_COMPANY", "COMPANY_ID", ["COMPANY_NAME"], ["STATUS" => "E"], "COMPANY_NAME", "ASC", null, false, true);
            $provinceList = ApplicationHelper::getTableKVListWithSortOption($this->adapter, "HRIS_PROVINCES", "PROVINCE_ID", ["PROVINCE_NAME"], null ,"PROVINCE_ID", "ASC", "-", true, true, null);


            $genderId->setValueOptions($genderList);
            $bloodGroupId->setValueOptions($bloodGroupList);
            $religionId->setValueOptions($religionList);
            $addrPermZoneId->setValueOptions($zoneList);
            $addrTempZoneId->setValueOptions($zoneList);
            $companyId->setValueOptions($companyList);
            $countryId->setValueOptions($this->getCountryList());
            $addrPermProvinceId->setValueOptions($provinceList);
            $addrTempProvinceId->setValueOptions($provinceList);
        }
        if (!$this->formTwo) {
            $this->formTwo = $builder->createForm($formTabTwo);
        }
        if (!$this->formThree) {
            $this->formThree = $builder->createForm($formTabThree);
            $idAccCode = $this->formThree->get('idAccCode');
            $bankAccountList = $this->repository->fetchBankAccountList($this->erpCompanyCode);
            $bankAccountKVList = $this->listValueToKV($bankAccountList, "ACC_CODE", "ACC_EDESC");
            $idAccCode->setValueOptions($bankAccountKVList);
            
            $empowerCompanyList = $this->repository->fetchEmpowerCompany();
            $empowerBranchList = $this->repository->fetchEmpowerBranch($this->erpCompanyCode);
            $empowerCompanyCodeKVList = $this->listValueToKV($empowerCompanyList, "COMPANY_CODE", "COMPANY_EDESC",true);
            $empowerBranchKVList = $this->listValueToKV($empowerBranchList, "BRANCH_CODE", "BRANCH_EDESC",true);
            

             $empowerCompanyCode = $this->formThree->get('empowerCompanyCode');
             $empowerBranchCode = $this->formThree->get('empowerBranchCode');
             $empowerCompanyCode->setValueOptions($empowerCompanyCodeKVList);
             $empowerBranchCode->setValueOptions($empowerBranchKVList);

             $bankId = $this->formThree->get('bankId');
             $bankNameList = ApplicationHelper::getTableKVListWithSortOption($this->adapter, "HRIS_BANKS", "BANK_ID", ["BANK_NAME"], "STATUS = 'E'" ,"BANK_ID", "ASC", "-", true, true, null);
             $bankId->setValueOptions($bankNameList);
        }
        if (!$this->formFour) {
            $this->formFour = $builder->createForm($formTabFour);
        }
        if (!$this->formSix) {
            $this->formSix = $builder->createForm($formTabSix);
        }
        if (!$this->formSeven) {
            $this->formSeven = $builder->createForm($formTabSeven);
        }
        if (!$this->formEight) {
            $this->formEight = $builder->createForm($formTabEight);
        }
        if (!$this->formNine) {
            $this->formNine = $builder->createForm($formTabNine);
        }
    }

    public function editAction() {
        $id = (int) $this->params()->fromRoute('id', 0);
        $tab = (int) $this->params()->fromRoute('tab', 1);
//echo $tab; die;
        if (11 === $tab) {
            $this->flashmessenger()->addMessage("Employee Successfully Submitted!!!");
            return $this->redirect()->toRoute('employee', ['action' => 'index']);
        }
        
            $syngergyTable=$this->repository->checkIfTableExists('COMPANY_SETUP');
            $distributionTable=$this->repository->checkIfTableExists('DIST_LOGIN_USER');
        
        $this->erpCompanyCode=$this->repository->getCompanyCodeByEmpId($id)['COMPANY_CODE'];
        
        $this->initializeMultipleForm();
        $request = $this->getRequest();

        $formOneModel = new HrEmployeesFormTabOne();
        $formTwoModel = new HrEmployeesFormTabTwo();
        $formThreeModel = new HrEmployeesFormTabThree();
        $formFourModel = new HrEmployeesFormTabFour();
        $formSixModel = new HrEmployeesFormTabSix();

        $shiftAssignRepo = new ShiftAssignRepository($this->adapter);
        $recommApproverRepo = new RecommendApproveRepository($this->adapter);
        $employeeData = null;
        $profilePictureId = null;
        $recAppDetail = null;
        if ($id != 0) {
            $employeeData = (array) $this->repository->fetchById($id);
            $profilePictureId = $employeeData[HrEmployees::PROFILE_PICTURE_ID];
            $recAppDetail = $recommApproverRepo->fetchById($id);
        }
        $address = [];
        if ($request->isPost()) {
            $postData = $request->getPost();
            switch ($tab) {
                case 1:
                    $this->formOne->setData($postData);
                    if ($this->formOne->isValid()) {

                        $formOneModel->exchangeArrayFromForm($this->formOne->getData());
                        $formOneModel->addrPermVdcMunicipalityId = $this->repository->vdcStringToId($postData['addrPermDistrictId'], $postData['addrPermVdcMunicipalityId']);
                        $formOneModel->addrTempVdcMunicipalityId = $this->repository->vdcStringToId($postData['addrTempDistrictId'], $postData['addrTempVdcMunicipalityId']);
                        $formOneModel->birthDate = Helper::getExpressionDate($formOneModel->birthDate);
                        $formOneModel->addrPermCountryId = 168;
                        $formOneModel->addrTempCountryId = 168;

                        if ($id == 0) {
                            $id = ((int) Helper::getMaxId($this->adapter, "HRIS_EMPLOYEES", "EMPLOYEE_ID")) + 1;
                            $formOneModel->employeeId = $id;
                            $formOneModel->createdBy = $this->employeeId;
                            $formOneModel->createdDt = Helper::getcurrentExpressionDate();
                            $this->repository->add($formOneModel);
                        } else {
                            $formOneModel->modifiedBy = $this->employeeId;
                            $formOneModel->modifiedDt = Helper::getcurrentExpressionDate();
                            $this->repository->edit($formOneModel, $id);
                        }
                        return $this->redirect()->toRoute('employee', ['action' => 'edit', 'id' => $id, 'tab' => 2]);
                    }
                    break;
                case 2:
                    $this->formTwo->setData($postData);
                    if ($this->formTwo->isValid()) {
                        $formTwoModel->exchangeArrayFromForm($this->formTwo->getData());
                        $formTwoModel->famSpouseBirthDate = Helper::getExpressionDate($formTwoModel->famSpouseBirthDate);
                        $formTwoModel->famSpouseWeddingAnniversary = Helper::getExpressionDate($formTwoModel->famSpouseWeddingAnniversary);

                        $formTwoModel->modifiedBy = $this->employeeId;
                        $formTwoModel->modifiedDt = Helper::getcurrentExpressionDate();
                        $this->repository->edit($formTwoModel, $id);
                        return $this->redirect()->toRoute('employee', ['action' => 'edit', 'id' => $id, 'tab' => 3]);
                    }
                    break;
                case 3:
                    $this->formThree->setData($postData);
                    if ($this->formThree->isValid()) {
                        $formThreeModel->exchangeArrayFromForm($this->formThree->getData());
                        $formThreeModel->idDrivingLicenseExpiry = Helper::getExpressionDate($formThreeModel->idDrivingLicenseExpiry);
                        $formThreeModel->idCitizenshipIssueDate = Helper::getExpressionDate($formThreeModel->idCitizenshipIssueDate);
                        $formThreeModel->idPassportExpiry = Helper::getExpressionDate($formThreeModel->idPassportExpiry);
                        $formThreeModel->modifiedBy = $this->employeeId;
                        $formThreeModel->modifiedDt = Helper::getcurrentExpressionDate();
                        $this->repository->edit($formThreeModel, $id);
                        return $this->redirect()->toRoute('employee', ['action' => 'edit', 'id' => $id, 'tab' => 4]);
                    }
                    break;
                case 4:
                    $this->formFour->setData($postData);
                    if ($this->formFour->isValid()) {
                        $formFourModel->exchangeArrayFromForm($this->formFour->getData());
                        $formFourModel->modifiedBy = $this->employeeId;
                        $formFourModel->modifiedDt = Helper::getcurrentExpressionDate();
                        $this->repository->edit($formFourModel, $id);
                        $this->repository->updateJobHistory($id);
                        /*
                         * Recommender Approver Assign part 
                         */
                        $recommenderId = $postData->recommender;
                        $approverId = $postData->approver;

                        $recommendApprove = new RecommendApprove();
                        $recommendApprove->employeeId = $id;
                        $recommendApprove->recommendBy = $recommenderId;
                        $recommendApprove->approvedBy = $approverId;
                        if ($recAppDetail == null) {
                            $recommendApprove->createdDt = Helper::getcurrentExpressionDate();
                            $recommendApprove->status = 'E';
                            $recommApproverRepo->add($recommendApprove);
                        } else {
                            $recommendApprove->modifiedDt = Helper::getcurrentExpressionDate();
                            $recommApproverRepo->edit($recommendApprove, $id);
                        }

                        if($postData->update == 'Y'){
                            $data = $postData;
                            $data['employeeId'] = $id;
                            $data['createdBy'] = $this->employeeId;

                            $this->repository->updateServiceStatus($data);
                        }

                        return $this->redirect()->toRoute('employee', ['action' => 'edit', 'id' => $id, 'tab' => 5]);
                    }
                    break;
                case 5:
                    break;
                case 6:
                    break;
                case 7:
                    break;
                case 8:
                    break;
                case 9:
                    break;
                case 10:
                    break;
            }
        }
        if ($employeeData != null) {
            if ($tab != 1 || !$request->isPost()) {
                $formOneModel->exchangeArrayFromDB($employeeData);
                $address['addrPermZoneId'] = $formOneModel->addrPermZoneId;
                $address['addrPermDistrictId'] = $formOneModel->addrPermDistrictId;
                $address['addrPermVdcMunicipalityId'] = $formOneModel->addrPermVdcMunicipalityId;
                $address['addrTempZoneId'] = $formOneModel->addrTempZoneId;
                $address['addrTempDistrictId'] = $formOneModel->addrTempDistrictId;
                $address['addrTempVdcMunicipalityId'] = $formOneModel->addrTempVdcMunicipalityId;
                $address['addrPermProvinceId'] = $formOneModel->addrPermProvinceId;
                $address['addrTempProvinceId'] = $formOneModel->addrTempProvinceId;
                
                $formOneModel->addrPermVdcMunicipalityId = $this->repository->vdcIdToString($formOneModel->addrPermVdcMunicipalityId);
                $formOneModel->addrTempVdcMunicipalityId = $this->repository->vdcIdToString($formOneModel->addrTempVdcMunicipalityId);
                $this->formOne->bind($formOneModel);
            }

            if ($tab != 2 || !$request->isPost()) {
                $formTwoModel->exchangeArrayFromDB($employeeData);
                $this->formTwo->bind($formTwoModel);
            }

            if ($tab != 3 || !$request->isPost()) {
                $formThreeModel->exchangeArrayFromDB($employeeData);
                $address['citizenshipIssuePlace']=$formThreeModel->idCitizenshipIssuePlace;
                $this->formThree->bind($formThreeModel);
                
            }

            if ($tab != 4 || !$request->isPost()) {
                $formFourModel->exchangeArrayFromDB($employeeData);
                $this->formFour->bind($formFourModel);
            }
            if ($tab != 5 || !$request->isPost()) {
                
            }
            if ($tab != 6 || !$request->isPost()) {
                $formSixModel->exchangeArrayFromDB($employeeData);
                $this->formSix->bind($formSixModel);
            }

            if ($tab != 7 || !$request->isPost()) {
                
            }

            if ($tab != 8 || !$request->isPost()) {
                
            }
        }
        $rankTypes = array(
            'GPA' => "GPA",
            'PER' => 'Percentage'
        );
        $programKVList = ApplicationHelper::getTableKVListWithSortOption($this->adapter, "HRIS_ACADEMIC_PROGRAMS", "ACADEMIC_PROGRAM_ID", ["ACADEMIC_PROGRAM_NAME"], ["STATUS" => 'E'], "ACADEMIC_PROGRAM_NAME", "ASC", null, false, true);
        $programSE = $this->getSelectElement(['name' => 'academicProgramId', 'id' => 'academicProgramId', 'label' => "Academic Program", 'class' => 'form-control'], $programKVList);
        return Helper::addFlashMessagesToArray($this, [
                'tab' => $tab,
                "id" => $id,
                'formOne' => $this->formOne,
                'formTwo' => $this->formTwo,
                'formThree' => $this->formThree,
                'formFour' => $this->formFour,
                'formSix' => $this->formSix,
                'formSeven' => $this->formSeven,
                'formEight' => $this->formEight,
                'formNine' => $this->formNine,
                'filetypes' => ApplicationHelper::getTableKVList($this->adapter, 'HRIS_FILE_TYPE', 'FILETYPE_CODE', ['NAME'],"Status='E'"),
                'serviceTypes' => ApplicationHelper::getTableKVListWithSortOption($this->adapter, "HRIS_SERVICE_TYPES", "SERVICE_TYPE_ID", ["SERVICE_TYPE_NAME"], ["STATUS" => 'E'], "SERVICE_TYPE_NAME", "ASC", null, true, true),
                'positions' => ApplicationHelper::getTableKVListWithSortOption($this->adapter, "HRIS_POSITIONS", "POSITION_ID", ["POSITION_NAME"], ["STATUS" => 'E'], "POSITION_NAME", "ASC", null, true, true),
                'designations' => ApplicationHelper::getTableKVListWithSortOption($this->adapter, "HRIS_DESIGNATIONS", "DESIGNATION_ID", ["DESIGNATION_TITLE"], ["STATUS" => 'E'], "DESIGNATION_TITLE", "ASC", null, true, true),
                'departments' => ApplicationHelper::getTableKVListWithSortOption($this->adapter, "HRIS_DEPARTMENTS", "DEPARTMENT_ID", ["DEPARTMENT_NAME"], ["STATUS" => 'E'], "DEPARTMENT_NAME", "ASC", null, true, true),
                'branches' => ApplicationHelper::getTableKVListWithSortOption($this->adapter, "HRIS_BRANCHES", "BRANCH_ID", ["BRANCH_NAME"], ["STATUS" => 'E'], "BRANCH_NAME", "ASC", null, true, true),
                'locations' => ApplicationHelper::getTableKVListWithSortOption($this->adapter, "HRIS_LOCATIONS", "LOCATION_ID", ["LOCATION_EDESC"], ["STATUS" => 'E'], "LOCATION_EDESC", "ASC", null, true, true),
                'functionalTypes' => ApplicationHelper::getTableKVListWithSortOption($this->adapter, "HRIS_FUNCTIONAL_TYPES", "FUNCTIONAL_TYPE_ID", ["FUNCTIONAL_TYPE_EDESC"], ["STATUS" => 'E'], "FUNCTIONAL_TYPE_EDESC", "ASC", null, true, true),
                'functionalLevels' => ApplicationHelper::getTableKVListWithSortOption($this->adapter, "HRIS_FUNCTIONAL_LEVELS", "FUNCTIONAL_LEVEL_ID", ["FUNCTIONAL_LEVEL_EDESC"], ["STATUS" => 'E'], "FUNCTIONAL_LEVEL_EDESC", "ASC", null, true, true),
                'academicDegree' => ApplicationHelper::getTableKVListWithSortOption($this->adapter, "HRIS_ACADEMIC_DEGREES", "ACADEMIC_DEGREE_ID", ["ACADEMIC_DEGREE_NAME"], ["STATUS" => 'E'], "ACADEMIC_DEGREE_NAME", "ASC", null, false, true),
                'academicUniversity' => ApplicationHelper::getTableKVListWithSortOption($this->adapter, "HRIS_ACADEMIC_UNIVERSITY", "ACADEMIC_UNIVERSITY_ID", ["ACADEMIC_UNIVERSITY_NAME"], ["STATUS" => 'E'], "ACADEMIC_UNIVERSITY_NAME", "ASC", null, false, true),
                'academicProgram' => $programKVList,
                'academicCourse' => ApplicationHelper::getTableKVListWithSortOption($this->adapter, "HRIS_ACADEMIC_COURSES", "ACADEMIC_COURSE_ID", ["ACADEMIC_COURSE_NAME"], ["STATUS" => 'E'], "ACADEMIC_COURSE_NAME", "ASC", null, false, true),
                'rankTypes' => $rankTypes,
                'profilePictureId' => $profilePictureId,
                'address' => $address,
                'recommenderId' => ($recAppDetail != null) ? $recAppDetail['RECOMMEND_BY'] : 0,
                'approverId' => ($recAppDetail != null) ? $recAppDetail['APPROVED_BY'] : 0,
                'shifts' => ApplicationHelper::getTableKVListWithSortOption($this->adapter, ShiftSetup::TABLE_NAME, ShiftSetup::SHIFT_ID, [ShiftSetup::SHIFT_ENAME], [ShiftSetup::STATUS => 'E'], ShiftSetup::SHIFT_ENAME, "ASC", null, false, true),
                'leaves' => ApplicationHelper::getTableKVListWithSortOption($this->adapter, LeaveMaster::TABLE_NAME, LeaveMaster::LEAVE_ID, [LeaveMaster::LEAVE_ENAME], [LeaveMaster::STATUS => 'E'], LeaveMaster::LEAVE_ENAME, "ASC", null, false, true),
                'recommenders' => ApplicationHelper::getTableKVListWithSortOption($this->adapter, "HRIS_EMPLOYEES", "EMPLOYEE_ID", ["FIRST_NAME", "MIDDLE_NAME", "LAST_NAME"], ["STATUS" => "E"], "FIRST_NAME", "ASC", " ", false, true),
                'approvers' => ApplicationHelper::getTableKVListWithSortOption($this->adapter, "HRIS_EMPLOYEES", "EMPLOYEE_ID", ["FIRST_NAME", "MIDDLE_NAME", "LAST_NAME"], ["STATUS" => "E"], "FIRST_NAME", "ASC", " ", false, true),
                'customRender' => Helper::renderCustomView(),
                'programSE' => $programSE,
                'countries' => $this->getCountryList(),
                'allDistricts' =>ApplicationHelper::getTableKVList($this->adapter, 'HRIS_DISTRICTS', 'DISTRICT_ID', ['DISTRICT_NAME'], null, null, true),
                'syngergyTable' =>$syngergyTable,
                'distributionTable' =>$distributionTable,
//                'relation' => ApplicationHelper::getTableKVListWithSortOption($this->adapter, "HRIS_RELATIONS", "RELATION_ID", ["RELATION_NAME"], ["STATUS" => 'E'], "RELATION_NAME", "ASC", null, false, true),
            'relation' => ApplicationHelper::getTableList($this->adapter, "HRIS_RELATIONS", ["RELATION_ID","RELATION_NAME"], ["STATUS" => 'E']),
            'serviceEventTypes' => ApplicationHelper::getTableKVListWithSortOption($this->adapter, "HRIS_SERVICE_EVENT_TYPES", "SERVICE_EVENT_TYPE_ID", ["SERVICE_EVENT_TYPE_NAME"], ["STATUS" => 'E'], "SERVICE_EVENT_TYPE_NAME", "ASC", null, true, true),
            'group' => ApplicationHelper::getTableKVListWithSortOption($this->adapter, "HRIS_SALARY_SHEET_GROUP", "GROUP_ID", ["GROUP_NAME"],null, "GROUP_NAME", "ASC", null, true, true),
        ]);
    }

    public function viewAction() {
        $id = (int) $this->params()->fromRoute('id');
        if (0 === $id) {
            return $this->redirect()->toRoute('employee', ['action' => 'index']);
        }

        $this->initializeMultipleForm();
        $empQualificationRepo = new EmployeeQualificationRepository($this->adapter);
        $empExperienceRepo = new EmployeeExperienceRepository($this->adapter);
        $empTrainingRepo = new EmployeeTrainingRepository($this->adapter);

        $employeeData = (array) $this->repository->getById($id);
        $profilePictureId = $employeeData[HrEmployees::PROFILE_PICTURE_ID];
        $filePathArray = ApplicationHelper::getTableKVList($this->adapter, EmployeeFileModel::TABLE_NAME, null, [EmployeeFileModel::FILE_PATH], [EmployeeFileModel::FILE_CODE => $profilePictureId], null);
        $filePath = empty($filePathArray) ? $this->config->getApplicationConfig()['default-profile-picture'] : $filePathArray[0];

        $perVdcMunicipalityDtl = $this->repository->getVdcMunicipalityDtl($employeeData[HrEmployees::ADDR_PERM_VDC_MUNICIPALITY_ID]);
        $perDistrictDtl = $this->repository->getDistrictDtl($employeeData[HrEmployees::ADDR_PERM_DISTRICT_ID]);
        $perZoneDtl = $this->repository->getZoneDtl($employeeData[HrEmployees::ADDR_PERM_ZONE_ID]);
        $perProvinceDtl = $this->repository->getProvinceDtl($id)['PERM_PROVINCE'];

        $tempVdcMunicipalityDtl = $this->repository->getVdcMunicipalityDtl($employeeData[HrEmployees::ADDR_TEMP_VDC_MUNICIPALITY_ID]);
        $tempDistrictDtl = $this->repository->getDistrictDtl($employeeData[HrEmployees::ADDR_TEMP_DISTRICT_ID]);
        $tempZoneDtl = $this->repository->getZoneDtl($employeeData[HrEmployees::ADDR_TEMP_ZONE_ID]);
        $tempProvinceDtl = $this->repository->getProvinceDtl($id)['TEMP_PROVINCE'];
        
        $empQualificationDtl = $empQualificationRepo->getByEmpId($id);
        $empExperienceList = $empExperienceRepo->getByEmpId($id);
        $empTrainingList = $empTrainingRepo->getByEmpId($id);

        $jobHistoryRepo = new JobHistoryRepository($this->adapter);
        $jobHistoryList = $jobHistoryRepo->filter(null, null, $id);

        $employeeFileRepo = new EmployeeFile($this->adapter);
        $employeeFile = $employeeFileRepo->fetchByEmpId($id);

        $assetRepo = new IssueRepository($this->adapter);
        $assetDetails = $assetRepo->fetchAssetByEmployee($id);
        
        $relationRepo = new \Setup\Repository\EmployeeRelationRepo($this->adapter);
        $relationDetails = $relationRepo->getByEmpId($id);
        
        return Helper::addFlashMessagesToArray($this, [
                'formOne' => $this->formOne,
                'formTwo' => $this->formTwo,
                'formThree' => $this->formThree,
                'formFour' => $this->formFour,
                'formSix' => $this->formSix,
                "formSeven" => $this->formSeven,
                'formEight' => $this->formEight,
                "id" => $id,
                'profilePictureId' => $profilePictureId,
                'employeeData' => $employeeData,
                'filePath' => $filePath,
                'perDistrictName' => $perDistrictDtl['DISTRICT_NAME'],
                'perZoneName' => $perZoneDtl['ZONE_NAME'],
                'tempDistrictName' => $tempDistrictDtl['DISTRICT_NAME'],
                'tempZoneName' => $tempZoneDtl['ZONE_NAME'],
                'empQualificationList' => $empQualificationDtl,
                'empExperienceList' => $empExperienceList,
                'empTrainingList' => $empTrainingList,
                'jobHistoryList' => $jobHistoryList,
                "employeeFile" => $employeeFile,
                "assetDetails" => $assetDetails,
                "relationDetails" => $relationDetails,
                'acl' => $this->acl,
                'tempProvinceName' => $tempProvinceDtl,
                'perProvinceName' => $perProvinceDtl,
        ]);
    }

    public function deleteAction() {
        $id = (int) $this->params()->fromRoute("id");
        $employee = new HrEmployees();
        $employee->employeeId = $id;
        $employee->deletedBy = $this->employeeId;
        $employee->deletedDate = Helper::getcurrentExpressionDateTime();
        $this->repository->delete($employee);
        $this->flashmessenger()->addMessage("Employee Successfully Deleted!!!");
        return $this->redirect()->toRoute('employee');
    }

    public function pullAcademicDetailAction() {
        try {
            $request = $this->getRequest();
            $data = $request->getPost();


            $academicDegreeRepository = new AcademicDegreeRepository($this->adapter);
            $academicUniversityRepository = new AcademicUniversityRepository($this->adapter);
            $academicProgramRepository = new AcademicProgramRepository($this->adapter);
            $academicCourseRepository = new AcademicCourseRepository($this->adapter);
            $employeeQualificationRepository = new EmployeeQualificationRepository($this->adapter);

            $degreeList = [];
            $universityList = [];
            $programList = [];
            $courseList = [];
            $employeeQualificationList = [];

            $degrees = $academicDegreeRepository->fetchAll();
            $universities = $academicUniversityRepository->fetchAll();
            $programs = $academicProgramRepository->fetchAll();
            $courses = $academicCourseRepository->fetchAll();
            $employeeQualifications = $employeeQualificationRepository->fetchByEmployeeId($data['employeeId']);

            foreach ($degrees as $row) {
                array_push($degreeList, [
                    'id' => $row['ACADEMIC_DEGREE_ID'],
                    'name' => $row['ACADEMIC_DEGREE_NAME']
                ]);
            }
            foreach ($universities as $row) {
                array_push($universityList, [
                    'id' => $row['ACADEMIC_UNIVERSITY_ID'],
                    'name' => $row['ACADEMIC_UNIVERSITY_NAME']
                ]);
            }
            foreach ($programs as $row) {
                array_push($programList, [
                    'id' => $row['ACADEMIC_PROGRAM_ID'],
                    'name' => $row['ACADEMIC_PROGRAM_NAME']
                ]);
            }
            foreach ($courses as $row) {
                array_push($courseList, [
                    'id' => $row['ACADEMIC_COURSE_ID'],
                    'name' => $row['ACADEMIC_COURSE_NAME']
                ]);
            }
            foreach ($employeeQualifications as $row) {
                $degreeRow = $academicDegreeRepository->fetchById($row['ACADEMIC_DEGREE_ID']);
                $degreeDtl = [
                    'id' => $degreeRow['ACADEMIC_DEGREE_ID'],
                    'name' => $degreeRow['ACADEMIC_DEGREE_NAME']
                ];
                $universityRow = $academicUniversityRepository->fetchById($row['ACADEMIC_UNIVERSITY_ID']);
                $universityDtl = [
                    'id' => $universityRow['ACADEMIC_UNIVERSITY_ID'],
                    'name' => $universityRow['ACADEMIC_UNIVERSITY_NAME']
                ];
                $programRow = $academicProgramRepository->fetchById($row['ACADEMIC_PROGRAM_ID']);
                $programDtl = [
                    'id' => $programRow['ACADEMIC_PROGRAM_ID'],
                    'name' => $programRow['ACADEMIC_PROGRAM_NAME']
                ];
                $courseRow = $academicCourseRepository->fetchById($row['ACADEMIC_COURSE_ID']);
                $courseDtl = [
                    'id' => $courseRow['ACADEMIC_COURSE_ID'],
                    'name' => $courseRow['ACADEMIC_COURSE_NAME']
                ];


                $documentRow = array_push($employeeQualificationList, [
                    'degreeDtl' => $degreeDtl,
                    'universityDtl' => $universityDtl,
                    'programDtl' => $programDtl,
                    'courseDtl' => $courseDtl,
                    'rankType' => $row['RANK_TYPE'],
                    'rankValue' => $row['RANK_VALUE'],
                    'passedYr' => $row['PASSED_YR'],
                    'id' => $row['ID']
                ]);
            }

            $data = [
                'degreeList' => $degreeList,
                'universityList' => $universityList,
                'programList' => $programList,
                'courseList' => $courseList,
                'num' => count($employeeQualificationList),
                'employeeQualificationList' => $employeeQualificationList
            ];


            return new JsonModel(['success' => true, 'data' => $data, 'message' => null]);
        } catch (Exception $e) {
            return new JsonModel(['success' => false, 'data' => null, 'message' => $e->getMessage()]);
        }
    }

    public function pullExperienceDetailAction() {
        try {
            $request = $this->getRequest();
            $data = $request->getPost();


            $repository = new EmployeeExperienceRepository($this->adapter);
            $experienceList = [];
            $employeeId = (int) $data['employeeId'];
            $result = $repository->getByEmpId($employeeId);
            foreach ($result as $row) {
                array_push($experienceList, $row);
            }
            $num = count($experienceList);

            return new JsonModel([
                "success" => true,
                "data" => $experienceList,
                "num" => $num
            ]);
        } catch (Exception $e) {
            return new JsonModel(['success' => false, 'data' => null, 'message' => $e->getMessage()]);
        }
    }

    public function pullTrainingDetailAction() {
        try {
            $request = $this->getRequest();
            $data = $request->getPost();


            $repository = new EmployeeTrainingRepository($this->adapter);
            $trainingList = [];
            $employeeId = (int) $data['employeeId'];
            $result = $repository->getByEmpId($employeeId);
            foreach ($result as $row) {
                array_push($trainingList, $row);
            }
            $num = count($trainingList);

            return new JsonModel([
                'success' => true,
                'data' => $trainingList,
                'num' => $num
            ]);
        } catch (Exception $e) {
            return new JsonModel(['success' => false, 'data' => null, 'message' => $e->getMessage()]);
        }
    }

    public function submitQualificationDtlAction() {
        try {
            $request = $this->getRequest();
            $data = $request->getPost();

            $repository = new EmployeeQualificationRepository($this->adapter);
            $empQualificationModel = new EmployeeQualification();
            if ($data['qualificationRecordNum'] > 0) {
                foreach ($data['qualificationRecord'] as $qualificationDtl) {
                    $id = $qualificationDtl['id'];
                    $academicDegreeId = $qualificationDtl['academicDegreeId'];
                    $academicUniversityId = $qualificationDtl['academicUniversityId'];
                    $academicProgramId = $qualificationDtl['academicProgramId'];
                    $academicCourseId = $qualificationDtl['academicCourseId'];
                    $rankType = $qualificationDtl['rankType'];
                    $rankValue = $qualificationDtl['rankValue'];
                    $passedYr = $qualificationDtl['passedYr'];
                    $employeeId = $data['employeeId'];

                    $empQualificationModel->employeeId = $employeeId;
                    $empQualificationModel->academicDegreeId = $academicDegreeId['id'];
                    $empQualificationModel->academicUniversityId = $academicUniversityId['id'];
                    $empQualificationModel->academicProgramId = $academicProgramId['id'];
                    $empQualificationModel->academicCourseId = $academicCourseId['id'];
                    $empQualificationModel->rankType = $rankType['id'];
                    $empQualificationModel->rankValue = $rankValue;
                    $empQualificationModel->passedYr = $passedYr;
                    $empQualificationModel->createdDt = Helper::getcurrentExpressionDate();
                    $empQualificationModel->status = 'E';

                    if ($id != 0) {
                        $empQualificationModel->modifiedDt = Helper::getcurrentExpressionDate();
                        $repository->edit($empQualificationModel, $id);
                    } else if ($id == 0) {
                        $empQualificationModel->id = Helper::getMaxId($this->adapter, EmployeeQualification::TABLE_NAME, EmployeeQualification::ID) + 1;
                        $repository->add($empQualificationModel);
                    }
                }
            }

            return new JsonModel(['success' => true, 'data' => "Qualification Detail Successfully Added", 'message' => null]);
        } catch (Exception $e) {
            return new JsonModel(['success' => false, 'data' => null, 'message' => $e->getMessage()]);
        }
    }

    public function submitExperienceDtlAction() {
        try {
            $request = $this->getRequest();
            $data = $request->getPost();


            $experienceListEmpty = (int) $data['experienceListEmpty'];
            $employeeId = (int) $data['employeeId'];

            $employeeRepo = new EmployeeRepository($this->adapter);
            $employeeExperienceRepo = new EmployeeExperienceRepository($this->adapter);
            $employeeDetail = $employeeRepo->fetchById((int) $this->employeeId);

            if ($experienceListEmpty == 1) {
                $experienceList = $data['experienceList'];
                foreach ($experienceList as $experience) {
                    $employeeExperienceModel = new EmployeeExperience();
                    $employeeExperienceModel->employeeId = (int) $employeeId;
                    $employeeExperienceModel->status = 'E';
                    $employeeExperienceModel->organizationType = $experience['organizationTypeId']['id'];
                    $employeeExperienceModel->organizationName = $experience['organizationName'];
                    $employeeExperienceModel->fromDate = $experience['fromDate'];
                    $employeeExperienceModel->toDate = $experience['toDate'];
                    $employeeExperienceModel->position = $experience['position'];

                    $id = (int) $experience['id'];
                    if ($id == 0) {
                        $employeeExperienceModel->id = (int) (Helper::getMaxId($this->adapter, EmployeeExperience::TABLE_NAME, EmployeeExperience::ID)) + 1;
                        $employeeExperienceModel->createdBy = (int) $this->employeeId;
                        $employeeExperienceModel->createdDate = Helper::getcurrentExpressionDate();
                        $employeeExperienceModel->approvedDate = Helper::getcurrentExpressionDate();
                        $employeeExperienceModel->companyId = (int) $employeeDetail['COMPANY_ID'];
                        $employeeExperienceModel->branchId = (int) $employeeDetail['BRANCH_ID'];
                        $employeeExperienceRepo->add($employeeExperienceModel);
                    } else {
                        $employeeExperienceModel->modifiedBy = (int) $this->employeeId;
                        $employeeExperienceModel->modifiedDate = Helper::getcurrentExpressionDate();
                        $employeeExperienceRepo->edit($employeeExperienceModel, $id);
                    }
                }
            }

            return new JsonModel(['success' => true, 'data' => "Employee Experience Detail Successfully Added", 'message' => null]);
        } catch (Exception $e) {
            return new JsonModel(['success' => false, 'data' => null, 'message' => $e->getMessage()]);
        }
    }

    public function submitTrainingDtlAction() {
        try {
            $request = $this->getRequest();
            $data = $request->getPost();


            $trainingListEmpty = $data['trainingListEmpty'];
            $employeeId = (int) $data['employeeId'];
            $employeeRepo = new EmployeeRepository($this->adapter);
            $employeeTrainingRepo = new EmployeeTrainingRepository($this->adapter);
            $employeeDetail = $employeeRepo->fetchById($this->employeeId);

            if ($trainingListEmpty == 1) {
                $trainingList = $data['trainingList'];
                foreach ($trainingList as $training) {
                    $employeeTrainingModel = new EmployeeTraining();
                    $employeeTrainingModel->employeeId = $employeeId;
                    $employeeTrainingModel->status = 'E';
                    $employeeTrainingModel->trainingName = $training['trainingName'];
                    $employeeTrainingModel->description = $training['description'];
                    $employeeTrainingModel->fromDate = $training['fromDate'];
                    $employeeTrainingModel->toDate = $training['toDate'];

                    $id = (int) $training['id'];
                    if ($id == 0) {
                        $employeeTrainingModel->id = ((int) Helper::getMaxId($this->adapter, EmployeeTraining::TABLE_NAME, EmployeeTraining::ID)) + 1;
                        $employeeTrainingModel->createdBy = (int) $this->employeeId;
                        $employeeTrainingModel->createdDate = Helper::getcurrentExpressionDate();
                        $employeeTrainingModel->approvedDate = Helper::getcurrentExpressionDate();
                        $employeeTrainingModel->companyId = (int) $employeeDetail['COMPANY_ID'];
                        $employeeTrainingModel->branchId = (int) $employeeDetail['BRANCH_ID'];
                        $employeeTrainingRepo->add($employeeTrainingModel);
                    } else {
                        $employeeTrainingModel->modifiedBy = (int) $this->employeeId;
                        $employeeTrainingModel->modifiedDate = Helper::getcurrentExpressionDate();
                        $employeeTrainingRepo->edit($employeeTrainingModel, $id);
                    }
                }
            }

            return new JsonModel(['success' => true, 'data' => "Employee Training Detail Successfully Added", 'message' => null]);
        } catch (Exception $e) {
            return new JsonModel(['success' => false, 'data' => null, 'message' => $e->getMessage()]);
        }
    }

    public function deleteQualificationDtlAction() {
        try {
            $request = $this->getRequest();
            $data = $request->getPost();


            $id = $data['id'];
            $repository = new EmployeeQualificationRepository($this->adapter);
            $repository->delete($id);

            return new JsonModel(['success' => true, 'data' => "Qualification Detail Successfully Removed", 'message' => null]);
        } catch (Exception $e) {
            return new JsonModel(['success' => false, 'data' => null, 'message' => $e->getMessage()]);
        }
    }

    public function deleteExperienceDtlAction() {
        try {
            $request = $this->getRequest();
            $data = $request->getPost();

            $id = $data['id'];
            $repository = new EmployeeExperienceRepository($this->adapter);
            $repository->delete($id);

            return new JsonModel(['success' => true, 'data' => "Experience Detail Successfully Removed", 'message' => null]);
        } catch (Exception $e) {
            return new JsonModel(['success' => false, 'data' => null, 'message' => $e->getMessage()]);
        }
    }

    public function deleteTrainingDtlAction() {
        try {
            $request = $this->getRequest();
            $data = $request->getPost();

            $id = (int) $data['id'];
            $repository = new EmployeeTrainingRepository($this->adapter);
            $repository->delete($id);

            return new JsonModel(['success' => true, 'data' => "Training Detail Successfully Removed", 'message' => null]);
        } catch (Exception $e) {
            return new JsonModel(['success' => false, 'data' => null, 'message' => $e->getMessage()]);
        }
    }

    public function pullEmployeeFileAction() {
        try {
            $request = $this->getRequest();
            $data = $request->getPost();

            $employeeFileId = $data["employeeFileId"];

            $employeeFileRepo = new \Setup\Repository\EmployeeFile($this->adapter);
            $employeeFile = $employeeFileRepo->fetchById($employeeFileId);


            return new JsonModel(['success' => true, 'data' => $employeeFile, 'message' => null]);
        } catch (Exception $e) {
            return new JsonModel(['success' => false, 'data' => null, 'message' => $e->getMessage()]);
        }
    }

    public function pushEmployeeProfileAction() {
        try {
            $request = $this->getRequest();
            $data = $request->getPost();

            $employeefile = new \Setup\Model\EmployeeFile();
            $return = [];
            if ($data['fileCode'] == null) {
                $employeefile->fileCode = ((int) Helper::getMaxId($this->adapter, 'HRIS_EMPLOYEE_FILE', 'FILE_CODE')) + 1;
                $employeefile->filetypeCode = $data['fileTypeCode'];
                $employeefile->filePath = $data['filePath'];
                $employeefile->fileName = $data['fileName'];
                $employeefile->status = 'E';
                $employeefile->createdDt = Helper::getcurrentExpressionDate();

                $employeeFileRepo = new \Setup\Repository\EmployeeFile($this->adapter);
                $employeeFileRepo->add($employeefile);

                $employeeRepo = new EmployeeRepository($this->adapter);
                $employeeModel = new \Setup\Model\HrEmployees();
                $employeeModel->profilePictureId = $employeefile->fileCode;
                $employeeRepo->edit($employeeModel, $data['employeeId']);
                $return = ["success" => true, "data" => ['fileCode' => $employeefile->fileCode]];
            } else {
                $employeefile->filetypeCode = $data['fileTypeCode'];
                $employeefile->filePath = $data['filePath'];

                $employeeFileRepo = new \Setup\Repository\EmployeeFile($this->adapter);
                $employeeFileRepo->edit($employeefile, $data['fileCode']);
                $return = ["success" => true, "data" => ['fileCode' => $data['fileCode']]];
            }

            return new JsonModel(['success' => true, 'data' => $return, 'message' => null]);
        } catch (Exception $e) {
            return new JsonModel(['success' => false, 'data' => null, 'message' => $e->getMessage()]);
        }
    }

    public function pushEmployeeDocumentAction() {
        try {
            $request = $this->getRequest();
            $data = $request->getPost();

            $employeefile = new \Setup\Model\EmployeeFile();
            $employeefile->fileCode = ((int) Helper::getMaxId($this->adapter, 'HRIS_EMPLOYEE_FILE', 'FILE_CODE')) + 1;
            $employeefile->employeeId = $data['employeeId'];
            $employeefile->filetypeCode = $data['fileTypeCode'];
            $employeefile->filePath = $data['filePath'];
            $employeefile->fileName = $data['oldFileName'];
            $employeefile->status = 'E';
            $employeefile->createdDt = Helper::getcurrentExpressionDate();

            $employeeFileRepo = new \Setup\Repository\EmployeeFile($this->adapter);
            $employeeFileRepo->add($employeefile);


            return new JsonModel(['success' => true, 'data' => ['fileCode' => $employeefile->fileCode], 'message' => null]);
        } catch (Exception $e) {
            return new JsonModel(['success' => false, 'data' => null, 'message' => $e->getMessage()]);
        }
    }

    public function pullEmployeeFileByEmpIdAction() {
        try {
            $request = $this->getRequest();
            $data = $request->getPost();

            $employeeId = $data['employeeId'];
            $employeeFileRepo = new \Setup\Repository\EmployeeFile($this->adapter);
            $employeeFile = $employeeFileRepo->fetchByEmpId($employeeId);


            return new JsonModel(['success' => true, 'data' => $employeeFile, 'message' => null]);
        } catch (Exception $e) {
            return new JsonModel(['success' => false, 'data' => null, 'message' => $e->getMessage()]);
        }
    }

    public function dropEmployeeFileAction() {
        try {
            $request = $this->getRequest();
            $data = $request->getPost();

            $employeeRepo = new \Setup\Repository\EmployeeFile($this->adapter);
            $employeeRepo->delete($data['fileCode']);

            return new JsonModel(['success' => true, 'data' => ['fileCode' => $data['fileCode']], 'message' => null]);
        } catch (Exception $e) {
            return new JsonModel(['success' => false, 'data' => null, 'message' => $e->getMessage()]);
        }
    }
 
    public function pullEmployeeListForEmployeeTableAction() {
        try {
            $request = $this->getRequest();
            $data = $request->getPost();

            $list = $this->repository->fetchBy($data);
            return new JsonModel(['success' => true, 'data' => $list, 'message' => null]);
        } catch (Exception $e) {
            return new JsonModel(['success' => false, 'data' => null, 'message' => $e->getMessage()]);
        }
    }

    public function addDegreeAction() {
        try {
            $request = $this->getRequest();
            $data = $request->getPost();

            $academicDegree = new AcademicDegree();
            $academicDegree->academicDegreeId = ((int) Helper::getMaxId($this->adapter, AcademicDegree::TABLE_NAME, AcademicDegree::ACADEMIC_DEGREE_ID)) + 1;
            $academicDegree->academicDegreeName = $data['academicDegreeName'];
            $academicDegree->weight = $data['weight'];
            $academicDegree->remarks = $data['remarks'];
            $academicDegree->createdDt = Helper::getcurrentExpressionDate();
            $academicDegree->createdBy = $this->employeeId;
            $academicDegree->status = 'E';
            $degreeRepo = new AcademicDegreeRepository($this->adapter);
            $degreeRepo->add($academicDegree);

            return new JsonModel(['success' => true, 'data' => null, 'message' => "Academic Degree Successfully added."]);
        } catch (Exception $e) {
            return new JsonModel(['success' => false, 'data' => null, 'message' => $e->getMessage()]);
        }
    }

    public function addUniversityAction() {
        try {
            $request = $this->getRequest();
            $data = $request->getPost();

            $academicUniversity = new AcademicUniversity();
            $academicUniversity->academicUniversityId = ((int) Helper::getMaxId($this->adapter, AcademicUniversity::TABLE_NAME, AcademicUniversity::ACADEMIC_UNIVERSITY_ID)) + 1;
            $academicUniversity->academicUniversityName = $data['academicUniversityName'];
            $academicUniversity->remarks = $data['remarks'];
            $academicUniversity->createdDt = Helper::getcurrentExpressionDate();
            $academicUniversity->createdBy = $this->employeeId;
            $academicUniversity->status = 'E';
            $universityRepo = new AcademicUniversityRepository($this->adapter);
            $universityRepo->add($academicUniversity);
            return new JsonModel(['success' => true, 'data' => null, 'message' => "Academic University Successfully added."]);
        } catch (Exception $e) {
            return new JsonModel(['success' => false, 'data' => null, 'message' => $e->getMessage()]);
        }
    }

    public function addProgramAction() {
        try {
            $request = $this->getRequest();
            $data = $request->getPost();

            $academicProgram = new AcademicProgram();
            $academicProgram->academicProgramName = $data['academicProgramName'];
            $academicProgram->remarks = $data['remarks'];
            $academicProgram->academicProgramId = ((int) Helper::getMaxId($this->adapter, AcademicProgram::TABLE_NAME, AcademicProgram::ACADEMIC_PROGRAM_ID)) + 1;
            $academicProgram->createdDt = Helper::getcurrentExpressionDate();
            $academicProgram->createdBy = $this->employeeId;
            $academicProgram->status = 'E';
            $programRepo = new AcademicProgramRepository($this->adapter);
            $programRepo->add($academicProgram);
            return new JsonModel(['success' => true, 'data' => null, 'message' => "Academic Program Successfully added."]);
        } catch (Exception $e) {
            return new JsonModel(['success' => false, 'data' => null, 'message' => $e->getMessage()]);
        }
    }

    public function addCourseAction() {
        try {
            $request = $this->getRequest();
            $data = $request->getPost();
            $academicCourse = new AcademicCourse();
            $academicCourse->academicProgramId = $data['academicProgramId'];
            $academicCourse->academicCourseName = $data['academicCourseName'];
            $academicCourse->remarks = $data['remarks'];
            $academicCourse->academicCourseId = ((int) Helper::getMaxId($this->adapter, AcademicCourse::TABLE_NAME, AcademicCourse::ACADEMIC_COURSE_ID)) + 1;
            $academicCourse->createdDt = Helper::getcurrentExpressionDate();
            $academicCourse->createdBy = $this->employeeId;
            $academicCourse->status = 'E';
            $courseRepo = new AcademicCourseRepository($this->adapter);
            $courseRepo->add($academicCourse);
            return new JsonModel(['success' => true, 'data' => null, 'message' => "Academic Course Successfully added."]);
        } catch (Exception $e) {
            return new JsonModel(['success' => false, 'data' => null, 'message' => $e->getMessage()]);
        }
    }

    public function setupEmployeeAction() {
        $id = (int) $this->params()->fromRoute("id", 0);
        if ($id === 0) {
            return $this->redirect()->toRoute('employee', ['action' => 'edit', 'id' => $id, 'tab' => 11]);
        }

        $this->repository->setupEmployee($id);
        return $this->redirect()->toRoute('employee', ['action' => 'edit', 'id' => $id, 'tab' => 11]);
    }

    public function districtAction() {
        $request = $this->getRequest();
        $post = $request->getPost();
        $zoneId = $post->id;
        $districtKV = HrisQuery::singleton()
            ->setAdapter($this->adapter)
            ->setTableName("HRIS_DISTRICTS")
            ->setColumnList(["DISTRICT_ID", "DISTRICT_NAME"])
            ->setWhere(["ZONE_ID" => $zoneId])
            ->setKeyValue("DISTRICT_ID", "DISTRICT_NAME")
            ->setIncludeEmptyRow(true)
            ->result();
        return new JsonModel($districtKV);
    }

    public function municipalityAction() {
        $request = $this->getRequest();
        $post = $request->getPost();
        $districtId = $post->id;
        $districtKV = HrisQuery::singleton()
            ->setAdapter($this->adapter)
            ->setTableName("HRIS_VDC_MUNICIPALITIES")
            ->setColumnList(["VDC_MUNICIPALITY_ID", "VDC_MUNICIPALITY_NAME"])
            ->setWhere(["DISTRICT_ID" => $districtId])
            ->setKeyValue("VDC_MUNICIPALITY_ID", "VDC_MUNICIPALITY_NAME")
            ->setIncludeEmptyRow(true)
            ->result();
        return new JsonModel($districtKV);
    }
    
    public function addDistributionEmpAction(){
        try{
             $request = $this->getRequest();
             $id = $request->getPost('id');
             if($id>0 && $id!=null){
             ApplicationHelper::rawQueryResult($this->adapter, '
                     BEGIN HRIS_CREATE_DIS_EMP('.$id.'); END;');
             }
            return new JsonModel(['success' => true, 'data' => null, 'message' => "Successfully Created Distribution Employee."]);
        } catch (Exception $e) {
            return new JsonModel(['success' => false, 'data' => null, 'message' => $e->getMessage()]);
        }
        
    }
    
    
    public function pullRelationDetailAction() {
        try {
            $request = $this->getRequest();
            $data = $request->getPost();


            $repository = new \Setup\Repository\EmployeeRelationRepo($this->adapter);
            $employeeId = (int) $data['employeeId'];
            $relationList = [];
            $result = $repository->getByEmpId($employeeId);
            foreach ($result as $row) {
                array_push($relationList, $row);
            }
            $num = count($relationList);

            return new JsonModel([
                "success" => true,
                "data" => $relationList,
                "num" => $num
            ]);
        } catch (Exception $e) {
            return new JsonModel(['success' => false, 'data' => null, 'message' => $e->getMessage()]);
        }
    }
    
    
    public function submitRelationDtlAction()
    {
        try {
            $request = $this->getRequest();
            $data = $request->getPost();
            
            $relationListEmpty = (int) $data['relationListEmpty'];
            $employeeId = (int) $data['employeeId'];
            
            $employeeRepo = new EmployeeRepository($this->adapter);
            $employeeRelationRepo = new \Setup\Repository\EmployeeRelationRepo($this->adapter);
            $employeeDetail = $employeeRepo->fetchById((int) $this->employeeId);


            if ($relationListEmpty == 1) {
                $relationList = $data['relationList'];
                foreach ($relationList as $relations) {
                    $employeeRelationModel= new \Setup\Model\EmployeeRelation();
                    $employeeRelationModel->employeeId= (int)$employeeId;
                    $employeeRelationModel->status= 'E';
                    $employeeRelationModel->personName=$relations['personName'] ;
                    $employeeRelationModel->relationId=$relations['relationId']['RELATION_ID'] ;
                    $employeeRelationModel->dob=$relations['dob'] ;
                    $employeeRelationModel->isDependent=$relations['isDependent']['id'] ;
                    $employeeRelationModel->isNominee=$relations['isNominee']['id'] ;

                    $id = (int) $relations['eRId'];
                    if ($id == 0) {
                        $employeeRelationModel->eRId = (int) (Helper::getMaxId($this->adapter, \Setup\Model\EmployeeRelation::TABLE_NAME, \Setup\Model\EmployeeRelation::E_R_ID)) + 1;
                        $employeeRelationModel->createdBy = (int) $this->employeeId;
                        $employeeRelationModel->createdDt = Helper::getcurrentExpressionDate();
                        $employeeRelationRepo->add($employeeRelationModel);
                    } else {
                        $employeeRelationModel->modifiedBy = (int) $this->employeeId;
                        $employeeRelationModel->modifiedDt = Helper::getcurrentExpressionDate();
                        $employeeRelationRepo->edit($employeeRelationModel, $id);
                    }
                }
            }

            return new JsonModel(['success' => true, 'data' => "Employee Experience Detail Successfully Added", 'message' => null]);
        } catch (Exception $e) {
            return new JsonModel(['success' => false, 'data' => null, 'message' => $e->getMessage()]);
        }
    }
    
    
    public function deleteRelationDtlAction() {
        try {
            $request = $this->getRequest();
            $data = $request->getPost();

            $id = $data['id'];
            $repository = new \Setup\Repository\EmployeeRelationRepo($this->adapter);
            $repository->delete($id);

            return new JsonModel(['success' => true, 'data' => "Experience Detail Successfully Removed", 'message' => null]);
        } catch (Exception $e) {
            return new JsonModel(['success' => false, 'data' => null, 'message' => $e->getMessage()]);
        }
    }
    
    public function resignedOrRetiredAction(){
        return $this->stickFlashMessagesTo([
                'searchValues' => $this->getSearchDataforResignedOrRetired(),
                'acl' => $this->acl,
                'employeeDetail' => $this->storageData['employee_detail'],
        ]);
    }
    
    public function pullResignedOrResignedAction() {
        try {
            $request = $this->getRequest();
            $data = $request->getPost();

            $list = $this->repository->fetchResignedOrRetired($data);
            return new JsonModel(['success' => true, 'data' => $list, 'message' => null]);
        } catch (Exception $e) {
            return new JsonModel(['success' => false, 'data' => null, 'message' => $e->getMessage()]);
        }
    }
    
    public function getSearchDataforResignedOrRetired(){
        $employeeWhere = ["RETIRED_FLAG = 'Y' OR RESIGNED_FLAG = 'Y' OR STATUS='D'"];
        $companyList = ApplicationHelper::getTableList($this->adapter, Company::TABLE_NAME, [Company::COMPANY_ID, Company::COMPANY_NAME], [Company::STATUS => "E"]);
        $branchList = ApplicationHelper::getTableList($this->adapter, Branch::TABLE_NAME, [Branch::BRANCH_ID, Branch::BRANCH_NAME, Branch::COMPANY_ID], [Branch::STATUS => "E"],"","BRANCH_NAME ASC");
        $departmentList = ApplicationHelper::getTableList($this->adapter, Department::TABLE_NAME, [Department::DEPARTMENT_ID, Department::DEPARTMENT_NAME, Department::COMPANY_ID, Department::BRANCH_ID], [Department::STATUS => "E"],"","DEPARTMENT_NAME ASC");
        $designationList = ApplicationHelper::getTableList($this->adapter, Designation::TABLE_NAME, [Designation::DESIGNATION_ID, Designation::DESIGNATION_TITLE, Designation::COMPANY_ID], [Designation::STATUS => 'E'],"","DESIGNATION_TITLE ASC");
        $positionList = ApplicationHelper::getTableList($this->adapter, Position::TABLE_NAME, [Position::POSITION_ID, Position::POSITION_NAME, Position::COMPANY_ID], [Position::STATUS => "E"],"","POSITION_NAME ASC");
        $serviceTypeList = ApplicationHelper::getTableList($this->adapter, ServiceType::TABLE_NAME, [ServiceType::SERVICE_TYPE_ID, ServiceType::SERVICE_TYPE_NAME], [ServiceType::STATUS => "E"],"","SERVICE_TYPE_NAME ASC");
        $serviceEventTypeList = ApplicationHelper::getTableList($this->adapter, ServiceEventType::TABLE_NAME, [ServiceEventType::SERVICE_EVENT_TYPE_ID, ServiceEventType::SERVICE_EVENT_TYPE_NAME], [ServiceEventType::STATUS => "E"],"","SERVICE_EVENT_TYPE_NAME ASC");
        $genderList = ApplicationHelper::getTableList($this->adapter, Gender::TABLE_NAME, [Gender::GENDER_ID, Gender::GENDER_NAME], [Gender::STATUS => "E"]);
        $locationList = ApplicationHelper::getTableList($this->adapter, Location::TABLE_NAME, [Location::LOCATION_ID, Location::LOCATION_EDESC], [Location::STATUS => "E"]);
        $functionalTypeList = ApplicationHelper::getTableList($this->adapter, FunctionalTypes::TABLE_NAME, [FunctionalTypes::FUNCTIONAL_TYPE_ID, FunctionalTypes::FUNCTIONAL_TYPE_EDESC], [FunctionalTypes::STATUS=> "E"],"","FUNCTIONAL_TYPE_EDESC ASC");
        $employeeList = ApplicationHelper::getTableList($this->adapter, HrEmployees::TABLE_NAME, [
                    new Expression(HrEmployees::EMPLOYEE_ID." AS ".HrEmployees::EMPLOYEE_ID),
                    new Expression(HrEmployees::EMPLOYEE_CODE." AS ".HrEmployees::EMPLOYEE_CODE),
                    new Expression("EMPLOYEE_CODE||'-'||FULL_NAME AS FULL_NAME"),
                    new Expression(HrEmployees::FULL_NAME." AS FULL_NAME_SCIENTIFIC"),
                    new Expression(HrEmployees::COMPANY_ID." AS ".HrEmployees::COMPANY_ID),
                    new Expression(HrEmployees::BRANCH_ID." AS ".HrEmployees::BRANCH_ID),
                    new Expression(HrEmployees::DEPARTMENT_ID." AS ".HrEmployees::DEPARTMENT_ID),
                    new Expression(HrEmployees::DESIGNATION_ID." AS ".HrEmployees::DESIGNATION_ID),
                    new Expression(HrEmployees::POSITION_ID." AS ".HrEmployees::POSITION_ID),
                    new Expression(HrEmployees::SERVICE_TYPE_ID." AS ".HrEmployees::SERVICE_TYPE_ID),
                    new Expression(HrEmployees::SERVICE_EVENT_TYPE_ID." AS ".HrEmployees::SERVICE_EVENT_TYPE_ID),
                    new Expression(HrEmployees::GENDER_ID." AS ".HrEmployees::GENDER_ID),
                    new Expression(HrEmployees::EMPLOYEE_TYPE." AS ".HrEmployees::EMPLOYEE_TYPE),
                    new Expression(HrEmployees::GROUP_ID." AS ".HrEmployees::GROUP_ID),
                    new Expression(HrEmployees::FUNCTIONAL_TYPE_ID." AS ".HrEmployees::FUNCTIONAL_TYPE_ID),
                        ], $employeeWhere,"","FULL_NAME_SCIENTIFIC ASC");

        $searchValues = [
            'company' => $companyList,
            'branch' => $branchList,
            'department' => $departmentList,
            'designation' => $designationList,
            'position' => $positionList,
            'serviceType' => $serviceTypeList,
            'serviceEventType' => $serviceEventTypeList,
            'gender' => $genderList,
            'employeeType' => [['EMPLOYEE_TYPE_KEY' => 'R', 'EMPLOYEE_TYPE_VALUE' => 'Employee'], ['EMPLOYEE_TYPE_KEY' => 'C', 'EMPLOYEE_TYPE_VALUE' => 'Worker']],
            'employee' => $employeeList,
            'location' => $locationList,
            'functionalType' => $functionalTypeList,
        ];
        return $searchValues;
    }

    public function excelExportAction(){
        $request = $this->getRequest();
        $data = $request->getPost();
        $columns = $data['map'];
        $columns['PROFILE_PICTURE'] = 'Profile Picture';
        
        $employeeData = $this->repository->fetchBy($data['searchData']);
        unlink(realpath(Helper::UPLOAD_DIR . "/Employees_List.xlsx"));
        

        $spreadsheet = new Spreadsheet();
        $writer = new Xlsx($spreadsheet);
        $writer->setPreCalculateFormulas(false);
        $this->employeeFileRepo = new EmployeeFile($this->adapter);
        
//        $spreadsheet->getActiveSheet()->getRowDimension($row)->setRowHeight(100);
//        $spreadsheet->getActiveSheet()->getColumnDimension($currentColumn)->setWidth(40);
        
//        for heading row1 start
        $headerRow=1;
        $currentColumn = Coordinate::stringFromColumnIndex(1);
        $cell = $currentColumn.'1';
        $spreadsheet->getActiveSheet()->setCellValue($cell, 'Photo');
        $spreadsheet->getActiveSheet()->getRowDimension($headerRow)->setRowHeight(100);
        $spreadsheet->getActiveSheet()->getColumnDimension($currentColumn)->setWidth(20);
        $column = 2;
        
        foreach($columns as $key => $value){
        
        
        $currentColumn = Coordinate::stringFromColumnIndex($column);
        $cell = $currentColumn.''.$headerRow;
        $spreadsheet->getActiveSheet()->getColumnDimension($currentColumn)->setWidth(40);
        $spreadsheet->getActiveSheet()->setCellValue($cell, $value);
        $column++;
        }
//        for heading row2 end
        
        
        
        $row = 2;
        foreach($employeeData as $employee){
//            $column = 1;
//            echo Helper::UPLOAD_DIR.'/1584077606.jpg';
//            print_r($employee);
//            die();
//            foreach($employee as $key => $value){
                
                
//                    $currentColumn = Coordinate::stringFromColumnIndex($column);
                    $cell = 'A'.$row;
                    $spreadsheet->getActiveSheet()->getRowDimension($row)->setRowHeight(100);
//                    $spreadsheet->getActiveSheet()->getColumnDimension($cell)->setWidth(40);
                    
                    $drawing = new \PhpOffice\PhpSpreadsheet\Worksheet\Drawing();
                    $drawing->setName('Paid');
                    $drawing->setDescription('Paid');
                    
                    $noProfileImg=Helper::UPLOAD_DIR.'/default-profile-picture.jpg';
                    
            if ($employee['FILE_PATH']) {
                $userImg = Helper::UPLOAD_DIR .'/'. $employee['FILE_PATH'];
                if (file_exists($userImg)) {
                    $drawing->setPath($userImg); // put your path and image here
                } else {
                    $drawing->setPath($noProfileImg); // put your path and image here
                }
            } else {
                $drawing->setPath($noProfileImg); // put your path and image here
            }

            $drawing->setCoordinates($cell);
                    $drawing->setHeight(100);
                    $drawing->setWidth(100);
                    $drawing->setResizeProportional(true);
                            
                    $drawing->setOffsetX(10);
//                    $drawing->setRotation(25);
                    $drawing->getShadow()->setVisible(true);
                    $drawing->getShadow()->setDirection(45);
                    $drawing->setWorksheet($spreadsheet->getActiveSheet());
                    
                    
                    
//                    $gdImage = imagecreatefromjpeg('C:\xampp\htdocs\neo-hris\public\uploads\1513750732.png');
////                    $gdImage = imagecreatefromjpeg('https://www.instagram.com/static/images/web/mobile_nav_type_logo.png/735145cfe0a4.png');
////                    $objDrawing = new PHPExcel_Worksheet_MemoryDrawing();
//                    $objDrawing = new \PhpOffice\PhpSpreadsheet\Worksheet\MemoryDrawing();
//$objDrawing->setName('Sample image');
//$objDrawing->setDescription('Sample image');
//$objDrawing->setImageResource($gdImage);
//$objDrawing->setRenderingFunction(\PhpOffice\PhpSpreadsheet\Worksheet\MemoryDrawing::RENDERING_JPEG);
//$objDrawing->setMimeType(\PhpOffice\PhpSpreadsheet\Worksheet\MemoryDrawing::MIMETYPE_DEFAULT);
//$objDrawing->setHeight(150);
//$objDrawing->setCoordinates($cell);
//$objDrawing->setWorksheet($spreadsheet->getActiveSheet());
                    
                    
                    
//                    
//                    
//                    
//                    
                    
                    
//                    if($column == 1){
//                        $drawing = new Drawing();
//                        $drawing->setName('Profile Picture');
//                        $drawing->setDescription('Profile Picture');
//                        $drawing->setWidthAndHeight(40, 40);
////                        $profile = $this->employeeFileRepo->fetchByEmpId($employee['EMPLOYEE_ID']);
//                        
////                        print_r($profile);
////                        die();
//                        $drawing->setPath('C:/xampp/htdocs/neo-hris/public/uploads/1584077606.jpg'); 
////                        $drawing->setPath(Helper::UPLOAD_DIR.'/1584077606.jpg'); 
//                        $drawing->setCoordinates($cell);
//                        //$drawing->setOffsetX(110);
//                        //$drawing->setRotation(25);
//                        $drawing->getShadow()->setVisible(true);
//                        $drawing->getShadow()->setDirection(45);
//                        $drawing->setWorksheet($spreadsheet->getActiveSheet());
            
            
//                    $spreadsheet->getActiveSheet()->setCellValue($cell, $value);
//                    $spreadsheet->getActiveSheet()->setCellValue($cell, 'photo');
//                        $column++;
//                        continue;
//                    }
                
                    
                $column = 2;
                     foreach($columns as $key => $value){
                    
                    $currentColumn = Coordinate::stringFromColumnIndex($column);
                    $cell = $currentColumn.''.$row;
//                    $spreadsheet->getActiveSheet()->getRowDimension($row)->setRowHeight(40);
//                    $spreadsheet->getActiveSheet()->getColumnDimension($currentColumn)->setWidth(40);
//                    $spreadsheet->getActiveSheet()->setCellValue($cell, $value);
                    $spreadsheet->getActiveSheet()->setCellValue($cell, $employee[$key]);
                    $column++;
                } 
                
                
//            } 
                $row++;
        } 
        
        ($spreadsheet->getActiveSheet()->set);
        $writer->save(Helper::UPLOAD_DIR . "/Employees_List.xlsx");
        return new JsonModel(['success' => true, 'message' => null]);
    }
    
    public function checkUniqueAction() {
        try {
            $request = $this->getRequest();
            $postedData = $request->getPost();
            $boundedParameter = [];
            $boundedParameter['colVal']=$postedData['columnValue'];
            $boundedParameter['pkValue']=$postedData['pkValue'];
            $sql = "SELECT ID_THUMB_ID
                    FROM HRIS_EMPLOYEES
                    WHERE ID_THUMB_ID = :colVal
                    AND ( ID_THUMB_ID  != 
                      (SELECT ID_THUMB_ID
                      FROM HRIS_EMPLOYEES
                      WHERE EMPLOYEE_ID = :pkValue ) and 1=1)";
            $result = EntityHelper::rawQueryResult($this->adapter, $sql,$boundedParameter);
            $data['notUnique'] = count($result) > 0;
            $data['message'] = "Already Reserved";
            return new JsonModel(['success' => true, 'data' => $data, 'error' => '']);
        } catch (Exception $e) {
            return new JsonModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
        }
    }
    
    
}
