<?php

namespace Notification\Model;

class NotificationEvents {

    const LEAVE_APPLIED = 1;
    const LEAVE_RECOMMEND_ACCEPTED = 2;
    const LEAVE_RECOMMEND_REJECTED = 3;
    const LEAVE_APPROVE_ACCEPTED = 4;
    const LEAVE_APPROVE_REJECTED = 5;
    const LEAVE_CANCELLED = 6;
    
    const ATTENDANCE_APPLIED = 7;
    const ATTENDANCE_APPROVE_ACCEPTED = 8;
    const ATTENDANCE_APPROVE_REJECTED = 9;
    const ATTENDANCE_CANCELLED = 10;
    
    const TRAVEL_APPLIED = 11;
    const TRAVEL_RECOMMEND_ACCEPTED = 12;
    const TRAVEL_RECOMMEND_REJECTED = 13;
    const TRAVEL_APPROVE_ACCEPTED = 14;
    const TRAVEL_APPROVE_REJECTED = 15;
    const TRAVEL_CANCELLED = 16;
    
    const TRAINING_ASSIGNED = 17;
    const TRAINING_CANCELLED = 18;
    
    const LOAN_APPLIED = 19;
    const LOAN_RECOMMEND_ACCEPTED = 20;
    const LOAN_RECOMMEND_REJECTED = 21;
    const LOAN_APPROVE_ACCEPTED = 22;
    const LOAN_APPROVE_REJECTED = 23;
    const LOAN_CANCELLED = 24;
    
    const ADVANCE_APPLIED = 25;
    const ADVANCE_RECOMMEND_ACCEPTED = 26;
    const ADVANCE_RECOMMEND_REJECTED = 27;
    const ADVANCE_APPROVE_ACCEPTED = 28;
    const ADVANCE_APPROVE_REJECTED = 29;
    const ADVANCE_CANCELLED = 30;
    
    const HOLIDAY_ASSIGNED = 31;
    const HOLIDAY_CANCELLED = 32; 
    
    const LEAVE_ASSIGNED = 33;
    const SHIFT_ASSIGNED = 34;
    
    const SERVICE_EVENT_TYPE_UPDATE = 35;
    
    const SALARY_REVIEW = 36;  
    
    const WORKONDAYOFF_APPLIED = 37;
    const WORKONDAYOFF_RECOMMEND_ACCEPTED = 38;
    const WORKONDAYOFF_RECOMMEND_REJECTED = 39;
    const WORKONDAYOFF_APPROVE_ACCEPTED = 40;
    const WORKONDAYOFF_APPROVE_REJECTED = 41;
    const WORKONDAYOFF_CANCELLED = 42;
    
    const WORKONHOLIDAY_APPLIED = 43;
    const WORKONHOLIDAY_RECOMMEND_ACCEPTED = 44;
    const WORKONHOLIDAY_RECOMMEND_REJECTED = 45;
    const WORKONHOLIDAY_APPROVE_ACCEPTED = 46;
    const WORKONHOLIDAY_APPROVE_REJECTED = 47;
    const WORKONHOLIDAY_CANCELLED = 48;
}