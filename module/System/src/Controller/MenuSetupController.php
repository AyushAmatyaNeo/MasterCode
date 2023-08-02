<?php

namespace System\Controller;

use Application\Helper\EntityHelper;
use Application\Helper\Helper;
use Exception;
use System\Form\MenuSetupForm;
use System\Model\MenuSetup;
use System\Repository\MenuSetupRepository;
use System\Repository\RolePermissionRepository;
use System\Repository\RoleSetupRepository;
use Zend\Authentication\AuthenticationService;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Form\Annotation\AnnotationBuilder;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\JsonModel;

class MenuSetupController extends AbstractActionController {

    private $repository;
    private $adapter;
    private $form;
    private $employeeId;

    public function __construct(AdapterInterface $adapter) {
        $this->repository = new MenuSetupRepository($adapter);
        $this->adapter = $adapter;
        $auth = new AuthenticationService();
        $this->employeeId = $auth->getStorage()->read()['employee_id'];
    }

    public function initializeForm() {
        $menuSetupForm = new MenuSetupForm();
        $builder = new AnnotationBuilder();
        $this->form = $builder->createForm($menuSetupForm);
    }

    public function indexAction() {
        $list = $this->repository->fetchAll();

        $request = $this->getRequest();
        $this->initializeForm();

        $menuList = EntityHelper::getTableKVList($this->adapter, MenuSetup::TABLE_NAME, MenuSetup::MENU_ID, [MenuSetup::MENU_NAME], [MenuSetup::STATUS => "E"]);
        ksort($menuList);

        $roleSetupRepository = new RoleSetupRepository($this->adapter);
        $roleList = $roleSetupRepository->fetchAll();

        if ($request->isPost()) {
            $menuSetup = new MenuSetup();
            $this->form->setData($request->getPost());
            if ($this->form->isValid()) {
                $menuSetup->exchangeArrayFromForm($this->form->getData());
                $menuSetup->menuId = ((int) Helper::getMaxId($this->adapter, MenuSetup::TABLE_NAME, MenuSetup::MENU_ID)) + 1;
                $menuSetup->createdDt = Helper::getcurrentExpressionDate();
                $menuSetup->createdBy = $this->employeeId;
                $menuSetup->status = 'E';
                $this->repository->add($menuSetup);

                $this->flashmessenger()->addMessage("Menu Successfully Added!!!");
                return $this->redirect()->toRoute("menusetup");
            }
        }
        return Helper::addFlashMessagesToArray($this, [
                    'form' => $this->form,
                    'menuList' => $menuList,
                    "list" => $list,
                    "roleList" => $roleList
        ]);
    }

    public function menuAction() {
        try {
            $request = $this->getRequest();
            $data = $request->getPost();

            return new JsonModel(['success' => true, 'data' => $this->menu(), 'message' => null]);
        } catch (Exception $e) {
            return new JsonModel(['success' => false, 'data' => null, 'message' => $e->getMessage()]);
        }
    }

    public function menuInsertionAction() {
        try {
            $request = $this->getRequest();
            $data = $request->getPost();

            $record = $data['dataArray'];
            $model = new MenuSetup();
            $repository = new MenuSetupRepository($this->adapter);
            $model->menuId = Helper::getMaxId($this->adapter, MenuSetup::TABLE_NAME, MenuSetup::MENU_ID) + 1;
            $model->menuCode = $record['menuCode'];
            $model->menuName = $record['menuName'];
            $model->route = $record['route'];
            $model->action = $record['action'];
            $model->menuIndex = $record['menuIndex'];
            $model->iconClass = $record['iconClass'];
            if ($data['parentMenu'] != null) {
                $model->parentMenu = $data['parentMenu'];
            }
            $model->menuDescription = $record['menuDescription'];
            $model->isVisible = $record['isVisible'];
            $model->status = 'E';
            $model->createdDt = Helper::getcurrentExpressionDate();
            $model->createdBy = $this->employeeId;

            $menuIndexErr = "";
            $repository->add($model);
            $data = "Menu Successfully Added!!";
            $menuData = $this->menu();

            return new JsonModel([
                "success" => true,
                "data" => $data,
                "menuData" => $menuData,
                "menuIndexErr" => $menuIndexErr
            ]);
        } catch (Exception $e) {
            return new JsonModel(['success' => false, 'data' => null, 'message' => $e->getMessage()]);
        }
    }

