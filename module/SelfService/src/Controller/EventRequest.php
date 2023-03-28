<?php

namespace SelfService\Controller;

use Application\Controller\HrisController;
use Application\Helper\Helper;
use Exception;
use Notification\Controller\HeadNotification;
use Notification\Model\NotificationEvents;
use SelfService\Form\EventRequestForm;
use SelfService\Model\EventRequest as EventRequestModel;
use SelfService\Repository\EventRequestRepository;
use Setup\Repository\EventsRepository;
use Zend\Authentication\Storage\StorageInterface;
use Zend\Db\Adapter\AdapterInterface;
use Zend\View\Model\JsonModel;

class EventRequest extends HrisController {

    public function __construct(AdapterInterface $adapter, StorageInterface $storage) {
        parent::__construct($adapter, $storage);
        $this->initializeRepository(EventRequestRepository::class);
        $this->initializeForm(EventRequestForm::class);
    }

    public function indexAction() {
        $request = $this->getRequest();
        if ($request->isPost()) {
            try {
                $list = $this->repository->getAllByEmployeeId($this->employeeId);
                // echo '<pre>';print_r($list);die;
                return new JsonModel(['success' => true, 'data' => $list, 'error' => '']);
            } catch (Exception $e) {
                return new JsonModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
            }
        }
        return $this->stickFlashMessagesTo([]);
    }

    public function prepareForm() {
        $eventList = $this->geteventList($this->employeeId);

        $eventId = $this->form->get('eventId');
        $eventId->setValueOptions($eventList['eventKVList']);

        $eventType = $this->form->get('eventType');
        $eventType->setValueOptions($this->eventTypes);
    }

    public function addAction() {
        $request = $this->getRequest();
        $model = new EventRequestModel();
        if ($request->isPost()) {
            $postData = $request->getPost();
            $this->form->setData($postData);
            if ($this->form->isValid()) {
                $model->exchangeArrayFromForm($this->form->getData());
                if ($postData['eventId'] == -1) {
                    $model->eventId = null;
                }
                $model->requestId = ((int) Helper::getMaxId($this->adapter, EventRequestModel::TABLE_NAME, EventRequestModel::REQUEST_ID)) + 1;
                $model->employeeId = $this->employeeId;
                $model->requestedDate = Helper::getcurrentExpressionDate();
                $model->status = 'RQ';
                // echo '<pre>';print_r($model);die;
                $this->repository->add($model);
                $this->flashmessenger()->addMessage("Event Request Successfully added!!!");
                try {
                    HeadNotification::pushNotification(NotificationEvents::EVENT_APPLIED, $model, $this->adapter, $this);
                } catch (Exception $e) {
                    $this->flashmessenger()->addMessage($e->getMessage());
                }
                return $this->redirect()->toRoute("eventRequest");
            }
			// print_r ($trainings['eventList']);die();
        }
        $this->prepareForm();
        $events = $this->geteventList($this->employeeId);
        return Helper::addFlashMessagesToArray($this, [
                    'form' => $this->form,
                    'eventList' => $events['eventList'],
                    'customRenderer' => Helper::renderCustomView()
        ]);
    }

    public function viewAction() {
        $id = (int) $this->params()->fromRoute('id');
        if ($id === 0) {
            return $this->redirect()->toRoute("eventRequest");
        }
        $detail = $this->repository->fetchById($id);
        return Helper::addFlashMessagesToArray($this, [
                    'form' => $this->form,
                    'detail' => $detail,
                    'recommender' => $detail['RECOMMENDED_BY_NAME'] == null ? $detail['RECOMMENDER_NAME'] : $detail['RECOMMENDED_BY_NAME'],
                    'approver' => $detail['APPROVED_BY_NAME'] == null ? $detail['APPROVER_NAME'] : $detail['APPROVED_BY_NAME'],
        ]);
    }

    public function deleteAction() {
        $id = (int) $this->params()->fromRoute("id");
        if (!$id) {
            return $this->redirect()->toRoute('eventRequest');
        }
        // echo '<pre>';print_r($id);die;
        $this->repository->delete($id);
        $this->flashmessenger()->addMessage("Event Request Successfully Cancelled.");
        return $this->redirect()->toRoute('eventRequest');
    }

    private $eventList = null;
    private $eventTypes = array(
        'CP' => 'Personal',
        'CC' => 'Company Contribution'
    );

    private function geteventList($employeeId) {
        if ($this->eventList === null) {
            $eventRepo = new EventsRepository($this->adapter);
            $eventResult = $eventRepo->selectAll($employeeId);
            $eventList = [];
            $allEvents = [];
            $eventList[''] = "---";
            foreach ($eventResult as $eventRow) {
                $eventList[$eventRow['EVENT_ID']] = $eventRow['EVENT_NAME'] . " (" . $eventRow['START_DATE'] . " to " . $eventRow['END_DATE'] . ")";
                $allEvents[$eventRow['EVENT_ID']] = $eventRow;
            }
            $this->eventList = ['eventKVList' => $eventList, 'eventList' => $allEvents];
        }
        return $this->eventList;
    }

}
