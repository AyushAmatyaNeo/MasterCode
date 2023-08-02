<?php
namespace Application\Controller;

use Application\Helper\Helper;
use Application\Model\HrisAuthStorage;
use Application\Model\Preference;
use Application\Model\User;
use Application\Model\UserLog;
use Application\Repository\LoginRepository;
use Application\Repository\MonthRepository;
use Application\Repository\UserLogRepository;
use AttendanceManagement\Repository\AttendanceDetailRepository;
use Setup\Repository\EmployeeRepository;
use System\Repository\RoleControlRepository;
use System\Repository\RolePermissionRepository;
use System\Repository\RoleSetupRepository;
use System\Repository\SystemSettingRepository;
use Zend\Authentication\AuthenticationService;
use Zend\Db\Adapter\AdapterInterface;
use Zend\EventManager\EventManagerInterface;
use Zend\Form\Annotation\AnnotationBuilder;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Exception;

class AuthController extends AbstractActionController {

    protected $form;
    protected $storage;
    protected $authservice;
    protected $adapter;
    protected $preference;

    public function __construct(AuthenticationService $authService, AdapterInterface $adapter) {
        $this->authservice = $authService;
        $this->storage = $authService->getStorage();
        $this->adapter = $adapter;

        $preferenceRepo = new SystemSettingRepository($adapter);
        $this->preference = new Preference();
        $this->preference->exchangeArrayFromDB($preferenceRepo->fetch());
    }

    public function setEventManager(EventManagerInterface $events) {
        parent::setEventManager($events);
        $controller = $this;
        $events->attach('dispatch', function ($e) use ($controller) {
            $controller->layout('layout/login');
        }, 100);
    }

    public function getAuthService() {
        if (!$this->authservice) {
            $this->authservice = $this->getServiceLocator()
                    ->get('AuthService');
        }
        return $this->authservice;
    }

    public function getSessionStorage() {
        if (!$this->storage) {
            $this->storage = $this->getServiceLocator()
                    ->get(HrisAuthStorage::class);
        }
        return $this->storage;
    }

    public function getForm() {
        if (!$this->form) {
            $user = new User();
            $builder = new AnnotationBuilder();
            $this->form = $builder->createForm($user);
        }

        return $this->form;
    }

    public function loginAction() {
        //to make register attendance by default checked on login page:: condition start
        $type = $this->params()->fromRoute('type');
        if ($type !== null) {
            $this->getSessionStorage()->forgetMe();
            $this->getAuthService()->clearIdentity();
        }
        //end
        
        if ($this->getAuthService()->hasIdentity()) {
            return $this->redirect()->toRoute('dashboard');
        }
        $form = $this->getForm();
        return new ViewModel([
            'form' => $form,
            'type' => $type,
            'messages' => $this->flashmessenger()->getMessages(),
            'preference' => $this->preference
        ]);
    }

