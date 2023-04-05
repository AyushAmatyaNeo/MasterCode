<?php
namespace SelfService\Controller;

use Application\Controller\HrisController;
use Application\Factory\ConfigInterface;
use Application\Helper\EntityHelper as ApplicationHelper;
use Application\Helper\Helper;
use Application\Model\HrisQuery;
use Asset\Repository\IssueRepository;
use AttendanceManagement\Model\ShiftAssign;
use AttendanceManagement\Model\ShiftSetup;
use AttendanceManagement\Repository\ShiftAssignRepository;
use LeaveManagement\Model\LeaveMaster;
use Setup\Form\HrEmployeesFormTabEight;
use Setup\Form\HrEmployeesFormTabFive;
use Setup\Form\HrEmployeesFormTabFour;
use Setup\Form\HrEmployeesFormTabNine;
use Setup\Form\HrEmployeesFormTabOne;
use Setup\Form\HrEmployeesFormTabSeven;
use Setup\Form\HrEmployeesFormTabSix;
use Setup\Form\HrEmployeesFormTabThree;
use Setup\Form\HrEmployeesFormTabTwo;
use Setup\Model\EmployeeExperience;
use Setup\Model\EmployeeFile as EmployeeFileModel;
use Setup\Model\EmployeeQualification;
use Setup\Model\EmployeeTraining;
use Setup\Model\HrEmployees;
use Setup\Model\RecommendApprove;
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
use TheSeer\Tokenizer\Exception;
use Zend\Authentication\Storage\StorageInterface;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Form\Annotation\AnnotationBuilder;
use Zend\View\Model\JsonModel;

