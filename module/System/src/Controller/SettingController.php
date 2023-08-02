<?php

namespace System\Controller;

use Application\Custom\CustomViewModel;
use Interop\Container\ContainerInterface;
use System\Model\Setting;
use System\Repository\SettingRepository;
use Zend\Authentication\AuthenticationService;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Mvc\Controller\AbstractActionController;

class SettingController extends AbstractActionController {

    private $container;
    private $adapter;
    private $repository;
    private $userId;

    public function __construct(ContainerInterface $container) {
        $this->container = $container;
        $this->adapter = $this->container->get(AdapterInterface::class);
        $this->repository = new SettingRepository($this->adapter);

        $auth = new AuthenticationService();
        $this->userId = $auth->getStorage()->read()['user_id'];
    }

    public function indexAction() {
        $setting = $this->repository->fetchById($this->userId);
        return new CustomViewModel(($setting == null) ? null : $setting->getArrayCopy());
    }

    public function updateAction() {
        $request = $this->getRequest();
        $postedData = $request->getPost();
        $userSetting = new Setting();
        $userSetting->exchangeArrayFromDB($postedData->getArrayCopy());
        $successFlag = $this->repository->edit($userSetting, $this->userId);
        return new CustomViewModel(['success' => $successFlag]);
    }

}
