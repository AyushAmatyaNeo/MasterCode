<?php

namespace SelfService\Controller;

use Application\Custom\CustomViewModel;
use Application\Helper\Helper;
use Exception;
use Training\Repository\EventAssignRepository;
use Zend\Authentication\Storage\StorageInterface;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Mvc\Controller\AbstractActionController;

class EventList extends AbstractActionController {

    private $form;
    private $adapter;
    private $eventAssignRepo;
    private $employeeId;

    public function __construct(AdapterInterface $adapter, StorageInterface $storage) {
        $this->adapter = $adapter;
        $this->eventAssignRepo = new EventAssignRepository($this->adapter);
        $this->storageData = $storage->read();
        $this->employeeId = $this->storageData['employee_id'];
    }

    public function indexAction() {
        $request = $this->getRequest();
        if ($request->isPost()) {
            try {
                $result = $this->eventAssignRepo->getAllEventList($this->employeeId);
                $list = [];
                $getValue = function($eventTypeId) {
                    if ($eventTypeId == 'CC') {
                        return 'Company Contribution';
                    } else if ($eventTypeId == 'CP') {
                        return 'Company Personal';
                    }
                };
                foreach ($result as $row) {
                    $row['EVENT_TYPE'] = $getValue($row['EVENT_TYPE']);
                    array_push($list, $row);
                }
                return new CustomViewModel(['success' => true, 'data' => $list, 'error' => '']);
            } catch (Exception $e) {
                return new CustomViewModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
            }
        }
        return Helper::addFlashMessagesToArray($this, []);
    }

    public function viewAction() {
        $employeeId = (int) $this->params()->fromRoute("employeeId");
        $eventId = (int) $this->params()->fromRoute("eventId");

        if (!$employeeId && !$eventId) {
            return $this->redirect()->toRoute('eventList');
        }

        $detail = $this->eventAssignRepo->getDetailByEmployeeID($employeeId, $eventId);
        return Helper::addFlashMessagesToArray($this, ['detail' => $detail]);
    }

}
