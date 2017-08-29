CREATE OR REPLACE PROCEDURE HRIS_RECALCULATE_LEAVE(
    P_EMPLOYEE_ID HRIS_ATTENDANCE.EMPLOYEE_ID%TYPE  :=NULL,
    P_LEAVE_ID HRIS_LEAVE_MASTER_SETUP.LEAVE_ID%TYPE:=NULL)
AS
  V_TOTAL_NO_OF_DAYS NUMBER;
BEGIN
  FOR leave_assign IN
  (SELECT           *
  FROM HRIS_EMPLOYEE_LEAVE_ASSIGN
  WHERE (EMPLOYEE_ID =
    CASE
      WHEN P_EMPLOYEE_ID IS NOT NULL
      THEN P_EMPLOYEE_ID
    END
  OR P_EMPLOYEE_ID IS NULL)
  AND (LEAVE_ID     =
    CASE
      WHEN P_LEAVE_ID IS NOT NULL
      THEN P_LEAVE_ID
    END
  OR P_LEAVE_ID IS NULL)
  )
  LOOP
    BEGIN
      SELECT SUM(R.NO_OF_DAYS) AS TOTAL_NO_OF_DAYS
      INTO V_TOTAL_NO_OF_DAYS
      FROM HRIS_EMPLOYEE_LEAVE_REQUEST R
      JOIN HRIS_LEAVE_MASTER_SETUP L
      ON (R.LEAVE_ID    = L.LEAVE_ID)
      WHERE R.STATUS    = 'AP'
      AND L.IS_MONTHLY  = 'N'
      AND R.EMPLOYEE_ID = leave_assign.EMPLOYEE_ID
      AND R.LEAVE_ID    = leave_assign.LEAVE_ID
      GROUP BY R.EMPLOYEE_ID ,
        R.LEAVE_ID;
    EXCEPTION
    WHEN no_data_found THEN
      V_TOTAL_NO_OF_DAYS:=0;
    END;
    UPDATE HRIS_EMPLOYEE_LEAVE_ASSIGN
    SET BALANCE       = TOTAL_DAYS - V_TOTAL_NO_OF_DAYS
    WHERE EMPLOYEE_ID = leave_assign.EMPLOYEE_ID
    AND LEAVE_ID      = leave_assign.LEAVE_ID;
  END LOOP;
END;