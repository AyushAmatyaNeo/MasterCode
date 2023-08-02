<?php

namespace System\Controller;

use Application\Controller\HrisController;
use Application\Helper\Helper;
use Application\Model\Preference;
use System\Form\SystemSettingForm;
use System\Repository\SystemSettingRepository;
use Zend\Authentication\Storage\StorageInterface;
use Zend\Db\Adapter\AdapterInterface;

class SystemSetting extends HrisController {

    private $synergyRepo;

    public function __construct(AdapterInterface $adapter, StorageInterface $storage) {
        parent::__construct($adapter, $storage);
        $this->initializeRepository(SystemSettingRepository::class);
        $this->initializeForm(SystemSettingForm::class);
    }

    public function indexAction() {
        $request = $this->getRequest();
        $preference = new Preference();
        if ($request->isPost()) {
            $postedData = $request->getPost();
            $this->form->setData($postedData);
            if ($this->form->isValid()) {
                $preference->exchangeArrayFromForm($this->form->getData());
                $this->repository->edit($preference);
                $this->flashmessenger()->addMessage("System Setting successfully Edited.");
                return $this->redirect()->toRoute("system-setting");
            }
        } else {
            $systemSetting = $this->repository->fetch();
            $preference->exchangeArrayFromDB($systemSetting);
            $this->form->bind($preference);
        }
        return $this->stickFlashMessagesTo([
                    'form' => $this->form,
                    'customRenderer' => Helper::renderCustomView(),
        ]);
    }

}
