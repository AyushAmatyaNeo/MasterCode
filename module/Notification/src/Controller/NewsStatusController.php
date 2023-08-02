<?php

namespace Notification\Controller;

use Application\Helper\Helper;
use Exception;
use Notification\Repository\NewsFileRepository;
use Notification\Repository\NewsRepository;
use Zend\Authentication\AuthenticationService;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\JsonModel;

class NewsStatusController extends AbstractActionController {

    private $adapter;
    private $repository;
    private $employeeId;

    public function __construct(AdapterInterface $adapter) {
        $this->adapter = $adapter;
        $this->repository = new NewsRepository($adapter);

        $auth = new AuthenticationService();
        $this->employeeId = $auth->getStorage()->read()['employee_id'];
    }

    public function indexAction() {
        $result = $this->repository->fetchAll();
        $list = Helper::extractDbData($result);
        return Helper::addFlashMessagesToArray($this, [
                    'news' => $list
        ]);
    }

    public function viewAction() {
        $id = $this->params()->fromRoute('id');

        $newsDetails = $this->repository->fetchById($id);
        $newsfileRepo = new NewsFileRepository($this->adapter);
        $newsFiles = $newsfileRepo->fetchAllNewsFiles($id);

        return Helper::addFlashMessagesToArray($this, [
                    'newsDetails' => $newsDetails,
                    'newsFileDetails' => $newsFiles
        ]);
    }

    public function allNewsTypeListAction() {
        $id = $this->params()->fromRoute('id');
        $request = $this->getRequest();
        if ($request->isPost()) {
            try {
                $result = $this->repository->allNewsTypeWise($id, $this->employeeId);
                $list = Helper::extractDbData($result);
                return new JsonModel(['success' => true, 'data' => $list, 'error' => '']);
            } catch (Exception $e) {
                return new JsonModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
            }
        }

        return Helper::addFlashMessagesToArray($this, [
        ]);
    }

}