    public function authenticateAction() {
        $form = $this->getForm();
        $redirect = 'login';
        $request = $this->getRequest();
        if ($request->isPost()) {
            $form->setData($request->getPost());
            if ($form->isValid()) {
				try {
                
                 /*
                 * To check First Time Password 
                 */
                $firstTimePwdChange=$this->checkFirstTimePasswordChange($request->getPost('username'),$request->getPost('password'));
                if ($firstTimePwdChange) {
                    return $firstTimePwdChange;
                }
                /*
                 * End Of First Time Password 
                 */
                
                
                
                /*
                 * password expiration check | comment this code if this feature is not needed
                 */
                $needPwdChange = $this->checkPasswordExpire($request->getPost('username'),$request->getPost('password'));
                if ($needPwdChange) {
                    return $needPwdChange;
                }
                /*
                 * end of password expiration check
                 */
                /*
                 * user authentication
                 */

                $this->getAuthService()->getAdapter()
                        ->setIdentity($request->getPost('username'))
                        ->setCredential($request->getPost('password'))
                        ->getDbSelect()->where("STATUS = 'E' AND IS_LOCKED = 'N'");

                $result = $this->getAuthService()->authenticate();
                foreach ($result->getMessages() as $message) {
                    $this->flashmessenger()->addMessage($message);
                }
                if ($result->isValid()) {
                    if (isset($_COOKIE[$request->getPost('username')])) {
                        setcookie($request->getPost('username'), '', 1, "/");
                    }
                    //after authentication success get the user specific details
                    $resultRow = $this->getAuthService()->getAdapter()->getResultRowObject();

                    $isLocked = $this->checkIfAccountLocked($resultRow);
                    if ($isLocked) {
                        return $isLocked;
                    }
                    $allowRegisterAttendance = false;
                    $attendanceType = "IN";
                    if ($this->preference->allowSystemAttendance == 'Y') {
                        $employeeId = $resultRow->EMPLOYEE_ID;
                        $attendanceDetailRepo = new AttendanceDetailRepository($this->adapter);
                        $todayAttendance = $attendanceDetailRepo->fetchByEmpIdAttendanceDT($employeeId, 'TRUNC(SYSDATE)');
                        $inTime = $todayAttendance['IN_TIME'];
                        $attendanceType = ($inTime) ? "OUT" : "IN";
                        $allowRegisterAttendance = ($todayAttendance['TRAVEL_ID'] == null && $todayAttendance['LEAVE_ID'] == null) ? true : false;
                    }

                    $employeeRepo = new EmployeeRepository($this->adapter);
                    $employeeDetail = $employeeRepo->employeeDetailSession($resultRow->EMPLOYEE_ID);

                    $companyRepo = new \Setup\Repository\CompanyRepository($this->adapter);
                    $companyDetail = $companyRepo->fetchById($employeeDetail['COMPANY_ID']);

                    $monthRepo = new MonthRepository($this->adapter);
                    $fiscalYear = $monthRepo->getCurrentFiscalYear();

                    $repository = new RolePermissionRepository($this->adapter);
                    $rawMenus = $repository->fetchAllMenuByRoleId($resultRow->ROLE_ID);
                    $menus = Helper::extractDbData($rawMenus);

                    $roleRepo = new RoleSetupRepository($this->adapter);
                    $acl = $roleRepo->fetchById($resultRow->ROLE_ID);
                    $acl['CONTROL'] = explode(',', $acl['CONTROL']);
                    $roleControlRepo = new RoleControlRepository($this->adapter);
                    $roleControlDetails = $roleControlRepo->fetchById($acl['ROLE_ID']);
                    $acl['CONTROL_VALUES']=$roleControlDetails;

                    $this->getAuthService()->getStorage()->write([
                        "user_name" => $request->getPost('username'),
                        "user_id" => $resultRow->USER_ID,
                        "employee_id" => $resultRow->EMPLOYEE_ID,
                        "role_id" => $resultRow->ROLE_ID,
                        "employee_detail" => $employeeDetail,
                        "fiscal_year" => $fiscalYear,
                        "menus" => $menus,
                        'register_attendance' => $attendanceType,
                        'allow_register_attendance' => $allowRegisterAttendance,
                        'acl' => (array) $acl,
                        'preference' => (array) $this->preference,
                        'company_detail' => $companyDetail
                    ]);


                    // to add user log details in HRIS_USER_LOG
                    $this->setUserLog($this->adapter, $request->getServer('REMOTE_ADDR'), $resultRow->USER_ID);

                    /*
                     * 
                     */
                    if (1 == $request->getPost('rememberme')) {
                        $this->getSessionStorage()
                                ->setRememberMe(1);
                        $this->getAuthService()->setStorage($this->getSessionStorage());
                    }
                    /*
                     * 
                     */

                    $redirect = 'dashboard';
                } else {
                    $this->allowLoginFor($request->getPost('username'), 5, 3600);
                }
				
				} catch (Exception $e) {
                    $this->flashmessenger()->addMessage('Login Error');
                }
            }
        }
        return $this->redirect()->toRoute($redirect);
    }

    private function setUserLog(AdapterInterface $adapter, $clientIp, $userId) {
        $userLogRepo = new UserLogRepository($adapter);

        $userLog = new UserLog();
        $userLog->loginIp = $clientIp;
        $userLog->userId = $userId;

        $userLogRepo->add($userLog);
    }

    public function logoutAction() {
        $this->getSessionStorage()->forgetMe();
        $this->getAuthService()->clearIdentity();

        $this->flashmessenger()->addMessage("You've been logged out");
        return $this->redirect()->toRoute('login');
    }

