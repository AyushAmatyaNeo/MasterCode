<?php

namespace System\Controller;

use Exception;
use Interop\Container\ContainerInterface;
use System\Model\DashboardDetail;
use System\Repository\DashboardDetailRepo;
use System\Repository\MenuSetupRepository;
use System\Repository\RolePermissionRepository;
use System\Repository\RoleSetupRepository;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\JsonModel;

class DashboardController extends AbstractActionController {

    private $container;
    private $adapter;

    public function __construct(ContainerInterface $container) {
        $this->container = $container;
        $this->adapter = $this->container->get(AdapterInterface::class);
    }

    public function indexAction() {
        $roleRepo = new RoleSetupRepository($this->adapter);
        $rolesRaw = $roleRepo->fetchAll();
        $roles = [];
        foreach ($rolesRaw as $value) {
            array_push($roles, $value);
        }
        $dashboardItems = $this->container->get("config")['dashboard-items'];
        $roleTypes = $this->container->get("config")['role-types'];
        return ['dashboardItems' => $dashboardItems, 'roles' => $roles, 'roleTypes' => $roleTypes];
    }

    public function fetchRoleDashboardsAction() {
        try {
            $request = $this->getRequest();
            $data = $request->getPost();

            $roleId = $data['roleId'];
            $dashboardRepo = new DashboardDetailRepo($this->adapter);
            $result = $dashboardRepo->fetchById($roleId);
            $dashboards = [];
            foreach ($result as $dashboard) {
                array_push($dashboards, $dashboard);
            }


            return new JsonModel(['success' => true, 'data' => $dashboards, 'message' => null]);
        } catch (Exception $e) {
            return new JsonModel(['success' => false, 'data' => null, 'message' => $e->getMessage()]);
        }
    }

    public function assignDashboardAction() {
        try {
            $request = $this->getRequest();
            $data = $request->getPost();


            $dashboard = $data['dashboard'];
            $roleId = $data['roleId'];
            $status = $data['status'];
            $roleType = $data['roleType'];

            $dashboardRepo = new DashboardDetailRepo($this->adapter);

            $dashboardDetail = new DashboardDetail;
            $dashboardDetail->dashboard = $dashboard;
            $dashboardDetail->roleId = $roleId;
            $dashboardDetail->roleType = $roleType;
            if ($status == 'true') {
                $dashboardRepo->add($dashboardDetail);
            } else {
                $ids['dashboard'] = $dashboard;
                $ids['roleId'] = $roleId;
                $dashboardRepo->delete($ids);
            }

            return new JsonModel(['success' => true, 'data' => $data, 'message' => null]);
        } catch (Exception $e) {
            return new JsonModel(['success' => false, 'data' => null, 'message' => $e->getMessage()]);
        }
    }

    public function updateDashboardAssignAction() {
        try {
            $request = $this->getRequest();
            $data = $request->getPost();

            $dashboard = $data['dashboard'];
            $roleId = $data['roleId'];
            $roleType = $data['roleType'];

            $dashboardRepo = new DashboardDetailRepo($this->adapter);

            $dashboardDetail = new DashboardDetail;
            $dashboardDetail->roleType = $roleType;

            $dashboardRepo->edit($dashboardDetail, [DashboardDetail::ROLE_ID => $roleId, DashboardDetail::DASHBOARD => $dashboard]);

            return new JsonModel(['success' => true, 'data' => $data, 'message' => null]);
        } catch (Exception $e) {
            return new JsonModel(['success' => false, 'data' => null, 'message' => $e->getMessage()]);
        }
    }

    public function menuDeleteAction() {
        try {
            $request = $this->getRequest();
            $data = $request->getPost();

            $menuId = $data['menuId'];
            $menuRepository = new MenuSetupRepository($this->adapter);
            $rolePermissionRepository = new RolePermissionRepository($this->adapter);

            $allChildMenuList = $menuRepository->getAllCHildMenu($menuId);
            foreach ($allChildMenuList as $allChildMenu) {
                $menuDeleteResult = $menuRepository->delete($allChildMenu['MENU_ID']);
                $rolePermissionResult = $rolePermissionRepository->delete($allChildMenu['MENU_ID']);
            }
            $menuData = $this->menu();

            return new JsonModel([
                "success" => true,
                "menuData" => $menuData,
                "data" => "Menu with all respective detail successfully deleted!!"
            ]);
        } catch (Exception $e) {
            return new JsonModel(['success' => false, 'data' => null, 'message' => $e->getMessage()]);
        }
    }

}