class Profile extends HrisController {

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
        }
        if (!$this->formTwo) {
            $this->formTwo = $builder->createForm($formTabTwo);
        }
        if (!$this->formThree) {
            $this->formThree = $builder->createForm($formTabThree);
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

    public function indexAction() {
        $id = $this->employeeId;
        $tab = (int) $this->params()->fromRoute('tab', 0);
        if ($tab === 0) {
            $tab = 1;
        }
        $this->initializeMultipleForm();
        $empQualificationRepo = new EmployeeQualificationRepository($this->adapter);
        $empExperienceRepo = new EmployeeExperienceRepository($this->adapter);
        $empTrainingRepo = new EmployeeTrainingRepository($this->adapter);


        $employeeData = (array) $this->repository->getById($id);
        $profilePictureId = $employeeData[HrEmployees::PROFILE_PICTURE_ID];
        $filePathArray = ApplicationHelper::getTableKVList($this->adapter, EmployeeFileModel::TABLE_NAME, EmployeeFileModel::FILE_CODE, [EmployeeFileModel::FILE_PATH], [EmployeeFileModel::FILE_CODE => $profilePictureId], null);
        $filePath = empty($filePathArray) ? $this->config->getApplicationConfig()['default-profile-picture'] : $filePathArray[key($filePathArray)];
        $perVdcMunicipalityDtl = $this->repository->getVdcMunicipalityDtl($employeeData[HrEmployees::ADDR_PERM_VDC_MUNICIPALITY_ID]);
        $perDistrictDtl = $this->repository->getDistrictDtl($perVdcMunicipalityDtl['DISTRICT_ID']);
        $perZoneDtl = $this->repository->getZoneDtl($perDistrictDtl['ZONE_ID']);

        $tempVdcMunicipalityDtl = $this->repository->getVdcMunicipalityDtl($employeeData[HrEmployees::ADDR_TEMP_VDC_MUNICIPALITY_ID]);
        $tempDistrictDtl = $this->repository->getDistrictDtl($tempVdcMunicipalityDtl['DISTRICT_ID']);
        $tempZoneDtl = $this->repository->getZoneDtl($tempDistrictDtl['ZONE_ID']);

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
                'formSeven' => $this->formSeven,
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
        ]);
    }

    public function editAction() {
        $id = (int) $this->params()->fromRoute('id', 0);
        $tab = (int) $this->params()->fromRoute('tab', 1);

        if($id!=$this->employeeId){
        return $this->redirect()->toRoute('profile', ['action' => 'index']);
        }
        if (11 === $tab) {
            $this->flashmessenger()->addMessage("Employee Successfully Submitted!!!");
            return $this->redirect()->toRoute('profile', ['action' => 'index']);
        }


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
        $getEmpShiftDtl = null;
        $employeePreDtl = null;
        if ($id != 0) {
            $employeeData = (array) $this->repository->fetchById($id);
            $profilePictureId = $employeeData[HrEmployees::PROFILE_PICTURE_ID];
            $getEmpShiftDtl = $shiftAssignRepo->fetchByEmployeeId($id);
            $employeePreDtl = $recommApproverRepo->fetchById($id);
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

                        return $this->redirect()->toRoute('profile', ['action' => 'edit', 'id' => $id, 'tab' => 2]);
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
                        return $this->redirect()->toRoute('profile', ['action' => 'edit', 'id' => $id, 'tab' => 3]);
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
                        return $this->redirect()->toRoute('profile', ['action' => 'edit', 'id' => $id, 'tab' => 4]);
                    }
                    break;
                case 4:
                    $this->formFour->setData($postData);
                    if ($this->formFour->isValid()) {
                        $formFourModel->exchangeArrayFromForm($this->formFour->getData());
                        $formFourModel->modifiedBy = $this->employeeId;
                        $formFourModel->modifiedDt = Helper::getcurrentExpressionDate();
                        $this->repository->edit($formFourModel, $id);
                        /*
                         * Shift Assign part
                         */
                        $shiftId = $postData->shift;
                        $shiftAssign = new ShiftAssign();
                        $shiftAssign->employeeId = $id;
                        $shiftAssign->shiftId = $shiftId;
                        // echo '<pre>';print_r($getEmpShiftDtl);die;

                        if ($getEmpShiftDtl != null) {
                            // print_r('sddf');die;
                            $shiftAssignClone = clone $shiftAssign;

                            unset($shiftAssignClone->employeeId);
                            unset($shiftAssignClone->shiftId);
                            unset($shiftAssignClone->createdDt);

                            if ($shiftId != $getEmpShiftDtl['SHIFT_ID']) {
                                $shiftAssignClone->status = 'D';
                                $shiftAssignClone->modifiedDt = Helper::getcurrentExpressionDate();
                                $shiftAssignClone->modifiedBy = $this->employeeId;
                                $shiftAssignRepo->edit($shiftAssignClone, [$id, $getEmpShiftDtl['SHIFT_ID']]);

                                $shiftAssign->createdDt = Helper::getcurrentExpressionDate();
                                $shiftAssign->createdBy = $this->employeeId;
                                $shiftAssign->status = 'E';
                                $shiftAssignRepo->add($shiftAssign);
                            }
                        } else {
                            // print_r('123');die;

                            $shiftAssign->createdDt = Helper::getcurrentExpressionDate();
                            $shiftAssign->createdBy = $this->employeeId;
                            $shiftAssign->status = 'E';
                            $shiftAssignRepo->add($shiftAssign);
                        }
                        /*
                         * 
                         */

                        /*
                         * Recommender Approver Assign part 
                         */
                        $recommenderId = $postData->recommender;
                        $approverId = $postData->approver;

                        $recommendApprove = new RecommendApprove();
                        if ($employeePreDtl == null) {
                            $recommendApprove->employeeId = $id;
                            $recommendApprove->recommendBy = $recommenderId;
                            $recommendApprove->approvedBy = $approverId;
                            $recommendApprove->createdDt = Helper::getcurrentExpressionDate();
                            $recommendApprove->status = 'E';
                            $recommApproverRepo->add($recommendApprove);
                        } else if ($employeePreDtl != null) {
                            $id = $employeePreDtl['EMPLOYEE_ID'];
                            $recommendApprove->employeeId = $id;
                            $recommendApprove->recommendBy = $recommenderId;
                            $recommendApprove->approvedBy = $approverId;
                            $recommendApprove->modifiedDt = Helper::getcurrentExpressionDate();
                            $recommendApprove->status = 'E';
                            $recommApproverRepo->edit($recommendApprove, $id);
                        }
                        /*
                         * 
                         */
                        return $this->redirect()->toRoute('profile', ['action' => 'edit', 'id' => $id, 'tab' => 5]);
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
                "bloodGroups" => ApplicationHelper::getTableKVList($this->adapter, 'HRIS_BLOOD_GROUPS', 'BLOOD_GROUP_ID', ['BLOOD_GROUP_CODE'], NULL, NULL, TRUE),
                "genders" => ApplicationHelper::getTableKVList($this->adapter, \Setup\Model\Gender::TABLE_NAME, \Setup\Model\Gender::GENDER_ID, [\Setup\Model\Gender::GENDER_NAME], null, null, true),
                "zones" => ApplicationHelper::getTableKVList($this->adapter, \Setup\Model\Zones::TABLE_NAME, \Setup\Model\Zones::ZONE_ID, [\Setup\Model\Zones::ZONE_NAME], null, null, true),
                "religions" => ApplicationHelper::getTableKVList($this->adapter, 'HRIS_RELIGIONS', 'RELIGION_ID', ['RELIGION_NAME'], null, null, true),
                "companies" => ApplicationHelper::getTableKVListWithSortOption($this->adapter, "HRIS_COMPANY", "COMPANY_ID", ["COMPANY_NAME"], ["STATUS" => "E"], "COMPANY_NAME", "ASC", null, false, true),
                "countries" => ApplicationHelper::getTableKVList($this->adapter, 'HRIS_COUNTRIES', 'COUNTRY_ID', ['COUNTRY_NAME'], null, null, true),
                'filetypes' => ApplicationHelper::getTableKVList($this->adapter, 'HRIS_FILE_TYPE', 'FILETYPE_CODE', ['NAME'],"Status='E'"),
                'serviceTypes' => ApplicationHelper::getTableKVListWithSortOption($this->adapter, "HRIS_SERVICE_TYPES", "SERVICE_TYPE_ID", ["SERVICE_TYPE_NAME"], ["STATUS" => 'E'], "SERVICE_TYPE_NAME", "ASC", null, true, true),
                'positions' => ApplicationHelper::getTableKVListWithSortOption($this->adapter, "HRIS_POSITIONS", "POSITION_ID", ["POSITION_NAME"], ["STATUS" => 'E'], "POSITION_NAME", "ASC", null, true, true),
                'designations' => ApplicationHelper::getTableKVListWithSortOption($this->adapter, "HRIS_DESIGNATIONS", "DESIGNATION_ID", ["DESIGNATION_TITLE"], ["STATUS" => 'E'], "DESIGNATION_TITLE", "ASC", null, true, true),
                'departments' => ApplicationHelper::getTableKVListWithSortOption($this->adapter, "HRIS_DEPARTMENTS", "DEPARTMENT_ID", ["DEPARTMENT_NAME"], ["STATUS" => 'E'], "DEPARTMENT_NAME", "ASC", null, true, true),
                'branches' => ApplicationHelper::getTableKVListWithSortOption($this->adapter, "HRIS_BRANCHES", "BRANCH_ID", ["BRANCH_NAME"], ["STATUS" => 'E'], "BRANCH_NAME", "ASC", null, true, true),
                'academicDegree' => ApplicationHelper::getTableKVListWithSortOption($this->adapter, "HRIS_ACADEMIC_DEGREES", "ACADEMIC_DEGREE_ID", ["ACADEMIC_DEGREE_NAME"], ["STATUS" => 'E'], "ACADEMIC_DEGREE_NAME", "ASC", null, false, true),
                'academicUniversity' => ApplicationHelper::getTableKVListWithSortOption($this->adapter, "HRIS_ACADEMIC_UNIVERSITY", "ACADEMIC_UNIVERSITY_ID", ["ACADEMIC_UNIVERSITY_NAME"], ["STATUS" => 'E'], "ACADEMIC_UNIVERSITY_NAME", "ASC", null, false, true),
                'academicProgram' => ApplicationHelper::getTableKVListWithSortOption($this->adapter, "HRIS_ACADEMIC_PROGRAMS", "ACADEMIC_PROGRAM_ID", ["ACADEMIC_PROGRAM_NAME"], ["STATUS" => 'E'], "ACADEMIC_PROGRAM_NAME", "ASC", null, false, true),
                'academicCourse' => ApplicationHelper::getTableKVListWithSortOption($this->adapter, "HRIS_ACADEMIC_COURSES", "ACADEMIC_COURSE_ID", ["ACADEMIC_COURSE_NAME"], ["STATUS" => 'E'], "ACADEMIC_COURSE_NAME", "ASC", null, false, true),
                'rankTypes' => $rankTypes,
                'profilePictureId' => $profilePictureId,
                'address' => $address,
                'shiftId' => ($getEmpShiftDtl != null) ? $getEmpShiftDtl['SHIFT_ID'] : 0,
                'recommenderId' => ($employeePreDtl != null) ? $employeePreDtl['RECOMMEND_BY'] : 0,
                'approverId' => ($employeePreDtl != null) ? $employeePreDtl['APPROVED_BY'] : 0,
                'shifts' => ApplicationHelper::getTableKVListWithSortOption($this->adapter, ShiftSetup::TABLE_NAME, ShiftSetup::SHIFT_ID, [ShiftSetup::SHIFT_ENAME], [ShiftSetup::STATUS => 'E'], ShiftSetup::SHIFT_ENAME, "ASC", null, false, true),
                'leaves' => ApplicationHelper::getTableKVListWithSortOption($this->adapter, LeaveMaster::TABLE_NAME, LeaveMaster::LEAVE_ID, [LeaveMaster::LEAVE_ENAME], [LeaveMaster::STATUS => 'E'], LeaveMaster::LEAVE_ENAME, "ASC", null, false, true),
                'recommenders' => ApplicationHelper::getTableKVListWithSortOption($this->adapter, "HRIS_EMPLOYEES", "EMPLOYEE_ID", ["FIRST_NAME", "MIDDLE_NAME", "LAST_NAME"], ["STATUS" => "E"], "FIRST_NAME", "ASC", " ", false, true),
                'approvers' => ApplicationHelper::getTableKVListWithSortOption($this->adapter, "HRIS_EMPLOYEES", "EMPLOYEE_ID", ["FIRST_NAME", "MIDDLE_NAME", "LAST_NAME"], ["STATUS" => "E"], "FIRST_NAME", "ASC", " ", false, true),
                'customRender' => Helper::renderCustomView(),
//            added
            'programSE' => $programSE,
                'countries' => $this->getCountryList(),
                'allDistricts' =>ApplicationHelper::getTableKVList($this->adapter, 'HRIS_DISTRICTS', 'DISTRICT_ID', ['DISTRICT_NAME'], null, null, true),
//                'syngergyTable' =>$syngergyTable,
//                'distributionTable' =>$distributionTable,
//                'relation' => ApplicationHelper::getTableKVListWithSortOption($this->adapter, "HRIS_RELATIONS", "RELATION_ID", ["RELATION_NAME"], ["STATUS" => 'E'], "RELATION_NAME", "ASC", null, false, true),
            'relation' => ApplicationHelper::getTableList($this->adapter, "HRIS_RELATIONS", ["RELATION_ID","RELATION_NAME"], ["STATUS" => 'E']),
        ]);
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

            $emplyoeeId = $data['employeeId'];
            $companyId = $data['companyId'];
            $branchId = $data['branchId'];
            $departmentId = $data['departmentId'];
            $designationId = $data['designationId'];
            $positionId = $data['positionId'];
            $serviceTypeId = $data['serviceTypeId'];
            $serviceEventTypeId = $data['serviceEventTypeId'];
            $employeeTypeId = $data['employeeTypeId'];

            $repository = new EmployeeRepository($this->adapter);
            $result = $repository->filterRecords($emplyoeeId, $branchId, $departmentId, $designationId, $positionId, $serviceTypeId, $serviceEventTypeId, 1, $companyId, $employeeTypeId);
            $employeeList = [];
            foreach ($result as $row) {
                if ($row['MARITAL_STATUS'] == 'U') {
                    $row['MARITAL_STATUS'] = "Unmarried";
                } else {
                    $row['MARITAL_STATUS'] = "Married";
                }
                $perVdcMunicipalityDtl = $repository->getVdcMunicipalityDtl($row['ADDR_PERM_VDC_MUNICIPALITY_ID']);
                $perDistrictDtl = $repository->getDistrictDtl($perVdcMunicipalityDtl['DISTRICT_ID']);
                $perZoneDtl = $repository->getZoneDtl($perDistrictDtl['ZONE_ID']);

                $tempVdcMunicipalityDtl = $repository->getVdcMunicipalityDtl($row['ADDR_TEMP_VDC_MUNICIPALITY_ID']);
                $tempDistrictDtl = $repository->getDistrictDtl($tempVdcMunicipalityDtl['DISTRICT_ID']);
                $tempZoneDtl = $repository->getZoneDtl($tempDistrictDtl['ZONE_ID']);

                $row['ADDR_PERM_DISTRICT_NAME'] = $perDistrictDtl['DISTRICT_NAME'];
                $row['ADDR_TEMP_DISTRICT_NAME'] = $tempDistrictDtl['DISTRICT_NAME'];
                $row['ADDR_PERM_ZONE_NAME'] = $perZoneDtl['ZONE_NAME'];
                $row['ADDR_TEMP_ZONE_NAME'] = $tempZoneDtl['ZONE_NAME'];

                array_push($employeeList, $row);
            }


            return new JsonModel(['success' => true, 'data' => $employeeList, 'message' => null]);
        } catch (Exception $e) {
            return new JsonModel(['success' => false, 'data' => null, 'message' => $e->getMessage()]);
        }
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

    public function getSelfDetailsAction(){
        return new JsonModel(['name' => $this->storageData['employee_detail']['EMPLOYEE_CODE'].'-'.$this->storageData['employee_detail']['FULL_NAME'], 'companyLogo' => $this->storageData['employee_detail']['COMPANY_FILE_PATH']]);
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

                    $id = (array_key_exists('eRId', $relations))?$relations['eRId']:0;
                    if ($id == 0 ) {
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
    
    
}
