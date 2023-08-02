<?php

namespace Training\Controller;

use Application\Custom\CustomViewModel;
use Application\Helper\Helper;
use Exception;
use Training\Repository\TrainingAttendanceRepository;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\JsonModel;

class TrainingAttendanceController extends AbstractActionController {

    private $adapter;
    private $repository;

    public function __construct(AdapterInterface $adapter) {
        $this->adapter = $adapter;
        $this->repository = new TrainingAttendanceRepository($adapter);
    }

    public function indexAction() {
        $request = $this->getRequest();
        if ($request->isPost()) {
            try {
                $result = $this->repository->fetchAll();
                $companyList = Helper::extractDbData($result);
                return new JsonModel(['success' => true, 'data' => $companyList, 'error' => '']);
            } catch (Exception $e) {
                return new JsonModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
            }
        }
        return Helper::addFlashMessagesToArray($this, []);
    }

    public function attendanceAction() {
        $id = (int) $this->params()->fromRoute("id");
        if ($id === 0) {
            return $this->redirect()->toRoute('trainingAtt');
        }

        $assignedList = $this->repository->fetchTrainingAssignedEmp($id);
        $dates = $this->repository->fetchTrainingDates($id);
        $attendance = $this->repository->fetchAttendance($id);

        $temp = [];
        foreach ($attendance as $att) {
            $temp[$att['TRAINING_DT']][$att['EMPLOYEE_ID']] = $att['ATTENDANCE_STATUS'] === 'P';
        }


        return Helper::addFlashMessagesToArray($this, ['list' => $assignedList, 'trainingId' => $id, 'dates' => $dates, 'attendance' => $temp]);
    }

    public function updateTrainingAtdAction() {
        try {
            $request = $this->getRequest();
            if ($request->isPost()) {
                $postedData = $request->getPost();

                $attendanceData = $postedData['data'];
                $trainingId = $postedData['trainingId'];
                if (!isset($attendanceData)) {
                    throw new Exception("parameter data is required");
                }

                $response = $this->repository->updateTrainingAtd($attendanceData, $trainingId);
                return new CustomViewModel(['success' => true, 'data' => $response, 'error' => '']);
            } else {
                throw new Exception("The request should be of type post");
            }
        } catch (Exception $e) {
            return new CustomViewModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
        }
    }

}
