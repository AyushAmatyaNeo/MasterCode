create or replace PROCEDURE hris_travel_leave_reward ( p_travel_id NUMBER ) AS

v_travel_duration NUMBER;
v_substitute_leave_id hris_leave_master_setup.leave_id%TYPE;
v_balance hris_employee_leave_assign.balance%TYPE;
v_employee_id hris_employee_work_holiday.employee_id%TYPE;
p_employee_id hris_employees.employee_id%TYPE;
v_functional_type_id HRIS_EMPLOYEES.FUNCTIONAL_TYPE_ID%TYPE;
v_increment_day NUMBER := 0;
v_increment_day_new NUMBER :=0;
v_total_increment NUMBER :=0;

BEGIN

begin
select employee_id,ROUND((SUM(TO_DATE-FROM_DATE+1)/15),0) AS TOTAL_TRAVEL 
into
v_employee_id,v_increment_day
from hris_employee_travel_request where employee_id=(SELECT
employee_id
from 
hris_employee_travel_request
WHERE
travel_id = p_travel_id)
and STATUS='AP' AND TO_DATE < '01-JAN-20'
GROUP BY EMPLOYEE_ID;

exception when no_data_found then
null; 
end;

 -- TO GIVE 2 DAYS FOR 15 DAY TRAVEL FROM JAN 1 2020 START
begin
select employee_id, ROUND((SUM(TO_DATE-FROM_DATE+1)/15),0)*2 AS TOTAL_TRAVEL 
into
v_employee_id, v_increment_day_new
from hris_employee_travel_request where employee_id=(SELECT
employee_id
from 
hris_employee_travel_request
WHERE
travel_id = p_travel_id)
and STATUS='AP' AND TO_DATE >= '01-JAN-20'
GROUP BY EMPLOYEE_ID;

exception when no_data_found then
null; 
end;
 -- TO GIVE 2 DAYS FOR 15 DAY TRAVEL FROM JAN 1 2020 END

v_total_increment:=v_increment_day+v_increment_day_new;

Dbms_Output.Put_Line(v_employee_id);
Dbms_Output.Put_Line(v_increment_day);
Dbms_Output.Put_Line(v_increment_day_new);
Dbms_Output.Put_Line(v_total_increment);





SELECT
FUNCTIONAL_TYPE_ID
INTO
v_functional_type_id
FROM
hris_employees
WHERE 
employee_id=v_employee_id;

IF
(v_functional_type_id=1 )
THEN
dbms_output.put_line('LEAVE ADDITION OF TRAVEL PROJECT LEAVE');
SELECT
leave_id
INTO
v_substitute_leave_id
FROM
hris_leave_master_setup
WHERE
is_project = 'Y'
AND STATUS='E' AND 
ROWNUM = 1;

BEGIN
SELECT
balance
INTO
v_balance
FROM
hris_employee_leave_assign
WHERE
employee_id = v_employee_id
AND
leave_id = v_substitute_leave_id;

EXCEPTION
WHEN no_data_found THEN
INSERT INTO hris_employee_leave_assign (
employee_id,
leave_id,
previous_year_bal,
total_days,
balance,
created_dt,
created_by
) VALUES (
v_employee_id,
v_substitute_leave_id,
0,
0,
0,
trunc(SYSDATE),
v_employee_id
);

END;

UPDATE hris_employee_leave_assign SET total_days=v_total_increment
where employee_id=v_employee_id and leave_id=v_substitute_leave_id;



HRIS_RECALCULATE_LEAVE(v_employee_id,v_substitute_leave_id);



END IF;

END;