    public function checkPasswordExpire($userName,$pwd) {
        if (!($this->preference->forcePasswordRenew == 'Y')) {
            return false;
        }

        $maxPasswordDays = $this->preference->forcePasswordRenewDay;
        $loginRepo = new LoginRepository($this->adapter);
        $result = $loginRepo->checkPasswordExpire($userName,$pwd);
        $createdDays = $result['CREATED_DAYS'];
        $modifiedDays = $result['MODIFIED_DAYS'];
        $isLocked = $result['IS_LOCKED'];

        if ($modifiedDays == null) {
            $passwordDays = $createdDays;
        } else {
            $passwordDays = $modifiedDays;
        }

        if ($isLocked == 'Y') {
            return false;
        }

        if ($passwordDays > $maxPasswordDays) {
            return $this->redirect()->toRoute('updatePwd', ['action' => 'changePwd', 'un' => $userName]);
        } else {
            return false;
        }
    }

    public function changePwdAction() {
        $userName = $this->params()->fromRoute('un');
        $message = [];

        $request = $this->getRequest();
        $loginRepo = new LoginRepository($this->adapter);
        if ($request->isPost()) {
            $postData = $request->getPost();
            $userName = $postData['username'];
            $oldPassword = $postData['oldpassword'];
            $newPassword = $postData['password'];
            $userOldPassword = $loginRepo->getPwdByUserName($userName);

            if ($oldPassword != $userOldPassword) {
                array_push($message, 'old password is not correct');
            } elseif ($oldPassword == $userOldPassword && $userOldPassword == $newPassword) {
                array_push($message, 'new password cannot be same as old password');
            } else {
                $loginRepo->updatePwdByUserName($userName, $newPassword);
                $this->flashmessenger()->addMessage('please login with updated password');
                return $this->redirect()->toRoute('login');
            }
        }

        return new ViewModel([
            'userName' => $userName,
            'messages' => $message
        ]);
    }

    public function checkIfAccountLocked($account) {
//        if (!($this->preference->allowAccountLock == 'Y')) {
//            return false;
//        }
        if ($account->IS_LOCKED == 'Y') {
            $this->flashmessenger()->clearCurrentMessages();
            $this->flashmessenger()->addMessage('The account ' . $account->USER_NAME . ' has been locked Please contact the Admin');
            $this->getAuthService()->clearIdentity();
            return $this->redirect()->toRoute('login');
        } else {
            return false;
        }
    }

    public function allowLoginFor($cookie_name, $tryCount, $withIn) {
        if (!($this->preference->allowAccountLock == 'Y')) {
            return;
        }
        
        $tryCount = $this->preference->accountLockTryNumber;
        $withIn = $this->preference->accountLockTrySecond;
        $loginRepo = new LoginRepository($this->adapter);
        $userValid = $loginRepo->fetchByUserName($cookie_name);

        if ($userValid && ($userValid->IS_LOCKED == 'Y')) {
            $this->flashmessenger()->clearCurrentMessages();
            $this->flashmessenger()->addMessage("The account {$cookie_name} has been locked Please contact the Admin");
            $this->getAuthService()->clearIdentity();
            return;
        }

        if ($userValid) {
            
            $cookie_value = isset($_COOKIE[$cookie_name]) ? $_COOKIE[$cookie_name] : 0;
            $cookie_value++;
            $atteptLeft = $tryCount - $cookie_value;
            setcookie($cookie_name, $cookie_value, time() + $withIn, "/");

            $this->flashmessenger()->clearCurrentMessages();
            $this->flashmessenger()->addMessage("Incorrect password for {$cookie_name}. After {$atteptLeft} unsuccessful attempt, account will be locked");
            $this->getAuthService()->clearIdentity();

            if ($atteptLeft == 0) {
                $loginRepo->updateByUserName($cookie_name);
                setcookie($cookie_name, '', 1, "/");
                $this->flashmessenger()->clearCurrentMessages();
                $this->flashmessenger()->addMessage("The account {$cookie_name} has been locked Please contact the Admin");
                $this->getAuthService()->clearIdentity();
            }

        }
    }
    
     public function checkFirstTimePasswordChange($userName,$pwd) {
        if (!($this->preference->firstTimePwdRenew == 'Y')) {
            return false;
        }
        $loginRepo = new LoginRepository($this->adapter);
        $result = $loginRepo->fetchByUserName($userName,$pwd);
        return ($result['FIRST_TIME']=='Y')?  $this->redirect()->toRoute('updatePwd', ['action' => 'changePwd', 'un' => $userName]):false;
    }

}
