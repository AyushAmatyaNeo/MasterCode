<?php

namespace Notification\Model;

class LeaveRequestNotificationModel extends NotificationModel
{

    public $leaveName;
    public $fromDate;
    public $toDate;
    public $noOfDays;
    public $leaveType;

    public $leaveRecommendStatus;
    public $leaveApprovedStatus;
}
