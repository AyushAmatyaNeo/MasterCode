CREATE OR REPLACE PROCEDURE HRIS_TRAINING_REWARD (
    P_EMPLOYEE_ID     NUMBER,
    P_TRAINING_ID     NUMBER
)
    AS
V_WOH_FLAG CHAR(1 BYTE);
BEGIN

 -- check if employeewise reward type set in  start
  BEGIN
  SELECT WOH_FLAG INTO V_WOH_FLAG FROM HRIS_EMPLOYEES WHERE EMPLOYEE_ID=P_EMPLOYEE_ID;
   EXCEPTION
  WHEN no_data_found THEN
    NULL;
  END;
  
  -- check if employeewise reward type set in  end

IF(V_WOH_FLAG IS NULL OR (V_WOH_FLAG!='O' AND V_WOH_FLAG!='L'))
  THEN

    BEGIN
        SELECT
            p.woh_flag
        INTO
            V_WOH_FLAG
        FROM
            hris_employees e
            JOIN hris_positions p ON (
                e.position_id = p.position_id
            )
        WHERE
            e.employee_id = P_EMPLOYEE_ID;

    EXCEPTION
        WHEN no_data_found THEN
            hris_raise_err(P_EMPLOYEE_ID,'Work on dayoff reward could not be given.','Employee position is not set');
    END;
  END IF;
    
     dbms_output.put_line(V_WOH_FLAG);
    IF(V_WOH_FLAG='L')
    THEN
    HRIS_TRAINING_LEAVE_REWARD(P_EMPLOYEE_ID,P_TRAINING_ID);
    END IF;
    

   
    
    
END;