<?php

namespace System\Controller;

use Application\Custom\CustomViewModel;
use Application\Helper\EntityHelper;
use Application\Helper\Helper;
use Exception;
use System\Model\RoleSetup;
use System\Repository\MenuSetupRepository;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Mvc\Controller\AbstractActionController;

class MenuReport extends AbstractActionController {

    private $adapter;
    private $menuSetupRepository;

    public function __construct(AdapterInterface $adapter) {
        $this->adapter = $adapter;
        $this->menuSetupRepository = new MenuSetupRepository($this->adapter);
    }

    public function indexAction() {
        $roleList = EntityHelper::getTableList($this->adapter, RoleSetup::TABLE_NAME, [RoleSetup::ROLE_ID, RoleSetup::ROLE_NAME], [RoleSetup::STATUS => 'E']);

        $url = $this->plugin('url');
        $fetchRoleWiseMenuUrl = $url->fromRoute('menu-report', ['action' => 'fetchRoleWiseMenu']);
        return Helper::addFlashMessagesToArray($this, ['fetchRoleWiseMenuUrl' => $fetchRoleWiseMenuUrl, 'roleList' => $roleList]);
    }

    private function menu($parent_menu = null) {
        $result = $this->menuSetupRepository->getHierarchicalMenuWithRoleId($parent_menu);
        $num = count($result);

        if ($num > 0) {
            $temArray = array();
            $total = 0;
            foreach ($result as $row) {
                $tempMenu = $this->menu($row['MENU_ID']);
                if (!$tempMenu) {
                    $children = false;
                } else {
                    $children = $tempMenu['array'];
                }

                $total++;
                if ($children) {
                    $menuDtlArray = array(
                        "text" => $row['MENU_NAME'],
                        "children" => $children,
                    );
                    $temArray[] = $menuDtlArray;
                } else {
                    $temArray[] = array(
                        "text" => $row['MENU_NAME'],
                    );
                }
            }
            return ['array' => $temArray];
        } else {
            return false;
        }
    }

    public function fetchRoleWiseMenuAction() {
        try {
            $request = $this->getRequest();
            if ($request->isPost()) {
                $postedData = $request->getPost();

                $roleId = $postedData['roleId'];
                if (!isset($roleId)) {
                    throw new Exception("parameter roleId is required");
                }

                $this->menuSetupRepository->setRoleId($roleId);
                $data = $this->menuSetupRepository->getHierarchicalMenuWithRoleId();
                $menuList = [];
                foreach ($data as $key => $row) {
                    $tempMenu = $this->menu($row['MENU_ID']);
                    if ($tempMenu) {
                        $menuDtlArray = array(
                            "text" => $row['MENU_NAME'],
                            "children" => $tempMenu['array'],
                        );
                        array_push($menuList, $menuDtlArray);
                    } else {
                        array_push($menuList, array(
                            "text" => $row['MENU_NAME'],
                        ));
                    }
                }
                return new CustomViewModel(['success' => true, 'data' => $menuList, 'error' => '']);
            } else {
                throw new Exception("The request should be of type post");
            }
        } catch (Exception $e) {
            return new CustomViewModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
        }
    }

}