    public function menuUpdateAction() {
        try {
            $request = $this->getRequest();
            $data = $request->getPost();


            $record = $data['dataArray'];
            $model = new MenuSetup();
            $repository = new MenuSetupRepository($this->adapter);
            $menuId = $record['menuId'];
            $model->modifiedDt = Helper::getcurrentExpressionDate();
            $model->modifiedBy = $this->employeeId;
            $model->menuCode = $record['menuCode'];
            $model->menuName = $record['menuName'];
            $model->route = $record['route'];
            $model->action = $record['action'];
            $model->menuIndex = $record['menuIndex'];
            $model->iconClass = $record['iconClass'];

            $model->menuDescription = $record['menuDescription'];
            $model->isVisible = $record['isVisible'];

            unset($model->status);
            unset($model->parentMenu);
            unset($model->menuId);
            unset($model->createdDt);

            $menuIndexErr = "";
            $repository->edit($model, $menuId);
            $data = "Menu Successfully Updated!!";
            $menuData = $this->menu();

            return new JsonModel([
                "success" => true,
                "data" => $data,
                "menuData" => $menuData,
                "menuIndexErr" => $menuIndexErr
            ]);
        } catch (Exception $e) {
            return new JsonModel(['success' => false, 'data' => null, 'message' => $e->getMessage()]);
        }
    }

    public function menuDeleteAction() {
        try {
            $request = $this->getRequest();
            $data = $request->getPost();

            $menuId = $data['menuId'];
            $this->repository->delete($menuId);
            return new JsonModel([
                "success" => true,
                "data" => $this->menu(),
                "message" => "Menu deleted successfully.",
                "error" => null
            ]);
        } catch (Exception $e) {
            return new JsonModel(['success' => false, 'data' => null, 'message' => $e->getMessage()]);
        }
    }

    private function menu($parent_menu = null) {
        $menuSetupRepository = new MenuSetupRepository($this->adapter);
        $result = $menuSetupRepository->getHierarchicalMenu($parent_menu);
        $num = count($result);
        if ($num > 0) {
            $temArray = array();
            foreach ($result as $row) {
                $children = $this->menu($row['MENU_ID']);
                if ($children) {
                    $temArray[] = array(
                        "text" => $row['MENU_NAME'],
                        "id" => $row['MENU_ID'],
                        "icon" => "fa fa-folder icon-state-success",
                        "children" => $children
                    );
                } else {
                    $temArray[] = array(
                        "text" => $row['MENU_NAME'],
                        "id" => $row['MENU_ID'],
                        "icon" => "fa fa-folder icon-state-success"
                    );
                }
            }
            return $temArray;
        } else {
            return false;
        }
    }

    public function pullMenuDetailAction() {
        try {
            $request = $this->getRequest();
            $data = $request->getPost();

            $menuId = $data['id'];
            $repository = new MenuSetupRepository($this->adapter);
            $result = $repository->fetchById($menuId);


            return new JsonModel(['success' => true, 'data' => $result, 'message' => null]);
        } catch (Exception $e) {
            return new JsonModel(['success' => false, 'data' => null, 'message' => $e->getMessage()]);
        }
    }

    public function permissionAssignAction() {
        try {
            $request = $this->getRequest();
            $data = $request->getPost();

            $rolePermissionRepository = new RolePermissionRepository($this->adapter);

            $roleId = $data['roleId'];
            $menuId = $data['menuId'];
            $checked = $data['checked'];

            $rolePermissionRepository->menuRoleAssign($menuId, $roleId, $checked == 'true' ? 'Y' : 'N');

            return new JsonModel(['success' => true, 'data' => $data, 'message' => null]);
        } catch (Exception $e) {
            return new JsonModel(['success' => false, 'data' => null, 'message' => $e->getMessage()]);
        }
    }

    public function pullRolePermissionListAction() {
        try {
            $request = $this->getRequest();
            $data = $request->getPost();


            $menuId = $data['menuId'];

            $rolePermissionRepository = new RolePermissionRepository($this->adapter);
            $roleRepository = new RoleSetupRepository($this->adapter);

            $result = $roleRepository->fetchAll();
            $rolePermissionList = $rolePermissionRepository->findAllRoleByMenuId($menuId);

            $tempArray = [];
            foreach ($result as $item) {
                array_push($tempArray, $item);
            }

            $temArray1 = [];
            foreach ($rolePermissionList as $row) {
                array_push($temArray1, $row);
            }


            return new JsonModel([
                "success" => true,
                "data" => $tempArray,
                "data1" => $temArray1
            ]);
        } catch (Exception $e) {
            return new JsonModel(['success' => false, 'data' => null, 'message' => $e->getMessage()]);
        }
    }

}
