CREATE OR REPLACE PROCEDURE HRIS_ATTD_BETWEEN_DATES(
    V_CONTRACT_ID   NUMBER,
    V_MONTH_CODE_ID NUMBER )
AS
  V_WORKING_CYCLE CHAR(1 BYTE);
  V_FROM_DATE     DATE;
  V_TO_DATE       DATE;
  V_DATE_DIFF     NUMBER;
  V_TEMP_DAY      NUMBER;
  V_TEMP_COUNT    NUMBER;
  V_TEMP_EMP_ID   NUMBER;
BEGIN
  DELETE
  FROM HRIS_CUST_CONTRACT_ATTENDANCE
  WHERE CONTRACT_ID=V_CONTRACT_ID
  AND MONTH_CODE_ID=V_MONTH_CODE_ID;
  SELECT WORKING_CYCLE
  INTO V_WORKING_CYCLE
  FROM HRIS_CUSTOMER_CONTRACT
  WHERE CONTRACT_ID=V_CONTRACT_ID;
  SELECT FROM_DATE,
    TO_DATE
  INTO V_FROM_DATE,
    V_TO_DATE
  FROM HRIS_MONTH_CODE
  WHERE MONTH_ID     =V_MONTH_CODE_ID;
  IF V_WORKING_CYCLE = 'W' THEN
    BEGIN
      SELECT V_TO_DATE - V_FROM_DATE INTO V_DATE_DIFF FROM dual;
      BEGIN
        FOR i IN 0..V_DATE_DIFF
        LOOP
          SELECT TO_CHAR(V_FROM_DATE+i,'D') INTO V_TEMP_DAY FROM DUAL;
          DBMS_OUTPUT.PUT_LINE(V_FROM_DATE+i);
          DBMS_OUTPUT.PUT_LINE('DAY NO IS '||V_TEMP_DAY);
          SELECT COUNT(*)
          INTO V_TEMP_COUNT
          FROM HRIS_CUST_CONTRACT_WEEKDAYS
          WHERE CONTRACT_ID=V_CONTRACT_ID
          AND WEEKDAY      =V_TEMP_DAY;
          DBMS_OUTPUT.PUT_LINE(V_TEMP_COUNT);
          IF V_TEMP_COUNT > 0 THEN
            BEGIN
              FOR V_EMPLOYEES IN
              (SELECT          *
              FROM HRIS_CUST_CONTRACT_EMP
              WHERE CONTRACT_ID=V_CONTRACT_ID
              AND MONTH_CODE_ID=V_MONTH_CODE_ID
              )
              LOOP
                DBMS_OUTPUT.PUT_LINE(V_EMPLOYEES.EMPLOYEE_ID);
                INSERT
                INTO HRIS_CUST_CONTRACT_ATTENDANCE
                  (
                    CONTRACT_ID,
                    ATTENDANCE_DT,
                    EMPLOYEE_ID,
                    MONTH_CODE_ID,
                    IN_TIME,
                    OUT_TIME
                  )
                  VALUES
                  (
                    V_CONTRACT_ID,
                    V_FROM_DATE+i,
                    V_EMPLOYEES.EMPLOYEE_ID,
                    V_MONTH_CODE_ID,
                    V_EMPLOYEES.START_TIME,
                    V_EMPLOYEES.END_TIME
                  );
              END LOOP;
            END;
          END IF;
        END LOOP;
      END;
    END;
  END IF;
  IF V_WORKING_CYCLE = 'R' THEN
    BEGIN
      BEGIN
        FOR V_R_DATES IN
        (SELECT        *
          FROM HRIS_CUST_CONTRACT_DATES
          WHERE CONTRACT_ID=V_CONTRACT_ID
          AND MANUAL_DATE BETWEEN V_FROM_DATE AND V_TO_DATE
        )
        LOOP
          DBMS_OUTPUT.PUT_LINE
          (
            V_R_DATES.CONTRACT_ID
          )
          ;
          DBMS_OUTPUT.PUT_LINE(V_R_DATES.MANUAL_DATE);
          BEGIN
            FOR V_EMPLOYEES IN
            (SELECT          *
              FROM HRIS_CUST_CONTRACT_EMP
              WHERE CONTRACT_ID=V_CONTRACT_ID
              AND MONTH_CODE_ID=V_MONTH_CODE_ID
            )
            LOOP
              INSERT
              INTO HRIS_CUST_CONTRACT_ATTENDANCE
                (
                  CONTRACT_ID,
                  ATTENDANCE_DT,
                  EMPLOYEE_ID,
                  MONTH_CODE_ID,
                  IN_TIME,
                  OUT_TIME
                )
                VALUES
                (
                  V_CONTRACT_ID,
                  V_R_DATES.MANUAL_DATE,
                  V_EMPLOYEES.EMPLOYEE_ID,
                  V_MONTH_CODE_ID,
                  V_EMPLOYEES.START_TIME,
                  V_EMPLOYEES.END_TIME
                );
            END LOOP;
          END;
        END LOOP;
      END;
      --
    END;
  END IF;
END;/
            create or replace PROCEDURE HRIS_ATTD_IN_OUT(
    P_EMPLOYEE_ID          IN HRIS_ATTENDANCE_DETAIL.EMPLOYEE_ID%TYPE,
    P_FROM_ATTENDANCE_TIME IN TIMESTAMP,
    P_TO_ATTENDANCE_TIME   IN TIMESTAMP,
    P_IN_TIME OUT HRIS_ATTENDANCE_DETAIL.IN_TIME%TYPE,
    P_OUT_TIME OUT HRIS_ATTENDANCE_DETAIL.OUT_TIME%TYPE,
    P_IS_TWO_DAY_SHIFT OUT CHAR)
AS
  V_IN_TIME          TIMESTAMP;
  V_OUT_TIME         TIMESTAMP;
  V_IS_TWO_DAY_SHIFT CHAR:='N';
  V_YESTERDAY_OUT_TIME TIMESTAMP;
BEGIN


begin
select 
case when
out_time is null then in_time else out_time + interval '3' minute
end 
INTO V_YESTERDAY_OUT_TIME
from Hris_Attendance_Detail
where Attendance_Dt=trunc(P_FROM_ATTENDANCE_TIME)-1 and employee_id=P_EMPLOYEE_ID;
EXCEPTION
WHEN no_data_found THEN
NULL;
END;

if(V_YESTERDAY_OUT_TIME is null)
then
V_YESTERDAY_OUT_TIME:=trunc(P_FROM_ATTENDANCE_TIME)-1;
end if;

  SELECT MIN(A.ATTENDANCE_TIME) AS IN_TIME,
    MAX(A.ATTENDANCE_TIME)      AS OUT_TIME
  INTO P_IN_TIME,
    P_OUT_TIME
  FROM HRIS_ATTENDANCE A
  WHERE (A.ATTENDANCE_TIME >= P_FROM_ATTENDANCE_TIME
  AND A.ATTENDANCE_TIME    <= P_TO_ATTENDANCE_TIME)
  AND A.EMPLOYEE_ID         = P_EMPLOYEE_ID 
  and A.ATTENDANCE_TIME>V_YESTERDAY_OUT_TIME;
  --
  SELECT MIN(TO_DATE(TO_CHAR(A.ATTENDANCE_DT,'DD-MON-YYYY')
    ||' '
    ||TO_CHAR(A.ATTENDANCE_TIME,'HH:MI AM'),'DD-MON-YYYY HH:MI AM')) AS IN_TIME
  INTO V_IN_TIME
  FROM HRIS_ATTENDANCE A
  LEFT JOIN HRIS_ATTD_DEVICE_MASTER ADM
  ON (A.IP_ADDRESS          =ADM.DEVICE_IP)
  WHERE (A.ATTENDANCE_TIME >= P_FROM_ATTENDANCE_TIME
  AND A.ATTENDANCE_TIME    <= P_TO_ATTENDANCE_TIME)
  AND A.EMPLOYEE_ID         = P_EMPLOYEE_ID
  AND (ADM.PURPOSE           ='IN') 
  and A.ATTENDANCE_TIME>V_YESTERDAY_OUT_TIME;
  --
  SELECT MAX(TO_DATE(TO_CHAR(A.ATTENDANCE_DT,'DD-MON-YYYY')
    ||' '
    || TO_CHAR(A.ATTENDANCE_TIME,'HH:MI AM'),'DD-MON-YYYY HH:MI AM')) AS IN_TIME
  INTO V_OUT_TIME
  FROM HRIS_ATTENDANCE A
  LEFT JOIN HRIS_ATTD_DEVICE_MASTER ADM
  ON (A.IP_ADDRESS          =ADM.DEVICE_IP)
  WHERE (A.ATTENDANCE_TIME >= P_FROM_ATTENDANCE_TIME
  AND A.ATTENDANCE_TIME    <= P_TO_ATTENDANCE_TIME)
  AND A.EMPLOYEE_ID         = P_EMPLOYEE_ID
  AND (ADM.PURPOSE           ='OUT') 
  and A.ATTENDANCE_TIME>V_YESTERDAY_OUT_TIME;
  --
  IF V_IN_TIME        IS NOT NULL THEN
    P_IN_TIME         :=V_IN_TIME;
    V_IS_TWO_DAY_SHIFT:='Y';
  END IF;
  --
  IF V_OUT_TIME       IS NOT NULL THEN
    P_OUT_TIME        :=V_OUT_TIME;
    V_IS_TWO_DAY_SHIFT:='Y';
  END IF;
  P_IS_TWO_DAY_SHIFT:=V_IS_TWO_DAY_SHIFT;
END;/
            CREATE OR REPLACE PROCEDURE HRIS_ATTENDANCE_AFTER_INSERT(
    P_EMPLOYEE_ID HRIS_ATTENDANCE.EMPLOYEE_ID%TYPE,
    P_ATTENDANCE_DT HRIS_ATTENDANCE.ATTENDANCE_DT%TYPE,
    P_ATTENDANCE_TIME HRIS_ATTENDANCE.ATTENDANCE_TIME%TYPE,
    P_REMARKS HRIS_ATTENDANCE.REMARKS%TYPE,
    P_PURPOSE HRIS_ATTD_DEVICE_MASTER.PURPOSE%TYPE:=NULL)
AS
  V_IN_TIME HRIS_ATTENDANCE_DETAIL.IN_TIME%TYPE;
  V_SHIFT_ID HRIS_SHIFTS.SHIFT_ID%TYPE;
  V_OVERALL_STATUS HRIS_ATTENDANCE_DETAIL.OVERALL_STATUS%TYPE;
  V_LATE_STATUS HRIS_ATTENDANCE_DETAIL.LATE_STATUS%TYPE:='N';
  V_HALFDAY_FLAG HRIS_ATTENDANCE_DETAIL.HALFDAY_FLAG%TYPE;
  V_HALFDAY_PERIOD HRIS_ATTENDANCE_DETAIL.HALFDAY_PERIOD%TYPE;
  V_GRACE_PERIOD HRIS_ATTENDANCE_DETAIL.GRACE_PERIOD%TYPE;
  V_LATE_IN HRIS_SHIFTS.LATE_IN%TYPE;
  V_EARLY_OUT HRIS_SHIFTS.EARLY_OUT%TYPE;
  V_LATE_START_TIME   TIMESTAMP;
  V_EARLY_END_TIME    TIMESTAMP;
  V_TOTAL_WORKING_MIN NUMBER;
  V_LATE_COUNT        NUMBER :=0;
  V_TOTAL_HOUR        NUMBER :=0;
  V_TWO_DAY_SHIFT HRIS_ATTENDANCE_DETAIL.TWO_DAY_SHIFT%TYPE;
  V_IGNORE_TIME HRIS_SHIFTS.IGNORE_TIME%TYPE;
  V_HALF_INTERVAL   DATE;
  V_ATTENDANCE_DT   DATE;
  V_ATTENDANCE_TIME TIMESTAMP;
  V_YESTERDAY_SHIFT HRIS_SHIFTS.SHIFT_ID%TYPE;
  V_YESTERDAY_TWO_DAY_SHIFT HRIS_ATTENDANCE_DETAIL.TWO_DAY_SHIFT%TYPE;
BEGIN
-- TO CHECK IF YESTERDAY IS NIGHT SHIFT START
SELECT 
S.SHIFT_ID , 
S.TWO_DAY_SHIFT INTO V_YESTERDAY_SHIFT, V_YESTERDAY_TWO_DAY_SHIFT
FROM 
HRIS_ATTENDANCE_DETAIL AD
left join HRIS_SHIFTS S on (S.SHIFT_ID=AD.SHIFT_ID)
where employee_id=P_EMPLOYEE_ID and ATTENDANCE_DT=P_ATTENDANCE_DT-1;
-- TO CHECK IF YESTERDAY IS NIGHT SHIFT END


  V_ATTENDANCE_DT  :=TRUNC(P_ATTENDANCE_DT);
  V_ATTENDANCE_TIME:=TO_DATE(TO_CHAR(V_ATTENDANCE_DT,'DD-MON-YYYY') ||' ' ||TO_CHAR(P_ATTENDANCE_TIME,'HH:MI AM'),'DD-MON-YYYY HH:MI AM');
  --
  BEGIN
    SELECT SHIFT_ID,
      OVERALL_STATUS,
      LATE_STATUS,
      HALFDAY_FLAG,
      HALFDAY_PERIOD,
      GRACE_PERIOD,
      IN_TIME,
      HALFDAY_PERIOD,
      TWO_DAY_SHIFT,
      IGNORE_TIME
    INTO V_SHIFT_ID,
      V_OVERALL_STATUS,
      V_LATE_STATUS,
      V_HALFDAY_FLAG,
      V_HALFDAY_PERIOD,
      V_GRACE_PERIOD,
      V_IN_TIME,
      V_HALFDAY_PERIOD,
      V_TWO_DAY_SHIFT,
      V_IGNORE_TIME
    FROM HRIS_ATTENDANCE_DETAIL
    WHERE ATTENDANCE_DT       = TRUNC(V_ATTENDANCE_DT)
    AND EMPLOYEE_ID           = P_EMPLOYEE_ID;
    IF V_IGNORE_TIME          ='Y' THEN
      IF(V_OVERALL_STATUS     ='DO') THEN
        V_OVERALL_STATUS     :='WD';
      ELSIF (V_OVERALL_STATUS ='HD') THEN
        V_OVERALL_STATUS     :='WH';
      ELSIF (V_OVERALL_STATUS ='LV') THEN
        NULL;
      ELSIF (V_OVERALL_STATUS ='TV') THEN
        NULL;
      ELSIF (V_OVERALL_STATUS ='TN') THEN
        NULL;
      ELSE
        V_OVERALL_STATUS :='PR';
      END IF;
      IF P_PURPOSE='IN' THEN
        UPDATE HRIS_ATTENDANCE_DETAIL
        SET IN_TIME         = TO_DATE ( TO_CHAR (V_ATTENDANCE_TIME, 'DD-MON-YY HH:MI AM'), 'DD-MON-YY HH:MI AM'),
          OVERALL_STATUS    = V_OVERALL_STATUS,
          IN_REMARKS        = P_REMARKS
        WHERE ATTENDANCE_DT = V_ATTENDANCE_DT
        AND EMPLOYEE_ID     = P_EMPLOYEE_ID
        AND IN_TIME        IS NULL;
        RETURN;
      END IF;
      IF P_PURPOSE      ='OUT' THEN
        V_ATTENDANCE_DT:=V_ATTENDANCE_DT-1;
        UPDATE HRIS_ATTENDANCE_DETAIL
        SET OUT_TIME        = V_ATTENDANCE_TIME,
          OVERALL_STATUS    = V_OVERALL_STATUS,
          OUT_REMARKS       = P_REMARKS
        WHERE ATTENDANCE_DT = V_ATTENDANCE_DT
        AND EMPLOYEE_ID     = P_EMPLOYEE_ID;
        RETURN;
      END IF;
      IF (V_IN_TIME IS NULL) THEN
        UPDATE HRIS_ATTENDANCE_DETAIL
        SET IN_TIME         = TO_DATE ( TO_CHAR (V_ATTENDANCE_TIME, 'DD-MON-YY HH:MI AM'), 'DD-MON-YY HH:MI AM'),
          OVERALL_STATUS    = V_OVERALL_STATUS,
          LATE_STATUS       = V_LATE_STATUS,
          IN_REMARKS        = P_REMARKS
        WHERE ATTENDANCE_DT = V_ATTENDANCE_DT
        AND EMPLOYEE_ID     = P_EMPLOYEE_ID;
        RETURN;
      END IF;
      SELECT SUM(ABS(EXTRACT( HOUR FROM DIFF ))*60 + ABS(EXTRACT( MINUTE FROM DIFF )))
      INTO V_TOTAL_HOUR
      FROM
        (SELECT (V_ATTENDANCE_TIME-V_IN_TIME) AS DIFF FROM DUAL
        ) ;
      UPDATE HRIS_ATTENDANCE_DETAIL
      SET OUT_TIME        = V_ATTENDANCE_TIME,
        LATE_STATUS       =V_LATE_STATUS,
        OUT_REMARKS       = P_REMARKS,
        TOTAL_HOUR        =V_TOTAL_HOUR,
        OT_MINUTES        =(V_TOTAL_HOUR- V_TOTAL_WORKING_MIN)
      WHERE ATTENDANCE_DT = V_ATTENDANCE_DT
      AND EMPLOYEE_ID     = P_EMPLOYEE_ID;
      RETURN;
    END IF;
    
    IF  V_YESTERDAY_TWO_DAY_SHIFT='E' THEN
    V_SHIFT_ID:=V_YESTERDAY_SHIFT;
      --
      SELECT LATE_IN,
        EARLY_OUT,
        LATE_START_TIME,
        EARLY_END_TIME,
        EARLY_END_TIME + (LATE_START_TIME -EARLY_END_TIME)/2,
        TOTAL_WORKING_HR
      INTO V_LATE_IN,
        V_EARLY_OUT,
        V_LATE_START_TIME,
        V_EARLY_END_TIME,
        V_HALF_INTERVAL,
        V_TOTAL_WORKING_MIN
      FROM
        (SELECT S.LATE_IN,
          S.EARLY_OUT,
          TO_DATE(TO_CHAR(V_ATTENDANCE_DT,'DD-MON-YYYY')
          ||' '
          ||TO_CHAR(S.START_TIME+((1/1440)*NVL(S.LATE_IN,0)),'HH:MI AM'),'DD-MON-YYYY HH:MI AM') AS LATE_START_TIME,
          TO_DATE(TO_CHAR(V_ATTENDANCE_DT,'DD-MON-YYYY')
          ||' '
          || TO_CHAR(S.END_TIME -((1/1440)*NVL(S.EARLY_OUT,0)),'HH:MI AM'),'DD-MON-YYYY HH:MI AM') AS EARLY_END_TIME,
          S.TOTAL_WORKING_HR
        FROM HRIS_SHIFTS S
        WHERE S.SHIFT_ID=V_SHIFT_ID
        );
      --
      IF V_ATTENDANCE_TIME < V_HALF_INTERVAL THEN
        V_LATE_START_TIME :=V_LATE_START_TIME-1;
        V_ATTENDANCE_DT   :=V_ATTENDANCE_DT  -1;
        SELECT OVERALL_STATUS,
          LATE_STATUS,
          HALFDAY_FLAG,
          HALFDAY_PERIOD,
          GRACE_PERIOD,
          IN_TIME,
          HALFDAY_PERIOD,
          GRACE_PERIOD
        INTO V_OVERALL_STATUS,
          V_LATE_STATUS,
          V_HALFDAY_FLAG,
          V_HALFDAY_PERIOD,
          V_GRACE_PERIOD,
          V_IN_TIME,
          V_HALFDAY_PERIOD,
          V_GRACE_PERIOD
        FROM HRIS_ATTENDANCE_DETAIL
        WHERE ATTENDANCE_DT = TRUNC(V_ATTENDANCE_DT)
        AND EMPLOYEE_ID     = P_EMPLOYEE_ID;
      ELSE
        V_EARLY_END_TIME:=V_EARLY_END_TIME+1;
      END IF;
      --
    END IF;
  EXCEPTION
  WHEN NO_DATA_FOUND THEN
    DBMS_OUTPUT.PUT_LINE ('Attendance Job for '||V_ATTENDANCE_DT||' not excecuted');
    RETURN;
  END;
  --
  BEGIN
    IF V_HALFDAY_PERIOD IS NOT NULL THEN
      SELECT LATE_IN,
        EARLY_OUT,
        TO_DATE(TO_CHAR(V_ATTENDANCE_DT,'DD-MON-YYYY')
        ||' '
        ||TO_CHAR(LATE_START_TIME,'HH:MI AM'),'DD-MON-YYYY HH:MI AM'),
        TO_DATE(TO_CHAR(V_ATTENDANCE_DT,'DD-MON-YYYY')
        ||' '
        ||TO_CHAR(EARLY_END_TIME,'HH:MI AM'),'DD-MON-YYYY HH:MI AM'),
        TOTAL_WORKING_HR
      INTO V_LATE_IN,
        V_EARLY_OUT,
        V_LATE_START_TIME,
        V_EARLY_END_TIME,
        V_TOTAL_WORKING_MIN
      FROM
        (SELECT S.LATE_IN,
          S.EARLY_OUT,
          (
          CASE
            WHEN V_HALFDAY_PERIOD ='F'
            THEN S.HALF_DAY_IN_TIME
            ELSE S.START_TIME
          END ) +((1/1440)*NVL(S.LATE_IN,0)) AS LATE_START_TIME,
          (
          CASE
            WHEN V_HALFDAY_PERIOD ='F'
            THEN S.END_TIME
            ELSE S.HALF_DAY_OUT_TIME
          END ) -((1/1440)*NVL(S.EARLY_OUT,0)) AS EARLY_END_TIME,
          S.TOTAL_WORKING_HR
        FROM HRIS_SHIFTS S
        WHERE S.SHIFT_ID =V_SHIFT_ID
        );
    ELSIF V_GRACE_PERIOD IS NOT NULL THEN
      SELECT LATE_IN,
        EARLY_OUT,
        TO_DATE(TO_CHAR(V_ATTENDANCE_DT,'DD-MON-YYYY')
        ||' '
        ||TO_CHAR(LATE_START_TIME,'HH:MI AM'),'DD-MON-YYYY HH:MI AM'),
        TO_DATE(TO_CHAR(V_ATTENDANCE_DT,'DD-MON-YYYY')
        ||' '
        ||TO_CHAR(EARLY_END_TIME,'HH:MI AM'),'DD-MON-YYYY HH:MI AM'),
        TOTAL_WORKING_HR
      INTO V_LATE_IN,
        V_EARLY_OUT,
        V_LATE_START_TIME,
        V_EARLY_END_TIME,
        V_TOTAL_WORKING_MIN
      FROM
        (SELECT S.LATE_IN,
          S.EARLY_OUT,
          (
          CASE
            WHEN V_GRACE_PERIOD ='E'
            THEN S.GRACE_START_TIME
            ELSE S.START_TIME
          END) +((1/1440)*NVL(S.LATE_IN,0)) AS LATE_START_TIME,
          (
          CASE
            WHEN V_GRACE_PERIOD ='E'
            THEN S.END_TIME
            ELSE S.GRACE_END_TIME
          END) -((1/1440)*NVL(S.EARLY_OUT,0)) AS EARLY_END_TIME,
          S.TOTAL_WORKING_HR
        FROM HRIS_SHIFTS S
        WHERE S.SHIFT_ID=V_SHIFT_ID
        );
    END IF;
  EXCEPTION
  WHEN NO_DATA_FOUND THEN
    RAISE_APPLICATION_ERROR(-20344, 'SHIFT WITH SHIFT_ID => '|| V_SHIFT_ID ||' NOT FOUND.');
  END;
  --   CHECK FOR ADJUSTED SHIFT
  BEGIN
    SELECT TO_DATE(TO_CHAR(V_ATTENDANCE_DT,'DD-MON-YYYY')
      ||' '
      ||TO_CHAR(LATE_START_TIME,'HH:MI AM'),'DD-MON-YYYY HH:MI AM'),
      TO_DATE(TO_CHAR(V_ATTENDANCE_DT,'DD-MON-YYYY')
      ||' '
      ||TO_CHAR(EARLY_END_TIME,'HH:MI AM'),'DD-MON-YYYY HH:MI AM')
    INTO V_LATE_START_TIME,
      V_EARLY_END_TIME
    FROM
      (SELECT SA.START_TIME + ((1/1440)*NVL(V_LATE_IN,0))  AS LATE_START_TIME,
        SA.END_TIME         -((1/1440)*NVL(V_EARLY_OUT,0)) AS EARLY_END_TIME
      FROM HRIS_SHIFT_ADJUSTMENT SA
      JOIN HRIS_EMPLOYEE_SHIFT_ADJUSTMENT ESA
      ON (SA.ADJUSTMENT_ID=ESA.ADJUSTMENT_ID)
      WHERE (TRUNC(V_ATTENDANCE_DT) BETWEEN TRUNC(SA.ADJUSTMENT_START_DATE) AND TRUNC(SA.ADJUSTMENT_END_DATE) )
      AND ESA.EMPLOYEE_ID =P_EMPLOYEE_ID
      );
  EXCEPTION
  WHEN NO_DATA_FOUND THEN
    DBMS_OUTPUT.PUT_LINE('NO ADJUSTMENT FOUND FOR EMPLOYEE =>'|| P_EMPLOYEE_ID || 'ON THE DATE'||V_ATTENDANCE_DT);
  END;
  --      END FOR CHECK FOR ADJUSTED_SHIFT
  IF (V_IN_TIME            IS NULL) THEN
    IF(V_OVERALL_STATUS     ='DO') THEN
      V_OVERALL_STATUS     :='WD';
    ELSIF (V_OVERALL_STATUS ='HD') THEN
      V_OVERALL_STATUS     :='WH';
    ELSIF (V_OVERALL_STATUS ='LV') THEN
      IF(V_HALFDAY_FLAG    !='Y' AND V_HALFDAY_PERIOD IS NOT NULL) OR V_GRACE_PERIOD IS NOT NULL THEN
        V_OVERALL_STATUS   :='LP';
      END IF;
    ELSIF (V_OVERALL_STATUS ='TV') THEN
      NULL;
    ELSIF (V_OVERALL_STATUS ='TN') THEN
      NULL;
    ELSE
      V_OVERALL_STATUS :='PR';
    END IF;
    IF(V_ATTENDANCE_DT < TRUNC(SYSDATE)) THEN
      V_LATE_STATUS   :='X';
    END IF;
    IF V_OVERALL_STATUS  = 'PR' AND V_LATE_START_TIME<V_ATTENDANCE_TIME THEN
      IF(V_ATTENDANCE_DT < TRUNC(SYSDATE)) THEN
        V_LATE_STATUS   :='Y';
      ELSE
        V_LATE_STATUS :='L';
      END IF;
    END IF;
    --
    UPDATE HRIS_ATTENDANCE_DETAIL
    SET IN_TIME         = TO_DATE ( TO_CHAR (V_ATTENDANCE_TIME, 'DD-MON-YY HH:MI AM'), 'DD-MON-YY HH:MI AM'),
      OVERALL_STATUS    = V_OVERALL_STATUS,
      LATE_STATUS       = V_LATE_STATUS,
      IN_REMARKS        = P_REMARKS
    WHERE ATTENDANCE_DT = V_ATTENDANCE_DT
    AND EMPLOYEE_ID     = P_EMPLOYEE_ID;
    RETURN;
  END IF;
  --
  IF V_OVERALL_STATUS ='PR' AND V_EARLY_END_TIME>V_ATTENDANCE_TIME THEN
    IF (V_LATE_STATUS IN ('L','Y')) THEN
      V_LATE_STATUS :='B';
    ELSE
      V_LATE_STATUS :='E';
    END IF;
  ELSE
    IF (V_LATE_STATUS     ='B') THEN
      V_LATE_STATUS      :='L';
    ELSIF ( V_LATE_STATUS ='E') THEN
      V_LATE_STATUS      := 'N';
    ELSIF ( V_LATE_STATUS ='X') THEN
      V_LATE_STATUS      := 'N';
    ELSIF ( V_LATE_STATUS ='Y') THEN
      V_LATE_STATUS      := 'L';
    END IF;
  END IF;
  --
  SELECT SUM(ABS(EXTRACT( HOUR FROM DIFF ))*60 + ABS(EXTRACT( MINUTE FROM DIFF )))
  INTO V_TOTAL_HOUR
  FROM
    (SELECT (V_ATTENDANCE_TIME-V_IN_TIME) AS DIFF FROM DUAL
    ) ;
  IF(V_TOTAL_HOUR<=5) THEN
    RETURN;
  END IF;
  UPDATE HRIS_ATTENDANCE_DETAIL
  SET OUT_TIME        = V_ATTENDANCE_TIME,
    LATE_STATUS       =V_LATE_STATUS,
    OUT_REMARKS       = P_REMARKS,
    TOTAL_HOUR        =V_TOTAL_HOUR,
    OT_MINUTES        =(V_TOTAL_HOUR- V_TOTAL_WORKING_MIN)
  WHERE ATTENDANCE_DT = V_ATTENDANCE_DT
  AND EMPLOYEE_ID     = P_EMPLOYEE_ID;
END;/
            CREATE OR REPLACE PROCEDURE HRIS_ATTENDANCE_INSERT(
    P_THUMB_ID        NUMBER,
    P_ATTENDANCE_DT   DATE,
    P_IP_ADDRESS      VARCHAR2,
    P_ATTENDANCE_FROM VARCHAR2,
    P_ATTENDANCE_TIME TIMESTAMP,
    P_REMARKS         VARCHAR2:=NULL)
AS
  V_EMPLOYEE_ID NUMBER                          :=NULL;
  V_PURPOSE HRIS_ATTD_DEVICE_MASTER.PURPOSE%TYPE:='I/0';
BEGIN
  BEGIN
    SELECT PURPOSE
    INTO V_PURPOSE
    FROM HRIS_ATTD_DEVICE_MASTER
    WHERE DEVICE_IP=P_IP_ADDRESS;
  EXCEPTION
  WHEN no_data_found THEN
    NULL;
  END;
  BEGIN
    SELECT EMPLOYEE_ID
    INTO V_EMPLOYEE_ID
    FROM HRIS_EMPLOYEES
    WHERE ID_THUMB_ID=P_THUMB_ID;
  EXCEPTION
  WHEN NO_DATA_FOUND THEN
    NULL;
  WHEN too_many_rows THEN
    NULL;
  END;
  INSERT
  INTO HRIS_ATTENDANCE
    (
      EMPLOYEE_ID,
      ATTENDANCE_DT,
      IP_ADDRESS,
      ATTENDANCE_FROM,
      ATTENDANCE_TIME,
      THUMB_ID
    )
    VALUES
    (
      V_EMPLOYEE_ID ,
      P_ATTENDANCE_DT,
      P_IP_ADDRESS,
      P_ATTENDANCE_FROM,
      P_ATTENDANCE_TIME,
      P_THUMB_ID
    );
  BEGIN
    IF V_EMPLOYEE_ID IS NOT NULL THEN
      HRIS_ATTENDANCE_AFTER_INSERT(V_EMPLOYEE_ID,P_ATTENDANCE_DT,P_ATTENDANCE_TIME,P_REMARKS);
    END IF;
  END;
EXCEPTION
WHEN OTHERS THEN
  DBMS_OUTPUT.PUT_LINE('THUMB_ID: '||P_THUMB_ID||'ATTENDANCE_DT:'||P_ATTENDANCE_DT||'IP_ADDRESS: '||P_IP_ADDRESS||'P_ATTENDANCE_FROM: '||P_ATTENDANCE_FROM);
END;/
            CREATE OR REPLACE PROCEDURE HRIS_ATTENDANCE_NOTIFICATION(
    P_EMPLOYEE_ID HRIS_ATTENDANCE_DETAIL.EMPLOYEE_ID%TYPE,
    P_MESSAGE_DATETIME HRIS_NOTIFICATION.MESSAGE_DATETIME%TYPE,
    P_OVERALL_STATUS HRIS_ATTENDANCE_DETAIL.OVERALL_STATUS%TYPE,
    P_LATE_STATUS HRIS_ATTENDANCE_DETAIL.LATE_STATUS%TYPE)
AS
  V_MESSAGE_TITLE HRIS_NOTIFICATION.MESSAGE_TITLE%TYPE :=NULL;
  V_MESSAGE_DESC HRIS_NOTIFICATION.MESSAGE_DESC%TYPE   :=NULL;
  V_ROUTE HRIS_NOTIFICATION.ROUTE%TYPE                 :=NULL;
BEGIN
  IF P_OVERALL_STATUS = 'AB' THEN
    V_MESSAGE_TITLE  := 'Attendance Notification';
    V_MESSAGE_DESC   := 'Request for leave? You were absent on '||TO_CHAR(P_MESSAGE_DATETIME,'DD-MON-YYYY') ||'.';
    V_ROUTE          :='{"route":"leaverequest","action":"add"}';
  END IF;
  --
  IF P_OVERALL_STATUS = 'LA' THEN
    V_MESSAGE_TITLE  := 'Attendance Notification';
    V_MESSAGE_DESC   := 'Request for leave? You have been given Three day late absent penalty on '||TO_CHAR(P_MESSAGE_DATETIME,'DD-MON-YYYY') ||'.';
    V_ROUTE          :='{"route":"leaverequest","action":"add"}';
  END IF;
  --
  IF P_OVERALL_STATUS = 'BA' THEN
    V_MESSAGE_TITLE  := 'Attendance Notification';
    V_MESSAGE_DESC   := 'Request for leave? You have been given Late In and Early out on same day penalty on '||TO_CHAR(P_MESSAGE_DATETIME,'DD-MON-YYYY') ||'.';
    V_ROUTE          :='{"route":"leaverequest","action":"add"}';
  END IF;
  --
  IF (P_LATE_STATUS  ='X' OR P_LATE_STATUS ='Y') THEN
    V_MESSAGE_TITLE := 'Attendance Notification';
    V_MESSAGE_DESC  := 'Request for attendance? Missed punch on '||TO_CHAR(P_MESSAGE_DATETIME,'DD-MON-YYYY') ||' Detected.';
    V_ROUTE         :='{"route":"attendancerequest","action":"add"}';
  END IF;
  IF (V_MESSAGE_TITLE IS NOT NULL AND V_MESSAGE_DESC IS NOT NULL AND V_ROUTE IS NOT NULL) THEN
    HRIS_SYSTEM_NOTIFICATION(P_EMPLOYEE_ID,P_MESSAGE_DATETIME,V_MESSAGE_TITLE,V_MESSAGE_DESC,V_ROUTE);
  END IF;
END;    
/
            create or replace PROCEDURE hris_backdate_attendance ( p_id hris_attendance_request.id%TYPE ) AS

    p_attendance_dt   hris_attendance_request.attendance_dt%TYPE;
    p_employee_id     hris_employees.employee_id%TYPE;
    p_in_time         hris_attendance_request.in_time%TYPE;
    p_out_time        hris_attendance_request.out_time%TYPE;
    p_status          hris_attendance_request.status%TYPE;
    p_in_remarks      hris_attendance_request.in_remarks%TYPE;
    p_out_remarks      hris_attendance_request.out_remarks%TYPE;
    p_next_day_out char(1 BYTE);
BEGIN
    SELECT
        attendance_dt,
        employee_id,
        in_time,
        out_time,
        status,
        in_remarks,
        out_remarks,
        NEXT_DAY_OUT
    INTO
        p_attendance_dt,p_employee_id,p_in_time,p_out_time,p_status,p_in_remarks,p_out_remarks,p_next_day_out
    FROM
        hris_attendance_request
    WHERE
        id = p_id;

    IF
        p_status != 'AP'
    THEN
        return;
    END IF;
    IF
        p_in_time IS NOT NULL
    THEN
        INSERT INTO hris_attendance (
            attendance_dt,
            employee_id,
            attendance_time,
            attendance_from,
            remarks,
            IP_ADDRESS
        ) VALUES (
            p_attendance_dt,
            p_employee_id,
            TO_DATE(
                TO_CHAR(p_attendance_dt,'DD-MON-YYYY') || ' ' || TO_CHAR(p_in_time,'HH:MI AM'),
                'DD-MON-YYYY HH:MI AM'
            ),
            'SYSTEM',
            p_in_remarks,
            'IN'
        );

        hris_attendance_after_insert(
            p_employee_id,
            p_attendance_dt,
            p_in_time,
            p_in_remarks
        );
    END IF;

    IF
        p_out_time IS NOT NULL
    THEN
        INSERT INTO hris_attendance (
            attendance_dt,
            employee_id,
            attendance_time,
            attendance_from,
            remarks,
            IP_ADDRESS
        ) VALUES (
            p_attendance_dt,
            p_employee_id,
            case when p_next_day_out = 'Y'
            then
            TO_DATE(
                TO_CHAR((p_attendance_dt+1),'DD-MON-YYYY') || ' ' || TO_CHAR(p_out_time,'HH:MI AM'),
                'DD-MON-YYYY HH:MI AM'
            )
            else
            TO_DATE(
                TO_CHAR(p_attendance_dt,'DD-MON-YYYY') || ' ' || TO_CHAR(p_out_time,'HH:MI AM'),
                'DD-MON-YYYY HH:MI AM'
            )
            end,
            'SYSTEM',
            p_out_remarks,
            'OUT'
        );

        hris_attendance_after_insert(
            p_employee_id,
            p_attendance_dt,
            p_out_time,
            p_out_remarks
        );
    END IF;

    IF
        ( trunc(p_attendance_dt) <= trunc(SYSDATE) )
    THEN
        hris_queue_reattendance(
            trunc(p_attendance_dt),
            p_employee_id,
            trunc(p_attendance_dt)
        );
    END IF;

EXCEPTION
    WHEN no_data_found THEN
        NULL;
END;/
            create or replace PROCEDURE HRIS_CASE_EMP_MAP(
    P_EMPLOYEE_ID          IN HRIS_EMPLOYEES.EMPLOYEE_ID%TYPE,
    P_CASE_ID              IN hris_best_case_emp_map.case_id%TYPE,
    P_CASE_ACTION          VARCHAR2
    )
AS
v_exists varchar2(1) := 'F';
begin
IF P_CASE_ACTION = 'A' THEN
BEGIN
            select 'T'
            into v_exists
            from hris_best_case_emp_map
            where EMPLOYEE_ID = P_EMPLOYEE_ID
            and case_id = P_CASE_ID;
        exception
            when no_data_found then
            null;
        end;
        if v_exists <> 'T' then

        INSERT INTO hris_best_case_emp_map(CASE_ID, EMPLOYEE_ID)
        VALUES(P_CASE_ID, P_EMPLOYEE_ID);

        end if;

        FOR SHIFT_LIST IN (SELECT SHIFT_ID FROM HRIS_BEST_CASE_SHIFT_MAP WHERE CASE_ID = P_CASE_ID) LOOP

        begin
        select 'T'
            into v_exists
            from HRIS_EMPLOYEE_SHIFTS
            where EMPLOYEE_ID = P_EMPLOYEE_ID
            and shift_id = SHIFT_LIST.SHIFT_ID;
        exception
            when no_data_found then
            null;
        end;

        if v_exists <> 'T' then
        INSERT INTO HRIS_EMPLOYEE_SHIFTS VALUES(
        P_EMPLOYEE_ID,
        SHIFT_LIST.SHIFT_ID,
        (SELECT START_DATE FROM hris_best_case_setup WHERE CASE_ID = P_CASE_ID), 
        (SELECT END_DATE FROM hris_best_case_setup WHERE CASE_ID = P_CASE_ID),
        p_case_id
        );
        end if;
        END LOOP;

ELSE

DELETE FROM hris_best_case_emp_map WHERE EMPLOYEE_ID = P_EMPLOYEE_ID AND CASE_ID = P_CASE_ID;

DELETE FROM hris_employee_shifts WHERE EMPLOYEE_ID = P_EMPLOYEE_ID AND CASE_ID = P_CASE_ID;

--FOR SHIFT_LIST IN (SELECT SHIFT_ID FROM HRIS_BEST_CASE_SHIFT_MAP WHERE CASE_ID = P_CASE_ID) LOOP
--DELETE FROM HRIS_EMPLOYEE_SHIFTS WHERE SHIFT_ID = SHIFT_LIST.SHIFT_ID AND EMPLOYEE_ID = P_EMPLOYEE_ID;
--END LOOP;

END IF;
end;/
            CREATE OR REPLACE PROCEDURE HRIS_COMPULSORY_OT_CANCEL(
    P_COMPULSORY_OT_ID HRIS_COMPULSORY_OVERTIME.COMPULSORY_OVERTIME_ID%TYPE)
AS
  V_START_DATE DATE;
  V_END_DATE   DATE;
  V_DATE_DIFF  NUMBER;
BEGIN
  SELECT START_DATE,
    END_DATE,
    (TRUNC(END_DATE)-TRUNC(START_DATE))
  INTO V_START_DATE,
    V_END_DATE,
    V_DATE_DIFF
  FROM HRIS_COMPULSORY_OVERTIME
  WHERE COMPULSORY_OVERTIME_ID = P_COMPULSORY_OT_ID;
  FOR i IN 0..V_DATE_DIFF
  LOOP
    FOR ot IN
    (SELECT *
    FROM HRIS_OVERTIME
    WHERE DESCRIPTION = 'THIS IS AUTOGENERATED OT REQUEST FROM COMPULSORY OT.'
    AND OVERTIME_DATE = TRUNC(V_START_DATE+ i)
    AND EMPLOYEE_ID                      IN
      (SELECT EMPLOYEE_ID
      FROM HRIS_EMPLOYEE_COMPULSORY_OT
      WHERE COMPULSORY_OVERTIME_ID = P_COMPULSORY_OT_ID
      )
    )
    LOOP
      DELETE FROM HRIS_OVERTIME_DETAIL WHERE OVERTIME_ID = ot.OVERTIME_ID;
      --
      DELETE FROM HRIS_OVERTIME WHERE OVERTIME_ID = ot.OVERTIME_ID;
    END LOOP;
  END LOOP;
  UPDATE HRIS_COMPULSORY_OVERTIME
  SET STATUS                   ='D'
  WHERE COMPULSORY_OVERTIME_ID = P_COMPULSORY_OT_ID;
END;
/
            create or replace PROCEDURE HRIS_COMPULSORY_OT_PROC(
    V_DATE DATE)
AS
  V_COMPULSORY_OVERTIME_ID NUMBER;
TYPE OVERTIME_DETAIL_TYPE
IS
  TABLE OF HRIS_OVERTIME_DETAIL%ROWTYPE INDEX BY BINARY_INTEGER;
  V_OT_DETAIL OVERTIME_DETAIL_TYPE;
  V_DESCRIPTION    VARCHAR2(255 BYTE):='THIS IS AUTOGENERATED OT REQUEST FROM COMPULSORY OT.';
  V_TOTAL_HOUR     NUMBER;
  V_OVERTIME_ID    NUMBER;
  V_DETAIL_ID      NUMBER;
  V_MESSAGE_ID     NUMBER;
  V_TO_EMPLOYEE_ID NUMBER;
  V_ROLE_ID        NUMBER;
  V_ROUTE          VARCHAR2(4000 BYTE);
  V_EMPLOYEE_NAME  VARCHAR2(255 BYTE);
BEGIN
  FOR cot IN
  (SELECT  *
  FROM HRIS_COMPULSORY_OVERTIME
  WHERE TRUNC(V_DATE) BETWEEN START_DATE AND END_DATE AND STATUS='E'
  )
  LOOP
    V_COMPULSORY_OVERTIME_ID:=cot.COMPULSORY_OVERTIME_ID;
    BEGIN
      FOR ot IN
      (SELECT EMPLOYEE_ID,
        ATTENDANCE_DT,
        IN_TIME,
        OUT_TIME,
        START_TIME,
        END_TIME,
        EXTRACT(HOUR FROM EARLY_OT_HR)*60+EXTRACT(MINUTE FROM EARLY_OT_HR) AS ACT_EARLY_OT_HR,
        EXTRACT(HOUR FROM LATE_OT_HR) *60+EXTRACT(MINUTE FROM LATE_OT_HR)  AS ACT_LATE_OT_HR,
        EARLY_OVERTIME_HR,
        LATE_OVERTIME_HR
      FROM
        (SELECT AD.EMPLOYEE_ID,
          AD.ATTENDANCE_DT,
          AD.IN_TIME,
          AD.OUT_TIME,
          S.START_TIME,
          S.END_TIME,
          ((S.START_TIME-TRUNC(S.START_TIME))-(AD.IN_TIME -TRUNC(AD.IN_TIME))) AS EARLY_OT_HR,
          ((AD.OUT_TIME -TRUNC(AD.OUT_TIME))-(S.END_TIME-TRUNC(S.END_TIME)))   AS LATE_OT_HR,
          OT.EARLY_OVERTIME_HR,
          OT.LATE_OVERTIME_HR
        FROM HRIS_ATTENDANCE_DETAIL AD ,
          (SELECT COT.EARLY_OVERTIME_HR,
            COT.LATE_OVERTIME_HR,
            COT.START_DATE,
            COT.END_DATE,
            ECOT.EMPLOYEE_ID
          FROM HRIS_COMPULSORY_OVERTIME COT
          JOIN HRIS_EMPLOYEE_COMPULSORY_OT ECOT
          ON (COT.COMPULSORY_OVERTIME_ID  = ECOT.COMPULSORY_OVERTIME_ID)
          WHERE COT.COMPULSORY_OVERTIME_ID=V_COMPULSORY_OVERTIME_ID
          ) OT,
          HRIS_SHIFTS S
        WHERE AD.EMPLOYEE_ID    =OT.EMPLOYEE_ID
        AND AD.ATTENDANCE_DT    =TRUNC(V_DATE)
        AND AD.OVERALL_STATUS  IN ('PR','LA','BA')
        AND AD.LATE_STATUS NOT IN ('X','Y')
        AND AD.SHIFT_ID         =S.SHIFT_ID
        )
      )
      LOOP
        V_OT_DETAIL.DELETE;
        V_TOTAL_HOUR:=0;
        --
        IF(ot.EARLY_OVERTIME_HR    >=30) AND (ot.ACT_EARLY_OT_HR >=30) THEN
          V_OT_DETAIL(0).START_TIME:=ot.IN_TIME ;
          V_OT_DETAIL(0).END_TIME  :=ot.START_TIME;
          V_OT_DETAIL(0).STATUS    :='E';
          V_OT_DETAIL(0).TOTAL_HOUR:=ot.ACT_EARLY_OT_HR;
          V_TOTAL_HOUR             :=V_TOTAL_HOUR+ot.ACT_EARLY_OT_HR;
        END IF;
        IF(ot.LATE_OVERTIME_HR     >=30) AND (ot.ACT_LATE_OT_HR >=30) THEN
          V_OT_DETAIL(1).START_TIME:=ot.END_TIME;
          V_OT_DETAIL(1).END_TIME  :=ot.OUT_TIME;
          V_OT_DETAIL(1).STATUS    :='E';
          V_OT_DETAIL(1).TOTAL_HOUR:=ot.ACT_LATE_OT_HR;
          V_TOTAL_HOUR             :=V_TOTAL_HOUR+ot.ACT_LATE_OT_HR;
        END IF;
        IF(V_OT_DETAIL.COUNT >0) THEN
          SELECT NVL(MAX(OVERTIME_ID),1)+1 INTO V_OVERTIME_ID FROM HRIS_OVERTIME;
          INSERT
          INTO HRIS_OVERTIME
            (
              OVERTIME_ID,
              EMPLOYEE_ID,
              OVERTIME_DATE,
              REQUESTED_DATE,
              DESCRIPTION,
              STATUS,
              TOTAL_HOUR
            )
            VALUES
            (
              V_OVERTIME_ID,
              ot.EMPLOYEE_ID,
              ot.ATTENDANCE_DT,
              ot.ATTENDANCE_DT,
              V_DESCRIPTION,
              'RQ',
              V_TOTAL_HOUR
            );
          FOR i IN V_OT_DETAIL.FIRST .. V_OT_DETAIL.LAST
          LOOP
            SELECT NVL(MAX(DETAIL_ID),1)+1 INTO V_DETAIL_ID FROM HRIS_OVERTIME_DETAIL;
            INSERT
            INTO HRIS_OVERTIME_DETAIL
              (
                DETAIL_ID,
                OVERTIME_ID,
                START_TIME,
                END_TIME,
                STATUS,
                TOTAL_HOUR
              )
              VALUES
              (
                V_DETAIL_ID,
                V_OVERTIME_ID,
                V_OT_DETAIL(i).START_TIME,
                V_OT_DETAIL(i).END_TIME,
                'E',
                V_OT_DETAIL(i).TOTAL_HOUR
              );
          END LOOP;
          SELECT NVL(MAX(MESSAGE_ID),0)+1 INTO V_MESSAGE_ID FROM HRIS_NOTIFICATION;
          SELECT RECOMMEND_BY,
            (
            CASE
              WHEN RECOMMEND_BY=APPROVED_BY
              THEN 4
              ELSE 2
            END)
          INTO V_TO_EMPLOYEE_ID,
            V_ROLE_ID
          FROM HRIS_RECOMMENDER_APPROVER
          WHERE EMPLOYEE_ID = ot.EMPLOYEE_ID;
          V_ROUTE          :='{"route":"overtimeApprove","action":"view","id":"'||V_OVERTIME_ID||'","role":'||V_ROLE_ID||'}';
          SELECT FULL_NAME
          INTO V_EMPLOYEE_NAME
          FROM HRIS_EMPLOYEES
          WHERE EMPLOYEE_ID=ot.EMPLOYEE_ID;
          INSERT
          INTO HRIS_NOTIFICATION
            (
              MESSAGE_ID,
              MESSAGE_DATETIME,
              MESSAGE_TITLE,
              MESSAGE_DESC,
              MESSAGE_FROM,
              MESSAGE_TO,
              STATUS,
              EXPIRY_TIME,
              ROUTE
            )
            VALUES
            (
              V_MESSAGE_ID,
              V_DATE,
              'Compulsory OT',
              'OT REQUEST OF '
              ||V_EMPLOYEE_NAME
              ||' FROM THE DATE '
              ||TO_CHAR(ot.ATTENDANCE_DT,'DD-MON-YYYY'),
              ot.EMPLOYEE_ID,
              V_TO_EMPLOYEE_ID,
              'U',
              V_DATE+14,
              V_ROUTE
            );
        END IF;
      END LOOP;
    END;
  END LOOP;
END;/
            create or replace PROCEDURE HRIS_CREATE_USER_ACCOUNTS(
    P_PASSWORD HRIS_USERS.PASSWORD%TYPE := 'password@123')
AS
  V_USER_ID  NUMBER :=20;
  V_USERNAME VARCHAR2(255 BYTE);
BEGIN
  FOR CUR_EMP IN
  (SELECT EMPLOYEE_ID,
    FIRST_NAME,
    MIDDLE_NAME,
    LAST_NAME
  FROM HRIS_EMPLOYEES
  WHERE EMPLOYEE_ID NOT IN
    (SELECT EMPLOYEE_ID FROM HRIS_USERS WHERE STATUS ='E'
    )
  )
  LOOP
    BEGIN
      SELECT NVL(MAX(USER_ID),0)+1 INTO V_USER_ID FROM HRIS_USERS;
      V_USERNAME := CONCAT(CONCAT(CONCAT(LOWER(TRIM(CUR_EMP.FIRST_NAME)),'_'),
      CASE
      WHEN CUR_EMP.MIDDLE_NAME IS NOT NULL THEN
        CONCAT(LOWER(TRIM(CUR_EMP.MIDDLE_NAME)), '_')
      ELSE
        ''
      END ),LOWER(TRIM(CUR_EMP.LAST_NAME)));
      INSERT
      INTO HRIS_USERS
        (
          USER_ID,
          EMPLOYEE_ID,
          USER_NAME,
          PASSWORD,
          ROLE_ID,
          STATUS,
          CREATED_DT
        )
        VALUES
        (
          V_USER_ID,
          CUR_EMP.EMPLOYEE_ID,
          V_USERNAME,
          P_PASSWORD,
          11,
          'E',
          TRUNC(SYSDATE)
        );
    END;
  END LOOP;
END;
/
            CREATE OR REPLACE PROCEDURE HRIS_DAILY_SYSTEM_CHECK(
    P_DATE DATE:=NULL)
AS
  V_DATE      DATE;
  V_MONTH_ID  NUMBER(7,0);
  V_FROM_DATE DATE;
  V_TO_DATE   DATE;
  V_YEAR      NUMBER;
  V_MONTH_NO  NUMBER;
  V_FISCAL_YEAR_ID HRIS_MONTH_CODE.FISCAL_YEAR_ID%TYPE;
  V_FISCAL_YEAR_MONTH_NO HRIS_MONTH_CODE.FISCAL_YEAR_MONTH_NO%TYPE;
  IS_START_OF_MONTH         CHAR(1 BYTE);
  IS_TWO_DAY_BEFORE_MTH_END CHAR(1 BYTE);
  IS_END_OF_MONTH           CHAR(1 BYTE);
  V_DEDUCTION_RATE FLOAT:=0.5;
BEGIN
  --SET DATE
  IF P_DATE IS NULL THEN
    V_DATE  :=TRUNC(SYSDATE);
  ELSE
    V_DATE :=TRUNC(P_DATE);
  END IF;
  --
  SELECT MONTH_ID,
    FROM_DATE,
    TO_DATE,
    YEAR,
    MONTH_NO,
    FISCAL_YEAR_ID,
    FISCAL_YEAR_MONTH_NO
  INTO V_MONTH_ID,
    V_FROM_DATE,
    V_TO_DATE,
    V_YEAR,
    V_MONTH_NO,
    V_FISCAL_YEAR_ID,
    V_FISCAL_YEAR_MONTH_NO
  FROM HRIS_MONTH_CODE
  WHERE V_DATE BETWEEN TRUNC(FROM_DATE) AND TRUNC(TO_DATE);
  --
  SELECT (
    CASE
      WHEN V_DATE = TRUNC(V_FROM_DATE)
      THEN 'Y'
      ELSE 'N'
    END),
    (
    CASE
      WHEN TRUNC(V_TO_DATE-2) = V_DATE
      THEN 'Y'
      ELSE 'N'
    END),
    (
    CASE
      WHEN V_DATE = TRUNC(V_TO_DATE)
      THEN 'Y'
      ELSE 'N'
    END)
  INTO IS_START_OF_MONTH,
    IS_TWO_DAY_BEFORE_MTH_END,
    IS_END_OF_MONTH
  FROM DUAL;
  --SERVICE STATUS UPDATE
  FOR service IN
  (SELECT * FROM HRIS_JOB_HISTORY WHERE EVENT_DATE =V_DATE AND STATUS = 'E'
  )
  LOOP
    HRIS_UPDATE_EMPLOYEE_SERVICE(service.JOB_HISTORY_ID);
  END LOOP;
  --END OF SERVICE STATUS UPDATE
  --  NOTIFY PENALTY
  IF IS_TWO_DAY_BEFORE_MTH_END ='Y' THEN
    FOR penalty IN
    (SELECT DISTINCT A.EMPLOYEE_ID
    FROM HRIS_ATTENDANCE_DETAIL A
    WHERE A.OVERALL_STATUS IN ('LA','BA')
    AND (A.ATTENDANCE_DT BETWEEN V_FROM_DATE AND V_TO_DATE )
    )
    LOOP
      HRIS_SYSTEM_NOTIFICATION(penalty.EMPLOYEE_ID,SYSDATE,'Late Penalty','Please verify your attendance.','{"route":"penalty","action":"self","id":"'||V_MONTH_ID||'"}');
    END LOOP;
  END IF;
  IF IS_END_OF_MONTH ='Y' THEN
    NULL;
    --HRIS_LATE_LEAVE_DEDUCTION
  END IF;
  --  END OF NOTIFY PENALTY
END;
/
            create or replace PROCEDURE HRIS_EMPLOYEE_SETUP_PROC(
    P_EMPLOYEE_ID HRIS_EMPLOYEES.EMPLOYEE_ID%TYPE)
AS
  V_JOIN_DATE HRIS_EMPLOYEES.JOIN_DATE%TYPE;
  V_FISCAL_YEAR_ID HRIS_FISCAL_YEARS.FISCAL_YEAR_ID%TYPE;
  V_MONTH_ID HRIS_MONTH_CODE.MONTH_ID%TYPE;
  V_FISCAL_YEAR_MONTH_NO NUMBER;
  V_IS_EMP_IN            CHAR(1 BYTE);
  V_PRODATA_DAYS         NUMBER;
  V_COUNT                NUMBER;
  V_CUR_FIS_YR_ID HRIS_FISCAL_YEARS.FISCAL_YEAR_ID%TYPE;
  V_CUR_FIS_YR_START_DATE DATE;
  V_MONTH_COUNT NUMBER:=1;
BEGIN
  SELECT LEAVE_YEAR_ID,
    TRUNC(START_DATE)
  INTO V_CUR_FIS_YR_ID,
    V_CUR_FIS_YR_START_DATE
  FROM HRIS_LEAVE_YEARS
  WHERE TRUNC(SYSDATE) BETWEEN START_DATE AND END_DATE;
  --
  BEGIN
    SELECT TRUNC(JOIN_DATE)
    INTO V_JOIN_DATE
    FROM HRIS_EMPLOYEES
    WHERE EMPLOYEE_ID = P_EMPLOYEE_ID;
  EXCEPTION
  WHEN NO_DATA_FOUND THEN
    SYS.DBMS_OUTPUT.PUT_LINE('NO JOIN_DATE SET FROM THE EMPLOYEE WITH EMPLOYEE_ID : '||P_EMPLOYEE_ID);
    RETURN;
  END;
  BEGIN
    SELECT LEAVE_YEAR_ID,
      MONTH_ID,
      LEAVE_YEAR_MONTH_NO
    INTO V_FISCAL_YEAR_ID,
      V_MONTH_ID,
      V_FISCAL_YEAR_MONTH_NO
    FROM HRIS_LEAVE_MONTH_CODE
    WHERE (
      CASE
        WHEN V_JOIN_DATE>V_CUR_FIS_YR_START_DATE
        THEN V_JOIN_DATE
        ELSE V_CUR_FIS_YR_START_DATE
      END ) BETWEEN FROM_DATE AND TO_DATE;
  EXCEPTION
  WHEN no_data_found THEN
    SYS.DBMS_OUTPUT.PUT('No Current Month found.');
    RETURN;
  END;
  BEGIN
    FOR leave IN
    (SELECT LEAVE_ID,CARRY_FORWARD,
      DEFAULT_DAYS,
      IS_PRODATA_BASIS,
      IS_MONTHLY
    FROM HRIS_LEAVE_MASTER_SETUP
    WHERE STATUS                 ='E'
    AND ASSIGN_ON_EMPLOYEE_SETUP ='Y'
    )
    LOOP
      V_IS_EMP_IN    := HRIS_IS_EMP_IN(P_EMPLOYEE_ID,'HRIS_LEAVE_MASTER_SETUP','LEAVE_ID',leave.LEAVE_ID);
      IF V_IS_EMP_IN !='Y' THEN
        CONTINUE;
      END IF;
      IF (leave.IS_MONTHLY ='Y') THEN

      -- IF MONTHLY CARRY FORWARD IS NO 
      IF(leave.CARRY_FORWARD ='N')
      THEN
      DBMS_OUTPUT.PUT_LINE('FISCAL_YEAR_NO:'||V_FISCAL_YEAR_MONTH_NO);
      FOR i IN V_FISCAL_YEAR_MONTH_NO..12
        LOOP
          SELECT COUNT(*)
          INTO V_COUNT
          FROM HRIS_EMPLOYEE_LEAVE_ASSIGN
          WHERE EMPLOYEE_ID       =P_EMPLOYEE_ID
          AND LEAVE_ID            = leave.LEAVE_ID
          AND FISCAL_YEAR_MONTH_NO=i ;
          IF ( V_COUNT            =0 )THEN
            INSERT
            INTO HRIS_EMPLOYEE_LEAVE_ASSIGN
              (
                EMPLOYEE_ID,
                LEAVE_ID,
                PREVIOUS_YEAR_BAL,
                TOTAL_DAYS,
                BALANCE,
                --FISCAL_YEAR,
                FISCAL_YEAR_MONTH_NO,
                CREATED_DT
              )
              VALUES
              (
                P_EMPLOYEE_ID,
                leave.LEAVE_ID,
                0,
                leave.DEFAULT_DAYS,
                leave.DEFAULT_DAYS,
               -- V_FISCAL_YEAR_ID,
                i,
                TRUNC(SYSDATE)
              );
          END IF;
        END LOOP;


      END IF;

      -- IF MONTHLY CARRY FORWARD IS YES
      IF(leave.CARRY_FORWARD ='Y')
      THEN
      V_MONTH_COUNT:=1;
      
      SELECT COUNT(*)
          INTO V_COUNT
          FROM HRIS_EMPLOYEE_LEAVE_ASSIGN
          WHERE EMPLOYEE_ID       =P_EMPLOYEE_ID
          AND LEAVE_ID            = leave.LEAVE_ID;
          
        IF(V_COUNT=0) THEN
       FOR i IN V_FISCAL_YEAR_MONTH_NO..12
            LOOP

                INSERT
                INTO HRIS_EMPLOYEE_LEAVE_ASSIGN
                  (
                    EMPLOYEE_ID,
                    LEAVE_ID,
                    PREVIOUS_YEAR_BAL,
                    TOTAL_DAYS,
                    BALANCE,
                   -- FISCAL_YEAR,
                    FISCAL_YEAR_MONTH_NO,
                    CREATED_DT
                  )
                  VALUES
                  (
                    P_EMPLOYEE_ID,
                     leave.LEAVE_ID,
                    0,
                    leave.DEFAULT_DAYS*V_MONTH_COUNT,
                    leave.DEFAULT_DAYS*V_MONTH_COUNT,
                  --  V_FISCAL_YEAR_ID,
                    i,
                    TRUNC(SYSDATE)
                  );

                  V_MONTH_COUNT:=V_MONTH_COUNT+1;
            END LOOP;
            
             END IF;


      END IF;



        CONTINUE;
      END IF;
      V_PRODATA_DAYS           := leave.DEFAULT_DAYS;
      IF leave.IS_PRODATA_BASIS = 'Y' THEN
        V_PRODATA_DAYS         :=ROUND(leave.DEFAULT_DAYS*((13-V_FISCAL_YEAR_MONTH_NO)/12));
      END IF;
      SELECT COUNT(*)
      INTO V_COUNT
      FROM HRIS_EMPLOYEE_LEAVE_ASSIGN
      WHERE EMPLOYEE_ID =P_EMPLOYEE_ID
      AND LEAVE_ID      = leave.LEAVE_ID;
      IF ( V_COUNT      =0 )THEN
        INSERT
        INTO HRIS_EMPLOYEE_LEAVE_ASSIGN
          (
            EMPLOYEE_ID,
            LEAVE_ID,
            PREVIOUS_YEAR_BAL,
            TOTAL_DAYS,
            BALANCE,
            FISCAL_YEAR,
            CREATED_DT
          )
          VALUES
          (
            P_EMPLOYEE_ID,
            leave.LEAVE_ID,
            0,
            V_PRODATA_DAYS,
            V_PRODATA_DAYS,
            V_FISCAL_YEAR_ID,
            TRUNC(SYSDATE)
          );
      END IF;
    END LOOP;
  END;
  BEGIN
    FOR holiday IN
    (SELECT HOLIDAY_ID
      FROM HRIS_HOLIDAY_MASTER_SETUP
      WHERE ASSIGN_ON_EMPLOYEE_SETUP = 'Y'
      AND STATUS                     ='E'
      AND START_DATE                >=V_JOIN_DATE
    )
    LOOP
      HRIS_HOLIDAY_ASSIGN_AUTO
      (
        holiday.HOLIDAY_ID,P_EMPLOYEE_ID
      )
      ;
    END LOOP;
  END;
  --
  BEGIN
    FOR news IN
    (SELECT NEWS_ID FROM HRIS_NEWS WHERE STATUS ='E'
    )
    LOOP
      HRIS_NEWS_TO_PROC
      (
        news.NEWS_ID,P_EMPLOYEE_ID
      )
      ;
    END LOOP;
  END;
END;/
            create or replace PROCEDURE HRIS_FIX_SUB_LEAVE
AS
begin
for LEAVE_LIST in (select * from (SELECT LA.LEAVE_ID,la.employee_id,
                  LMS.LEAVE_CODE,
                  LMS.LEAVE_ENAME,
                  LA.PREVIOUS_YEAR_BAL,
                  LA.TOTAL_DAYS,
                  LA.BALANCE,
                  (SELECT SUM(ELR.NO_OF_DAYS/(
                    CASE
                      WHEN ELR.HALF_DAY IN ('F','S')
                      THEN 2
                      ELSE 1
                    END))
                  FROM HRIS_EMPLOYEE_LEAVE_REQUEST ELR
                   LEFT JOIN (SELECT * FROM HRIS_LEAVE_YEARS  WHERE TRUNC(SYSDATE) BETWEEN START_DATE AND END_DATE ) LY ON (1=1)
                  WHERE ELR.LEAVE_ID =LA.LEAVE_ID
                  AND ELR.EMPLOYEE_ID=LA.EMPLOYEE_ID
                  AND ELR.STATUS     ='AP'
                   AND ELR.START_DATE BETWEEN LY.START_DATE AND LY.END_DATE
                  ) AS LEAVE_TAKEN,
                  (SELECT SUM(ELA.NO_OF_DAYS)
                  FROM HRIS_EMPLOYEE_LEAVE_ADDITION ELA
                  WHERE ELA.EMPLOYEE_ID=LA.EMPLOYEE_ID
                  AND ELA.LEAVE_ID     =LA.LEAVE_ID
                  ) AS LEAVE_ADDED
                FROM HRIS_EMPLOYEE_LEAVE_ASSIGN LA
                LEFT JOIN HRIS_LEAVE_MASTER_SETUP LMS
                ON (LA.LEAVE_ID     =LMS.LEAVE_ID)
                WHERE LMS.IS_SUBSTITUTE='Y' AND LMS.STATUS ='E' 
                AND LMS.IS_MONTHLY = 'N' ORDER BY LMS.LEAVE_ENAME ASC)
                where 
                leave_taken>total_days or leave_added!=total_days)
loop
HRIS_RECALCULATE_LEAVE(LEAVE_LIST.EMPLOYEE_ID,LEAVE_LIST.LEAVE_ID);
end loop;
end;
 
 /
            CREATE OR REPLACE PROCEDURE HRIS_GEN_SAL_SH_REPORT(
P_SHEET_NO HRIS_SALARY_SHEET.SHEET_NO%TYPE )
AS
V_GENERATED_COUNT NUMBER;
BEGIN
SELECT COUNT(*)
INTO V_GENERATED_COUNT
FROM HRIS_SALARY_SHEET_EMP_DETAIL
WHERE SHEET_NO = P_SHEET_NO;
--
IF V_GENERATED_COUNT >0 THEN
DELETE FROM HRIS_SALARY_SHEET_EMP_DETAIL WHERE SHEET_NO=P_SHEET_NO;
END IF;
INSERT INTO HRIS_SALARY_SHEET_EMP_DETAIL
SELECT SS.SHEET_NO,
SS.MONTH_ID,
SS.YEAR,
SS.MONTH_NO,
SS.START_DATE,
SS.END_DATE,
(TRUNC(SS.END_DATE )-TRUNC(SS.START_DATE))+1 AS TOTAL_DAYS,
A.EMPLOYEE_ID,
E.FULL_NAME,
A.DAYOFF,
A.PRESENT,
A.HOLIDAY,
A.LEAVE,
A.PAID_LEAVE,
A.UNPAID_LEAVE,
A.ABSENT,
NVL(ROUND(OT.TOTAL_MIN/60,2),0) AS OVERTIME_HOUR,
A.TRAVEL,
A.TRAINING,
A.WORK_ON_HOLIDAY,
A.WORK_ON_DAYOFF,
(
CASE
WHEN EHM.SALARY IS NULL
THEN E.SALARY
ELSE EHM.SALARY
END),
(
CASE
WHEN ( EHM.MARITAL_STATUS IS NULL AND E.TAX_BASE IS NOT NULL )
THEN E.TAX_BASE
WHEN ( EHM.MARITAL_STATUS IS NULL AND E.TAX_BASE IS  NULL )
THEN E.MARITAL_STATUS
ELSE EHM.MARITAL_STATUS
END) ,
(
CASE
WHEN (
CASE
WHEN ( EHM.MARITAL_STATUS IS NULL AND E.TAX_BASE IS NOT NULL )
THEN E.TAX_BASE
WHEN ( EHM.MARITAL_STATUS IS NULL AND E.TAX_BASE IS  NULL )
THEN E.MARITAL_STATUS
ELSE EHM.MARITAL_STATUS
END) ='M'
THEN 'MARRIED'
ELSE 'UNMARRIED'
END) AS MARITAL_STATUS_DESC,
E.GENDER_ID,
G.GENDER_CODE,
G.GENDER_NAME,
E.JOIN_DATE,
C.COMPANY_ID,
C.COMPANY_NAME,
B.BRANCH_ID,
B.BRANCH_NAME,
DEP.DEPARTMENT_ID,
DEP.DEPARTMENT_NAME,
DES.DESIGNATION_ID,
DES.DESIGNATION_TITLE,
P.POSITION_ID,
P.POSITION_NAME,
P.LEVEL_NO,
ST.SERVICE_TYPE_ID,
ST.SERVICE_TYPE_NAME,
ST.TYPE AS SERVICE_TYPE,
(
CASE
WHEN ST.SERVICE_TYPE_NAME='Permanent'
THEN 'Y'
ELSE 'N'
END),
E.PERMANENT_DATE,
E.ADDR_PERM_STREET_ADDRESS,
E.ID_ACCOUNT_NO,
FT.FUNCTIONAL_TYPE_ID,
FT.FUNCTIONAL_TYPE_EDESC 
FROM
(SELECT SS.EMPLOYEE_ID,
SUM(
CASE
WHEN A.OVERALL_STATUS IN( 'DO','WD')
THEN 1
ELSE 0
END) AS DAYOFF,
SUM(
CASE
WHEN A.OVERALL_STATUS IN ('PR','BA','LA','TV','VP','TN','TP')
THEN 1
ELSE 0
END) AS PRESENT,
SUM(
CASE
WHEN A.OVERALL_STATUS IN ('HD','WH')
THEN 1
ELSE 0
END) AS HOLIDAY,
SUM(
CASE
WHEN A.OVERALL_STATUS IN ('LV','LP')
THEN 1
ELSE 0
END) AS LEAVE,
SUM(
CASE
WHEN L.PAID = 'Y'
THEN 1
ELSE 0
END) AS PAID_LEAVE,
SUM(
CASE
WHEN L.PAID = 'N'
THEN 1
ELSE 0
END) AS UNPAID_LEAVE,
SUM(
CASE
WHEN A.OVERALL_STATUS = 'AB'
THEN 1
ELSE 0
END) AS ABSENT,
SUM(
CASE
WHEN A.OVERALL_STATUS= 'TV'
THEN 1
ELSE 0
END) AS TRAVEL,
SUM(
CASE
WHEN A.OVERALL_STATUS ='TN'
THEN 1
ELSE 0
END) AS TRAINING,
SUM(
CASE
WHEN A.OVERALL_STATUS = 'WH'
THEN 1
ELSE 0
END) WORK_ON_HOLIDAY,
SUM(
CASE
WHEN A.OVERALL_STATUS ='WD'
THEN 1
ELSE 0
END) WORK_ON_DAYOFF
FROM
(SELECT SS.*,
E.EMPLOYEE_ID
FROM HRIS_SALARY_SHEET SS
JOIN HRIS_EMPLOYEES E
ON (SS.COMPANY_ID=E.COMPANY_ID
AND SS.GROUP_ID = E.GROUP_ID)
WHERE SS.SHEET_NO=P_SHEET_NO
) SS
LEFT JOIN HRIS_ATTENDANCE_DETAIL A
ON (( A.ATTENDANCE_DT BETWEEN SS.START_DATE AND SS.END_DATE)
AND (SS.EMPLOYEE_ID = A.EMPLOYEE_ID))
LEFT JOIN HRIS_LEAVE_MASTER_SETUP L
ON (A.LEAVE_ID = L.LEAVE_ID)
GROUP BY SS.EMPLOYEE_ID
) A
LEFT JOIN
(SELECT OT.EMPLOYEE_ID,
SUM(OT.TOTAL_HOUR) AS TOTAL_MIN
FROM
(SELECT SS.*,
E.EMPLOYEE_ID
FROM HRIS_SALARY_SHEET SS
JOIN HRIS_EMPLOYEES E
ON (SS.COMPANY_ID=E.COMPANY_ID
AND SS.GROUP_ID = E.GROUP_ID)
WHERE SS.SHEET_NO=P_SHEET_NO
) SS
LEFT JOIN HRIS_OVERTIME OT
ON ( OT.OVERTIME_DATE BETWEEN SS.START_DATE AND SS.END_DATE)
WHERE 1 =1
AND SS.SHEET_NO=P_SHEET_NO
AND OT.STATUS = 'AP'
GROUP BY OT.EMPLOYEE_ID
) OT ON (A.EMPLOYEE_ID = OT.EMPLOYEE_ID)
LEFT JOIN HRIS_EMPLOYEES E
ON(A.EMPLOYEE_ID = E.EMPLOYEE_ID)
LEFT JOIN HRIS_GENDERS G
ON(E.GENDER_ID=G.GENDER_ID)
LEFT JOIN HRIS_COMPANY C
ON(E.COMPANY_ID= C.COMPANY_ID)
LEFT JOIN HRIS_BRANCHES B
ON (E.BRANCH_ID=B.BRANCH_ID)
LEFT JOIN HRIS_DEPARTMENTS DEP
ON (E.DEPARTMENT_ID= DEP.DEPARTMENT_ID)
LEFT JOIN HRIS_DESIGNATIONS DES
ON (E.DESIGNATION_ID=DES.DESIGNATION_ID)
LEFT JOIN HRIS_POSITIONS P
ON (E.POSITION_ID=P.POSITION_ID)
LEFT JOIN HRIS_SERVICE_TYPES ST
ON (E.SERVICE_TYPE_ID=ST.SERVICE_TYPE_ID)
LEFT JOIN HRIS_SALARY_SHEET SS
ON (SS.SHEET_NO=P_SHEET_NO)
LEFT JOIN HRIS_EMPLOYEE_HISTORY_MIG EHM
ON (EHM.EMPLOYEE_ID=A.EMPLOYEE_ID
AND EHM.MONTH_ID =SS.MONTH_ID)
LEFT JOIN HRIS_FUNCTIONAL_TYPES FT
ON (E.FUNCTIONAL_TYPE_ID=FT.FUNCTIONAL_TYPE_ID)
WHERE 1 =1
AND A.EMPLOYEE_ID IN (select EMPLOYEE_ID from HRIS_PAYROLL_EMP_LIST)
ORDER BY C.COMPANY_NAME,
DEP.DEPARTMENT_NAME,
E.FULL_NAME ;
END;/
            CREATE OR REPLACE PROCEDURE HRIS_HOLIDAY_ASSIGN_AUTO(
    P_HOLIDAY_ID HRIS_HOLIDAY_MASTER_SETUP.HOLIDAY_ID%TYPE,
    P_EMPLOYEE_ID HRIS_EMPLOYEES. EMPLOYEE_ID%TYPE:=NULL)
AS
  V_IS_EMP_IN CHAR;
BEGIN
  DELETE
  FROM HRIS_EMPLOYEE_HOLIDAY
  WHERE HOLIDAY_ID = P_HOLIDAY_ID
  AND (EMPLOYEE_ID =
    CASE
      WHEN P_EMPLOYEE_ID IS NOT NULL
      THEN P_EMPLOYEE_ID
    END
  OR P_EMPLOYEE_ID IS NULL);
  --
  FOR employee IN
  (SELECT E.EMPLOYEE_ID,
    H.START_DATE
  FROM HRIS_EMPLOYEES E,
    (SELECT TRUNC(START_DATE) AS START_DATE
    FROM HRIS_HOLIDAY_MASTER_SETUP
    WHERE HOLIDAY_ID = P_HOLIDAY_ID
    AND STATUS       ='E'
    ) H
  WHERE E.STATUS         ='E'
  AND TRUNC(E.JOIN_DATE) < H.START_DATE
  AND (E.EMPLOYEE_ID     =
    CASE
      WHEN P_EMPLOYEE_ID IS NOT NULL
      THEN P_EMPLOYEE_ID
    END
  OR P_EMPLOYEE_ID IS NULL)
  )
  LOOP
    V_IS_EMP_IN    := HRIS_IS_EMP_IN(employee.EMPLOYEE_ID,'HRIS_HOLIDAY_MASTER_SETUP','HOLIDAY_ID',P_HOLIDAY_ID);
    IF V_IS_EMP_IN !='Y' THEN
      CONTINUE;
    END IF;
    INSERT
    INTO HRIS_EMPLOYEE_HOLIDAY
      (
        HOLIDAY_ID,
        EMPLOYEE_ID
      )
      VALUES
      (
        P_HOLIDAY_ID,
        employee.EMPLOYEE_ID
      );
    IF employee.START_DATE<TRUNC(SYSDATE) THEN
NULL;
-- need this task to be run on schedule | pending for now
--       HRIS_REATTENDANCE(employee.START_DATE,employee.EMPLOYEE_ID,employee.START_DATE);
    END IF;
  END LOOP;
END;/
            CREATE OR REPLACE PROCEDURE HRIS_INSERT_MENU(
    P_MENU_NAME HRIS_MENUS.MENU_NAME%TYPE,
    P_ROUTE HRIS_MENUS.ROUTE%TYPE,
    P_ACTION HRIS_MENUS.ACTION%TYPE,
    P_PARENT_MENU HRIS_MENUS.PARENT_MENU%TYPE,
    P_MENU_INDEX HRIS_MENUS.MENU_INDEX%TYPE,
    P_ICON_CLASS HRIS_MENUS.ICON_CLASS%TYPE,
    P_IS_VISIBLE HRIS_MENUS.IS_VISIBLE%TYPE)
AS
  ALREADY_EXISTS CHAR;
  V_MENU_ID HRIS_MENUS.MENU_ID%TYPE;
BEGIN
  SELECT (
    CASE
      WHEN COUNT(*) >0
      THEN 'Y'
      ELSE 'N'
    END)
  INTO ALREADY_EXISTS
  FROM HRIS_MENUS
  WHERE PARENT_MENU =P_PARENT_MENU
  AND ROUTE         =P_ROUTE
  AND ACTION        = P_ACTION;
  IF ALREADY_EXISTS ='N' THEN
    SELECT NVL(MAX(MENU_ID),0)+1 INTO V_MENU_ID FROM HRIS_MENUS;
    INSERT
    INTO HRIS_MENUS
      (
        MENU_ID,
        MENU_NAME,
        PARENT_MENU,
        ROUTE,
        STATUS,
        CREATED_DT,
        ICON_CLASS,
        ACTION,
        MENU_INDEX,
        IS_VISIBLE
      )
      VALUES
      (
        V_MENU_ID,
        P_MENU_NAME,
        P_PARENT_MENU,
        P_ROUTE,
        'E',
        TRUNC(SYSDATE),
        P_ICON_CLASS,
        P_ACTION,
        P_MENU_INDEX,
        P_IS_VISIBLE
      );
  END IF;
END;/
            CREATE OR REPLACE PROCEDURE HRIS_JOBS_PROC
AS
BEGIN
  FOR jobs IN
  (SELECT * FROM HRIS_JOBS WHERE EXECUTED ='N'
  )
  LOOP
    BEGIN
      EXECUTE IMMEDIATE jobs.WHAT;
      UPDATE HRIS_JOBS
      SET EXECUTED  ='Y',
        STATUS      ='S',
        EXECUTED_AT =SYSDATE
      WHERE JOB_ID  = jobs.JOB_ID;
    EXCEPTION
    WHEN OTHERS THEN
      UPDATE HRIS_JOBS
      SET EXECUTED  ='Y',
        STATUS      ='F',
        EXECUTED_AT =SYSDATE
      WHERE JOB_ID  = jobs.JOB_ID;
    END;
COMMIT;
  END LOOP;
END;/
            CREATE OR REPLACE PROCEDURE HRIS_LATE_LEAVE_DEDUCTION(
    P_COMPANY_ID HRIS_PENALIZED_MONTHS.COMPANY_ID%TYPE,
    P_FISCAL_YEAR_ID HRIS_PENALIZED_MONTHS.FISCAL_YEAR_ID%TYPE,
    P_FISCAL_YEAR_MONTH_NO HRIS_PENALIZED_MONTHS.FISCAL_YEAR_MONTH_NO%TYPE,
    P_DEDUCTION_DAY FLOAT,
    P_EMPLOYEE_ID HRIS_EMPLOYEES.EMPLOYEE_ID%TYPE,
    P_ACTION CHAR)
AS
  V_MONTH_START_DATE HRIS_MONTH_CODE.FROM_DATE%TYPE;
  V_MONTH_END_DATE HRIS_MONTH_CODE.TO_DATE %TYPE;
  V_LEAVE_ID HRIS_LEAVE_MASTER_SETUP.LEAVE_ID%TYPE;
  V_ALREADY_DEDUCTED_FLAG CHAR(1 BYTE);
BEGIN
  SELECT FROM_DATE,
    TO_DATE
  INTO V_MONTH_START_DATE,
    V_MONTH_END_DATE
  FROM HRIS_MONTH_CODE
  WHERE FISCAL_YEAR_ID    = P_FISCAL_YEAR_ID
  AND FISCAL_YEAR_MONTH_NO=P_FISCAL_YEAR_MONTH_NO;
  --
  DELETE
  FROM HRIS_EMPLOYEE_PENALTY_DAYS
  WHERE (TRUNC(ATTENDANCE_DT) BETWEEN V_MONTH_START_DATE AND V_MONTH_END_DATE)
  AND EMPLOYEE_ID IN
    (SELECT EMPLOYEE_ID
    FROM HRIS_EMPLOYEES
    WHERE HRIS_EMPLOYEES.COMPANY_ID=P_COMPANY_ID
    );
  IF(P_ACTION ='D') THEN
    DELETE
    FROM HRIS_PENALIZED_MONTHS
    WHERE FISCAL_YEAR_ID     =P_FISCAL_YEAR_ID
    AND FISCAL_YEAR_MONTH_NO =P_FISCAL_YEAR_MONTH_NO
    AND COMPANY_ID           =P_COMPANY_ID;
    RETURN;
  END IF;
  --
  SELECT (
    CASE
      WHEN COUNT(*) >0
      THEN 'Y'
      ELSE 'N'
    END)
  INTO V_ALREADY_DEDUCTED_FLAG
  FROM HRIS_PENALIZED_MONTHS
  WHERE FISCAL_YEAR_ID     =P_FISCAL_YEAR_ID
  AND FISCAL_YEAR_MONTH_NO =P_FISCAL_YEAR_MONTH_NO
  AND COMPANY_ID           =P_COMPANY_ID;
  --
  IF V_ALREADY_DEDUCTED_FLAG ='Y' THEN
    UPDATE HRIS_PENALIZED_MONTHS
    SET NO_OF_DAYS           =P_DEDUCTION_DAY,
      MODIFIED_DATE          =TRUNC(SYSDATE),
      MODIFIED_BY            = P_EMPLOYEE_ID
    WHERE FISCAL_YEAR_ID     =P_FISCAL_YEAR_ID
    AND FISCAL_YEAR_MONTH_NO =P_FISCAL_YEAR_MONTH_NO
    AND COMPANY_ID           =P_COMPANY_ID;
  ELSE
    INSERT
    INTO HRIS_PENALIZED_MONTHS
      (
        FISCAL_YEAR_ID,
        FISCAL_YEAR_MONTH_NO,
        COMPANY_ID,
        NO_OF_DAYS,
        CREATED_DATE,
        CREATED_BY
      )
      VALUES
      (
        P_FISCAL_YEAR_ID,
        P_FISCAL_YEAR_MONTH_NO,
        P_COMPANY_ID,
        P_DEDUCTION_DAY,
        TRUNC(SYSDATE),
        P_EMPLOYEE_ID
      );
  END IF;
  --
  FOR attendance IN
  (SELECT         *
    FROM HRIS_ATTENDANCE_DETAIL
    WHERE (TRUNC(ATTENDANCE_DT) BETWEEN V_MONTH_START_DATE AND V_MONTH_END_DATE)
    AND OVERALL_STATUS IN ('BA','LA')
    AND EMPLOYEE_ID    IN
      (SELECT EMPLOYEE_ID
      FROM HRIS_EMPLOYEES
      WHERE HRIS_EMPLOYEES.COMPANY_ID=P_COMPANY_ID
      )
  )
  LOOP
    BEGIN
      SELECT LEAVE_ID
      INTO V_LEAVE_ID
      FROM
        (SELECT L.LEAVE_ID
        FROM HRIS_EMPLOYEE_LEAVE_ASSIGN LA
        JOIN HRIS_LEAVE_MASTER_SETUP L
        ON (LA.LEAVE_ID      =L.LEAVE_ID)
        WHERE LA.EMPLOYEE_ID =attendance.EMPLOYEE_ID
        AND LA.BALANCE      >=P_DEDUCTION_DAY
        ORDER BY L.DEDUCTION_PRIORITY_NO
        )
      WHERE ROWNUM=1;
    EXCEPTION
    WHEN no_data_found THEN
      SELECT LEAVE_ID
      INTO V_LEAVE_ID
      FROM
        (SELECT L.LEAVE_ID
        FROM HRIS_EMPLOYEE_LEAVE_ASSIGN LA
        JOIN HRIS_LEAVE_MASTER_SETUP L
        ON (LA.LEAVE_ID      =L.LEAVE_ID)
        WHERE LA.EMPLOYEE_ID =attendance.EMPLOYEE_ID
        ORDER BY L.DEDUCTION_PRIORITY_NO
        )
      WHERE ROWNUM=1;
    END;
    --
    INSERT
    INTO HRIS_EMPLOYEE_PENALTY_DAYS
      (
        EMPLOYEE_ID,
        ATTENDANCE_DT,
        LEAVE_ID,
        NO_OF_DAYS,
        REMARKS,
        CREATED_DATE
      )
      VALUES
      (
        attendance.EMPLOYEE_ID,
        attendance.ATTENDANCE_DT,
        V_LEAVE_ID,
        P_DEDUCTION_DAY,
        '4th day penalty',
        TRUNC(SYSDATE)
      );
  END LOOP;
END;/
            CREATE OR REPLACE PROCEDURE HRIS_MANUAL_ATTENDANCE(
    P_EMPLOYEE_ID HRIS_ATTENDANCE_DETAIL.EMPLOYEE_ID%TYPE ,
    P_ATTENDANCE_DT HRIS_ATTENDANCE_DETAIL.ATTENDANCE_DT%TYPE,
    P_STATUS CHAR,
    P_SHIFT_ID NUMBER :=NULL,
    P_IN_TIME DATE :=NULL,
    P_OUT_TIME DATE :=NULL
    )
AS
  V_WEEK_DAY NUMBER(1);
    V_DYNAMIC_SQL VARCHAR2(1000 BYTE);
    V_TO_TIME TIMESTAMP;
BEGIN
 
IF(P_SHIFT_ID != '0' AND P_SHIFT_ID IS NOT NULL)
THEN
BEGIN
DELETE  FROM HRIS_EMPLOYEE_SHIFT_ROASTER WHERE EMPLOYEE_ID=P_EMPLOYEE_ID AND FOR_DATE=P_ATTENDANCE_DT;

INSERT INTO HRIS_EMPLOYEE_SHIFT_ROASTER
VALUES (P_EMPLOYEE_ID,P_SHIFT_ID,P_ATTENDANCE_DT,NULL,NULL,NULL,NULL);
END;
END IF;

select to_char(p_attendance_dt, 'd') INTO V_WEEK_DAY from dual;

  FOR attendance IN
  (SELECT A.EMPLOYEE_ID,
    A.ATTENDANCE_DT,
    S.START_TIME,
    S.END_TIME,
    A.OVERALL_STATUS,
    s.shift_id
  FROM HRIS_ATTENDANCE_DETAIL A
  JOIN HRIS_SHIFTS S
  ON (A.SHIFT_ID   =S.SHIFT_ID)
  WHERE A.EMPLOYEE_ID=P_EMPLOYEE_ID
  AND A.ATTENDANCE_DT= P_ATTENDANCE_DT
  AND A.OVERALL_STATUS IN ('AB','PR','BA','LA')
  )
  LOOP
  
   V_TO_TIME:=attendance.end_time;
    
    
    -- begin for overriding halfday case
    
    V_DYNAMIC_SQL:='SELECT CASE WHEN WEEKDAY'||V_WEEK_DAY||'=''H'' AND HALF_DAY_OUT_TIME IS NOT NULL  THEN
            HALF_DAY_OUT_TIME
            ELSE
            end_time
            END
            FROM  HRIS_SHIFTS WHERE SHIFT_ID='||attendance.shift_id;
            
            execute immediate V_DYNAMIC_SQL into V_TO_TIME;

    -- end for overriding halfday case
  
  
  
    IF P_STATUS ='P' THEN
      INSERT
      INTO HRIS_ATTENDANCE
        (
          EMPLOYEE_ID,
          ATTENDANCE_DT,
          ATTENDANCE_TIME,
          ATTENDANCE_FROM
        )
        VALUES
        (
          attendance.EMPLOYEE_ID,
          attendance.ATTENDANCE_DT,
          CASE WHEN P_IN_TIME IS NOT NULL
          THEN
          TO_DATE(TO_CHAR(attendance.ATTENDANCE_DT,'DD-MON-YYYY')
          ||' '
          ||TO_CHAR(P_IN_TIME,'HH24:MI'),'DD-MON-YYYY HH24:MI' )
          ELSE
          TO_DATE(TO_CHAR(attendance.ATTENDANCE_DT,'DD-MON-YYYY')
          ||' '
          ||TO_CHAR(attendance.START_TIME,'HH24:MI'),'DD-MON-YYYY HH24:MI' )
          END,
          'SYSTEM'
        );
      INSERT
      INTO HRIS_ATTENDANCE
        (
          EMPLOYEE_ID,
          ATTENDANCE_DT,
          ATTENDANCE_TIME,
          ATTENDANCE_FROM
        )
        VALUES
        (
          attendance.EMPLOYEE_ID,
          attendance.ATTENDANCE_DT,
          CASE WHEN P_OUT_TIME IS NOT NULL
          THEN
          TO_DATE(TO_CHAR(attendance.ATTENDANCE_DT,'DD-MON-YYYY')
          ||' '
          ||TO_CHAR(P_OUT_TIME,'HH24:MI'),'DD-MON-YYYY HH24:MI' )
          ELSE
          TO_DATE(TO_CHAR(attendance.ATTENDANCE_DT,'DD-MON-YYYY')
          ||' '
          ||TO_CHAR(V_TO_TIME,'HH24:MI'),'DD-MON-YYYY HH24:MI' )
          END,
          'SYSTEM'
        );
    END IF;
    IF P_STATUS ='A' THEN
      DELETE
      FROM HRIS_ATTENDANCE
      WHERE EMPLOYEE_ID=P_EMPLOYEE_ID
      AND ATTENDANCE_DT= P_ATTENDANCE_DT
      AND ATTENDANCE_FROM='SYSTEM';
    END IF ;
    HRIS_REATTENDANCE(attendance.ATTENDANCE_DT,attendance.EMPLOYEE_ID,attendance.ATTENDANCE_DT);
  END LOOP;
END;/
            CREATE OR REPLACE PROCEDURE HRIS_MANUAL_ATTENDANCE_ALL(
    P_EMPLOYEE_ID HRIS_ATTENDANCE_DETAIL.EMPLOYEE_ID%TYPE ,
    P_ATTENDANCE_DT HRIS_ATTENDANCE_DETAIL.ATTENDANCE_DT%TYPE,
    P_STATUS CHAR,
    P_SHIFT_ID NUMBER :=NULL,
    P_IN_TIME DATE :=NULL,
    P_OUT_TIME DATE :=NULL)
AS
    V_WEEK_DAY NUMBER(1);
    V_DYNAMIC_SQL VARCHAR2(1000 BYTE);
    V_TO_TIME TIMESTAMP;
BEGIN

IF(P_SHIFT_ID != '0' AND P_SHIFT_ID IS NOT NULL) 
THEN
BEGIN
DELETE  FROM HRIS_EMPLOYEE_SHIFT_ROASTER WHERE EMPLOYEE_ID=P_EMPLOYEE_ID AND FOR_DATE=P_ATTENDANCE_DT;

INSERT INTO HRIS_EMPLOYEE_SHIFT_ROASTER
VALUES (P_EMPLOYEE_ID,P_SHIFT_ID,P_ATTENDANCE_DT,NULL,NULL,NULL,NULL);

END;
END IF;

select to_char(P_ATTENDANCE_DT, 'd') INTO V_WEEK_DAY from dual;


  FOR attendance IN
  (SELECT A.EMPLOYEE_ID,
    A.ATTENDANCE_DT,
    S.START_TIME,
    S.END_TIME,
    A.OVERALL_STATUS,
    s.shift_id
  FROM HRIS_ATTENDANCE_DETAIL A
  JOIN HRIS_SHIFTS S
  ON (A.SHIFT_ID     =S.SHIFT_ID)
  WHERE A.EMPLOYEE_ID=P_EMPLOYEE_ID
  AND A.ATTENDANCE_DT= P_ATTENDANCE_DT
  )
  LOOP
  V_TO_TIME:=attendance.end_time;
  
  -- begin for overriding halfday case
  
  V_DYNAMIC_SQL:='SELECT CASE WHEN WEEKDAY'||V_WEEK_DAY||'=''H'' AND HALF_DAY_OUT_TIME IS NOT NULL  THEN
            HALF_DAY_OUT_TIME
            ELSE
            end_time
            END
            FROM  HRIS_SHIFTS WHERE SHIFT_ID='||attendance.shift_id;
            
            execute immediate V_DYNAMIC_SQL into V_TO_TIME;

    -- end for overriding halfday case
  
  
    IF P_STATUS ='P' THEN
      INSERT
      INTO HRIS_ATTENDANCE
        (
          EMPLOYEE_ID,
          ATTENDANCE_DT,
          ATTENDANCE_TIME,
          ATTENDANCE_FROM
        )
        VALUES
        (
          attendance.EMPLOYEE_ID,
          attendance.ATTENDANCE_DT,
          CASE WHEN P_IN_TIME IS NOT NULL
          THEN
          TO_DATE(TO_CHAR(attendance.ATTENDANCE_DT,'DD-MON-YYYY')
          ||' '
          ||TO_CHAR(P_IN_TIME,'HH24:MI'),'DD-MON-YYYY HH24:MI' )
          ELSE
          TO_DATE(TO_CHAR(attendance.ATTENDANCE_DT,'DD-MON-YYYY')
          ||' '
          ||TO_CHAR(attendance.START_TIME,'HH24:MI'),'DD-MON-YYYY HH24:MI' )
          END,
          'SYSTEM'
        );
      INSERT
      INTO HRIS_ATTENDANCE
        (
          EMPLOYEE_ID,
          ATTENDANCE_DT,
          ATTENDANCE_TIME,
          ATTENDANCE_FROM
        )
        VALUES
        (
          attendance.EMPLOYEE_ID,
          attendance.ATTENDANCE_DT,
          CASE WHEN P_OUT_TIME IS NOT NULL
          THEN
          TO_DATE(TO_CHAR(attendance.ATTENDANCE_DT,'DD-MON-YYYY')
          ||' '
          ||TO_CHAR(P_OUT_TIME,'HH24:MI'),'DD-MON-YYYY HH24:MI' )
          ELSE
          TO_DATE(TO_CHAR(attendance.ATTENDANCE_DT,'DD-MON-YYYY')
          ||' '
          ||TO_CHAR(V_TO_TIME,'HH24:MI'),'DD-MON-YYYY HH24:MI' )
          END,
          'SYSTEM'
        );
    END IF;
    IF P_STATUS ='A' THEN
    BEGIN
    DELETE  FROM HRIS_EMPLOYEE_SHIFT_ROASTER WHERE EMPLOYEE_ID=P_EMPLOYEE_ID AND FOR_DATE=P_ATTENDANCE_DT;
    
    
      DELETE
      FROM HRIS_ATTENDANCE
      WHERE EMPLOYEE_ID=P_EMPLOYEE_ID
      AND ATTENDANCE_DT= P_ATTENDANCE_DT
      AND ATTENDANCE_FROM='SYSTEM';
      END;
    END IF ;
    HRIS_REATTENDANCE(attendance.ATTENDANCE_DT,attendance.EMPLOYEE_ID,attendance.ATTENDANCE_DT);
  END LOOP;
END;/
            CREATE OR REPLACE PROCEDURE HRIS_MENU_ROLE_ASSIGN(
    P_MENU_ID     NUMBER,
    P_ROLE_ID     NUMBER,
    P_ASSIGN_FLAG CHAR )
AS
  V_EXIST_FLAG CHAR(1 BYTE);
BEGIN
  IF P_ASSIGN_FLAG ='Y' THEN
    FOR childs IN
    (SELECT MENU_ID,
      MENU_NAME,
      PARENT_MENU,
      STATUS,
      LEVEL
    FROM HRIS_MENUS
    WHERE STATUS             ='E'
      START WITH MENU_ID     =P_MENU_ID
      CONNECT BY PARENT_MENU = PRIOR MENU_ID
    ORDER SIBLINGS BY MENU_ID
    )
    LOOP
      SELECT (
        CASE
          WHEN COUNT(*) >0
          THEN 'Y'
          ELSE 'N'
        END)
      INTO V_EXIST_FLAG
      FROM HRIS_ROLE_PERMISSIONS
      WHERE MENU_ID   = childs.MENU_ID
      AND ROLE_ID     = P_ROLE_ID;
      IF(V_EXIST_FLAG = 'N') THEN
        INSERT
        INTO HRIS_ROLE_PERMISSIONS
          (
            ROLE_ID,
            MENU_ID,
            STATUS,
            CREATED_DT
          )
          VALUES
          (
            P_ROLE_ID,
            childs.MENU_ID,
            'E',
            TRUNC(SYSDATE)
          );
      END IF;
    END LOOP;
    FOR childs IN
    (SELECT MENU_ID,
        MENU_NAME,
        PARENT_MENU,
        STATUS,
        LEVEL
      FROM HRIS_MENUS
      WHERE STATUS                   ='E'
        START WITH MENU_ID           =P_MENU_ID
        CONNECT BY PRIOR PARENT_MENU = MENU_ID
      ORDER SIBLINGS BY MENU_ID
    )
    LOOP
      SELECT (
        CASE
          WHEN COUNT(*) >0
          THEN 'Y'
          ELSE 'N'
        END)
      INTO V_EXIST_FLAG
      FROM HRIS_ROLE_PERMISSIONS
      WHERE MENU_ID   = childs.MENU_ID
      AND ROLE_ID     = P_ROLE_ID;
      IF(V_EXIST_FLAG = 'N') THEN
        INSERT
        INTO HRIS_ROLE_PERMISSIONS
          (
            ROLE_ID,
            MENU_ID,
            STATUS,
            CREATED_DT
          )
          VALUES
          (
            P_ROLE_ID,
            childs.MENU_ID,
            'E',
            TRUNC(SYSDATE)
          );
      END IF;
    END LOOP;
  ELSE
    FOR childs IN
    (SELECT MENU_ID,
        MENU_NAME,
        PARENT_MENU,
        STATUS,
        LEVEL
      FROM HRIS_MENUS
      WHERE STATUS             ='E'
        START WITH MENU_ID     =P_MENU_ID
        CONNECT BY PARENT_MENU = PRIOR MENU_ID
      ORDER SIBLINGS BY MENU_ID
    )
    LOOP
      DELETE
      FROM HRIS_ROLE_PERMISSIONS
      WHERE ROLE_ID =P_ROLE_ID
      AND MENU_ID   = childs.MENU_ID;
    END LOOP;
  END IF;
END;/
            create or replace PROCEDURE HRIS_NEWS_BROADCAST(
    P_NEWS_DATE HRIS_NEWS.NEWS_DATE%TYPE,
    P_NEWS_TYPE HRIS_NEWS.NEWS_TYPE%TYPE,
    P_SERVICE_TYPE_ID NUMBER,
    P_EMPLOYEE_ID     NUMBER,
    P_DESC HRIS_NEWS.NEWS_EDESC%TYPE)
AS
  P_NEWS_ID HRIS_NEWS.NEWS_ID%TYPE;
  P_NEWS_TITLE HRIS_NEWS.NEWS_TITLE%TYPE;
  P_NEWS_EDESC HRIS_NEWS.NEWS_EDESC%TYPE;
  P_STATUS HRIS_NEWS.STATUS%TYPE:='E';
  P_EMPLOYEE_NAME VARCHAR2(255 BYTE);
BEGIN
  SELECT NVL(MAX (NEWS_ID),0) + 1 INTO P_NEWS_ID FROM HRIS_NEWS;
  SELECT SERVICE_EVENT_TYPE_NAME
  INTO P_NEWS_TITLE
  FROM HRIS_SERVICE_EVENT_TYPES
  WHERE SERVICE_EVENT_TYPE_ID=P_SERVICE_TYPE_ID;
  SELECT (FIRST_NAME
    ||' '
    ||MIDDLE_NAME
    ||' '
    ||LAST_NAME)
  INTO P_EMPLOYEE_NAME
  FROM HRIS_EMPLOYEES
  WHERE EMPLOYEE_ID=P_EMPLOYEE_ID;
  CASE P_SERVICE_TYPE_ID
  WHEN 1 THEN
    P_NEWS_EDESC:=CONCAT(P_EMPLOYEE_NAME,CONCAT(' Has Been Transfered',P_DESC));
  WHEN 2 THEN
    P_NEWS_EDESC:=CONCAT(P_EMPLOYEE_NAME,CONCAT(' Has Been Appointed',P_DESC));
  WHEN 3 THEN
    P_NEWS_EDESC:=CONCAT(P_EMPLOYEE_NAME,CONCAT(' Has Been Pramoted',P_DESC));
  WHEN 4 THEN
    P_NEWS_EDESC:=CONCAT(P_EMPLOYEE_NAME,CONCAT(' Has Been Demoted',P_DESC));
  WHEN 5 THEN
    P_NEWS_EDESC:=CONCAT(P_EMPLOYEE_NAME,CONCAT(' Has Resigned',P_DESC));
  WHEN 8 THEN
    P_NEWS_EDESC:=CONCAT(P_EMPLOYEE_NAME,CONCAT(' Has Retired',P_DESC));
  WHEN 14 THEN
    P_NEWS_EDESC:=CONCAT(P_EMPLOYEE_NAME,CONCAT(' Has Been Suspended',P_DESC));
  WHEN 15 THEN
    P_NEWS_EDESC:=CONCAT(P_EMPLOYEE_NAME,CONCAT(' is Temporary Assigned',P_DESC));
  WHEN 16 THEN
    P_NEWS_EDESC:=CONCAT(P_EMPLOYEE_NAME,CONCAT(' Awarded',P_DESC));
  ELSE
    P_NEWS_EDESC:=P_NEWS_TITLE;
  END CASE;
  INSERT
  INTO HRIS_NEWS
    (
      NEWS_ID,
      NEWS_DATE,
      NEWS_TYPE,
      NEWS_TITLE,
      NEWS_EDESC,

      CREATED_BY,
      STATUS
    )
    VALUES
    (
      P_NEWS_ID,
      P_NEWS_DATE,
      P_NEWS_TYPE,
      P_NEWS_TITLE,
      P_NEWS_EDESC,

      P_EMPLOYEE_ID,
      P_STATUS
    );
END;/
            CREATE OR REPLACE PROCEDURE HRIS_NEWS_TO_PROC(
    P_NEWS_ID HRIS_NEWS.NEWS_ID%TYPE,
    P_EMPLOYEE_ID HRIS_EMPLOYEES.EMPLOYEE_ID%TYPE:=NULL)
AS
  V_ASSIGN_COUNT           NUMBER;
  V_COMPANY_IDS            VARCHAR2(1000 BYTE) :=NULL;
  V_BRANCH_IDS             VARCHAR2(1000 BYTE) :=NULL;
  V_DEPARTMENT_IDS         VARCHAR2(1000 BYTE) :=NULL;
  V_DESIGNATION_IDS        VARCHAR2(1000 BYTE) :=NULL;
  V_POSITION_IDS           VARCHAR2(1000 BYTE) :=NULL;
  V_SERVICE_TYPE_IDS       VARCHAR2(1000 BYTE) :=NULL;
  V_SERVICE_EVENT_TYPE_IDS VARCHAR2(1000 BYTE) :=NULL;
  V_EMPLOYEE_TYPES         VARCHAR2(1000 BYTE) :=NULL;
  V_GENDER_IDS             VARCHAR2(1000 BYTE) :=NULL;
  V_EMPLOYEE_IDS           VARCHAR2(1000 BYTE) :=NULL;
  V_QUERY                  VARCHAR2(1000 BYTE);
  V_EMPLOYEE_ID            NUMBER;
  --
TYPE cur_typ
IS
  REF
  CURSOR;
    c cur_typ;
  BEGIN
    --
    DELETE
    FROM HRIS_NEWS_EMPLOYEE
    WHERE NEWS_ID    =P_NEWS_ID
    AND (EMPLOYEE_ID =
      CASE
        WHEN P_EMPLOYEE_ID IS NOT NULL
        THEN P_EMPLOYEE_ID
      END
    OR P_EMPLOYEE_ID IS NULL);
  --
  SELECT COUNT(*)
  INTO V_ASSIGN_COUNT
  FROM HRIS_NEWS_TO
  WHERE NEWS_ID=P_NEWS_ID;
  --
  IF(V_ASSIGN_COUNT    =0) THEN
    IF (P_EMPLOYEE_ID IS NULL) THEN
      V_QUERY         :='SELECT EMPLOYEE_ID
FROM HRIS_EMPLOYEES
WHERE STATUS     = ''E''';
    ELSE
      V_QUERY :='SELECT EMPLOYEE_ID
FROM HRIS_EMPLOYEES
WHERE STATUS     = ''E''
AND EMPLOYEE_ID = '||P_EMPLOYEE_ID;
    END IF;
  ELSE
    FOR assign IN
    (SELECT * FROM HRIS_NEWS_TO WHERE NEWS_ID= P_NEWS_ID
    )
    LOOP
      IF(assign.COMPANY_ID IS NOT NULL)THEN
        IF(V_COMPANY_IDS   IS NULL)THEN
          V_COMPANY_IDS    :=V_COMPANY_IDS||assign.COMPANY_ID;
        ELSE
          V_COMPANY_IDS :=V_COMPANY_IDS||','||assign.COMPANY_ID;
        END IF;
      END IF;
      --
      IF(assign.BRANCH_ID IS NOT NULL)THEN
        IF(V_BRANCH_IDS   IS NULL)THEN
          V_BRANCH_IDS    :=V_BRANCH_IDS||assign.BRANCH_ID;
        ELSE
          V_BRANCH_IDS :=V_BRANCH_IDS||','||assign.BRANCH_ID;
        END IF;
      END IF;
      --
      IF(assign.DEPARTMENT_ID IS NOT NULL)THEN
        IF(V_DEPARTMENT_IDS   IS NULL)THEN
          V_DEPARTMENT_IDS    :=V_DEPARTMENT_IDS||assign.DEPARTMENT_ID;
        ELSE
          V_DEPARTMENT_IDS :=V_DEPARTMENT_IDS||','||assign.DEPARTMENT_ID;
        END IF;
      END IF;
      --
      IF(assign.DESIGNATION_ID IS NOT NULL)THEN
        IF(V_DESIGNATION_IDS   IS NULL)THEN
          V_DESIGNATION_IDS    :=V_DESIGNATION_IDS||assign.DESIGNATION_ID;
        ELSE
          V_DESIGNATION_IDS :=V_DESIGNATION_IDS||','||assign.DESIGNATION_ID;
        END IF;
      END IF;
      --
      IF(assign.POSITION_ID IS NOT NULL)THEN
        IF(V_POSITION_IDS   IS NULL)THEN
          V_POSITION_IDS    :=V_POSITION_IDS||assign.POSITION_ID;
        ELSE
          V_POSITION_IDS :=V_POSITION_IDS||','||assign.POSITION_ID;
        END IF;
      END IF;
      --
      IF(assign.SERVICE_TYPE_ID IS NOT NULL)THEN
        IF(V_SERVICE_TYPE_IDS   IS NULL)THEN
          V_SERVICE_TYPE_IDS    :=V_SERVICE_TYPE_IDS||assign.SERVICE_TYPE_ID;
        ELSE
          V_SERVICE_TYPE_IDS :=V_SERVICE_TYPE_IDS||','||assign.SERVICE_TYPE_ID;
        END IF;
      END IF;
      --
      IF(assign.SERVICE_EVENT_TYPE_ID IS NOT NULL)THEN
        IF(V_SERVICE_EVENT_TYPE_IDS   IS NULL)THEN
          V_SERVICE_EVENT_TYPE_IDS    :=V_SERVICE_EVENT_TYPE_IDS||assign.SERVICE_EVENT_TYPE_ID;
        ELSE
          V_SERVICE_EVENT_TYPE_IDS :=V_SERVICE_EVENT_TYPE_IDS||','||assign.SERVICE_EVENT_TYPE_ID;
        END IF;
      END IF;
      --
      IF(assign.EMPLOYEE_TYPE IS NOT NULL)THEN
        IF(V_EMPLOYEE_TYPES   IS NULL)THEN
          V_EMPLOYEE_TYPES    :=V_EMPLOYEE_TYPES||'''' ||assign.EMPLOYEE_TYPE||'''';
        ELSE
          V_EMPLOYEE_TYPES :=V_EMPLOYEE_TYPES||','''||assign.EMPLOYEE_TYPE||'''';
        END IF;
      END IF;
      --
      IF(assign.GENDER_ID IS NOT NULL)THEN
        IF(V_GENDER_IDS   IS NULL)THEN
          V_GENDER_IDS    :=V_GENDER_IDS||assign.GENDER_ID;
        ELSE
          V_GENDER_IDS :=V_GENDER_IDS||','||assign.GENDER_ID;
        END IF;
      END IF;
      --
      IF(assign.EMPLOYEE_ID IS NOT NULL)THEN
        IF(V_EMPLOYEE_IDS   IS NULL)THEN
          V_EMPLOYEE_IDS    :=V_EMPLOYEE_IDS||assign.EMPLOYEE_ID;
        ELSE
          V_EMPLOYEE_IDS :=V_EMPLOYEE_IDS||','||assign.EMPLOYEE_ID;
        END IF;
      END IF;
      --
    END LOOP;
    --
    V_QUERY          := 'SELECT EMPLOYEE_ID FROM HRIS_EMPLOYEES WHERE 1=1';
    IF(V_COMPANY_IDS IS NOT NULL) THEN
      V_QUERY        :=V_QUERY||' AND COMPANY_ID IN ('||V_COMPANY_IDS||')';
    END IF;
    IF(V_BRANCH_IDS IS NOT NULL) THEN
      V_QUERY       :=V_QUERY||' AND BRANCH_ID IN ('||V_BRANCH_IDS||')';
    END IF;
    IF(V_DEPARTMENT_IDS IS NOT NULL) THEN
      V_QUERY           :=V_QUERY||' AND DEPARTMENT_ID IN ('||V_DEPARTMENT_IDS||')';
    END IF;
    IF(V_DESIGNATION_IDS IS NOT NULL) THEN
      V_QUERY            :=V_QUERY||' AND DESIGNATION_ID IN ('||V_DESIGNATION_IDS||')';
    END IF;
    IF(V_POSITION_IDS IS NOT NULL) THEN
      V_QUERY         :=V_QUERY||' AND POSITION_ID IN ('||V_POSITION_IDS||')';
    END IF;
    IF(V_SERVICE_TYPE_IDS IS NOT NULL) THEN
      V_QUERY             :=V_QUERY||' AND SERVICE_TYPE_ID IN ('||V_SERVICE_TYPE_IDS||')';
    END IF;
    IF(V_SERVICE_EVENT_TYPE_IDS IS NOT NULL) THEN
      V_QUERY                   :=V_QUERY||' AND SERVICE_EVENT_TYPE_ID IN ('||V_SERVICE_EVENT_TYPE_IDS||')';
    END IF;
    IF(V_EMPLOYEE_TYPES IS NOT NULL) THEN
      V_QUERY           :=V_QUERY||' AND EMPLOYEE_TYPE IN ('||V_EMPLOYEE_TYPES||')';
    END IF;
    IF(V_GENDER_IDS IS NOT NULL) THEN
      V_QUERY       :=V_QUERY||' AND GENDER_ID IN ('||V_GENDER_IDS||')';
    END IF;
    IF(V_EMPLOYEE_IDS IS NOT NULL) THEN
      V_QUERY         :=V_QUERY||' AND EMPLOYEE_ID IN ('||V_EMPLOYEE_IDS||')';
    END IF;
  END IF;
  --
  OPEN c FOR V_QUERY ;
  LOOP
    FETCH c INTO V_EMPLOYEE_ID;
    EXIT
  WHEN c%NOTFOUND;
    IF(P_EMPLOYEE_ID  IS NOT NULL )THEN
      IF P_EMPLOYEE_ID = V_EMPLOYEE_ID THEN
        INSERT
        INTO HRIS_NEWS_EMPLOYEE
          (
            NEWS_ID,
            EMPLOYEE_ID
          )
          VALUES
          (
            P_NEWS_ID,
            V_EMPLOYEE_ID
          );
      END IF;
    ELSE
      INSERT
      INTO HRIS_NEWS_EMPLOYEE
        (
          NEWS_ID,
          EMPLOYEE_ID
        )
        VALUES
        (
          P_NEWS_ID,
          V_EMPLOYEE_ID
        );
    END IF;
  END LOOP;
  CLOSE c;
END;/
            CREATE OR REPLACE PROCEDURE HRIS_NOTIFY_BIRTHDAYS(
    P_DATETIME DATE:=NULL)
AS
BEGIN
  FOR birthday_employees IN
  (SELECT EMP.EMPLOYEE_ID,
    EMP.FULL_NAME,
    EMP.BIRTH_DATE,
    TO_CHAR(EMP.BIRTH_DATE, 'fmddth Month') EMP_BIRTH_DATE
  FROM HRIS_EMPLOYEES EMP
  WHERE TO_CHAR(EMP.BIRTH_DATE, 'MMDD') = TO_CHAR(SYSDATE,'MMDD')
  AND EMP.RETIRED_FLAG                  = 'N'
  AND EMP.STATUS                        ='E'
  )
  LOOP
    BEGIN
      FOR all_employees IN
      (SELECT EMPLOYEE_ID
      FROM HRIS_EMPLOYEES
      WHERE RETIRED_FLAG ='N'
      AND STATUS         ='E'
      )
      LOOP
        IF birthday_employees.EMPLOYEE_ID = all_employees.EMPLOYEE_ID THEN
          HRIS_SYSTEM_NOTIFICATION(all_employees.EMPLOYEE_ID,SYSDATE,'Birthday','Happy Birthday '||birthday_employees.FULL_NAME||'. Have a nice day.' ,'{"route":"birthday","action":"wish","id":"'||birthday_employees.EMPLOYEE_ID||'"}');
        ELSE
          HRIS_SYSTEM_NOTIFICATION(all_employees.EMPLOYEE_ID,SYSDATE,'Birthday',birthday_employees.FULL_NAME||' has birthday today.','{"route":"birthday","action":"wish","id":"'||birthday_employees.EMPLOYEE_ID||'"}');
        END IF;
      END LOOP;
    END;
  END LOOP;
END;/
            create or replace PROCEDURE HRIS_OT_MANUAL_CR_OR_UP(
    V_MONTH_ID HRIS_MONTH_CODE.MONTH_ID%TYPE ,
    V_MONTH_DAY NUMBER ,
    V_EMPLOYEE_ID HRIS_OVERTIME_MANUAL.EMPLOYEE_ID%TYPE ,
    V_OVERTIME_HOUR HRIS_OVERTIME_MANUAL.OVERTIME_HOUR%TYPE)
AS
  V_ATTENDANCE_DATE HRIS_OVERTIME_MANUAL.ATTENDANCE_DATE%TYPE;
  V_ROW_COUNT NUMBER;
BEGIN
  SELECT FROM_DATE+V_MONTH_DAY -1
  INTO V_ATTENDANCE_DATE
  FROM HRIS_MONTH_CODE
  WHERE MONTH_ID =V_MONTH_ID;
  --
  SELECT COUNT(*)
  INTO V_ROW_COUNT
  FROM HRIS_OVERTIME_MANUAL
  WHERE ATTENDANCE_DATE =V_ATTENDANCE_DATE
  AND EMPLOYEE_ID       = V_EMPLOYEE_ID;
  IF (V_ROW_COUNT       >0 ) THEN
    UPDATE HRIS_OVERTIME_MANUAL
    SET OVERTIME_HOUR     =V_OVERTIME_HOUR
    WHERE ATTENDANCE_DATE =V_ATTENDANCE_DATE
    AND EMPLOYEE_ID       = V_EMPLOYEE_ID ;
  ELSE
    INSERT
    INTO HRIS_OVERTIME_MANUAL
      (
        ATTENDANCE_DATE,
        EMPLOYEE_ID,
        OVERTIME_HOUR
      )
      VALUES
      (
        V_ATTENDANCE_DATE,
        V_EMPLOYEE_ID,
        V_OVERTIME_HOUR
      );
  END IF;
END;/
            create or replace PROCEDURE HRIS_OVERTIME_AUTOMATION(
    V_DATE DATE)
AS
  S_OVERTIME_REQUEST VARCHAR(255 BYTE):= 'OVERTIME_REQUEST';
  V_ADMIN_ID HRIS_EMPLOYEES.EMPLOYEE_ID%TYPE;
  V_SHIFT_ID HRIS_SHIFTS.SHIFT_ID%TYPE;
  V_START_TIME HRIS_SHIFTS.START_TIME%TYPE;
  V_END_TIME HRIS_SHIFTS.END_TIME%TYPE;
  V_LATE_IN HRIS_SHIFTS.LATE_IN%TYPE;
  V_EARLY_OUT HRIS_SHIFTS.EARLY_OUT%TYPE;
  V_TOTAL_WORKING_HR HRIS_SHIFTS.TOTAL_WORKING_HR%TYPE;
  V_ACTUAL_WORKING_HR HRIS_SHIFTS.ACTUAL_WORKING_HR%TYPE;
  V_IN_TIME HRIS_ATTENDANCE_DETAIL.IN_TIME%TYPE;
  V_OUT_TIME HRIS_ATTENDANCE_DETAIL.OUT_TIME%TYPE;
  V_PUNCH_COUNT              NUMBER;
  V_NON_WORKING_HR           NUMBER;
  V_WORKING_HR               NUMBER;
  V_ACTUAL_WORKING_HR_IN_MIN NUMBER;
  V_TOTAL_WORKING_HR_IN_MIN  NUMBER;
  V_LATE_IN_MIN              NUMBER;
  V_EARLY_OUT_IN_MIN         NUMBER;
  V_BREAK_TIME_IN_MIN        NUMBER;
  V_NON_CONSIDERED_OVERTIME  NUMBER;
  V_OVERTIME                 NUMBER;
  V_CONSTRAINT_VAL_IN_MIN    NUMBER;
  V_PREF_CONDITION           CHAR(1 BYTE);
  V_OVERTIME_ID HRIS_OVERTIME.OVERTIME_ID%TYPE;
  V_OVERTIME_IN_HR          VARCHAR2(10 BYTE);
  V_COUNTER                 NUMBER:=1;
  V_PRE_OVERTIME            VARCHAR2(10 BYTE);
  V_PRE_OVERTIME_MIN        NUMBER;
  V_POST_OVERTIME           VARCHAR2(10 BYTE);
  V_POST_OVERTIME_MIN       NUMBER;
  V_OVERTIME_DETAIL_ID      NUMBER;
  V_EMP_OVERTIME_DATA_COUNT NUMBER;
BEGIN
  BEGIN
    SELECT EMPLOYEE_ID
    INTO V_ADMIN_ID
    FROM HRIS_EMPLOYEES
    WHERE IS_ADMIN  ='Y'
    AND STATUS      ='E'
    AND RETIRED_FLAG='N'
    AND ROWNUM      <2 ;
  EXCEPTION
  WHEN NO_DATA_FOUND THEN
    RAISE_APPLICATION_ERROR(-20001, 'NO EMPLOYEE IS DEFINED AS ADMIN');
  END;
  DECLARE
    V_PREF_COUNTER           NUMBER:=0;
    NO_PREFERENCE_DATA_FOUND EXCEPTION;
  BEGIN
    FOR CUR_PREF IN
    (SELECT       *
    FROM HRIS_PREFERENCE_SETUP
    WHERE PREFERENCE_NAME=S_OVERTIME_REQUEST
    AND STATUS           ='E'
    )
    LOOP
      DECLARE
        V_EMPLOYEE_COUNTER     NUMBER:=0;
        NO_EMPLOYEE_DATA_FOUND EXCEPTION;
      BEGIN
        FOR CUR_EMP IN
        (SELECT E.EMPLOYEE_ID AS EMPLOYEE_ID,
          S.START_TIME        AS START_TIME,
          S.END_TIME          AS END_TIME,
          S.LATE_IN           AS LATE_IN ,
          S.EARLY_OUT         AS EARLY_OUT,
          S.TOTAL_WORKING_HR  AS TOTAL_WORKING_HR,
          S.ACTUAL_WORKING_HR AS ACTUAL_WORKING_HR,
          S.SHIFT_ID          AS SHIFT_ID,
          AD.IN_TIME          AS IN_TIME,
          AD.OUT_TIME         AS OUT_TIME
        FROM HRIS_EMPLOYEES E
        JOIN
          (SELECT * FROM HRIS_EMPLOYEE_SHIFT_ASSIGN WHERE STATUS='E'
          ) SA
        ON (E.EMPLOYEE_ID =SA.EMPLOYEE_ID)
        LEFT JOIN
          (SELECT * FROM HRIS_SHIFTS WHERE STATUS='E'
          ) S
        ON (S.SHIFT_ID=SA.SHIFT_ID)
        LEFT JOIN HRIS_ATTENDANCE_DETAIL AD
        ON (E.EMPLOYEE_ID =AD.EMPLOYEE_ID)
        WHERE (V_DATE BETWEEN S.START_DATE AND S.END_DATE)
        AND AD.ATTENDANCE_DT = V_DATE
        AND E.STATUS         = 'E'
        AND E.RETIRED_FLAG   ='N'
        AND E.EMPLOYEE_TYPE  = CUR_PREF.EMPLOYEE_TYPE
        )
        LOOP
          BEGIN
            V_SHIFT_ID          := CUR_EMP.SHIFT_ID;
            V_START_TIME        := CUR_EMP.START_TIME;
            V_END_TIME          :=CUR_EMP.END_TIME;
            V_LATE_IN           :=CUR_EMP.LATE_IN;
            V_EARLY_OUT         :=CUR_EMP.EARLY_OUT;
            V_TOTAL_WORKING_HR  := CUR_EMP.TOTAL_WORKING_HR;
            V_ACTUAL_WORKING_HR :=CUR_EMP.ACTUAL_WORKING_HR;
            V_IN_TIME           := CUR_EMP.IN_TIME;
            V_OUT_TIME          :=CUR_EMP.OUT_TIME;
            IF CUR_EMP.SHIFT_ID IS NULL THEN
              BEGIN
                SELECT SHIFT_ID ,
                  START_TIME,
                  END_TIME,
                  NVL(LATE_IN,0),
                  NVL(EARLY_OUT,0),
                  NVL(TOTAL_WORKING_HR,0),
                  NVL(ACTUAL_WORKING_HR,0)
                INTO V_SHIFT_ID,
                  V_START_TIME,
                  V_END_TIME,
                  V_LATE_IN,
                  V_EARLY_OUT,
                  V_TOTAL_WORKING_HR,
                  V_ACTUAL_WORKING_HR
                FROM HRIS_SHIFTS
                WHERE STATUS     ='E'
                AND DEFAULT_SHIFT='Y'
                AND ROWNUM       <2;
              EXCEPTION
              WHEN NO_DATA_FOUND THEN
                RAISE_APPLICATION_ERROR(-20344, 'NO DEFAULT IS FOUND');
              END;
            END IF;
            --
            BEGIN
              SELECT COUNT(EMPLOYEE_ID)
              INTO V_EMP_OVERTIME_DATA_COUNT
              FROM HRIS_OVERTIME
              WHERE EMPLOYEE_ID            = CUR_EMP.EMPLOYEE_ID
              AND OVERTIME_DATE            =V_DATE;
              IF V_EMP_OVERTIME_DATA_COUNT =0 THEN
                --
                BEGIN
                  SELECT COUNT(*)
                  INTO V_PUNCH_COUNT
                  FROM HRIS_ATTENDANCE
                  WHERE EMPLOYEE_ID = CUR_EMP.EMPLOYEE_ID
                  AND ATTENDANCE_DT =V_DATE;
                  IF V_PUNCH_COUNT !=0 AND MOD(V_PUNCH_COUNT,2)=0 THEN
                    --
                    BEGIN
                      FOR CUR_OVERTIME       IN
                      (SELECT TRUNC(TOTAL_MINS/60,0)
                        ||':'
                        ||MOD(TOTAL_MINS,60) TOTAL_HRS,
                        TOTAL_MINS,
                        HR_TYPE
                      FROM
                        (SELECT
                          CASE MOD(RNUM,2)
                            WHEN 0
                            THEN 'WORKING'
                            ELSE 'NON-WORKING'
                          END AS HR_TYPE,
                          SUM(ABS(EXTRACT( HOUR FROM DIFF ))*60 + ABS(EXTRACT( MINUTE FROM DIFF ))) TOTAL_MINS
                        FROM
                          (SELECT ROW_NUMBER() OVER ( ORDER BY A.ATTENDANCE_TIME )    AS RNUM,
                            MOD((ROW_NUMBER() OVER ( ORDER BY A.ATTENDANCE_TIME )),2) AS NUM,
                            A.EMPLOYEE_ID,
                            A.IP_ADDRESS,
                            A.ATTENDANCE_DT,
                            A.ATTENDANCE_TIME,
                            (A.ATTENDANCE_TIME - LAG(A.ATTENDANCE_TIME) OVER (ORDER BY A.ATTENDANCE_TIME)) AS DIFF
                          FROM HRIS_ATTENDANCE A
                          WHERE A.EMPLOYEE_ID = CUR_EMP.EMPLOYEE_ID
                          AND A.ATTENDANCE_DT = V_DATE
                          )
                        GROUP BY MOD(RNUM,2)
                        )
                      )
                      LOOP
                        IF V_COUNTER       = 1 THEN
                          V_NON_WORKING_HR:=NVL(CUR_OVERTIME.TOTAL_MINS,0);
                        ELSE
                          V_WORKING_HR:=NVL(CUR_OVERTIME.TOTAL_MINS,0);
                        END IF;
                        -- NO EXCEPTION HANDLED HERE YET;
                        V_COUNTER:=V_COUNTER+1;
                      END LOOP;
                    END;
                    IF V_COUNTER != 3 THEN
                      CONTINUE;
                    END IF;
                    V_COUNTER:=1;
                    --
                    V_BREAK_TIME_IN_MIN          := (V_TOTAL_WORKING_HR-V_ACTUAL_WORKING_HR);
                    IF V_WORKING_HR               > V_ACTUAL_WORKING_HR THEN
                      IF V_BREAK_TIME_IN_MIN      > V_NON_WORKING_HR THEN
                        V_NON_CONSIDERED_OVERTIME:=(V_BREAK_TIME_IN_MIN - V_NON_WORKING_HR);
                      ELSE
                        V_NON_CONSIDERED_OVERTIME:=0;
                      END IF;
                      V_OVERTIME := ( V_WORKING_HR -V_ACTUAL_WORKING_HR)-V_NON_CONSIDERED_OVERTIME;
                      --                V_OVERTIME      :=V_OVERTIME      +NVL(V_LATE_IN_MIN,0)+NVL(V_EARLY_OUT_IN_MIN,0);
                      V_PREF_CONDITION:=
                      CASE
                      WHEN ((CUR_PREF.PREFERENCE_CONDITION = 'LESS_THAN') AND (V_OVERTIME <CUR_PREF.CONSTRAINT_VALUE)) OR ((CUR_PREF.PREFERENCE_CONDITION = 'GREATER_THAN') AND (V_OVERTIME >CUR_PREF.CONSTRAINT_VALUE)) OR ((CUR_PREF.PREFERENCE_CONDITION = 'EQUAL') AND (V_OVERTIME =CUR_PREF.CONSTRAINT_VALUE)) THEN
                        'Y'
                      ELSE
                        'N'
                      END;
                      IF V_PREF_CONDITION = 'Y' THEN
                        SELECT NVL(MAX (OVERTIME_ID),0) + 1 INTO V_OVERTIME_ID FROM HRIS_OVERTIME;
                        DBMS_OUTPUT.PUT_LINE(''||V_OVERTIME);
                        SELECT CONCAT( TRUNC(V_OVERTIME/60,0), CONCAT(':',MOD(V_OVERTIME,60)))
                        INTO V_OVERTIME_IN_HR
                        FROM DUAL;
                        INSERT
                        INTO HRIS_OVERTIME
                          (
                            OVERTIME_ID,
                            EMPLOYEE_ID,
                            OVERTIME_DATE,
                            REQUESTED_DATE,
                            DESCRIPTION,
                            REMARKS,
                            STATUS,
                            RECOMMENDED_BY,
                            RECOMMENDED_DATE,
                            RECOMMENDED_REMARKS,
                            APPROVED_BY,
                            APPROVED_DATE,
                            APPROVED_REMARKS,
                            MODIFIED_DATE,
                            TOTAL_HOUR
                          )
                          VALUES
                          (
                            V_OVERTIME_ID ,
                            CUR_EMP.EMPLOYEE_ID,
                            V_DATE,
                            V_DATE,
                            'Overtime Request',
                            NULL,
                            CUR_PREF.REQUEST_TYPE,
                            (
                            CASE CUR_PREF.REQUEST_TYPE
                              WHEN 'AP'
                              THEN V_ADMIN_ID
                              ELSE NULL
                            END),
                            (
                            CASE CUR_PREF.REQUEST_TYPE
                              WHEN 'AP'
                              THEN V_DATE
                              ELSE NULL
                            END),
                            NULL,
                            (
                            CASE CUR_PREF.REQUEST_TYPE
                              WHEN 'AP'
                              THEN V_ADMIN_ID
                              ELSE NULL
                            END),
                            (
                            CASE CUR_PREF.REQUEST_TYPE
                              WHEN 'AP'
                              THEN V_DATE
                              ELSE NULL
                            END),
                            NULL,
                            NULL,
                            V_OVERTIME
                          );
                        COMMIT;
                        IF (V_IN_TIME != V_START_TIME) AND (V_IN_TIME < V_START_TIME) THEN
                          SELECT NVL(MAX (DETAIL_ID),0) + 1
                          INTO V_OVERTIME_DETAIL_ID
                          FROM HRIS_OVERTIME_DETAIL;
                          SELECT SUM(ABS(EXTRACT( HOUR FROM PRE_OVERTIME ))*60 + ABS(EXTRACT( MINUTE FROM PRE_OVERTIME )))
                          INTO V_PRE_OVERTIME_MIN
                          FROM
                            (SELECT (V_START_TIME-V_IN_TIME) AS PRE_OVERTIME FROM DUAL
                            );
                          SELECT CONCAT( TRUNC(V_PRE_OVERTIME_MIN/60,0), CONCAT(':',MOD(V_PRE_OVERTIME_MIN,60)))
                          INTO V_PRE_OVERTIME
                          FROM DUAL;
                          INSERT
                          INTO HRIS_OVERTIME_DETAIL
                            (
                              DETAIL_ID,
                              OVERTIME_ID,
                              START_TIME,
                              END_TIME,
                              STATUS,
                              CREATED_BY,
                              CREATED_DATE,
                              MODIFIED_BY,
                              MODIFIED_DATE,
                              TOTAL_HOUR
                            )
                            VALUES
                            (
                              V_OVERTIME_DETAIL_ID ,
                              V_OVERTIME_ID,
                              V_IN_TIME,
                              V_START_TIME,
                              'E',
                              CUR_EMP.EMPLOYEE_ID,
                              V_DATE,
                              NULL,
                              NULL,
                              V_PRE_OVERTIME_MIN
                            );
                          COMMIT;
                        END IF;
                        IF (V_OUT_TIME != V_END_TIME) AND (V_END_TIME < V_OUT_TIME) THEN
                          SELECT NVL(MAX (DETAIL_ID),0) + 1
                          INTO V_OVERTIME_DETAIL_ID
                          FROM HRIS_OVERTIME_DETAIL;
                          SELECT SUM(ABS(EXTRACT( HOUR FROM POST_OVERTIME ))*60 + ABS(EXTRACT( MINUTE FROM POST_OVERTIME )))
                          INTO V_POST_OVERTIME_MIN
                          FROM
                            (SELECT (V_OUT_TIME-V_END_TIME) AS POST_OVERTIME FROM DUAL
                            );
                          SELECT CONCAT( TRUNC(V_POST_OVERTIME_MIN/60,0), CONCAT(':',MOD(V_POST_OVERTIME_MIN,60)))
                          INTO V_POST_OVERTIME
                          FROM DUAL;
                          INSERT
                          INTO HRIS_OVERTIME_DETAIL
                            (
                              DETAIL_ID,
                              OVERTIME_ID,
                              START_TIME,
                              END_TIME,
                              STATUS,
                              CREATED_BY,
                              CREATED_DATE,
                              MODIFIED_BY,
                              MODIFIED_DATE,
                              TOTAL_HOUR
                            )
                            VALUES
                            (
                              V_OVERTIME_DETAIL_ID ,
                              V_OVERTIME_ID,
                              V_END_TIME,
                              V_OUT_TIME,
                              'E',
                              CUR_EMP.EMPLOYEE_ID,
                              V_DATE,
                              NULL,
                              NULL,
                              V_POST_OVERTIME_MIN
                            );
                          COMMIT;
                        END IF;
                      END IF;
                    END IF;
                    --
                    --
                  END IF;
                END;
              END IF;
            END;
            V_EMPLOYEE_COUNTER:=V_EMPLOYEE_COUNTER+1;
          END;
        END LOOP;
        IF V_EMPLOYEE_COUNTER=0 THEN
          RAISE NO_EMPLOYEE_DATA_FOUND;
        END IF;
      EXCEPTION
      WHEN NO_EMPLOYEE_DATA_FOUND THEN
        RAISE_APPLICATION_ERROR(-20004,'FAILED ON PREFERENCE_ID : ' || CUR_PREF.PREFERENCE_ID || ' EMPLOYEE ARE NOT AVAILABLE WITH PROVIDED ATTENDANCE DATE ACCORDING TO SETUP');
      END;
      V_PREF_COUNTER:=V_PREF_COUNTER+1;
    END LOOP;
    IF V_PREF_COUNTER=0 THEN
      RAISE NO_PREFERENCE_DATA_FOUND;
    END IF;
  EXCEPTION
  WHEN NO_PREFERENCE_DATA_FOUND THEN
    RAISE_APPLICATION_ERROR(-20005, 'NO PREFERENCE SETTING IS DEFINED FOR OVERTIME AUTOMATION');
  END;
END HRIS_OVERTIME_AUTOMATION;/
            create or replace PROCEDURE HRIS_POST_CHECK_ATTENDANCE(
    P_ATTENDANCE_DT DATE ,
    P_EMPLOYEE_ID   NUMBER:=NULL)
AS
  V_ATTENDANCE_DT DATE;
BEGIN
  V_ATTENDANCE_DT :=TRUNC(P_ATTENDANCE_DT);
  --
  HRIS_REATTENDANCE(V_ATTENDANCE_DT,P_EMPLOYEE_ID);
  --
  --
  IF V_ATTENDANCE_DT = TRUNC(SYSDATE) THEN
    --
    HRIS_COMPULSORY_OT_PROC(V_ATTENDANCE_DT);
    --
    FOR attendance IN
    (SELECT         *
    FROM HRIS_ATTENDANCE_DETAIL
    WHERE ATTENDANCE_DT= V_ATTENDANCE_DT
    AND (EMPLOYEE_ID   =
      CASE
        WHEN P_EMPLOYEE_ID IS NOT NULL
        THEN P_EMPLOYEE_ID
      END
    OR P_EMPLOYEE_ID IS NULL)
    )
    LOOP
      -- check if wod is present for every employee
      DECLARE
        V_ID HRIS_EMPLOYEE_WORK_DAYOFF.ID%TYPE;
      BEGIN
        SELECT ID
        INTO V_ID
        FROM HRIS_EMPLOYEE_WORK_DAYOFF
        WHERE EMPLOYEE_ID = attendance.EMPLOYEE_ID
        AND TO_DATE       = V_ATTENDANCE_DT-(
          CASE
            WHEN (attendance.TWO_DAY_SHIFT ='E')
            THEN 1
            ELSE 0
          END)
        AND STATUS ='AP'
        AND ROWNUM =1;
        --
        HRIS_WOD_REWARD(V_ID);
      EXCEPTION
      WHEN NO_DATA_FOUND THEN
        DBMS_OUTPUT.PUT('NO WORK ON DAYOFF FOUND');
      END;
      -- check if woh is present for every employee
      DECLARE
        V_ID HRIS_EMPLOYEE_WORK_HOLIDAY.ID%TYPE;
      BEGIN
        SELECT ID
        INTO V_ID
        FROM HRIS_EMPLOYEE_WORK_HOLIDAY
        WHERE EMPLOYEE_ID =attendance.EMPLOYEE_ID
        AND TO_DATE       = V_ATTENDANCE_DT-(
          CASE
            WHEN (attendance.TWO_DAY_SHIFT ='E')
            THEN 1
            ELSE 0
          END)
        AND STATUS = 'AP'
        AND ROWNUM =1;
        --
        HRIS_WOH_REWARD(V_ID);
      EXCEPTION
      WHEN NO_DATA_FOUND THEN
        DBMS_OUTPUT.PUT('NO WORK ON DAYOFF FOUND');
      END;
    END LOOP;
  END IF;
END;/
            create or replace PROCEDURE HRIS_PRELOAD_ATTENDANCE(
    V_ATTENDANCE_DATE DATE,
    P_EMPLOYEE_ID HRIS_EMPLOYEES.EMPLOYEE_ID%TYPE:=NULL,
    P_SHIFT_ID HRIS_SHIFTS.SHIFT_ID%TYPE         :=NULL)
AS
  V_EMPLOYEE_ID HRIS_EMPLOYEES.EMPLOYEE_ID%TYPE;
  V_SHIFT_ID HRIS_SHIFTS.SHIFT_ID%TYPE;
  V_WEEKDAY1 HRIS_SHIFTS.WEEKDAY1%TYPE;
  V_WEEKDAY2 HRIS_SHIFTS.WEEKDAY2%TYPE;
  V_WEEKDAY3 HRIS_SHIFTS.WEEKDAY3%TYPE;
  V_WEEKDAY4 HRIS_SHIFTS.WEEKDAY4%TYPE;
  V_WEEKDAY5 HRIS_SHIFTS.WEEKDAY5%TYPE;
  V_WEEKDAY6 HRIS_SHIFTS.WEEKDAY6%TYPE;
  V_WEEKDAY7 HRIS_SHIFTS.WEEKDAY7%TYPE;
  V_DAYOFF  VARCHAR2(1 BYTE);
  V_HALFDAY CHAR(1 BYTE);
  V_HOLIDAY_ID HRIS_HOLIDAY_MASTER_SETUP.HOLIDAY_ID%TYPE;
  V_LEAVE_ID HRIS_LEAVE_MASTER_SETUP.LEAVE_ID%TYPE;
  V_LEAVE_HALFDAY_PERIOD HRIS_EMPLOYEE_LEAVE_REQUEST.HALF_DAY%TYPE;
  V_LEAVE_GRACE_PERIOD HRIS_EMPLOYEE_LEAVE_REQUEST.GRACE_PERIOD%TYPE;
  V_TRAVEL_ID HRIS_EMPLOYEE_TRAVEL_REQUEST.TRAVEL_ID%TYPE;
  V_TRAINING_ID HRIS_TRAINING_MASTER_SETUP.TRAINING_ID%TYPE;
  V_TRAINING_TYPE HRIS_ATTENDANCE_DETAIL.TRAINING_TYPE%TYPE;
  V_WOD_ID HRIS_EMPLOYEE_WORK_DAYOFF.ID%TYPE;
  V_WOH_ID HRIS_EMPLOYEE_WORK_HOLIDAY.ID%TYPE;
  V_TWO_DAY_SHIFT HRIS_SHIFTS.TWO_DAY_SHIFT%TYPE;
  V_IGNORE_TIME HRIS_SHIFTS.IGNORE_TIME%TYPE;
  V_MAX_ID                NUMBER;
  V_ATTENDANCE_DATA_COUNT NUMBER;
  CURSOR CUR_EMPLOYEE
  IS
    SELECT EMPLOYEE_ID
    FROM HRIS_EMPLOYEES
    WHERE STATUS     ='E'
    AND (EMPLOYEE_ID =
      CASE
        WHEN P_EMPLOYEE_ID IS NOT NULL
        THEN P_EMPLOYEE_ID
      END
    OR P_EMPLOYEE_ID IS NULL)
    AND JOIN_DATE    <= TRUNC(V_ATTENDANCE_DATE);
  --
  V_OVERALL_STATUS CHAR(2 BYTE);
BEGIN
  --
  OPEN CUR_EMPLOYEE;
  LOOP
    FETCH CUR_EMPLOYEE INTO V_EMPLOYEE_ID;
    EXIT
  WHEN CUR_EMPLOYEE%NOTFOUND;
    -- RESET VALUES FOR EACH LOOP
    V_DAYOFF              :='N';
    V_HALFDAY             :='N';
    V_HOLIDAY_ID          :=NULL;
    V_LEAVE_ID            :=NULL;
    V_TRAINING_ID         :=NULL;
    V_TRAINING_TYPE       :=NULL;
    V_TRAVEL_ID           :=NULL;
    V_OVERALL_STATUS      :=NULL;
    V_LEAVE_HALFDAY_PERIOD:=NULL;
    V_LEAVE_GRACE_PERIOD  :=NULL;
    --
    -- DELETE IF ALREADY EXISTS
    DELETE
    FROM HRIS_ATTENDANCE_DETAIL
    WHERE EMPLOYEE_ID = V_EMPLOYEE_ID
    AND ATTENDANCE_DT =V_ATTENDANCE_DATE;
    --
    IF P_SHIFT_ID IS NOT NULL THEN
      --    FETCH SHIFT DATA IF PASSED
      BEGIN
        SELECT HS.SHIFT_ID,
          WEEKDAY1,
          WEEKDAY2,
          WEEKDAY3,
          WEEKDAY4,
          WEEKDAY5,
          WEEKDAY6,
          WEEKDAY7,
          TWO_DAY_SHIFT,
          IGNORE_TIME
        INTO V_SHIFT_ID,
          V_WEEKDAY1,
          V_WEEKDAY2,
          V_WEEKDAY3,
          V_WEEKDAY4,
          V_WEEKDAY5,
          V_WEEKDAY6,
          V_WEEKDAY7,
          V_TWO_DAY_SHIFT,
          V_IGNORE_TIME
        FROM HRIS_SHIFTS HS
        WHERE HS.SHIFT_ID = P_SHIFT_ID ;
      END;
      --
    ELSE
      BEGIN
        SELECT HS.SHIFT_ID,
          WEEKDAY1,
          WEEKDAY2,
          WEEKDAY3,
          WEEKDAY4,
          WEEKDAY5,
          WEEKDAY6,
          WEEKDAY7,
          TWO_DAY_SHIFT,
          IGNORE_TIME
        INTO V_SHIFT_ID,
          V_WEEKDAY1,
          V_WEEKDAY2,
          V_WEEKDAY3,
          V_WEEKDAY4,
          V_WEEKDAY5,
          V_WEEKDAY6,
          V_WEEKDAY7,
          V_TWO_DAY_SHIFT,
          V_IGNORE_TIME
        FROM HRIS_EMPLOYEE_SHIFT_ROASTER ES,
          HRIS_SHIFTS HS
        WHERE 1                = 1
        AND ES.EMPLOYEE_ID     = V_EMPLOYEE_ID
        AND TRUNC(ES.FOR_DATE) = V_ATTENDANCE_DATE
        AND HS.STATUS          = 'E'
        AND ES.SHIFT_ID        = HS.SHIFT_ID;
      EXCEPTION
      WHEN NO_DATA_FOUND THEN
        BEGIN
          SELECT HS.SHIFT_ID,
            WEEKDAY1,
            WEEKDAY2,
            WEEKDAY3,
            WEEKDAY4,
            WEEKDAY5,
            WEEKDAY6,
            WEEKDAY7,
            TWO_DAY_SHIFT,
            IGNORE_TIME
          INTO V_SHIFT_ID,
            V_WEEKDAY1,
            V_WEEKDAY2,
            V_WEEKDAY3,
            V_WEEKDAY4,
            V_WEEKDAY5,
            V_WEEKDAY6,
            V_WEEKDAY7,
            V_TWO_DAY_SHIFT,
            V_IGNORE_TIME
          FROM
            (SELECT *
            FROM
              (SELECT *
              FROM HRIS_EMPLOYEE_SHIFT_ASSIGN
              WHERE EMPLOYEE_ID              = V_EMPLOYEE_ID
              AND (TRUNC(V_ATTENDANCE_DATE) >= START_DATE
              AND TRUNC(V_ATTENDANCE_DATE)  <=
                CASE
                  WHEN END_DATE IS NOT NULL
                  THEN END_DATE
                  ELSE TRUNC(V_ATTENDANCE_DATE)
                END)
              ORDER BY START_DATE DESC,
                END_DATE ASC
              )
            WHERE ROWNUM=1
            ) ES,
            HRIS_SHIFTS HS
          WHERE ES.SHIFT_ID = HS.SHIFT_ID;
        EXCEPTION
        WHEN NO_DATA_FOUND THEN
          BEGIN
            SELECT SHIFT_ID,
              WEEKDAY1,
              WEEKDAY2,
              WEEKDAY3,
              WEEKDAY4,
              WEEKDAY5,
              WEEKDAY6,
              WEEKDAY7,
              TWO_DAY_SHIFT,
              IGNORE_TIME
            INTO V_SHIFT_ID,
              V_WEEKDAY1,
              V_WEEKDAY2,
              V_WEEKDAY3,
              V_WEEKDAY4,
              V_WEEKDAY5,
              V_WEEKDAY6,
              V_WEEKDAY7,
              V_TWO_DAY_SHIFT,
              V_IGNORE_TIME
            FROM HRIS_SHIFTS
            WHERE V_ATTENDANCE_DATE BETWEEN START_DATE AND END_DATE
            AND DEFAULT_SHIFT = 'Y'
            AND STATUS        ='E'
            AND ROWNUM        =1 ;
          EXCEPTION
          WHEN NO_DATA_FOUND THEN
            RAISE_APPLICATION_ERROR(-20344, 'No default and normal shift defined for this time period');
          END;
        END;
      END;
    END IF;
    SELECT NVL(MAX (ID),0) + 1 INTO V_MAX_ID FROM HRIS_ATTENDANCE_DETAIL;
    BEGIN
      IF (TO_CHAR(V_ATTENDANCE_DATE,'D') ='1') THEN
        IF V_WEEKDAY1                    = 'DAY_OFF' THEN
          V_DAYOFF                      := 'Y';
        ELSIF V_WEEKDAY1                 ='H' THEN
          V_HALFDAY                     := 'Y';
        END IF;
      ELSIF (TO_CHAR(V_ATTENDANCE_DATE,'D') ='2') THEN
        IF V_WEEKDAY2                       = 'DAY_OFF' THEN
          V_DAYOFF                         := 'Y';
        ELSIF V_WEEKDAY2                    ='H' THEN
          V_HALFDAY                        := 'Y';
        END IF;
      ELSIF (TO_CHAR(V_ATTENDANCE_DATE,'D') ='3') THEN
        IF V_WEEKDAY3                       = 'DAY_OFF' THEN
          V_DAYOFF                         := 'Y';
        ELSIF V_WEEKDAY3                    ='H' THEN
          V_HALFDAY                        := 'Y';
        END IF;
      ELSIF (TO_CHAR(V_ATTENDANCE_DATE,'D') ='4') THEN
        IF V_WEEKDAY4                       = 'DAY_OFF' THEN
          V_DAYOFF                         := 'Y';
        ELSIF V_WEEKDAY4                    ='H' THEN
          V_HALFDAY                        := 'Y';
        END IF;
      ELSIF (TO_CHAR(V_ATTENDANCE_DATE,'D') ='5') THEN
        IF V_WEEKDAY5                       = 'DAY_OFF' THEN
          V_DAYOFF                         := 'Y';
        ELSIF V_WEEKDAY5                    ='H' THEN
          V_HALFDAY                        := 'Y';
        END IF;
      ELSIF (TO_CHAR(V_ATTENDANCE_DATE,'D') ='6') THEN
        IF V_WEEKDAY6                       = 'DAY_OFF' THEN
          V_DAYOFF                         := 'Y';
        ELSIF V_WEEKDAY6                    ='H' THEN
          V_HALFDAY                        := 'Y';
        END IF;
      ELSIF (TO_CHAR(V_ATTENDANCE_DATE,'D') ='7') THEN
        IF V_WEEKDAY7                       = 'DAY_OFF' THEN
          V_DAYOFF                         := 'Y';
        ELSIF V_WEEKDAY7                    ='H' THEN
          V_HALFDAY                        := 'Y';
        END IF;
      END IF;
      IF (V_DAYOFF       ='Y') THEN
        V_OVERALL_STATUS:='DO';
      END IF;
      IF (V_HALFDAY      ='Y') THEN
        V_OVERALL_STATUS:='AB';
        --        NOT DEFINED IN SETUP SO DEFAULT IS FIRST HALF
        V_LEAVE_HALFDAY_PERIOD:='S';
        --
      END IF;
    END;
    -- CHECK FOR HOLIDAY
    BEGIN
      SELECT H.HOLIDAY_ID
      INTO V_HOLIDAY_ID
      FROM HRIS_HOLIDAY_MASTER_SETUP H
      JOIN HRIS_EMPLOYEE_HOLIDAY EH
      ON (H.HOLIDAY_ID=EH.HOLIDAY_ID)
      WHERE V_ATTENDANCE_DATE BETWEEN H.START_DATE AND H.END_DATE
      AND EH.EMPLOYEE_ID  =V_EMPLOYEE_ID
      AND ROWNUM          <2;
      IF V_HOLIDAY_ID    IS NOT NULL THEN
        V_OVERALL_STATUS :='HD';
      END IF;
    EXCEPTION
    WHEN NO_DATA_FOUND THEN
      NULL;
    END;
    --
    
    --for training Start
    BEGIN
      SELECT TA.TRAINING_ID,
        'A'
      INTO V_TRAINING_ID,
        V_TRAINING_TYPE
      FROM HRIS_EMPLOYEE_TRAINING_ASSIGN TA
      LEFT JOIN HRIS_EMP_TRAINING_ATTENDANCE ETA ON (
        TA.TRAINING_ID = ETA.TRAINING_ID
        AND ETA.TRAINING_DT=V_ATTENDANCE_DATE
        AND ETA.EMPLOYEE_ID=V_EMPLOYEE_ID)
      INNER JOIN HRIS_TRAINING_MASTER_SETUP T
      ON TA.TRAINING_ID       = T.TRAINING_ID
      WHERE TA.EMPLOYEE_ID    = V_EMPLOYEE_ID
      AND TA.STATUS           = 'E'
      AND ETA.ATTENDANCE_STATUS = 'P'
      AND T.IS_WITHIN_COMPANY ='N'
      AND V_ATTENDANCE_DATE BETWEEN T.START_DATE AND T.END_DATE;
      IF V_TRAINING_ID  IS NOT NULL THEN
        V_OVERALL_STATUS:='TN';
      END IF;
    EXCEPTION
    WHEN NO_DATA_FOUND THEN
      NULL;
    END;
    BEGIN
      SELECT TR.REQUEST_ID,
        'R'
      INTO V_TRAINING_ID,
        V_TRAINING_TYPE
      FROM HRIS_EMPLOYEE_TRAINING_REQUEST TR
      WHERE TR.EMPLOYEE_ID     = V_EMPLOYEE_ID
      AND TR.STATUS            = 'AP'
      AND TR.IS_WITHIN_COMPANY ='N'
      AND V_ATTENDANCE_DATE BETWEEN TR.START_DATE AND TR.END_DATE
      AND ROWNUM         =1;
      IF V_TRAINING_ID  IS NOT NULL THEN
        V_OVERALL_STATUS:='TN';
      END IF;
    EXCEPTION
    WHEN NO_DATA_FOUND THEN
      NULL;
    END;
    BEGIN
      SELECT TRAVEL_ID
      INTO V_TRAVEL_ID
      FROM HRIS_EMPLOYEE_TRAVEL_REQUEST
      WHERE EMPLOYEE_ID = V_EMPLOYEE_ID
      AND STATUS        = 'AP'
      AND (V_ATTENDANCE_DATE BETWEEN FROM_DATE AND TO_DATE)
      AND ROWNUM          =1;
      IF V_TRAVEL_ID     IS NOT NULL THEN
        V_OVERALL_STATUS := 'TV';
      END IF;
    EXCEPTION
    WHEN NO_DATA_FOUND THEN
      NULL;
    END;
    --for training End
    
     --for Leave start
    BEGIN
      SELECT LEAVE_ID,
        HALF_DAY,
        GRACE_PERIOD
      INTO V_LEAVE_ID,
        V_LEAVE_HALFDAY_PERIOD,
        V_LEAVE_GRACE_PERIOD
      FROM
        (SELECT L.LEAVE_ID,
          (
          CASE
            WHEN L.HALF_DAY IS NULL
            OR L.HALF_DAY    = 'N'
            THEN NULL
            ELSE L.HALF_DAY
          END ) AS HALF_DAY ,
          L.GRACE_PERIOD
        FROM HRIS_EMPLOYEE_LEAVE_REQUEST L
        LEFT JOIN HRIS_LEAVE_MASTER_SETUP LMS
        ON (L.LEAVE_ID      =LMS.LEAVE_ID)
        WHERE L.EMPLOYEE_ID = V_EMPLOYEE_ID
        AND V_ATTENDANCE_DATE BETWEEN L.START_DATE AND L.END_DATE
        AND L.STATUS            = 'AP'
        AND (V_DAYOFF          !='Y'
        OR LMS.DAY_OFF_AS_LEAVE ='Y')
        AND (V_HOLIDAY_ID      IS NULL
        OR LMS.HOLIDAY_AS_LEAVE ='Y')
        ORDER BY L.REQUESTED_DT DESC
        )
      WHERE ROWNUM        =1;
      IF V_LEAVE_ID      IS NOT NULL AND V_LEAVE_HALFDAY_PERIOD IS NULL AND V_LEAVE_GRACE_PERIOD IS NULL THEN
        V_OVERALL_STATUS :='LV';
      END IF;
      --       IF V_LEAVE_HALFDAY_PERIOD IS NOT NULL THEN
      --         V_HALFDAY               := 'Y';
      --       END IF;
    EXCEPTION
    WHEN NO_DATA_FOUND THEN
      NULL;
    END;
    --for Leave end
    
    -- CHECK FOR WOD start
    BEGIN
      SELECT ID
      INTO V_WOD_ID
      FROM
        (SELECT ID
        FROM HRIS_EMPLOYEE_WORK_DAYOFF
        WHERE EMPLOYEE_ID = V_EMPLOYEE_ID
        AND (V_ATTENDANCE_DATE BETWEEN FROM_DATE AND TO_DATE )
        AND STATUS ='AP'
        ORDER BY REQUESTED_DATE DESC
        )
      WHERE ROWNUM      =1 ;
      V_OVERALL_STATUS :=
      CASE
      WHEN V_OVERALL_STATUS ='TV' THEN
        'VP'
      ELSE
        'WD'
      END;
    EXCEPTION
    WHEN NO_DATA_FOUND THEN
      NULL;
    END;
    -- CHECK FOR WOD end
    
    -- CHECK OF WOH start
    BEGIN
      SELECT ID
      INTO V_WOH_ID
      FROM
        (SELECT ID
        FROM HRIS_EMPLOYEE_WORK_HOLIDAY
        WHERE EMPLOYEE_ID =V_EMPLOYEE_ID
        AND (V_ATTENDANCE_DATE BETWEEN FROM_DATE AND TO_DATE )
        AND STATUS ='AP'
        ORDER BY REQUESTED_DATE DESC
        )
      WHERE ROWNUM      =1 ;
      V_OVERALL_STATUS :=
      CASE
      WHEN V_OVERALL_STATUS ='TV' THEN
        'VP'
      ELSE
        'WH'
      END;
    EXCEPTION
    WHEN NO_DATA_FOUND THEN
      NULL;
    END;
    -- CHECK OF WOH end
    
    BEGIN
      INSERT
      INTO HRIS_ATTENDANCE_DETAIL
        (
          ID,
          EMPLOYEE_ID,
          ATTENDANCE_DT,
          SHIFT_ID,
          DAYOFF_FLAG,
          HALFDAY_FLAG,
          HALFDAY_PERIOD,
          GRACE_PERIOD,
          HOLIDAY_ID,
          LEAVE_ID,
          TRAVEL_ID,
          TRAINING_ID,
          TRAINING_TYPE,
          OVERALL_STATUS,
          TWO_DAY_SHIFT,
          IGNORE_TIME
        )
        VALUES
        (
          V_MAX_ID,
          V_EMPLOYEE_ID,
          V_ATTENDANCE_DATE,
          V_SHIFT_ID,
          V_DAYOFF,
          V_HALFDAY,
          V_LEAVE_HALFDAY_PERIOD,
          V_LEAVE_GRACE_PERIOD,
          V_HOLIDAY_ID,
          V_LEAVE_ID,
          V_TRAVEL_ID,
          V_TRAINING_ID,
          V_TRAINING_TYPE,
          (
          CASE
            WHEN V_OVERALL_STATUS IS NULL
            THEN 'AB'
            ELSE V_OVERALL_STATUS
          END),
          (
          CASE
            WHEN V_TWO_DAY_SHIFT IS NULL
            THEN 'D'
            ELSE V_TWO_DAY_SHIFT
          END),
          V_IGNORE_TIME
        );
    END;
  END LOOP;
  CLOSE CUR_EMPLOYEE;
END;/
            CREATE OR REPLACE PROCEDURE HRIS_PRELOAD_ATTEND_PAYROLL(
    V_ATTENDANCE_DATE DATE,
    P_EMPLOYEE_ID HRIS_EMPLOYEES.EMPLOYEE_ID%TYPE:=NULL,
    P_SHIFT_ID HRIS_SHIFTS.SHIFT_ID%TYPE         :=NULL)
AS
  V_EMPLOYEE_ID HRIS_EMPLOYEES.EMPLOYEE_ID%TYPE;
  V_SHIFT_ID HRIS_SHIFTS.SHIFT_ID%TYPE;
  V_WEEKDAY1 HRIS_SHIFTS.WEEKDAY1%TYPE;
  V_WEEKDAY2 HRIS_SHIFTS.WEEKDAY2%TYPE;
  V_WEEKDAY3 HRIS_SHIFTS.WEEKDAY3%TYPE;
  V_WEEKDAY4 HRIS_SHIFTS.WEEKDAY4%TYPE;
  V_WEEKDAY5 HRIS_SHIFTS.WEEKDAY5%TYPE;
  V_WEEKDAY6 HRIS_SHIFTS.WEEKDAY6%TYPE;
  V_WEEKDAY7 HRIS_SHIFTS.WEEKDAY7%TYPE;
  V_DAYOFF  VARCHAR2(1 BYTE);
  V_HALFDAY CHAR(1 BYTE);
  V_HOLIDAY_ID HRIS_HOLIDAY_MASTER_SETUP.HOLIDAY_ID%TYPE;
  V_LEAVE_ID HRIS_LEAVE_MASTER_SETUP.LEAVE_ID%TYPE;
  V_LEAVE_HALFDAY_PERIOD HRIS_EMPLOYEE_LEAVE_REQUEST.HALF_DAY%TYPE;
  V_LEAVE_GRACE_PERIOD HRIS_EMPLOYEE_LEAVE_REQUEST.GRACE_PERIOD%TYPE;
  V_TRAVEL_ID HRIS_EMPLOYEE_TRAVEL_REQUEST.TRAVEL_ID%TYPE;
  V_TRAINING_ID HRIS_TRAINING_MASTER_SETUP.TRAINING_ID%TYPE;
  V_TRAINING_TYPE HRIS_ATTENDANCE_DETAIL.TRAINING_TYPE%TYPE;
  V_WOD_ID HRIS_EMPLOYEE_WORK_DAYOFF.ID%TYPE;
  V_WOH_ID HRIS_EMPLOYEE_WORK_HOLIDAY.ID%TYPE;
  V_TWO_DAY_SHIFT HRIS_SHIFTS.TWO_DAY_SHIFT%TYPE;
  V_MAX_ID                NUMBER;
  V_ATTENDANCE_DATA_COUNT NUMBER;
  CURSOR CUR_EMPLOYEE
  IS
    SELECT EMPLOYEE_ID
    FROM HRIS_EMPLOYEES
    WHERE STATUS     ='E'
    AND RETIRED_FLAG ='N'
    AND IS_ADMIN     ='N'
    AND (EMPLOYEE_ID =
      CASE
        WHEN P_EMPLOYEE_ID IS NOT NULL
        THEN P_EMPLOYEE_ID
      END
    OR P_EMPLOYEE_ID IS NULL)
    AND JOIN_DATE    <= TRUNC(V_ATTENDANCE_DATE);
  --
  V_OVERALL_STATUS CHAR(2 BYTE);
BEGIN
  --
  OPEN CUR_EMPLOYEE;
  LOOP
    FETCH CUR_EMPLOYEE INTO V_EMPLOYEE_ID;
    EXIT
  WHEN CUR_EMPLOYEE%NOTFOUND;
    -- RESET VALUES FOR EACH LOOP
    V_DAYOFF              :='N';
    V_HALFDAY             :='N';
    V_HOLIDAY_ID          :=NULL;
    V_LEAVE_ID            :=NULL;
    V_TRAINING_ID         :=NULL;
    V_TRAINING_TYPE       :=NULL;
    V_TRAVEL_ID           :=NULL;
    V_OVERALL_STATUS      :=NULL;
    V_LEAVE_HALFDAY_PERIOD:=NULL;
    V_LEAVE_GRACE_PERIOD  :=NULL;
    --
    -- DELETE IF ALREADY EXISTS
    DELETE
    FROM HRIS_ATTENDANCE_PAYROLL
    WHERE EMPLOYEE_ID = V_EMPLOYEE_ID
    AND ATTENDANCE_DT =V_ATTENDANCE_DATE;
    --
    IF P_SHIFT_ID IS NOT NULL THEN
      --    FETCH SHIFT DATA IF PASSED
      BEGIN
        SELECT HS.SHIFT_ID,
          WEEKDAY1,
          WEEKDAY2,
          WEEKDAY3,
          WEEKDAY4,
          WEEKDAY5,
          WEEKDAY6,
          WEEKDAY7,
          TWO_DAY_SHIFT
        INTO V_SHIFT_ID,
          V_WEEKDAY1,
          V_WEEKDAY2,
          V_WEEKDAY3,
          V_WEEKDAY4,
          V_WEEKDAY5,
          V_WEEKDAY6,
          V_WEEKDAY7,
          V_TWO_DAY_SHIFT
        FROM HRIS_SHIFTS HS
        WHERE HS.SHIFT_ID = P_SHIFT_ID ;
      END;
      --
    ELSE
      BEGIN
        SELECT HS.SHIFT_ID,
          WEEKDAY1,
          WEEKDAY2,
          WEEKDAY3,
          WEEKDAY4,
          WEEKDAY5,
          WEEKDAY6,
          WEEKDAY7,
          TWO_DAY_SHIFT
        INTO V_SHIFT_ID,
          V_WEEKDAY1,
          V_WEEKDAY2,
          V_WEEKDAY3,
          V_WEEKDAY4,
          V_WEEKDAY5,
          V_WEEKDAY6,
          V_WEEKDAY7,
          V_TWO_DAY_SHIFT
        FROM HRIS_EMPLOYEE_SHIFT_ROASTER ES,
          HRIS_SHIFTS HS
        WHERE 1                = 1
        AND ES.EMPLOYEE_ID     = V_EMPLOYEE_ID
        AND TRUNC(ES.FOR_DATE) = V_ATTENDANCE_DATE
        AND HS.STATUS          = 'E'
        AND ES.SHIFT_ID        = HS.SHIFT_ID;
      EXCEPTION
      WHEN NO_DATA_FOUND THEN
        BEGIN
          SELECT HS.SHIFT_ID,
            WEEKDAY1,
            WEEKDAY2,
            WEEKDAY3,
            WEEKDAY4,
            WEEKDAY5,
            WEEKDAY6,
            WEEKDAY7,
            TWO_DAY_SHIFT
          INTO V_SHIFT_ID,
            V_WEEKDAY1,
            V_WEEKDAY2,
            V_WEEKDAY3,
            V_WEEKDAY4,
            V_WEEKDAY5,
            V_WEEKDAY6,
            V_WEEKDAY7,
            V_TWO_DAY_SHIFT
          FROM
            (SELECT *
            FROM
              (SELECT *
              FROM HRIS_EMPLOYEE_SHIFT_ASSIGN
              WHERE EMPLOYEE_ID              = V_EMPLOYEE_ID
              AND (TRUNC(V_ATTENDANCE_DATE) >= START_DATE
              AND TRUNC(V_ATTENDANCE_DATE)  <=
                CASE
                  WHEN END_DATE IS NOT NULL
                  THEN END_DATE
                  ELSE TRUNC(V_ATTENDANCE_DATE)
                END)
              ORDER BY START_DATE DESC,
                END_DATE ASC
              )
            WHERE ROWNUM=1
            ) ES,
            HRIS_SHIFTS HS
          WHERE ES.SHIFT_ID = HS.SHIFT_ID;
        EXCEPTION
        WHEN NO_DATA_FOUND THEN
          BEGIN
            SELECT SHIFT_ID,
              WEEKDAY1,
              WEEKDAY2,
              WEEKDAY3,
              WEEKDAY4,
              WEEKDAY5,
              WEEKDAY6,
              WEEKDAY7,
              TWO_DAY_SHIFT
            INTO V_SHIFT_ID,
              V_WEEKDAY1,
              V_WEEKDAY2,
              V_WEEKDAY3,
              V_WEEKDAY4,
              V_WEEKDAY5,
              V_WEEKDAY6,
              V_WEEKDAY7,
              V_TWO_DAY_SHIFT
            FROM HRIS_SHIFTS
            WHERE V_ATTENDANCE_DATE BETWEEN START_DATE AND END_DATE
            AND DEFAULT_SHIFT = 'Y'
            AND STATUS        ='E'
            AND ROWNUM        =1 ;
          EXCEPTION
          WHEN NO_DATA_FOUND THEN
            RAISE_APPLICATION_ERROR(-20344, 'No default and normal shift defined for this time period');
          END;
        END;
      END;
    END IF;
    SELECT NVL(MAX (ID),0) + 1 INTO V_MAX_ID FROM HRIS_ATTENDANCE_PAYROLL;
    BEGIN
      IF (TO_CHAR(V_ATTENDANCE_DATE,'D') ='1') THEN
        IF V_WEEKDAY1                    = 'DAY_OFF' THEN
          V_DAYOFF                      := 'Y';
        ELSIF V_WEEKDAY1                 ='H' THEN
          V_HALFDAY                     := 'Y';
        END IF;
      ELSIF (TO_CHAR(V_ATTENDANCE_DATE,'D') ='2') THEN
        IF V_WEEKDAY2                       = 'DAY_OFF' THEN
          V_DAYOFF                         := 'Y';
        ELSIF V_WEEKDAY2                    ='H' THEN
          V_HALFDAY                        := 'Y';
        END IF;
      ELSIF (TO_CHAR(V_ATTENDANCE_DATE,'D') ='3') THEN
        IF V_WEEKDAY3                       = 'DAY_OFF' THEN
          V_DAYOFF                         := 'Y';
        ELSIF V_WEEKDAY3                    ='H' THEN
          V_HALFDAY                        := 'Y';
        END IF;
      ELSIF (TO_CHAR(V_ATTENDANCE_DATE,'D') ='4') THEN
        IF V_WEEKDAY4                       = 'DAY_OFF' THEN
          V_DAYOFF                         := 'Y';
        ELSIF V_WEEKDAY4                    ='H' THEN
          V_HALFDAY                        := 'Y';
        END IF;
      ELSIF (TO_CHAR(V_ATTENDANCE_DATE,'D') ='5') THEN
        IF V_WEEKDAY5                       = 'DAY_OFF' THEN
          V_DAYOFF                         := 'Y';
        ELSIF V_WEEKDAY5                    ='H' THEN
          V_HALFDAY                        := 'Y';
        END IF;
      ELSIF (TO_CHAR(V_ATTENDANCE_DATE,'D') ='6') THEN
        IF V_WEEKDAY6                       = 'DAY_OFF' THEN
          V_DAYOFF                         := 'Y';
        ELSIF V_WEEKDAY6                    ='H' THEN
          V_HALFDAY                        := 'Y';
        END IF;
      ELSIF (TO_CHAR(V_ATTENDANCE_DATE,'D') ='7') THEN
        IF V_WEEKDAY7                       = 'DAY_OFF' THEN
          V_DAYOFF                         := 'Y';
        ELSIF V_WEEKDAY7                    ='H' THEN
          V_HALFDAY                        := 'Y';
        END IF;
      END IF;
      IF (V_DAYOFF       ='Y') THEN
        V_OVERALL_STATUS:='DO';
      END IF;
      IF (V_HALFDAY      ='Y') THEN
        V_OVERALL_STATUS:='PR';
        --        NOT DEFINED IN SETUP SO DEFAULT IS FIRST HALF
        V_LEAVE_HALFDAY_PERIOD:='S';
        --
      END IF;
    END;
    -- CHECK FOR HOLIDAY
    BEGIN
      SELECT H.HOLIDAY_ID
      INTO V_HOLIDAY_ID
      FROM HRIS_HOLIDAY_MASTER_SETUP H
      JOIN HRIS_EMPLOYEE_HOLIDAY EH
      ON (H.HOLIDAY_ID=EH.HOLIDAY_ID)
      WHERE V_ATTENDANCE_DATE BETWEEN H.START_DATE AND H.END_DATE
      AND EH.EMPLOYEE_ID  =V_EMPLOYEE_ID
      AND ROWNUM          <2;
      IF V_HOLIDAY_ID    IS NOT NULL THEN
        V_OVERALL_STATUS :='HD';
      END IF;
    EXCEPTION
    WHEN NO_DATA_FOUND THEN
      NULL;
    END;
    --
      BEGIN
        SELECT LEAVE_ID,
          HALF_DAY,
          GRACE_PERIOD
        INTO V_LEAVE_ID,
          V_LEAVE_HALFDAY_PERIOD,
          V_LEAVE_GRACE_PERIOD
        FROM
          (SELECT L.LEAVE_ID,
            (
            CASE
              WHEN L.HALF_DAY IS NULL
              OR L.HALF_DAY    = 'N'
              THEN NULL
              ELSE L.HALF_DAY
            END ) AS HALF_DAY ,
            L.GRACE_PERIOD
          FROM HRIS_EMPLOYEE_LEAVE_REQUEST L
          LEFT JOIN HRIS_LEAVE_MASTER_SETUP LMS
          ON (L.LEAVE_ID      =LMS.LEAVE_ID)
          WHERE L.EMPLOYEE_ID = V_EMPLOYEE_ID
          AND V_ATTENDANCE_DATE BETWEEN L.START_DATE AND L.END_DATE
          AND L.STATUS = 'AP'
         AND (V_DAYOFF          !='Y'
         OR LMS.DAY_OFF_AS_LEAVE ='Y')
         AND (V_HOLIDAY_ID      IS NULL
         OR LMS.HOLIDAY_AS_LEAVE ='Y')
          ORDER BY L.REQUESTED_DT DESC
          )
        WHERE ROWNUM        =1;
        IF V_LEAVE_ID      IS NOT NULL AND V_LEAVE_HALFDAY_PERIOD IS NULL AND V_LEAVE_GRACE_PERIOD IS NULL THEN
          V_OVERALL_STATUS :='LV';
        END IF;
        IF V_LEAVE_HALFDAY_PERIOD IS NOT NULL THEN
          V_HALFDAY               := 'Y';
        END IF;
      EXCEPTION
      WHEN NO_DATA_FOUND THEN
        NULL;
      END;
    BEGIN
      SELECT TA.TRAINING_ID,
        'A'
      INTO V_TRAINING_ID,
        V_TRAINING_TYPE
      FROM HRIS_EMPLOYEE_TRAINING_ASSIGN TA
      INNER JOIN HRIS_TRAINING_MASTER_SETUP T
      ON TA.TRAINING_ID       = T.TRAINING_ID
      WHERE TA.EMPLOYEE_ID    = V_EMPLOYEE_ID
      AND TA.STATUS           = 'E'
      AND T.IS_WITHIN_COMPANY ='N'
      AND V_ATTENDANCE_DATE BETWEEN T.START_DATE AND T.END_DATE;
      IF V_TRAINING_ID  IS NOT NULL THEN
        V_OVERALL_STATUS:='TN';
      END IF;
    EXCEPTION
    WHEN NO_DATA_FOUND THEN
      NULL;
    END;
    BEGIN
      SELECT TR.REQUEST_ID,
        'R'
      INTO V_TRAINING_ID,
        V_TRAINING_TYPE
      FROM HRIS_EMPLOYEE_TRAINING_REQUEST TR
      WHERE TR.EMPLOYEE_ID     = V_EMPLOYEE_ID
      AND TR.STATUS            = 'AP'
      AND TR.IS_WITHIN_COMPANY ='N'
      AND V_ATTENDANCE_DATE BETWEEN TR.START_DATE AND TR.END_DATE
      AND ROWNUM         =1;
      IF V_TRAINING_ID  IS NOT NULL THEN
        V_OVERALL_STATUS:='TN';
      END IF;
    EXCEPTION
    WHEN NO_DATA_FOUND THEN
      NULL;
    END;
    BEGIN
      SELECT TRAVEL_ID
      INTO V_TRAVEL_ID
      FROM HRIS_EMPLOYEE_TRAVEL_REQUEST
      WHERE EMPLOYEE_ID = V_EMPLOYEE_ID
      AND STATUS        = 'AP'
      AND (V_ATTENDANCE_DATE BETWEEN FROM_DATE AND TO_DATE)
      AND ROWNUM          =1;
      IF V_TRAVEL_ID     IS NOT NULL THEN
        V_OVERALL_STATUS := 'TV';
      END IF;
    EXCEPTION
    WHEN NO_DATA_FOUND THEN
      NULL;
    END;
    -- CHECK FOR WOD
    BEGIN
      SELECT ID
      INTO V_WOD_ID
      FROM
        (SELECT ID
        FROM HRIS_EMPLOYEE_WORK_DAYOFF
        WHERE EMPLOYEE_ID = V_EMPLOYEE_ID
        AND (V_ATTENDANCE_DATE BETWEEN FROM_DATE AND TO_DATE )
        AND STATUS ='AP'
        ORDER BY REQUESTED_DATE DESC
        )
      WHERE ROWNUM      =1 ;
      V_OVERALL_STATUS :=
      CASE
      WHEN V_OVERALL_STATUS ='TV' THEN
        'VP'
      ELSE
        'WD'
      END;
    EXCEPTION
    WHEN NO_DATA_FOUND THEN
      NULL;
    END;
    --
    -- CHECK OF WOH
    BEGIN
      SELECT ID
      INTO V_WOH_ID
      FROM
        (SELECT ID
        FROM HRIS_EMPLOYEE_WORK_HOLIDAY
        WHERE EMPLOYEE_ID =V_EMPLOYEE_ID
        AND (V_ATTENDANCE_DATE BETWEEN FROM_DATE AND TO_DATE )
        AND STATUS ='AP'
        ORDER BY REQUESTED_DATE DESC
        )
      WHERE ROWNUM      =1 ;
      V_OVERALL_STATUS :=
      CASE
      WHEN V_OVERALL_STATUS ='TV' THEN
        'VP'
      ELSE
        'WH'
      END;
    EXCEPTION
    WHEN NO_DATA_FOUND THEN
      NULL;
    END;
    --
    BEGIN
      INSERT
      INTO HRIS_ATTENDANCE_PAYROLL
        (
          ID,
          EMPLOYEE_ID,
          ATTENDANCE_DT,
          SHIFT_ID,
          DAYOFF_FLAG,
          HALFDAY_FLAG,
          HALFDAY_PERIOD,
          GRACE_PERIOD,
          HOLIDAY_ID,
          LEAVE_ID,
          TRAVEL_ID,
          TRAINING_ID,
          TRAINING_TYPE,
          OVERALL_STATUS,
          TWO_DAY_SHIFT
        )
        VALUES
        (
          V_MAX_ID,
          V_EMPLOYEE_ID,
          V_ATTENDANCE_DATE,
          V_SHIFT_ID,
          V_DAYOFF,
          V_HALFDAY,
          V_LEAVE_HALFDAY_PERIOD,
          V_LEAVE_GRACE_PERIOD,
          V_HOLIDAY_ID,
          V_LEAVE_ID,
          V_TRAVEL_ID,
          V_TRAINING_ID,
          V_TRAINING_TYPE,
          (
          CASE
            WHEN V_OVERALL_STATUS IS NULL
            THEN 'PR'
            ELSE V_OVERALL_STATUS
          END),
          (
          CASE
            WHEN V_TWO_DAY_SHIFT IS NULL
            THEN 'D'
            ELSE V_TWO_DAY_SHIFT
          END)
        );
    END;
  END LOOP;
  CLOSE CUR_EMPLOYEE;
END;/
            CREATE OR REPLACE PROCEDURE HRIS_PREPARE_PAYROLL_DATA(
    V_FISCAL_YEAR_ID HRIS_MONTH_CODE.FISCAL_YEAR_ID%TYPE,
    V_FISCAL_YEAR_MONTH_NO HRIS_MONTH_CODE.FISCAL_YEAR_MONTH_NO%TYPE )
AS
  V_MONTH_START_DATE HRIS_MONTH_CODE.FROM_DATE%TYPE;
  V_MONTH_END_DATE HRIS_MONTH_CODE.TO_DATE %TYPE;
  V_DURATION                               NUMBER;
  V_DATA_COUNT                             NUMBER;
BEGIN
  SELECT TRUNC(FROM_DATE),
    TRUNC(TO_DATE),
    (TRUNC(TO_DATE)-TRUNC(FROM_DATE))+1
  INTO V_MONTH_START_DATE,
    V_MONTH_END_DATE,
    V_DURATION
  FROM HRIS_MONTH_CODE
  WHERE FISCAL_YEAR_ID    = V_FISCAL_YEAR_ID
  AND FISCAL_YEAR_MONTH_NO=V_FISCAL_YEAR_MONTH_NO;
  DELETE
  FROM HRIS_ATTENDANCE_PAYROLL
  WHERE ATTENDANCE_DT BETWEEN V_MONTH_START_DATE AND V_MONTH_END_DATE;
  FOR d IN
  (SELECT (level-1) AS DAY FROM dual CONNECT BY level <V_DURATION+1
  )
  LOOP
    FOR e IN
    (SELECT EMPLOYEE_ID,
      JOIN_DATE
    FROM HRIS_EMPLOYEES
    WHERE STATUS     ='E'
    )
    LOOP
      SELECT COUNT(*)
      INTO V_DATA_COUNT
      FROM HRIS_ATTENDANCE_DETAIL
      WHERE EMPLOYEE_ID =e.EMPLOYEE_ID
      AND ATTENDANCE_DT =V_MONTH_START_DATE+d.day;
      IF V_DATA_COUNT   >0 THEN
        INSERT
        INTO HRIS_ATTENDANCE_PAYROLL
          (
            EMPLOYEE_ID,
            ATTENDANCE_DT,
            IN_TIME,
            OUT_TIME,
            IN_REMARKS,
            OUT_REMARKS,
            TOTAL_HOUR,
            LEAVE_ID,
            HOLIDAY_ID,
            TRAINING_ID,
            ID,
            TRAVEL_ID,
            SHIFT_ID,
            DAYOFF_FLAG,
            OVERALL_STATUS,
            LATE_STATUS,
            HALFDAY_FLAG,
            GRACE_PERIOD,
            HALFDAY_PERIOD,
            TWO_DAY_SHIFT,
            TRAINING_TYPE
          )
        SELECT EMPLOYEE_ID,
          ATTENDANCE_DT,
          IN_TIME,
          OUT_TIME,
          IN_REMARKS,
          OUT_REMARKS,
          TOTAL_HOUR,
          LEAVE_ID,
          HOLIDAY_ID,
          TRAINING_ID,
          ID,
          TRAVEL_ID,
          SHIFT_ID,
          DAYOFF_FLAG,
          OVERALL_STATUS,
          LATE_STATUS,
          HALFDAY_FLAG,
          GRACE_PERIOD,
          HALFDAY_PERIOD,
          TWO_DAY_SHIFT,
          TRAINING_TYPE
        FROM HRIS_ATTENDANCE_DETAIL
        WHERE EMPLOYEE_ID =e.EMPLOYEE_ID
        AND ATTENDANCE_DT =V_MONTH_START_DATE+d.day;
      ELSE
        IF (e.JOIN_DATE IS NOT NULL AND e.JOIN_DATE > V_MONTH_START_DATE+d.day) THEN
          INSERT
          INTO HRIS_ATTENDANCE_PAYROLL
            (
              EMPLOYEE_ID,
              ATTENDANCE_DT,
              OVERALL_STATUS,
              LATE_STATUS,
              HALFDAY_FLAG,
              TWO_DAY_SHIFT
            )
            VALUES
            (
              e.EMPLOYEE_ID,
              V_MONTH_START_DATE+d.day,
              'AB',
              'N',
              'N',
              'D'
            );
        ELSE
          HRIS_PRELOAD_ATTEND_PAYROLL(V_MONTH_START_DATE+d.day,e.EMPLOYEE_ID);
        END IF;
      END IF;
    END LOOP;
  END LOOP;
END;/
            CREATE OR REPLACE PROCEDURE HRIS_QUEUE_REATTENDANCE(
    P_FROM_ATTENDANCE_DT HRIS_ATTENDANCE.ATTENDANCE_DT%TYPE,
    P_EMPLOYEE_ID HRIS_ATTENDANCE.EMPLOYEE_ID%TYPE:=NULL,
    P_TO_ATTENDANCE_DT DATE                       :=NULL )
AS
  V_WHAT HRIS_JOBS.WHAT%TYPE;
BEGIN
  IF P_EMPLOYEE_ID    IS NULL AND P_TO_ATTENDANCE_DT IS NOT NULL THEN
    V_WHAT            :='HRIS_REATTENDANCE(''' ||P_FROM_ATTENDANCE_DT ||''',NULL,''' || P_TO_ATTENDANCE_DT||''');';
  ELSIF P_EMPLOYEE_ID IS NULL AND P_TO_ATTENDANCE_DT IS NULL THEN
    V_WHAT            :='HRIS_REATTENDANCE(''' ||P_FROM_ATTENDANCE_DT ||''');';
  ELSIF P_EMPLOYEE_ID IS NOT NULL AND P_TO_ATTENDANCE_DT IS NULL THEN
    V_WHAT            :='HRIS_REATTENDANCE(''' ||P_FROM_ATTENDANCE_DT ||''',' ||P_EMPLOYEE_ID ||');';
  ELSIF P_EMPLOYEE_ID IS NOT NULL AND P_TO_ATTENDANCE_DT IS NOT NULL THEN
    V_WHAT            :='HRIS_REATTENDANCE(''' ||P_FROM_ATTENDANCE_DT ||''','||P_EMPLOYEE_ID||',''' || P_TO_ATTENDANCE_DT||''');';
  END IF;
  INSERT
  INTO HRIS_JOBS
    (
      JOB_ID,
      WHAT
    )
    VALUES
    (
      (SELECT NVL(MAX(JOB_ID),0)+1 FROM HRIS_JOBS
      )
      ,
      'BEGIN '
      || V_WHAT
      || ' END;'
    );
END;/
            create or replace PROCEDURE HRIS_REATTENDANCE(
    P_FROM_ATTENDANCE_DT HRIS_ATTENDANCE.ATTENDANCE_DT%TYPE,
    P_EMPLOYEE_ID HRIS_ATTENDANCE.EMPLOYEE_ID%TYPE:=NULL,
    P_TO_ATTENDANCE_DT DATE                       :=NULL)
AS
  V_TO_ATTENDANCE_DT DATE;
  V_DATE_DIFF        NUMBER;
  --
  V_EMPLOYEE_ID HRIS_EMPLOYEES.EMPLOYEE_ID%TYPE;
  V_IN_TIME HRIS_ATTENDANCE_DETAIL.IN_TIME%TYPE;
  V_OUT_TIME HRIS_ATTENDANCE_DETAIL.OUT_TIME%TYPE;
  V_DIFF_IN_MIN NUMBER;
  --
  V_OVERALL_STATUS HRIS_ATTENDANCE_DETAIL.OVERALL_STATUS%TYPE;
  V_LATE_STATUS HRIS_ATTENDANCE_DETAIL.LATE_STATUS%TYPE:='N';
  V_HALFDAY_FLAG HRIS_ATTENDANCE_DETAIL.HALFDAY_FLAG%TYPE;
  V_HALFDAY_PERIOD HRIS_ATTENDANCE_DETAIL.HALFDAY_PERIOD%TYPE;
  V_GRACE_PERIOD HRIS_ATTENDANCE_DETAIL.GRACE_PERIOD%TYPE;
  V_TWO_DAY_SHIFT HRIS_ATTENDANCE_DETAIL.TWO_DAY_SHIFT%TYPE;
  V_IGNORE_TIME HRIS_ATTENDANCE_DETAIL.IGNORE_TIME%TYPE;
  V_TWO_DAY_SHIFT_AUTO CHAR;
  --
  V_FROM_DATE DATE;
  V_TO_DATE   DATE;
  --
  V_LATE_IN HRIS_SHIFTS.LATE_IN%TYPE;
  V_EARLY_OUT HRIS_SHIFTS.EARLY_OUT%TYPE;
  V_LATE_START_TIME   TIMESTAMP;
  V_EARLY_END_TIME    TIMESTAMP;
  V_TOTAL_WORKING_MIN NUMBER;
  --
  V_LATE_COUNT NUMBER;
  V_SHIFT_ID   NUMBER;
  --
  V_HALF_INTERVAL      DATE;
  v_NEXT_HALF_INTERVAL DATE;
  v_training_id          number;
  v_roaster_shift_id number;
  V_BREAK_DEDUCT_FLAG char(1 byte);
  v_middle_diff number;
BEGIN
  IF P_TO_ATTENDANCE_DT IS NOT NULL THEN
    V_TO_ATTENDANCE_DT  :=P_TO_ATTENDANCE_DT;
  ELSE
    V_TO_ATTENDANCE_DT :=SYSDATE;
  END IF;
  V_DATE_DIFF := TRUNC( V_TO_ATTENDANCE_DT)- TRUNC(P_FROM_ATTENDANCE_DT);
  FOR i                                   IN 0..V_DATE_DIFF
  LOOP
    BEGIN
      SELECT FROM_DATE,
        TO_DATE
      INTO V_FROM_DATE,
        V_TO_DATE
      FROM HRIS_MONTH_CODE
      WHERE TRUNC(P_FROM_ATTENDANCE_DT+i) BETWEEN TRUNC(FROM_DATE) AND TRUNC(TO_DATE);
    EXCEPTION
    WHEN NO_DATA_FOUND THEN
      V_FROM_DATE:=TRUNC(SYSDATE);
    END;
    --
    DELETE
    FROM HRIS_ATTENDANCE_DETAIL
    WHERE ATTENDANCE_DT= TRUNC(P_FROM_ATTENDANCE_DT+i)
    AND (EMPLOYEE_ID   =
      CASE
        WHEN P_EMPLOYEE_ID IS NOT NULL
        THEN P_EMPLOYEE_ID
      END
    OR P_EMPLOYEE_ID IS NULL);
    HRIS_PRELOAD_ATTENDANCE(TRUNC(P_FROM_ATTENDANCE_DT+i),P_EMPLOYEE_ID);
    --
    FOR employee IN
    (SELECT       *
    FROM HRIS_ATTENDANCE_DETAIL
    WHERE ATTENDANCE_DT = TRUNC(P_FROM_ATTENDANCE_DT+i)
    AND (EMPLOYEE_ID    =
      CASE
        WHEN P_EMPLOYEE_ID IS NOT NULL
        THEN P_EMPLOYEE_ID
      END
    OR P_EMPLOYEE_ID IS NULL)
    )
    LOOP
      V_DIFF_IN_MIN    :=NULL;
      V_OVERALL_STATUS :=employee.OVERALL_STATUS;
      V_LATE_STATUS    :=employee.LATE_STATUS;
      V_HALFDAY_FLAG   :=employee.HALFDAY_FLAG;
      V_HALFDAY_PERIOD :=employee.HALFDAY_PERIOD;
      V_GRACE_PERIOD   :=employee.GRACE_PERIOD;
      V_TWO_DAY_SHIFT  := employee.TWO_DAY_SHIFT;
      V_IGNORE_TIME    :=employee.IGNORE_TIME;
      V_SHIFT_ID       := employee.SHIFT_ID;
      v_roaster_shift_id := null;
      V_BREAK_DEDUCT_FLAG :=null;
      v_middle_diff :=0;
      
      begin
      select CASE when  shift_id > 0 then shift_id else null end into v_roaster_shift_id
      from HRIS_EMPLOYEE_SHIFT_ROASTER where employee_id=employee.EMPLOYEE_ID and FOR_DATE=employee.ATTENDANCE_DT;
      EXCEPTION
      WHEN NO_DATA_FOUND THEN
          null;
        END;
      
      --
      DELETE
      FROM HRIS_ATTENDANCE_DETAIL
      WHERE ATTENDANCE_DT= TRUNC(employee.ATTENDANCE_DT)
      AND EMPLOYEE_ID    = employee.EMPLOYEE_ID ;
      --
      if(v_roaster_shift_id is null)then
      V_SHIFT_ID    :=HRIS_BEST_CASE_SHIFT(employee.EMPLOYEE_ID,TRUNC(employee.ATTENDANCE_DT));
      end if;
      
      IF(V_SHIFT_ID IS NULL)THEN
        V_SHIFT_ID  :=employee.SHIFT_ID;
      ELSE
      select two_day_shift into V_TWO_DAY_SHIFT from hris_shifts where shift_id=V_SHIFT_ID;
      END IF;
      HRIS_PRELOAD_ATTENDANCE(employee.ATTENDANCE_DT,employee.EMPLOYEE_ID,V_SHIFT_ID);
      --
      HRIS_ATTD_IN_OUT(employee.EMPLOYEE_ID,TRUNC(employee.ATTENDANCE_DT),TRUNC(employee.ATTENDANCE_DT+1),V_IN_TIME,V_OUT_TIME,V_TWO_DAY_SHIFT_AUTO);
      --
      IF (V_IGNORE_TIME         ='Y') THEN
        IF V_TWO_DAY_SHIFT_AUTO ='Y' THEN
          HRIS_ATTD_IN_OUT(employee.EMPLOYEE_ID,TRUNC(employee.ATTENDANCE_DT),case when V_IN_TIME is not null then ( V_IN_TIME + interval '1' day ) else TRUNC(employee.ATTENDANCE_DT+2) end,V_IN_TIME,V_OUT_TIME,V_TWO_DAY_SHIFT_AUTO);
        END IF;
        IF V_IN_TIME IS NOT NULL THEN
          --
          IF V_IN_TIME  = V_OUT_TIME THEN
            V_OUT_TIME := NULL;
          END IF;
          --
          IF V_OUT_TIME IS NOT NULL THEN
            SELECT SUM(ABS(EXTRACT( HOUR FROM DIFF ))*60 + ABS(EXTRACT( MINUTE FROM DIFF )))
            INTO V_DIFF_IN_MIN
            FROM
              (SELECT V_OUT_TIME -V_IN_TIME AS DIFF FROM DUAL
              ) ;
          END IF;
          IF(V_OVERALL_STATUS     ='DO') THEN
            V_OVERALL_STATUS     :='WD';
          ELSIF (V_OVERALL_STATUS ='HD') THEN
            V_OVERALL_STATUS     :='WH';
          ELSIF (V_OVERALL_STATUS ='LV') THEN
            NULL;
          ELSIF (V_OVERALL_STATUS ='TV') THEN
            NULL;
          ELSIF (V_OVERALL_STATUS ='TN') THEN
            NULL;
          ELSIF (V_OVERALL_STATUS = 'AB') THEN
            IF V_HALFDAY_FLAG     ='N' AND (V_HALFDAY_PERIOD IS NOT NULL OR V_GRACE_PERIOD IS NOT NULL) THEN
              V_OVERALL_STATUS   :='LP';
            ELSE
              V_OVERALL_STATUS :='PR';
            END IF;
          END IF;
          UPDATE HRIS_ATTENDANCE_DETAIL
          SET IN_TIME         = V_IN_TIME,
            OUT_TIME          =V_OUT_TIME,
            OVERALL_STATUS    = V_OVERALL_STATUS,
            LATE_STATUS       = V_LATE_STATUS,
            TOTAL_HOUR        = V_DIFF_IN_MIN
          WHERE ATTENDANCE_DT = TO_DATE (employee.ATTENDANCE_DT, 'DD-MON-YY')
          AND EMPLOYEE_ID     = employee.EMPLOYEE_ID;
        END IF;
        CONTINUE;
      END IF;
      --
      IF V_TWO_DAY_SHIFT ='E' THEN
        --
        SELECT LATE_IN,
          EARLY_OUT,
          LATE_START_TIME,
          EARLY_END_TIME,
          EARLY_END_TIME + (LATE_START_TIME -EARLY_END_TIME)/2,
          TOTAL_WORKING_HR
        INTO V_LATE_IN,
          V_EARLY_OUT,
          V_LATE_START_TIME,
          V_EARLY_END_TIME,
          V_HALF_INTERVAL,
          V_TOTAL_WORKING_MIN
        FROM
          (SELECT S.LATE_IN,
            S.EARLY_OUT,
            TO_DATE(TO_CHAR(employee.ATTENDANCE_DT,'DD-MON-YYYY')
            ||' '
            ||TO_CHAR(S.START_TIME+((1/1440)*NVL(S.LATE_IN,0)),'HH:MI AM'),'DD-MON-YYYY HH:MI AM') AS LATE_START_TIME,
            TO_DATE(TO_CHAR(employee.ATTENDANCE_DT,'DD-MON-YYYY')
            ||' '
            || TO_CHAR(S.END_TIME -((1/1440)*NVL(S.EARLY_OUT,0)),'HH:MI AM'),'DD-MON-YYYY HH:MI AM') AS EARLY_END_TIME,
            S.TOTAL_WORKING_HR
          FROM HRIS_SHIFTS S
          WHERE S.SHIFT_ID=V_SHIFT_ID
          );
        V_NEXT_HALF_INTERVAL:=V_HALF_INTERVAL+1;
        --
        V_EARLY_END_TIME:=V_EARLY_END_TIME+1;
        HRIS_ATTD_IN_OUT(employee.EMPLOYEE_ID,V_HALF_INTERVAL,V_NEXT_HALF_INTERVAL,V_IN_TIME,V_OUT_TIME,V_TWO_DAY_SHIFT_AUTO);
        --
      END IF;
      --
      IF V_IN_TIME IS NOT NULL THEN
        --
        IF V_IN_TIME  = V_OUT_TIME THEN
          V_OUT_TIME := NULL;
        END IF;
        --
        IF V_OUT_TIME IS NOT NULL THEN
          SELECT SUM(ABS(EXTRACT( HOUR FROM DIFF ))*60 + ABS(EXTRACT( MINUTE FROM DIFF )))
          INTO V_DIFF_IN_MIN
          FROM
            (SELECT V_OUT_TIME -V_IN_TIME AS DIFF FROM DUAL
            ) ;
        END IF;
        --
        BEGIN
          IF V_HALFDAY_PERIOD IS NOT NULL THEN
            SELECT LATE_IN,
              EARLY_OUT,
              TO_DATE(TO_CHAR(employee.ATTENDANCE_DT,'DD-MON-YYYY')
              ||' '
              ||TO_CHAR(LATE_START_TIME,'HH:MI AM'),'DD-MON-YYYY HH:MI AM'),
              TO_DATE(TO_CHAR(employee.ATTENDANCE_DT,'DD-MON-YYYY')
              ||' '
              ||TO_CHAR(EARLY_END_TIME,'HH:MI AM'),'DD-MON-YYYY HH:MI AM'),
              TOTAL_WORKING_HR
            INTO V_LATE_IN,
              V_EARLY_OUT,
              V_LATE_START_TIME,
              V_EARLY_END_TIME,
              V_TOTAL_WORKING_MIN
            FROM
              (SELECT S.LATE_IN,
                S.EARLY_OUT,
                (
                CASE
                  WHEN V_HALFDAY_PERIOD ='F'
                  THEN S.HALF_DAY_IN_TIME
                  ELSE S.START_TIME
                END ) +((1/1440)*NVL(S.LATE_IN,0)) AS LATE_START_TIME,
                (
                CASE
                  WHEN V_HALFDAY_PERIOD ='F'
                  THEN S.END_TIME
                  ELSE S.HALF_DAY_OUT_TIME
                END ) -((1/1440)*NVL(S.EARLY_OUT,0)) AS EARLY_END_TIME,
                S.TOTAL_WORKING_HR
              FROM HRIS_SHIFTS S
              WHERE S.SHIFT_ID =V_SHIFT_ID
              );
          ELSIF V_GRACE_PERIOD IS NOT NULL THEN
            SELECT LATE_IN,
              EARLY_OUT,
              TO_DATE(TO_CHAR(employee.ATTENDANCE_DT,'DD-MON-YYYY')
              ||' '
              ||TO_CHAR(LATE_START_TIME,'HH:MI AM'),'DD-MON-YYYY HH:MI AM'),
              TO_DATE(TO_CHAR(employee.ATTENDANCE_DT,'DD-MON-YYYY')
              ||' '
              ||TO_CHAR(EARLY_END_TIME,'HH:MI AM'),'DD-MON-YYYY HH:MI AM'),
              TOTAL_WORKING_HR
            INTO V_LATE_IN,
              V_EARLY_OUT,
              V_LATE_START_TIME,
              V_EARLY_END_TIME,
              V_TOTAL_WORKING_MIN
            FROM
              (SELECT S.LATE_IN,
                S.EARLY_OUT,
                (
                CASE
                  WHEN V_GRACE_PERIOD ='E'
                  THEN S.GRACE_START_TIME
                  ELSE S.START_TIME
                END) +((1/1440)*NVL(S.LATE_IN,0)) AS LATE_START_TIME,
                (
                CASE
                  WHEN V_GRACE_PERIOD ='E'
                  THEN S.END_TIME
                  ELSE S.GRACE_END_TIME
                END) -((1/1440)*NVL(S.EARLY_OUT,0)) AS EARLY_END_TIME,
                S.TOTAL_WORKING_HR
              FROM HRIS_SHIFTS S
              WHERE S.SHIFT_ID=V_SHIFT_ID
              );
          ELSE
            SELECT LATE_IN,
              EARLY_OUT,
              TO_DATE(TO_CHAR(employee.ATTENDANCE_DT,'DD-MON-YYYY')
              ||' '
              ||TO_CHAR(LATE_START_TIME,'HH:MI AM'),'DD-MON-YYYY HH:MI AM'),
              TO_DATE(TO_CHAR(employee.ATTENDANCE_DT,'DD-MON-YYYY')
              ||' '
              ||TO_CHAR(EARLY_END_TIME,'HH:MI AM'),'DD-MON-YYYY HH:MI AM'),
              TOTAL_WORKING_HR
            INTO V_LATE_IN,
              V_EARLY_OUT,
              V_LATE_START_TIME,
              V_EARLY_END_TIME,
              V_TOTAL_WORKING_MIN
            FROM
              (SELECT S.LATE_IN,
                S.EARLY_OUT,
                S.START_TIME+((1/1440)*NVL(S.LATE_IN,0))   AS LATE_START_TIME,
                S.END_TIME  -((1/1440)*NVL(S.EARLY_OUT,0)) AS EARLY_END_TIME,
                S.TOTAL_WORKING_HR
              FROM HRIS_SHIFTS S
              WHERE S.SHIFT_ID=V_SHIFT_ID
              );
          END IF;
        EXCEPTION
        WHEN NO_DATA_FOUND THEN
          RAISE_APPLICATION_ERROR(-20344, 'SHIFT WITH SHIFT_ID => '|| V_SHIFT_ID ||' NOT FOUND.');
        END;
        --   CHECK FOR ADJUSTED SHIFT
        BEGIN
          SELECT TO_DATE(TO_CHAR(employee.ATTENDANCE_DT,'DD-MON-YYYY')
            ||' '
            ||TO_CHAR(LATE_START_TIME,'HH:MI AM'),'DD-MON-YYYY HH:MI AM'),
            TO_DATE(TO_CHAR(employee.ATTENDANCE_DT,'DD-MON-YYYY')
            ||' '
            ||TO_CHAR(EARLY_END_TIME,'HH:MI AM'),'DD-MON-YYYY HH:MI AM')
          INTO V_LATE_START_TIME,
            V_EARLY_END_TIME
          FROM
            (SELECT SA.START_TIME + ((1/1440)*NVL(V_LATE_IN,0))  AS LATE_START_TIME,
              SA.END_TIME         -((1/1440)*NVL(V_EARLY_OUT,0)) AS EARLY_END_TIME
            FROM HRIS_SHIFT_ADJUSTMENT SA
            JOIN HRIS_EMPLOYEE_SHIFT_ADJUSTMENT ESA
            ON (SA.ADJUSTMENT_ID=ESA.ADJUSTMENT_ID)
            WHERE (TRUNC(employee.ATTENDANCE_DT) BETWEEN TRUNC(SA.ADJUSTMENT_START_DATE) AND TRUNC(SA.ADJUSTMENT_END_DATE) )
            AND ESA.EMPLOYEE_ID =employee.EMPLOYEE_ID
            );
        EXCEPTION
        WHEN NO_DATA_FOUND THEN
          DBMS_OUTPUT.PUT_LINE('NO ADJUSTMENT FOUND FOR EMPLOYEE =>'|| employee.EMPLOYEE_ID || 'ON THE DATE'||employee.ATTENDANCE_DT);
        END;
        --      END FOR CHECK FOR ADJUSTED_SHIFT
        IF(V_OVERALL_STATUS     ='DO') THEN
          V_OVERALL_STATUS     :='WD';
        ELSIF (V_OVERALL_STATUS ='HD') THEN
          V_OVERALL_STATUS     :='WH';
        ELSIF (V_OVERALL_STATUS ='LV') THEN
          NULL;
        ELSIF (V_OVERALL_STATUS ='TV') THEN
          NULL;
        ELSIF (V_OVERALL_STATUS ='TN') THEN
          NULL;
        ELSIF (V_OVERALL_STATUS = 'AB') THEN
          IF V_HALFDAY_FLAG     ='N' AND (V_HALFDAY_PERIOD IS NOT NULL OR V_GRACE_PERIOD IS NOT NULL) THEN
            V_OVERALL_STATUS   :='LP';
          ELSE
            V_OVERALL_STATUS :='PR';
          END IF;
        END IF;
        --
        IF V_OVERALL_STATUS ='PR' AND (V_LATE_START_TIME<V_IN_TIME) THEN
          V_LATE_STATUS    :='L';
        END IF;
        --
        IF V_OVERALL_STATUS ='PR' AND (V_EARLY_END_TIME>V_OUT_TIME) THEN
          IF (V_LATE_STATUS = 'L') THEN
            V_LATE_STATUS  :='B';
          ELSE
            V_LATE_STATUS :='E';
          END IF;
        END IF;
        --
        IF TRUNC(employee.ATTENDANCE_DT) != TRUNC(SYSDATE) THEN
          IF V_IN_TIME                   IS NOT NULL AND V_OUT_TIME IS NULL THEN
            IF V_LATE_STATUS              ='L' THEN
              V_LATE_STATUS              := 'Y';
            ELSE
              V_LATE_STATUS := 'X';
            END IF;
          END IF;
          --
          SELECT COUNT(*)
          INTO V_LATE_COUNT
          FROM HRIS_ATTENDANCE_DETAIL
          WHERE EMPLOYEE_ID = employee.EMPLOYEE_ID
          AND (ATTENDANCE_DT BETWEEN V_FROM_DATE AND employee.ATTENDANCE_DT )
          AND OVERALL_STATUS           IN ('PR','LA')
          AND LATE_STATUS              IN ('E','L','Y') ;
          IF V_LATE_STATUS             IN ('E','L','Y') THEN
            V_LATE_COUNT       := V_LATE_COUNT+1;
            IF V_LATE_COUNT    != 0 AND MOD(V_LATE_COUNT,4)=0 THEN
              V_OVERALL_STATUS := 'LA';
            END IF;
          END IF;
          --
          IF V_LATE_STATUS   ='B' AND V_OVERALL_STATUS='PR' THEN
            V_OVERALL_STATUS:='BA';
          END IF;
        END IF;
        --
        
        -- to minus from shift special case for maruti start
        begin
        select BREAK_DEDUCT_FLAG into V_BREAK_DEDUCT_FLAG from hris_shifts where shift_id=V_SHIFT_ID;
        
        if V_BREAK_DEDUCT_FLAG='Y'
        then
        
        SELECT 
 nvl(
 SUM(ABS(EXTRACT( HOUR FROM middle_diff ))*60 + ABS(EXTRACT( MINUTE FROM middle_diff )))
 ,0) into v_middle_diff
 from(
select 
(
max(attendance_time) - min(attendance_time) ) as middle_diff
from HRIS_ATTENDANCE where 
attendance_dt=TO_DATE (employee.ATTENDANCE_DT, 'DD-MON-YY') 
and 
employee_id= employee.EMPLOYEE_ID 
and attendance_time between 
to_timestamp(V_IN_TIME)  + INTERVAL '1' HOUR and
to_timestamp(V_OUT_TIME)  - INTERVAL '1' HOUR
order by attendance_time asc
);

DBMS_OUTPUT.PUT_LINE('middle diff start');
DBMS_OUTPUT.PUT_LINE(v_middle_diff);
DBMS_OUTPUT.PUT_LINE('middle diff end');

       -- V_TOTAL_WORKING_MIN := V_TOTAL_WORKING_MIN -v_middle_diff ;
 
        end if;
        
        
        end;
        -- to minus from shift special case for maruti end
        
        
        UPDATE HRIS_ATTENDANCE_DETAIL
        SET IN_TIME         = V_IN_TIME,
          OUT_TIME          =V_OUT_TIME,
          OVERALL_STATUS    = V_OVERALL_STATUS,
          LATE_STATUS       = V_LATE_STATUS,
          TOTAL_HOUR        = V_DIFF_IN_MIN - v_middle_diff ,
          OT_MINUTES        = (V_DIFF_IN_MIN - V_TOTAL_WORKING_MIN - v_middle_diff),
          IN_REMARKS  = (select remarks from HRIS_ATTENDANCE where EMPLOYEE_ID = employee.EMPLOYEE_ID and ATTENDANCE_TIME=V_IN_TIME AND ROWNUM=1),
          OUT_REMARKS = (select remarks from HRIS_ATTENDANCE where EMPLOYEE_ID = employee.EMPLOYEE_ID and ATTENDANCE_TIME=V_OUT_TIME AND ROWNUM=1)
        WHERE ATTENDANCE_DT = TO_DATE (employee.ATTENDANCE_DT, 'DD-MON-YY')
        AND EMPLOYEE_ID     = employee.EMPLOYEE_ID;
        --
      END IF ;
      --
      DECLARE
        V_ID HRIS_EMPLOYEE_WORK_DAYOFF.ID%TYPE;
      BEGIN
        SELECT ID
        INTO V_ID
        FROM HRIS_EMPLOYEE_WORK_DAYOFF
        WHERE EMPLOYEE_ID = employee.EMPLOYEE_ID
        AND TO_DATE       = TRUNC(employee.ATTENDANCE_DT)
        AND STATUS        ='AP'
        AND ROWNUM        =1;
        --
        HRIS_WOD_REWARD(V_ID);
      EXCEPTION
      WHEN NO_DATA_FOUND THEN
        DBMS_OUTPUT.PUT('NO WORK ON DAYOFF FOUND');
      END;
      -- check if woh is present for every employee
      DECLARE
        V_ID HRIS_EMPLOYEE_WORK_HOLIDAY.ID%TYPE;
      BEGIN
        SELECT ID
        INTO V_ID
        FROM HRIS_EMPLOYEE_WORK_HOLIDAY
        WHERE EMPLOYEE_ID =employee.EMPLOYEE_ID
        AND TO_DATE       = TRUNC(employee.ATTENDANCE_DT)
        AND STATUS        = 'AP'
        AND ROWNUM        =1;
        --
        HRIS_WOH_REWARD(V_ID);
      EXCEPTION
      WHEN NO_DATA_FOUND THEN
        DBMS_OUTPUT.PUT('NO WORK ON DAYOFF FOUND');
      END;

 -- added from trainign assign start
    BEGIN
    v_training_id:=NULL;
            SELECT
                ta.training_id
            INTO
                v_training_id
            FROM
                hris_employee_training_assign ta
                INNER JOIN hris_training_master_setup t ON ta.training_id = t.training_id
            WHERE
                    ta.employee_id = employee.employee_id
                AND
                    t.status = 'E'
                AND
                    ta.status = 'E'
                AND
                    p_from_attendance_dt + i BETWEEN t.start_date AND t.end_date;
                     EXCEPTION
                WHEN no_data_found THEN
                    dbms_output.put('NO training for the day ON DAYOFF FOUND');



                     IF v_training_id IS NOT NULL
                     THEN 
                     HRIS_TRAINING_LEAVE_REWARD(v_employee_id,v_training_id);
                     END IF;

        END;            
    -- added from trainign assign 


    END LOOP;
  END LOOP;
END;/
            create or replace PROCEDURE HRIS_REATTENDANCE_TWO_DAY(
    P_ATTENDANCE_DT    DATE,
    P_EMPLOYEE_ID      NUMBER,
    P_SHIFT_ID         NUMBER,
    P_MONTH_START_DATE DATE,
    P_MONTH_END_DATE   DATE)
AS
  V_LATE_START_TIME    TIMESTAMP;
  V_EARLY_END_TIME     TIMESTAMP;
  V_HALF_INTERVAL      DATE;
  v_NEXT_HALF_INTERVAL DATE;
  V_IN_TIME HRIS_ATTENDANCE_DETAIL.IN_TIME%TYPE;
  V_OUT_TIME HRIS_ATTENDANCE_DETAIL.OUT_TIME%TYPE;
  V_DIFF_IN_MIN NUMBER;
  --
  V_OVERALL_STATUS HRIS_ATTENDANCE_DETAIL.OVERALL_STATUS%TYPE;
  V_LATE_STATUS HRIS_ATTENDANCE_DETAIL.LATE_STATUS%TYPE:='N';
  V_HALFDAY_FLAG HRIS_ATTENDANCE_DETAIL.HALFDAY_FLAG%TYPE;
  V_HALFDAY_PERIOD HRIS_ATTENDANCE_DETAIL.HALFDAY_PERIOD%TYPE;
  V_GRACE_PERIOD HRIS_ATTENDANCE_DETAIL.GRACE_PERIOD%TYPE;
  V_LATE_COUNT NUMBER;
  --
  V_LATE_IN HRIS_SHIFTS.LATE_IN%TYPE;
  V_EARLY_OUT HRIS_SHIFTS.EARLY_OUT%TYPE;
  V_ADJUSTED_START_TIME HRIS_SHIFT_ADJUSTMENT.START_TIME%TYPE:=NULL;
  V_ADJUSTED_END_TIME HRIS_SHIFT_ADJUSTMENT.END_TIME%TYPE    :=NULL;
BEGIN
  SELECT OVERALL_STATUS,
    LATE_STATUS,
    HALFDAY_FLAG,
    V_HALFDAY_PERIOD,
    V_GRACE_PERIOD
  INTO V_OVERALL_STATUS,
    V_LATE_STATUS,
    V_HALFDAY_FLAG,
    V_HALFDAY_PERIOD,
    V_GRACE_PERIOD
  FROM HRIS_ATTENDANCE_DETAIL
  WHERE ATTENDANCE_DT = P_ATTENDANCE_DT
  AND EMPLOYEE_ID     =P_EMPLOYEE_ID;
  --
  SELECT S.START_TIME+((1/1440)*NVL(S.LATE_IN,0)),
    S.END_TIME       -((1/1440)*NVL(S.EARLY_OUT,0))
  INTO V_LATE_START_TIME,
    V_EARLY_END_TIME
  FROM HRIS_SHIFTS S
  WHERE S.SHIFT_ID=P_SHIFT_ID ;
  --
  V_LATE_START_TIME := TO_DATE(TO_CHAR(P_ATTENDANCE_DT,'DD-MON-YYYY')||' '||TO_CHAR(V_LATE_START_TIME,'HH:MI AM'),'DD-MON-YYYY HH:MI AM');
  V_EARLY_END_TIME  := TO_DATE(TO_CHAR(P_ATTENDANCE_DT,'DD-MON-YYYY')||' '|| TO_CHAR(V_EARLY_END_TIME,'HH:MI AM'),'DD-MON-YYYY HH:MI AM');
  --
  SELECT V_EARLY_END_TIME + (V_LATE_START_TIME -V_EARLY_END_TIME)/2
  INTO V_HALF_INTERVAL
  FROM DUAL;
  V_NEXT_HALF_INTERVAL:=V_HALF_INTERVAL+1;
  --
  HRIS_ATTD_IN_OUT(P_EMPLOYEE_ID,V_HALF_INTERVAL,V_NEXT_HALF_INTERVAL,V_IN_TIME,V_OUT_TIME);
  --
  IF V_IN_TIME IS NULL AND V_OUT_TIME IS NULL THEN
    RETURN;
  END IF ;
  --
  IF V_IN_TIME  = V_OUT_TIME THEN
    V_OUT_TIME := NULL;
  END IF;
  --
  IF V_IN_TIME IS NOT NULL AND V_OUT_TIME IS NOT NULL THEN
    SELECT SUM(ABS(EXTRACT( HOUR FROM DIFF ))*60 + ABS(EXTRACT( MINUTE FROM DIFF )))
    INTO V_DIFF_IN_MIN
    FROM
      (SELECT V_OUT_TIME -V_IN_TIME AS DIFF FROM DUAL
      ) ;
  END IF;
  BEGIN
    IF V_HALFDAY_PERIOD IS NOT NULL THEN
      SELECT S.LATE_IN,
        S.EARLY_OUT,
        (
        CASE
          WHEN V_HALFDAY_PERIOD ='F'
          THEN S.HALF_DAY_IN_TIME
          ELSE S.START_TIME
        END )+((1/1440)*NVL(S.LATE_IN,0)),
        (
        CASE
          WHEN V_HALFDAY_PERIOD ='F'
          THEN S.END_TIME
          ELSE S.HALF_DAY_OUT_TIME
        END ) -((1/1440)*NVL(S.EARLY_OUT,0))
      INTO V_LATE_IN,
        V_EARLY_OUT,
        V_LATE_START_TIME,
        V_EARLY_END_TIME
      FROM HRIS_SHIFTS S
      WHERE S.SHIFT_ID    =P_SHIFT_ID ;
    ELSIF V_GRACE_PERIOD IS NOT NULL THEN
      SELECT S.LATE_IN,
        S.EARLY_OUT,
        (
        CASE
          WHEN V_GRACE_PERIOD ='E'
          THEN S.GRACE_START_TIME
          ELSE S.START_TIME
        END)+((1/1440)*NVL(S.LATE_IN,0)),
        (
        CASE
          WHEN V_GRACE_PERIOD ='E'
          THEN S.END_TIME
          ELSE S.GRACE_END_TIME
        END) -((1/1440)*NVL(S.EARLY_OUT,0))
      INTO V_LATE_IN,
        V_EARLY_OUT,
        V_LATE_START_TIME,
        V_EARLY_END_TIME
      FROM HRIS_SHIFTS S
      WHERE S.SHIFT_ID=P_SHIFT_ID ;
    ELSE
      SELECT S.LATE_IN,
        S.EARLY_OUT,
        S.START_TIME+((1/1440)*NVL(S.LATE_IN,0)),
        S.END_TIME  -((1/1440)*NVL(S.EARLY_OUT,0))
      INTO V_LATE_IN,
        V_EARLY_OUT,
        V_LATE_START_TIME,
        V_EARLY_END_TIME
      FROM HRIS_SHIFTS S
      WHERE S.SHIFT_ID=P_SHIFT_ID ;
    END IF;
    V_LATE_START_TIME := TO_DATE(TO_CHAR(P_ATTENDANCE_DT,'DD-MON-YYYY')||' '||TO_CHAR(V_LATE_START_TIME,'HH:MI AM'),'DD-MON-YYYY HH:MI AM');
    V_EARLY_END_TIME  := TO_DATE(TO_CHAR(P_ATTENDANCE_DT+1,'DD-MON-YYYY')||' '|| TO_CHAR(V_EARLY_END_TIME,'HH:MI AM'),'DD-MON-YYYY HH:MI AM');
    --
  EXCEPTION
  WHEN NO_DATA_FOUND THEN
    RAISE_APPLICATION_ERROR(-20344, 'SHIFT WITH SHIFT_ID => '|| P_SHIFT_ID ||' NOT FOUND.');
  END;
  --   CHECK FOR ADJUSTED SHIFT
  BEGIN
    SELECT SA.START_TIME,
      SA.END_TIME
    INTO V_ADJUSTED_START_TIME,
      V_ADJUSTED_END_TIME
    FROM HRIS_SHIFT_ADJUSTMENT SA
    JOIN HRIS_EMPLOYEE_SHIFT_ADJUSTMENT ESA
    ON (SA.ADJUSTMENT_ID=ESA.ADJUSTMENT_ID)
    WHERE (TRUNC(P_ATTENDANCE_DT) BETWEEN TRUNC(SA.ADJUSTMENT_START_DATE) AND TRUNC(SA.ADJUSTMENT_END_DATE) )
    AND ESA.EMPLOYEE_ID       =P_EMPLOYEE_ID;
    IF(V_ADJUSTED_START_TIME IS NOT NULL) THEN
      V_LATE_START_TIME      :=V_ADJUSTED_START_TIME;
      V_LATE_START_TIME      := V_LATE_START_TIME+((1/1440)*NVL(V_LATE_IN,0));
    END IF;
    IF(V_ADJUSTED_END_TIME IS NOT NULL) THEN
      V_EARLY_END_TIME     :=V_ADJUSTED_END_TIME;
      V_EARLY_END_TIME     := V_EARLY_END_TIME -((1/1440)*NVL(V_EARLY_OUT,0));
    END IF;
    V_LATE_START_TIME := TO_DATE(TO_CHAR(P_ATTENDANCE_DT,'DD-MON-YYYY')||' '||TO_CHAR(V_LATE_START_TIME,'HH:MI AM'),'DD-MON-YYYY HH:MI AM');
    V_EARLY_END_TIME  := TO_DATE(TO_CHAR(P_ATTENDANCE_DT+1,'DD-MON-YYYY')||' '|| TO_CHAR(V_EARLY_END_TIME,'HH:MI AM'),'DD-MON-YYYY HH:MI AM');
    --
  EXCEPTION
  WHEN NO_DATA_FOUND THEN
    DBMS_OUTPUT.PUT_LINE('NO ADJUSTMENT FOUND FOR EMPLOYEE =>'|| P_EMPLOYEE_ID || 'ON THE DATE'||P_ATTENDANCE_DT);
  END;
  --      END FOR CHECK FOR ADJUSTED_SHIFT
  IF(V_OVERALL_STATUS     ='DO') THEN
    V_OVERALL_STATUS     :='WD';
  ELSIF (V_OVERALL_STATUS ='HD') THEN
    V_OVERALL_STATUS     :='WH';
  ELSIF (V_OVERALL_STATUS ='LV') THEN
    NULL;
  ELSIF (V_OVERALL_STATUS ='TV') THEN
    NULL;
  ELSIF (V_OVERALL_STATUS ='TN') THEN
    NULL;
  ELSIF(V_HALFDAY_FLAG    ='Y' AND V_HALFDAY_PERIOD IS NOT NULL) OR V_GRACE_PERIOD IS NOT NULL THEN
    V_OVERALL_STATUS     :='LP';
  ELSIF (V_OVERALL_STATUS = 'AB') THEN
    V_OVERALL_STATUS     :='PR';
  END IF;
  --
  IF (V_IN_TIME   IS NOT NULL) AND (V_LATE_START_TIME<V_IN_TIME) THEN
    V_LATE_STATUS :='L';
  END IF;
  --
  IF (V_OUT_TIME     IS NOT NULL) AND (V_EARLY_END_TIME>V_OUT_TIME) THEN
    IF (V_LATE_STATUS = 'L') THEN
      V_LATE_STATUS  :='B';
    ELSE
      V_LATE_STATUS :='E';
    END IF;
  END IF;
  --
  IF V_IN_TIME      IS NOT NULL AND V_OUT_TIME IS NULL THEN
    IF V_LATE_STATUS ='L' THEN
      V_LATE_STATUS := 'Y';
    ELSE
      V_LATE_STATUS := 'X';
    END IF;
  END IF;
  --
  IF V_IN_TIME IS NULL AND V_OUT_TIME IS NOT NULL THEN
    --    CHANGE WHEN NEW VALUE IS ADDED
    IF V_LATE_STATUS ='E' THEN
      V_LATE_STATUS := 'Y';
    ELSE
      V_LATE_STATUS := 'X';
    END IF;
  END IF;
  --
  SELECT COUNT(*)
  INTO V_LATE_COUNT
  FROM HRIS_ATTENDANCE_DETAIL
  WHERE EMPLOYEE_ID = P_EMPLOYEE_ID
  AND (ATTENDANCE_DT BETWEEN P_MONTH_START_DATE AND P_ATTENDANCE_DT )
  AND OVERALL_STATUS           IN ('PR','LA')
  AND LATE_STATUS              IN ('E','L','Y') ;
  IF V_LATE_STATUS             IN ('E','L','Y') THEN
    V_LATE_COUNT       := V_LATE_COUNT+1;
    IF V_LATE_COUNT    != 0 AND MOD(V_LATE_COUNT,4)=0 THEN
      V_OVERALL_STATUS := 'LA';
    END IF;
  END IF;
  --
  IF V_LATE_STATUS   ='B' AND V_OVERALL_STATUS='PR' THEN
    V_OVERALL_STATUS:='BA';
  END IF;
  --
  UPDATE HRIS_ATTENDANCE_DETAIL
  SET IN_TIME         = V_IN_TIME,
    OUT_TIME          =V_OUT_TIME,
    OVERALL_STATUS    = V_OVERALL_STATUS,
    LATE_STATUS       = V_LATE_STATUS,
    TOTAL_HOUR        = V_DIFF_IN_MIN
  WHERE ATTENDANCE_DT = TRUNC(P_ATTENDANCE_DT)
  AND EMPLOYEE_ID     = P_EMPLOYEE_ID;
END;/
            create or replace PROCEDURE HRIS_RECALCULATE_LEAVE(
    P_EMPLOYEE_ID HRIS_ATTENDANCE.EMPLOYEE_ID%TYPE  :=NULL,
    P_LEAVE_ID HRIS_LEAVE_MASTER_SETUP.LEAVE_ID%TYPE:=NULL)
AS
  V_TOTAL_NO_OF_DAYS         NUMBER;
  V_TOTAL_NO_OF_PENALTY_DAYS NUMBER;
  V_IS_ASSIGNED              CHAR(1 BYTE);
  V_TOTAL_NO_OF_ENCASH         NUMBER;
  V_STATUS CHAR(1 BYTE):='E';
BEGIN
  FOR leave_addition IN
  (SELECT LA.EMPLOYEE_ID,
    LA.LEAVE_ID,
    SUM(LA.NO_OF_DAYS) AS NO_OF_DAYS from 
    HRIS_EMPLOYEE_LEAVE_ADDITION LA
    LEFT JOIN HRIS_LEAVE_MASTER_SETUP LMS ON (LA.LEAVE_ID=LMS.LEAVE_ID)
    LEFT JOIN HRIS_EMPLOYEE_WORK_DAYOFF WD ON(WD.ID=LA.WOD_ID)
    LEFT JOIN HRIS_EMPLOYEE_WORK_HOLIDAY WH ON(WH.ID=LA.WOH_ID)
    LEFT JOIN HRIS_TRAINING_MASTER_SETUP tms ON (TMS.TRAINING_ID=LA.TRAINING_ID)
    WHERE LMS.STATUS=V_STATUS 
   GROUP BY LA.EMPLOYEE_ID,
    LA.LEAVE_ID
  )
  LOOP
    SELECT (
      CASE
        WHEN COUNT(*)>0
        THEN 'Y'
        ELSE 'N'
      END)
    INTO V_IS_ASSIGNED
    FROM HRIS_EMPLOYEE_LEAVE_ASSIGN
    WHERE EMPLOYEE_ID = leave_addition.EMPLOYEE_ID
    AND LEAVE_ID      = leave_addition.LEAVE_ID;
    IF(V_IS_ASSIGNED  ='Y')THEN
      UPDATE HRIS_EMPLOYEE_LEAVE_ASSIGN
      SET TOTAL_DAYS   = leave_addition.NO_OF_DAYS
      WHERE EMPLOYEE_ID= leave_addition.EMPLOYEE_ID
      AND LEAVE_ID     = leave_addition.LEAVE_ID;
    ELSE
      INSERT
      INTO HRIS_EMPLOYEE_LEAVE_ASSIGN
        (
          EMPLOYEE_ID,
          LEAVE_ID,
          TOTAL_DAYS,
          BALANCE,
          CREATED_DT
        )
        VALUES
        (
          leave_addition.EMPLOYEE_ID,
          leave_addition.LEAVE_ID,
          leave_addition.NO_OF_DAYS,
          0,
          TRUNC(SYSDATE)
        );
    END IF;
  END LOOP;
  --
  FOR leave_assign IN
  (SELECT A.*
    FROM HRIS_EMPLOYEE_LEAVE_ASSIGN A
    JOIN HRIS_LEAVE_MASTER_SETUP L
    ON (A.LEAVE_ID     = L.LEAVE_ID)
    WHERE L.IS_MONTHLY = 'N'
    AND L.STATUS=V_STATUS 
    AND (A.EMPLOYEE_ID =
      CASE
        WHEN P_EMPLOYEE_ID IS NOT NULL
        THEN P_EMPLOYEE_ID
      END
    OR P_EMPLOYEE_ID IS NULL)
    AND (A.LEAVE_ID   =
      CASE
        WHEN P_LEAVE_ID IS NOT NULL
        THEN P_LEAVE_ID
      END
    OR P_LEAVE_ID IS NULL)
  )
  LOOP
    BEGIN
      SELECT NVL(SUM(R.NO_OF_DAYS/(
        CASE
          WHEN R.HALF_DAY IN ('F','S')
          THEN 2
          ELSE 1
        END)),0) AS TOTAL_NO_OF_DAYS
      INTO V_TOTAL_NO_OF_DAYS
      FROM HRIS_EMPLOYEE_LEAVE_REQUEST R
      WHERE R.STATUS IN('AP','CP','CR')
      AND R.EMPLOYEE_ID = leave_assign.EMPLOYEE_ID
      AND R.LEAVE_ID    = leave_assign.LEAVE_ID
      GROUP BY R.EMPLOYEE_ID ,
        R.LEAVE_ID;
    EXCEPTION
    WHEN no_data_found THEN
      V_TOTAL_NO_OF_DAYS:=0;
    END;
    BEGIN
      SELECT NVL(SUM(NO_OF_DAYS),0)
      INTO V_TOTAL_NO_OF_PENALTY_DAYS
      FROM HRIS_EMPLOYEE_PENALTY_DAYS PD
      LEFT JOIN HRIS_LEAVE_MASTER_SETUP LMS ON (LMS.LEAVE_ID=PD.LEAVE_ID)
      WHERE LMS.STATUS=V_STATUS 
      AND PD.EMPLOYEE_ID = leave_assign.EMPLOYEE_ID
      AND PD.LEAVE_ID      = leave_assign.LEAVE_ID;
    EXCEPTION
    WHEN NO_DATA_FOUND THEN
      V_TOTAL_NO_OF_PENALTY_DAYS:=0;
    END;


 BEGIN
SELECT
     SUM(SLC.ENCASH_DAYS) AS TOTAL_NO_OF_ENCASH
      INTO V_TOTAL_NO_OF_ENCASH
      FROM Hris_Emp_Self_Leave_Closing SLC
      WHERE SLC.STATUS IN('E')
      AND SLC.EMPLOYEE_ID = leave_assign.EMPLOYEE_ID
      AND SLC.LEAVE_ID    = leave_assign.LEAVE_ID
      GROUP BY SLC.EMPLOYEE_ID ,
        SLC.LEAVE_ID;
        EXCEPTION
    WHEN NO_DATA_FOUND THEN
      V_TOTAL_NO_OF_ENCASH:=0;
    END;


    UPDATE HRIS_EMPLOYEE_LEAVE_ASSIGN
    SET BALANCE       = TOTAL_DAYS+PREVIOUS_YEAR_BAL - (V_TOTAL_NO_OF_DAYS+V_TOTAL_NO_OF_PENALTY_DAYS+V_TOTAL_NO_OF_ENCASH)
    WHERE EMPLOYEE_ID = leave_assign.EMPLOYEE_ID
    AND LEAVE_ID      = leave_assign.LEAVE_ID;
  END LOOP;
END;/
            create or replace PROCEDURE HRIS_RECALC_MONTHLY_LEAVES(
    P_EMPLOYEE_ID HRIS_ATTENDANCE.EMPLOYEE_ID%TYPE  :=NULL,
    P_LEAVE_ID HRIS_LEAVE_MASTER_SETUP.LEAVE_ID%TYPE:=NULL)
AS
V_BALANCE                     NUMBER;
BEGIN
  UPDATE HRIS_EMPLOYEE_LEAVE_ASSIGN
  SET BALANCE     =TOTAL_DAYS
  WHERE LEAVE_ID IN
    (SELECT LEAVE_ID FROM HRIS_LEAVE_MASTER_SETUP WHERE IS_MONTHLY='Y'
    )AND (EMPLOYEE_ID =
      CASE
        WHEN P_EMPLOYEE_ID IS NOT NULL
        THEN P_EMPLOYEE_ID
      END
    OR P_EMPLOYEE_ID IS NULL)
    AND (LEAVE_ID =
      CASE
        WHEN P_LEAVE_ID IS NOT NULL
        THEN P_LEAVE_ID
      END
    OR P_LEAVE_ID IS NULL);

    -- TO UPDATE MONTHYLY_LEAVE WHERE   CARYY FORWARD IS NO
  FOR leave IN
  (SELECT AA.EMPLOYEE_ID,
    AA.LEAVE_ID,
    AA.LEAVE_YEAR_MONTH_NO,
    SUM(AA.TOTAL_NO_OF_DAYS) AS  TOTAL_NO_OF_DAYS
    FROM(SELECT R.EMPLOYEE_ID,
    R.LEAVE_ID,
    M.LEAVE_YEAR_MONTH_NO,
   CASE WHEN 
   R.HALF_DAY IN ('F','S')
   THEN R.NO_OF_DAYS/2 
   ELSE R.NO_OF_DAYS 
   END  AS TOTAL_NO_OF_DAYS
  FROM HRIS_EMPLOYEE_LEAVE_REQUEST R
   LEFT JOIN (SELECT * FROM HRIS_LEAVE_YEARS  WHERE TRUNC(SYSDATE) BETWEEN START_DATE AND END_DATE ) LY ON (1=1)
  JOIN HRIS_LEAVE_MASTER_SETUP L
  ON (R.LEAVE_ID = L.LEAVE_ID),
    HRIS_LEAVE_MONTH_CODE M
  WHERE R.STATUS   = 'AP'
  AND L.IS_MONTHLY = 'Y'
  AND L.CARRY_FORWARD='N'
  AND R.START_DATE BETWEEN M.FROM_DATE AND M.TO_DATE
  AND R.START_DATE BETWEEN LY.START_DATE AND LY.END_DATE
  AND (R.EMPLOYEE_ID =
      CASE
        WHEN P_EMPLOYEE_ID IS NOT NULL
        THEN P_EMPLOYEE_ID
      END
    OR P_EMPLOYEE_ID IS NULL)
    AND (R.LEAVE_ID =
      CASE
        WHEN P_LEAVE_ID IS NOT NULL
        THEN P_LEAVE_ID
      END
    OR P_LEAVE_ID IS NULL)) AA
  GROUP BY AA.EMPLOYEE_ID,
    AA.LEAVE_ID,
    AA.LEAVE_YEAR_MONTH_NO
  )
  LOOP
    UPDATE HRIS_EMPLOYEE_LEAVE_ASSIGN
    SET BALANCE             = TOTAL_DAYS - leave.TOTAL_NO_OF_DAYS
    WHERE EMPLOYEE_ID       = leave.EMPLOYEE_ID
    AND LEAVE_ID            = leave.LEAVE_ID
    AND FISCAL_YEAR_MONTH_NO=leave.LEAVE_YEAR_MONTH_NO;
  END LOOP;


  -- TO UPDATE MONTHYLY_LEAVE WHERE   CARYY FORWARD IS YES

  FOR leave IN
  (SELECT EMPLOYEE_ID,LEAVE_ID,
SUM(TOTAL_NO_OF_DAYS ) AS TOTAL_NO_OF_DAYS FROM (
SELECT R.EMPLOYEE_ID,
    R.LEAVE_ID,
    SUM(R.NO_OF_DAYS) AS TOTAL_NO_OF_DAYS
  FROM HRIS_EMPLOYEE_LEAVE_REQUEST R
  JOIN HRIS_LEAVE_MASTER_SETUP L
  ON (R.LEAVE_ID = L.LEAVE_ID)
    LEFT JOIN (SELECT * FROM  HRIS_LEAVE_YEARS WHERE TRUNC(SYSDATE) BETWEEN START_DATE AND END_DATE )LY ON (1=1) 
  WHERE R.STATUS   = 'AP'
  AND L.IS_MONTHLY = 'Y'
  AND L.CARRY_FORWARD='Y' 
  AND R.HALF_DAY NOT IN ('F','S')
  AND R.START_DATE BETWEEN LY.START_DATE AND LY.END_DATE
  AND (R.EMPLOYEE_ID =
      CASE
        WHEN P_EMPLOYEE_ID IS NOT NULL
        THEN P_EMPLOYEE_ID
      END
    OR P_EMPLOYEE_ID IS NULL)
    AND (R.LEAVE_ID =
      CASE
        WHEN P_LEAVE_ID IS NOT NULL
        THEN P_LEAVE_ID
      END
    OR P_LEAVE_ID IS NULL)
  GROUP BY R.EMPLOYEE_ID,
    R.LEAVE_ID
    UNION ALL
    SELECT R.EMPLOYEE_ID,
    R.LEAVE_ID,
    SUM(R.NO_OF_DAYS)/2 AS TOTAL_NO_OF_DAYS
  FROM HRIS_EMPLOYEE_LEAVE_REQUEST R
  JOIN HRIS_LEAVE_MASTER_SETUP L
  ON (R.LEAVE_ID = L.LEAVE_ID)
    LEFT JOIN (SELECT * FROM  HRIS_LEAVE_YEARS WHERE TRUNC(SYSDATE) BETWEEN START_DATE AND END_DATE )LY ON (1=1) 
  WHERE R.STATUS   = 'AP'
  AND L.IS_MONTHLY = 'Y'
  AND L.CARRY_FORWARD='Y' 
  AND R.HALF_DAY IN ('F','S')
  AND R.START_DATE BETWEEN LY.START_DATE AND LY.END_DATE
  AND (R.EMPLOYEE_ID =
      CASE
        WHEN P_EMPLOYEE_ID IS NOT NULL
        THEN P_EMPLOYEE_ID
      END
    OR P_EMPLOYEE_ID IS NULL)
    AND (R.LEAVE_ID =
      CASE
        WHEN P_LEAVE_ID IS NOT NULL
        THEN P_LEAVE_ID
      END
    OR P_LEAVE_ID IS NULL)
  GROUP BY R.EMPLOYEE_ID,
    R.LEAVE_ID) GROUP BY EMPLOYEE_ID,LEAVE_ID)
  LOOP

   FOR LEAVE_ASSIGN_DTL IN (
          SELECT
            *
          FROM
            HRIS_EMPLOYEE_LEAVE_ASSIGN
          WHERE
              EMPLOYEE_ID =leave.EMPLOYEE_ID
            AND
              LEAVE_ID =leave.LEAVE_ID
          ORDER BY FISCAL_YEAR_MONTH_NO
        ) LOOP
            V_BALANCE := LEAVE_ASSIGN_DTL.TOTAL_DAYS + (case when LEAVE_ASSIGN_DTL.previous_year_bal is null then 0 else LEAVE_ASSIGN_DTL.previous_year_bal end) -leave.TOTAL_NO_OF_DAYS;
          UPDATE HRIS_EMPLOYEE_LEAVE_ASSIGN
            SET
              BALANCE = V_BALANCE
          WHERE
              EMPLOYEE_ID = LEAVE_ASSIGN_DTL.EMPLOYEE_ID
            AND
              LEAVE_ID = LEAVE_ASSIGN_DTL.LEAVE_ID
            AND
              FISCAL_YEAR_MONTH_NO = LEAVE_ASSIGN_DTL.FISCAL_YEAR_MONTH_NO;

        END LOOP;


  END LOOP;





END;/
            CREATE OR REPLACE PROCEDURE HRIS_SHIFT_ADD(
    P_EMPLOYEE_ID HRIS_EMPLOYEE_SHIFT_ASSIGN.EMPLOYEE_ID%TYPE,
    P_SHIFT_ID HRIS_EMPLOYEE_SHIFT_ASSIGN.SHIFT_ID%TYPE,
    P_START_DATE HRIS_EMPLOYEE_SHIFT_ASSIGN.START_DATE%TYPE,
    P_END_DATE HRIS_EMPLOYEE_SHIFT_ASSIGN.END_DATE%TYPE,
    P_CREATED_BY HRIS_EMPLOYEE_SHIFT_ASSIGN.CREATED_BY%TYPE)
AS
  V_ID HRIS_EMPLOYEE_SHIFT_ASSIGN.ID%TYPE;
BEGIN
  SELECT NVL(MAX(ID),0) +1 INTO V_ID FROM HRIS_EMPLOYEE_SHIFT_ASSIGN;
  INSERT
  INTO HRIS_EMPLOYEE_SHIFT_ASSIGN
    (
      ID,
      EMPLOYEE_ID,
      SHIFT_ID,
      START_DATE,
      END_DATE,
      CREATED_DT,
      CREATED_BY
    )
    VALUES
    (
      V_ID,
      P_EMPLOYEE_ID,
      P_SHIFT_ID,
      P_START_DATE,
      P_END_DATE,
      TRUNC(SYSDATE),
      P_CREATED_BY
    );
  IF TRUNC(P_START_DATE) <= TRUNC(SYSDATE) THEN
    HRIS_REATTENDANCE(TRUNC(P_START_DATE),P_EMPLOYEE_ID);
  END IF;
END;/
            CREATE OR REPLACE PROCEDURE HRIS_SHIFT_DELETE(
    P_ID HRIS_EMPLOYEE_SHIFT_ASSIGN.ID%TYPE )
AS
  V_OLD_START_DATE HRIS_EMPLOYEE_SHIFT_ASSIGN.START_DATE%TYPE;
  V_EMPLOYEE_ID HRIS_EMPLOYEE_SHIFT_ASSIGN.EMPLOYEE_ID%TYPE;
BEGIN
  SELECT START_DATE,
    EMPLOYEE_ID
  INTO V_OLD_START_DATE,
    V_EMPLOYEE_ID
  FROM HRIS_EMPLOYEE_SHIFT_ASSIGN
  WHERE ID =P_ID;
  --
  DELETE FROM HRIS_EMPLOYEE_SHIFT_ASSIGN WHERE ID =P_ID;
  --
  IF(TRUNC(V_OLD_START_DATE) <= TRUNC(SYSDATE))THEN
    HRIS_REATTENDANCE(TRUNC(V_OLD_START_DATE),V_EMPLOYEE_ID);
    --    DECLARE
    --      jobno NUMERIC;
    --    BEGIN
    --      dbms_job.submit(jobno, 'BEGIN HRIS_REATTENDANCE('''||TRUNC(V_OLD_START_DATE)||''','||V_EMPLOYEE_ID||'); END;', SYSTIMESTAMP + INTERVAL '10' SECOND, NULL);
    --      COMMIT;
    --    END;
  END IF;
END;/
            CREATE OR REPLACE PROCEDURE HRIS_SHIFT_EDIT(
    P_ID HRIS_EMPLOYEE_SHIFT_ASSIGN.ID%TYPE,
    P_SHIFT_ID HRIS_EMPLOYEE_SHIFT_ASSIGN.SHIFT_ID%TYPE,
    P_START_DATE HRIS_EMPLOYEE_SHIFT_ASSIGN.START_DATE%TYPE,
    P_END_DATE HRIS_EMPLOYEE_SHIFT_ASSIGN.END_DATE%TYPE,
    P_MODIFIED_BY HRIS_EMPLOYEE_SHIFT_ASSIGN.MODIFIED_BY%TYPE)
AS
  V_OLD_START_DATE HRIS_EMPLOYEE_SHIFT_ASSIGN.START_DATE%TYPE;
  V_EMPLOYEE_ID HRIS_EMPLOYEE_SHIFT_ASSIGN.EMPLOYEE_ID%TYPE;
BEGIN
  SELECT START_DATE,
    EMPLOYEE_ID
  INTO V_OLD_START_DATE,
    V_EMPLOYEE_ID
  FROM HRIS_EMPLOYEE_SHIFT_ASSIGN
  WHERE ID =P_ID;
  --
  IF(P_END_DATE IS NOT NULL) THEN
    UPDATE HRIS_EMPLOYEE_SHIFT_ASSIGN
    SET SHIFT_ID =P_SHIFT_ID,
      START_DATE =P_START_DATE,
      END_DATE   =P_END_DATE,
      MODIFIED_DT=SYSDATE,
      MODIFIED_BY=P_MODIFIED_BY
    WHERE ID     =P_ID;
  ELSE
    UPDATE HRIS_EMPLOYEE_SHIFT_ASSIGN
    SET SHIFT_ID =P_SHIFT_ID,
      START_DATE =P_START_DATE,
      MODIFIED_DT=SYSDATE,
      MODIFIED_BY=P_MODIFIED_BY
    WHERE ID     =P_ID;
  END IF;
  IF(TRUNC(V_OLD_START_DATE) <= TRUNC(P_START_DATE))THEN
    HRIS_REATTENDANCE(TRUNC(V_OLD_START_DATE),V_EMPLOYEE_ID);
  ELSE
    HRIS_REATTENDANCE(TRUNC(P_START_DATE),V_EMPLOYEE_ID);
  END IF;
END;/
            create or replace PROCEDURE HRIS_SYSTEM_NOTIFICATION(
    P_TO_EMPLOYEE_ID HRIS_NOTIFICATION.MESSAGE_TO%TYPE,
    P_MESSAGE_DATETIME HRIS_NOTIFICATION.MESSAGE_DATETIME%TYPE,
    P_MESSAGE_TITLE HRIS_NOTIFICATION.MESSAGE_TITLE%TYPE,
    P_MESSAGE_DESC HRIS_NOTIFICATION.MESSAGE_DESC%TYPE,
    P_ROUTE HRIS_NOTIFICATION.ROUTE%TYPE)
AS
  V_MESSAGE_ID HRIS_NOTIFICATION.MESSAGE_ID%TYPE;
  V_MESSAGE_FROM HRIS_NOTIFICATION.MESSAGE_FROM%TYPE;
  V_EXPIRY_TIME HRIS_NOTIFICATION.EXPIRY_TIME%TYPE;
BEGIN
  SELECT NVL(MAX(MESSAGE_ID),0)+1 INTO V_MESSAGE_ID FROM HRIS_NOTIFICATION;
  --
  SELECT EMPLOYEE_ID
  INTO V_MESSAGE_FROM
  FROM HRIS_EMPLOYEES
  WHERE IS_ADMIN='Y'
  AND ROWNUM    =1;
  --
  V_EXPIRY_TIME:=P_MESSAGE_DATETIME+14;
  --
  INSERT
  INTO HRIS_NOTIFICATION
    (
      MESSAGE_ID,
      MESSAGE_DATETIME,
      MESSAGE_TITLE,
      MESSAGE_DESC,
      MESSAGE_FROM,
      MESSAGE_TO,
      STATUS,
      EXPIRY_TIME,
      ROUTE
    )
    VALUES
    (
      V_MESSAGE_ID,
      P_MESSAGE_DATETIME,
      P_MESSAGE_TITLE,
      P_MESSAGE_DESC,
      V_MESSAGE_FROM,
      P_TO_EMPLOYEE_ID,
      'U',
      V_EXPIRY_TIME,
      P_ROUTE
    );
EXCEPTION
WHEN NO_DATA_FOUND THEN
  DBMS_OUTPUT.PUT_LINE ('No Admin is defined!!!' );
END;/
            CREATE OR REPLACE PROCEDURE HRIS_TRAVEL_CANCELLATION(
    P_TRAVEL_ID HRIS_EMPLOYEE_TRAVEL_REQUEST.TRAVEL_ID%TYPE )
    AS
    V_VOUCHER_NO   VARCHAR2(255 BYTE);
    TABLE_COUNT NUMBER(1,0);
begin
DBMS_OUTPUT.PUT_LINE ('NOthing to do');

end;



/
            CREATE OR REPLACE PROCEDURE HRIS_TRAVEL_LEAVE_REWARD(
P_TRAVEL_ID NUMBER
)
AS
BEGIN
 dbms_output.put_line('LEAVE ADDITION OF TRAVEL  LEAVE');
END;
/
            CREATE OR REPLACE PROCEDURE HRIS_TRAVEL_REQUEST_PROC(
    P_TRAVEL_ID HRIS_EMPLOYEE_TRAVEL_REQUEST.TRAVEL_ID%TYPE,
    P_LINK_TO_SYNERGY CHAR := 'N')
AS
  V_FROM_DATE HRIS_EMPLOYEE_TRAVEL_REQUEST.FROM_DATE%TYPE;
  V_TO_DATE HRIS_EMPLOYEE_TRAVEL_REQUEST.TO_DATE%TYPE;
  V_EMPLOYEE_ID HRIS_EMPLOYEE_TRAVEL_REQUEST.EMPLOYEE_ID%TYPE;
  V_STATUS HRIS_EMPLOYEE_TRAVEL_REQUEST.STATUS%TYPE;
  V_REQUESTED_AMOUNT HRIS_EMPLOYEE_TRAVEL_REQUEST.REQUESTED_AMOUNT%TYPE;
  V_SETTLEMENT_AMOUNT FLOAT;
  V_REQUEST_TYPE HRIS_EMPLOYEE_TRAVEL_REQUEST.REQUESTED_TYPE%TYPE;
  --
  V_LINK_TRAVEL_TO_SYNERGY HRIS_PREFERENCES.VALUE%TYPE;
  V_FORM_CODE HRIS_PREFERENCES.VALUE%TYPE;
  V_DR_ACC_CODE HRIS_PREFERENCES.VALUE%TYPE;
  V_CR_ACC_CODE HRIS_PREFERENCES.VALUE%TYPE;
  V_EXCESS_CR_ACC_CODE HRIS_PREFERENCES.VALUE%TYPE;
  V_LESS_DR_ACC_CODE HRIS_PREFERENCES.VALUE%TYPE;
  --
  V_COMPANY_CODE VARCHAR2(255 BYTE);
  V_BRANCH_CODE  VARCHAR2(255 BYTE);
  V_CREATED_BY   VARCHAR2(255 BYTE):='ADMIN';
  V_VOUCHER_NO   VARCHAR2(255 BYTE);
BEGIN
  BEGIN
    SELECT TR.FROM_DATE ,
      TR.TO_DATE,
      TR.EMPLOYEE_ID,
      TR.STATUS,
      TR.REQUESTED_AMOUNT,
      TR.REQUESTED_TYPE,
      C.COMPANY_CODE,
      C.COMPANY_CODE
      ||'.01',
      C.LINK_TRAVEL_TO_SYNERGY,
      C.FORM_CODE,
      C.DR_ACC_CODE,
      C.CR_ACC_CODE,
      C.EXCESS_CR_ACC_CODE,
      C.LESS_DR_ACC_CODE
    INTO V_FROM_DATE,
      V_TO_DATE,
      V_EMPLOYEE_ID,
      V_STATUS,
      V_REQUESTED_AMOUNT,
      V_REQUEST_TYPE,
      V_COMPANY_CODE,
      V_BRANCH_CODE,
      V_LINK_TRAVEL_TO_SYNERGY,
      V_FORM_CODE,
      V_DR_ACC_CODE,
      V_CR_ACC_CODE,
      V_EXCESS_CR_ACC_CODE,
      V_LESS_DR_ACC_CODE
    FROM HRIS_EMPLOYEE_TRAVEL_REQUEST TR
    JOIN HRIS_EMPLOYEES E
    ON (TR.EMPLOYEE_ID = E.EMPLOYEE_ID )
    JOIN HRIS_COMPANY C
    ON (E.COMPANY_ID= C.COMPANY_ID)
    WHERE TRAVEL_ID =P_TRAVEL_ID;
  EXCEPTION
  WHEN NO_DATA_FOUND THEN
    DBMS_OUTPUT.PUT('NO DATA FOUND FOR ID =>'|| P_TRAVEL_ID);
    RETURN;
  END;
  --
  IF V_STATUS IN ('AP','C') AND V_FROM_DATE <TRUNC(SYSDATE) THEN
    HRIS_REATTENDANCE(V_FROM_DATE,V_EMPLOYEE_ID,V_TO_DATE);
  END IF;
  --
END;/
            CREATE OR REPLACE PROCEDURE HRIS_UPDATE_EMPLOYEE_SERVICE(
    P_JOB_HISTORY_ID HRIS_JOB_HISTORY.JOB_HISTORY_ID%TYPE)
AS
BEGIN
  DECLARE
    JOB_HISTORY HRIS_JOB_HISTORY%ROWTYPE;
    IS_LATEST NUMBER:=0;
  BEGIN
    SELECT *
    INTO JOB_HISTORY
    FROM HRIS_JOB_HISTORY
    WHERE JOB_HISTORY_ID=P_JOB_HISTORY_ID AND STATUS = 'E';
    SELECT COUNT(*)
    INTO IS_LATEST
    FROM
      (SELECT MAX(START_DATE) AS MAX_START_DATE
      FROM HRIS_JOB_HISTORY
      WHERE EMPLOYEE_ID=JOB_HISTORY.EMPLOYEE_ID AND STATUS = 'E'
      GROUP BY EMPLOYEE_ID
      ) H
    WHERE H.MAX_START_DATE=JOB_HISTORY.START_DATE;
    IF (IS_LATEST         >0 AND JOB_HISTORY.START_DATE<=TRUNC(SYSDATE) ) THEN
      UPDATE HRIS_EMPLOYEES
      SET BRANCH_ID           =JOB_HISTORY.TO_BRANCH_ID,
        DEPARTMENT_ID         =JOB_HISTORY.TO_DEPARTMENT_ID,
        DESIGNATION_ID        =JOB_HISTORY.TO_DESIGNATION_ID,
        POSITION_ID           =JOB_HISTORY.TO_POSITION_ID,
        SERVICE_TYPE_ID       =JOB_HISTORY.TO_SERVICE_TYPE_ID,
        SERVICE_EVENT_TYPE_ID =JOB_HISTORY.SERVICE_EVENT_TYPE_ID,
        COMPANY_ID            =JOB_HISTORY.TO_COMPANY_ID,
        SALARY                = JOB_HISTORY.TO_SALARY,
        RETIRED_FLAG          = JOB_HISTORY.RETIRED_FLAG,
        STATUS                = (
        CASE
          WHEN JOB_HISTORY.DISABLED_FLAG = 'Y'
          THEN 'D'
          ELSE 'E'
        END)
      WHERE EMPLOYEE_ID =JOB_HISTORY.EMPLOYEE_ID;
    END IF;
  END;
END HRIS_UPDATE_EMPLOYEE_SERVICE; /
            CREATE OR REPLACE PROCEDURE HRIS_UPDATE_JOB_HISTORY(
    P_EMPLOYEE_ID HRIS_EMPLOYEES.EMPLOYEE_ID%TYPE)
AS
  V_LATEST_JOB_HISTORY_ID HRIS_JOB_HISTORY.JOB_HISTORY_ID%TYPE;
BEGIN
  SELECT JOB_HISTORY_ID
  INTO V_LATEST_JOB_HISTORY_ID
  FROM
    (SELECT *
    FROM HRIS_JOB_HISTORY
    WHERE EMPLOYEE_ID = P_EMPLOYEE_ID AND STATUS = 'E'
    ORDER BY START_DATE DESC
    )
  WHERE ROWNUM=1;
  FOR employee IN
  (SELECT * FROM HRIS_EMPLOYEES WHERE EMPLOYEE_ID = P_EMPLOYEE_ID
  )
  LOOP
    UPDATE HRIS_JOB_HISTORY
    SET TO_COMPANY_ID   = employee.COMPANY_ID,
      TO_BRANCH_ID      =employee.BRANCH_ID,
      TO_DEPARTMENT_ID  =employee.DEPARTMENT_ID,
      TO_DESIGNATION_ID =employee.DESIGNATION_ID,
      TO_POSITION_ID    =employee.POSITION_ID,
      TO_SERVICE_TYPE_ID=employee.SERVICE_TYPE_ID,
      TO_SALARY         =employee.SALARY,
      RETIRED_FLAG      = employee.RETIRED_FLAG,
      DISABLED_FLAG     = (
      CASE
        WHEN employee.STATUS= 'D'
        THEN 'N'
        ELSE 'Y'
      END)
    WHERE JOB_HISTORY_ID = V_LATEST_JOB_HISTORY_ID;
  END LOOP;
EXCEPTION
WHEN NO_DATA_FOUND THEN
  DBMS_OUTPUT.PUT_LINE ('No job history record found for employeeId : '||P_EMPLOYEE_ID);
END;
/
            create or replace PROCEDURE HRIS_WOD_LEAVE_ADDITION(
    P_WOD_ID HRIS_EMPLOYEE_WORK_DAYOFF.ID%TYPE )
AS
  V_EMPLOYEE_ID HRIS_EMPLOYEE_WORK_DAYOFF.EMPLOYEE_ID%TYPE;
  V_FROM_DATE HRIS_EMPLOYEE_WORK_DAYOFF.FROM_DATE%TYPE;
  V_TO_DATE HRIS_EMPLOYEE_WORK_DAYOFF.TO_DATE%TYPE;
  V_DURATION NUMBER;
  P_EMPLOYEE_ID HRIS_EMPLOYEES.EMPLOYEE_ID%TYPE;
  --
  V_TOTAL_HOUR HRIS_ATTENDANCE_DETAIL.TOTAL_HOUR%TYPE;
  --
  V_SUBSTITUTE_LEAVE_ID HRIS_LEAVE_MASTER_SETUP.LEAVE_ID%TYPE;
  V_BALANCE HRIS_EMPLOYEE_LEAVE_ASSIGN.BALANCE%TYPE;
  V_INCREMENT_DAY FLOAT    :=0;
  V_ON_TRAVEL CHAR(1 BYTE) :='N';
  V_SHIFT_ID  NUMBER(7,0);
BEGIN
-- get substitute leave id and set in variable V_SUBSTITUTE_LEAVE_ID
  SELECT LEAVE_ID
  INTO V_SUBSTITUTE_LEAVE_ID
  FROM HRIS_LEAVE_MASTER_SETUP
  WHERE STATUS='E' AND IS_SUBSTITUTE='Y'
  AND ROWNUM         = 1;
  --
  
  -- ger details of work on day off from param id and set in variables
  SELECT FROM_DATE,
    TO_DATE,
    TRUNC(TO_DATE)-TRUNC(FROM_DATE),
    EMPLOYEE_ID,
    APPROVED_BY
  INTO V_FROM_DATE,
    V_TO_DATE,
    V_DURATION,
    V_EMPLOYEE_ID,
    P_EMPLOYEE_ID
  FROM HRIS_EMPLOYEE_WORK_DAYOFF
  WHERE ID= P_WOD_ID;
  --
  
  --select past balance from HRIS_EMPLOYEE_LEAVE_ASSIGN  and set into variable
  -- if not found  the insert new record in HRIS_EMPLOYEE_LEAVE_ASSIGN 
  --for that employee and leave with balance and total 0
  BEGIN
    SELECT BALANCE
    INTO V_BALANCE
    FROM HRIS_EMPLOYEE_LEAVE_ASSIGN
    WHERE EMPLOYEE_ID=V_EMPLOYEE_ID
    AND LEAVE_ID     = V_SUBSTITUTE_LEAVE_ID;
  EXCEPTION
  WHEN no_data_found THEN
    INSERT
    INTO HRIS_EMPLOYEE_LEAVE_ASSIGN
      (
        EMPLOYEE_ID,
        LEAVE_ID,
        PREVIOUS_YEAR_BAL,
        TOTAL_DAYS,
        BALANCE,
        CREATED_DT,
        CREATED_BY
      )
      VALUES
      (
        V_EMPLOYEE_ID,
        V_SUBSTITUTE_LEAVE_ID,
        0,
        0,
        0,
        TRUNC(SYSDATE),
        P_EMPLOYEE_ID
      );
  END;
  --
  
  --loop until the duration of work on day applied
  FOR i IN 0..V_DURATION
  LOOP
  -- check attendance_details for that day(total worked hour,travel,shift_id) and set in variables
    BEGIN
      SELECT TOTAL_HOUR,
        (
        CASE
          WHEN TRAVEL_ID IS NOT NULL
          THEN 'Y'
          ELSE 'N'
        END),
        SHIFT_ID
      INTO V_TOTAL_HOUR,
        V_ON_TRAVEL,
        V_SHIFT_ID
      FROM HRIS_ATTENDANCE_DETAIL
      WHERE EMPLOYEE_ID= V_EMPLOYEE_ID
      AND ATTENDANCE_DT= TRUNC(V_FROM_DATE)+i;
    EXCEPTION
    WHEN no_data_found THEN
      CONTINUE;
    END;
    
    -- if on travel select total working hour from  shift and override into variable v-total_hour
    IF V_ON_TRAVEL = 'Y' THEN
      SELECT TOTAL_WORKING_HR
      INTO V_TOTAL_HOUR
      FROM HRIS_SHIFTS
      WHERE SHIFT_ID = V_SHIFT_ID;
    END IF;
    --
    -- check  total working hour  
    --if greater than 2 then add 0.5 leave if  greater tan 4 then 1 day leave 
    IF((V_TOTAL_HOUR /60)     >= 2 AND(V_TOTAL_HOUR /60) < 4) THEN
      V_INCREMENT_DAY         :=V_INCREMENT_DAY+.5;
    ELSIF ((V_TOTAL_HOUR /60) >=4) THEN
      V_INCREMENT_DAY         :=V_INCREMENT_DAY+1;
    END IF;
  END LOOP;
  --
BEGIN
DELETE FROM HRIS_EMPLOYEE_LEAVE_ADDITION  WHERE WOD_ID= P_WOD_ID;
END;
  INSERT
  INTO HRIS_EMPLOYEE_LEAVE_ADDITION
    (
      EMPLOYEE_ID,
      LEAVE_ID,
      NO_OF_DAYS,
      REMARKS,
      CREATED_DATE,
      WOD_ID,
      WOH_ID
    )
    VALUES
    (
      V_EMPLOYEE_ID,
      V_SUBSTITUTE_LEAVE_ID,
      V_INCREMENT_DAY,
      'WOD REWARD',
      TRUNC(SYSDATE),
      P_WOD_ID,
      NULL
    );
END;/
            create or replace PROCEDURE HRIS_WOD_OT_ADDITION(
    P_ID HRIS_EMPLOYEE_WORK_DAYOFF.ID%TYPE)
AS
  V_OVERTIME_ID HRIS_OVERTIME.OVERTIME_ID%TYPE;
  V_EMPLOYEE_ID HRIS_EMPLOYEE_WORK_HOLIDAY.EMPLOYEE_ID%TYPE ;
  V_RECOMMENDED_BY HRIS_EMPLOYEE_WORK_HOLIDAY.RECOMMENDED_BY%TYPE ;
  V_APPROVED_BY HRIS_EMPLOYEE_WORK_HOLIDAY.APPROVED_BY%TYPE;
  V_REQUESTED_DT HRIS_EMPLOYEE_WORK_HOLIDAY.REQUESTED_DATE%TYPE;
  V_FROM_DATE HRIS_EMPLOYEE_WORK_HOLIDAY.FROM_DATE%TYPE;
  V_TO_DATE HRIS_EMPLOYEE_WORK_HOLIDAY.TO_DATE%TYPE;
  V_STATUS        CHAR(2 BYTE)      :='AP';
  V_DESCRIPTION   VARCHAR2(255 BYTE):='THIS IS WOD OT.';
  V_TOTAL_HOUR    NUMBER ;
  V_DIFF          NUMBER;
  V_DETAIL_ID     NUMBER;
  V_START_TIME    DATE ;
  V_END_TIME      DATE ;
  V_DETAIL_STATUS CHAR(1 BYTE):='E';
BEGIN
  BEGIN
    SELECT EMPLOYEE_ID,
      RECOMMENDED_BY,
      APPROVED_BY,
      REQUESTED_DATE,
      FROM_DATE,
      TO_DATE
    INTO V_EMPLOYEE_ID,
      V_RECOMMENDED_BY,
      V_APPROVED_BY,
      V_REQUESTED_DT,
      V_FROM_DATE,
      V_TO_DATE
    FROM HRIS_EMPLOYEE_WORK_DAYOFF
    WHERE ID = P_ID;
  EXCEPTION
  WHEN NO_DATA_FOUND THEN
    DBMS_OUTPUT.PUT('No WOH found for '||P_ID);
  END;
  V_DIFF :=TRUNC(V_TO_DATE)-TRUNC(V_FROM_DATE);
  FOR i                   IN 0..V_DIFF
  LOOP
    SELECT NVL(MAX(OVERTIME_ID),1)+1 INTO V_OVERTIME_ID FROM HRIS_OVERTIME;
    SELECT NVL(MAX(DETAIL_ID),1)+1 INTO V_DETAIL_ID FROM HRIS_OVERTIME_DETAIL;
    BEGIN
      SELECT (
        CASE
          WHEN AD.OVERALL_STATUS = 'WD'
          THEN AD.IN_TIME
          ELSE S.START_TIME
        END),
        (
        CASE
          WHEN AD.OVERALL_STATUS ='WD'
          THEN AD.OUT_TIME
          ELSE S.END_TIME
        END),
        (
        CASE
          WHEN AD.OVERALL_STATUS ='WD'
          THEN AD.TOTAL_HOUR
          ELSE S.TOTAL_WORKING_HR
        END)
      INTO V_START_TIME,
        V_END_TIME,
        V_TOTAL_HOUR
      FROM HRIS_ATTENDANCE_DETAIL AD
      JOIN HRIS_SHIFTS S
      ON (AD.SHIFT_ID        =S.SHIFT_ID)
      WHERE AD.ATTENDANCE_DT = TRUNC(V_FROM_DATE)+i
      AND AD.EMPLOYEE_ID     =V_EMPLOYEE_ID
      AND AD.OVERALL_STATUS IN ( 'WD','VP');
    EXCEPTION
    WHEN NO_DATA_FOUND THEN
      CONTINUE;
    END;
    IF(V_START_TIME IS NOT NULL AND V_END_TIME IS NOT NULL ) THEN
      INSERT
      INTO HRIS_OVERTIME
        (
          OVERTIME_ID,
          EMPLOYEE_ID,
          OVERTIME_DATE,
          REQUESTED_DATE,
          DESCRIPTION,
          STATUS,
          RECOMMENDED_BY,
          RECOMMENDED_DATE,
          APPROVED_BY,
          APPROVED_DATE,
          TOTAL_HOUR,
          WOD_ID
        )
        VALUES
        (
          V_OVERTIME_ID,
          V_EMPLOYEE_ID,
          V_FROM_DATE+i,
          V_REQUESTED_DT,
          V_DESCRIPTION,
          V_STATUS,
          V_RECOMMENDED_BY,
          V_REQUESTED_DT,
          V_APPROVED_BY,
          V_REQUESTED_DT,
          V_TOTAL_HOUR,
          P_ID
        );
      INSERT
      INTO HRIS_OVERTIME_DETAIL
        (
          DETAIL_ID,
          OVERTIME_ID,
          START_TIME,
          END_TIME,
          STATUS,
          TOTAL_HOUR,
          WOD_ID
        )
        VALUES
        (
          V_DETAIL_ID,
          V_OVERTIME_ID,
          V_START_TIME,
          V_END_TIME,
          V_DETAIL_STATUS,
          V_TOTAL_HOUR,
          P_ID
        );
    END IF;
  END LOOP;
END;/
            create or replace PROCEDURE HRIS_WOD_REWARD
  (
    P_ID HRIS_EMPLOYEE_WORK_DAYOFF.ID%TYPE
  )
AS
  V_EMPLOYEE_ID HRIS_EMPLOYEE_WORK_DAYOFF.EMPLOYEE_ID%TYPE;
  V_WOH_FLAG HRIS_POSITIONS.WOH_FLAG%TYPE;
  V_FROM_DATE HRIS_EMPLOYEE_WORK_DAYOFF.FROM_DATE%TYPE;
  V_TO_DATE HRIS_EMPLOYEE_WORK_DAYOFF.TO_DATE%TYPE;
BEGIN
--select  work on day of details from HRIS_EMPLOYEE_WORK_DAYOFF Table
  SELECT EMPLOYEE_ID,
    TRUNC(FROM_DATE),
    TRUNC( TO_DATE)
  INTO V_EMPLOYEE_ID,
    V_FROM_DATE,
    V_TO_DATE
  FROM HRIS_EMPLOYEE_WORK_DAYOFF
  WHERE ID    = P_ID;
  -- if to date greater than today date then end procedure
  IF(V_TO_DATE>TRUNC(SYSDATE)) THEN 
    RETURN;
  END IF;
  --
  
  -- check if employeewise reward type set in  start
  BEGIN
  SELECT WOH_FLAG INTO V_WOH_FLAG FROM HRIS_EMPLOYEES WHERE EMPLOYEE_ID=V_EMPLOYEE_ID;
   EXCEPTION
  WHEN no_data_found THEN
    NULL;
  END;
  
  -- check if employeewise reward type set in  end
  
  
  -- select employee reward type in  position and set in variable  if not found terminate
  IF(V_WOH_FLAG IS NULL OR (V_WOH_FLAG!='O' AND V_WOH_FLAG!='L'))
  THEN
  BEGIN
    SELECT P.WOH_FLAG
    INTO V_WOH_FLAG
    FROM HRIS_EMPLOYEES E
    JOIN HRIS_POSITIONS P
    ON (E.POSITION_ID   = P.POSITION_ID)
    WHERE E.EMPLOYEE_ID =V_EMPLOYEE_ID;
  EXCEPTION
  WHEN no_data_found THEN
    HRIS_RAISE_ERR(V_EMPLOYEE_ID,'Work on dayoff reward could not be given.','Employee position is not set');
  END;
  
  END IF;
  --
  --delete from from necessary tables
  DELETE FROM HRIS_EMPLOYEE_LEAVE_ADDITION WHERE WOD_ID=P_ID;
  DELETE FROM HRIS_OVERTIME_DETAIL WHERE WOD_ID= P_ID;
  DELETE FROM HRIS_OVERTIME WHERE WOD_ID = P_ID;
  -- call another procedure acordint to reward type
  IF V_WOH_FLAG ='L' THEN
    HRIS_WOD_LEAVE_ADDITION(P_ID);
  ELSIF V_WOH_FLAG ='O' THEN
    HRIS_WOD_OT_ADDITION(P_ID);
  END IF;
END;
 /
            create or replace PROCEDURE HRIS_WOH_LEAVE_ADDITION(
    P_WOH_ID HRIS_EMPLOYEE_WORK_HOLIDAY.ID%TYPE )
AS
  V_EMPLOYEE_ID HRIS_EMPLOYEE_WORK_HOLIDAY.EMPLOYEE_ID%TYPE;
  V_FROM_DATE HRIS_EMPLOYEE_WORK_HOLIDAY.FROM_DATE%TYPE;
  V_TO_DATE HRIS_EMPLOYEE_WORK_HOLIDAY.TO_DATE%TYPE;
  V_DURATION NUMBER;
  P_EMPLOYEE_ID HRIS_EMPLOYEES.EMPLOYEE_ID%TYPE;
  --
  V_TOTAL_HOUR HRIS_ATTENDANCE_DETAIL.TOTAL_HOUR%TYPE;
  --
  V_SUBSTITUTE_LEAVE_ID HRIS_LEAVE_MASTER_SETUP.LEAVE_ID%TYPE;
  V_BALANCE HRIS_EMPLOYEE_LEAVE_ASSIGN.BALANCE%TYPE;
  V_INCREMENT_DAY FLOAT    :=0;
  V_ON_TRAVEL CHAR(1 BYTE) :='N';
  V_SHIFT_ID  NUMBER(7,0);
BEGIN
  SELECT LEAVE_ID
  INTO V_SUBSTITUTE_LEAVE_ID
  FROM HRIS_LEAVE_MASTER_SETUP
  WHERE STATUS='E' AND IS_SUBSTITUTE='Y'
  AND ROWNUM         = 1;
  --
  SELECT FROM_DATE,
    TO_DATE,
    TRUNC(TO_DATE)-TRUNC(FROM_DATE),
    EMPLOYEE_ID,
    APPROVED_BY
  INTO V_FROM_DATE,
    V_TO_DATE,
    V_DURATION,
    V_EMPLOYEE_ID,
    P_EMPLOYEE_ID
  FROM HRIS_EMPLOYEE_WORK_HOLIDAY
  WHERE ID= P_WOH_ID;
  --
  BEGIN
    SELECT BALANCE
    INTO V_BALANCE
    FROM HRIS_EMPLOYEE_LEAVE_ASSIGN
    WHERE EMPLOYEE_ID=V_EMPLOYEE_ID
    AND LEAVE_ID     = V_SUBSTITUTE_LEAVE_ID;
  EXCEPTION
  WHEN no_data_found THEN
    INSERT
    INTO HRIS_EMPLOYEE_LEAVE_ASSIGN
      (
        EMPLOYEE_ID,
        LEAVE_ID,
        PREVIOUS_YEAR_BAL,
        TOTAL_DAYS,
        BALANCE,
        CREATED_DT,
        CREATED_BY
      )
      VALUES
      (
        V_EMPLOYEE_ID,
        V_SUBSTITUTE_LEAVE_ID,
        0,
        0,
        0,
        TRUNC(SYSDATE),
        P_EMPLOYEE_ID
      );
  END;
  --
  FOR i IN 0..V_DURATION
  LOOP
    BEGIN
      SELECT TOTAL_HOUR,
        (
        CASE
          WHEN TRAVEL_ID IS NOT NULL
          THEN 'Y'
          ELSE 'N'
        END),
        SHIFT_ID
      INTO V_TOTAL_HOUR,
        V_ON_TRAVEL,
        V_SHIFT_ID
      FROM HRIS_ATTENDANCE_DETAIL
      WHERE EMPLOYEE_ID= V_EMPLOYEE_ID
      AND ATTENDANCE_DT= TRUNC(V_FROM_DATE)+i;
    EXCEPTION
    WHEN no_data_found THEN
      CONTINUE;
    END;
    --
    IF V_ON_TRAVEL = 'Y' THEN
      SELECT TOTAL_WORKING_HR
      INTO V_TOTAL_HOUR
      FROM HRIS_SHIFTS
      WHERE SHIFT_ID = V_SHIFT_ID;
    END IF;
    --
    IF((V_TOTAL_HOUR /60) >= 2 AND (V_TOTAL_HOUR /60) < 4) THEN
      V_INCREMENT_DAY         :=(V_INCREMENT_DAY+.5);
    ELSIF ((V_TOTAL_HOUR /60) >=4) THEN
      V_INCREMENT_DAY         :=(V_INCREMENT_DAY+1);
    END IF;
  END LOOP;
--
  INSERT
  INTO HRIS_EMPLOYEE_LEAVE_ADDITION
    (
      EMPLOYEE_ID,
      LEAVE_ID,
      NO_OF_DAYS,
      REMARKS,
      CREATED_DATE,
      WOD_ID,
      WOH_ID
    )
    VALUES
    (
      V_EMPLOYEE_ID,
      V_SUBSTITUTE_LEAVE_ID,
      V_INCREMENT_DAY,
      'WOH REWARD',
      TRUNC(SYSDATE),
      NULL,
      P_WOH_ID
    );
END;/
            create or replace PROCEDURE HRIS_WOH_OT_ADDITION(
    P_ID HRIS_EMPLOYEE_WORK_HOLIDAY.ID%TYPE)
AS
  V_OVERTIME_ID HRIS_OVERTIME.OVERTIME_ID%TYPE;
  V_EMPLOYEE_ID HRIS_EMPLOYEE_WORK_HOLIDAY.EMPLOYEE_ID%TYPE ;
  V_RECOMMENDED_BY HRIS_EMPLOYEE_WORK_HOLIDAY.RECOMMENDED_BY%TYPE ;
  V_APPROVED_BY HRIS_EMPLOYEE_WORK_HOLIDAY.APPROVED_BY%TYPE;
  V_REQUESTED_DT HRIS_EMPLOYEE_WORK_HOLIDAY.REQUESTED_DATE%TYPE;
  V_FROM_DATE HRIS_EMPLOYEE_WORK_HOLIDAY.FROM_DATE%TYPE;
  V_TO_DATE HRIS_EMPLOYEE_WORK_HOLIDAY.TO_DATE%TYPE;
  V_STATUS        CHAR(2 BYTE)      :='AP';
  V_DESCRIPTION   VARCHAR2(255 BYTE):='THIS IS WOH OT.';
  V_TOTAL_HOUR    NUMBER ;
  V_DIFF          NUMBER;
  V_DETAIL_ID     NUMBER;
  V_START_TIME    DATE ;
  V_END_TIME      DATE ;
  V_DETAIL_STATUS CHAR(1 BYTE):='E';
BEGIN
  BEGIN
    SELECT EMPLOYEE_ID,
      RECOMMENDED_BY,
      APPROVED_BY,
      REQUESTED_DATE,
      FROM_DATE,
      TO_DATE
    INTO V_EMPLOYEE_ID,
      V_RECOMMENDED_BY,
      V_APPROVED_BY,
      V_REQUESTED_DT,
      V_FROM_DATE,
      V_TO_DATE
    FROM HRIS_EMPLOYEE_WORK_HOLIDAY
    WHERE ID = P_ID;
  EXCEPTION
  WHEN NO_DATA_FOUND THEN
    DBMS_OUTPUT.PUT('No WOH found for '||P_ID);
  END;
  V_DIFF :=TRUNC(V_TO_DATE)-TRUNC(V_FROM_DATE);
  FOR i                   IN 0..V_DIFF
  LOOP
    SELECT NVL(MAX(OVERTIME_ID),1)+1 INTO V_OVERTIME_ID FROM HRIS_OVERTIME;
    SELECT NVL(MAX(DETAIL_ID),1)+1 INTO V_DETAIL_ID FROM HRIS_OVERTIME_DETAIL;
    BEGIN
      SELECT (
        CASE
          WHEN AD.OVERALL_STATUS = 'WD'
          THEN AD.IN_TIME
          ELSE S.START_TIME
        END),
        (
        CASE
          WHEN AD.OVERALL_STATUS ='WD'
          THEN AD.OUT_TIME
          ELSE S.END_TIME
        END),
        (
        CASE
          WHEN AD.OVERALL_STATUS ='WD'
          THEN AD.TOTAL_HOUR
          ELSE S.TOTAL_WORKING_HR
        END)
      INTO V_START_TIME,
        V_END_TIME,
        V_TOTAL_HOUR
      FROM HRIS_ATTENDANCE_DETAIL AD
      JOIN HRIS_SHIFTS S
      ON (AD.SHIFT_ID        =S.SHIFT_ID)
      WHERE AD.ATTENDANCE_DT = TRUNC(V_FROM_DATE)+i
      AND AD.EMPLOYEE_ID     =V_EMPLOYEE_ID
      AND AD.OVERALL_STATUS IN ( 'WH','VP');
    EXCEPTION
    WHEN NO_DATA_FOUND THEN
      CONTINUE;
    END;
    IF(V_START_TIME IS NOT NULL AND V_END_TIME IS NOT NULL ) THEN
      INSERT
      INTO HRIS_OVERTIME
        (
          OVERTIME_ID,
          EMPLOYEE_ID,
          OVERTIME_DATE,
          REQUESTED_DATE,
          DESCRIPTION,
          STATUS,
          RECOMMENDED_BY,
          RECOMMENDED_DATE,
          APPROVED_BY,
          APPROVED_DATE,
          TOTAL_HOUR,
          WOH_ID
        )
        VALUES
        (
          V_OVERTIME_ID,
          V_EMPLOYEE_ID,
          V_FROM_DATE+i,
          V_REQUESTED_DT,
          V_DESCRIPTION,
          V_STATUS,
          V_RECOMMENDED_BY,
          V_REQUESTED_DT,
          V_APPROVED_BY,
          V_REQUESTED_DT,
          V_TOTAL_HOUR,
          P_ID
        );
      INSERT
      INTO HRIS_OVERTIME_DETAIL
        (
          DETAIL_ID,
          OVERTIME_ID,
          START_TIME,
          END_TIME,
          STATUS,
          TOTAL_HOUR,
          WOH_ID
        )
        VALUES
        (
          V_DETAIL_ID,
          V_OVERTIME_ID,
          V_START_TIME,
          V_END_TIME,
          V_DETAIL_STATUS,
          V_TOTAL_HOUR,
          P_ID
        );
    END IF;
  END LOOP;
END;/
            create or replace PROCEDURE HRIS_WOH_REWARD
  (
    P_ID HRIS_EMPLOYEE_WORK_HOLIDAY.ID%TYPE
  )
AS
  V_EMPLOYEE_ID HRIS_EMPLOYEE_WORK_HOLIDAY.EMPLOYEE_ID%TYPE;
  V_WOH_FLAG HRIS_POSITIONS.WOH_FLAG%TYPE;
  V_FROM_DATE HRIS_EMPLOYEE_WORK_HOLIDAY.FROM_DATE%TYPE;
  V_TO_DATE HRIS_EMPLOYEE_WORK_HOLIDAY.TO_DATE%TYPE;
BEGIN
  SELECT EMPLOYEE_ID,
    TRUNC( FROM_DATE),
    TRUNC(TO_DATE)
  INTO V_EMPLOYEE_ID,
    V_FROM_DATE,
    V_TO_DATE
  FROM HRIS_EMPLOYEE_WORK_HOLIDAY
  WHERE ID    = P_ID;
  IF(V_TO_DATE>TRUNC(SYSDATE)) THEN
    RETURN;
  END IF;
  --
  
   -- check if employeewise reward type set in  start
  BEGIN
  SELECT WOH_FLAG INTO V_WOH_FLAG FROM HRIS_EMPLOYEES WHERE EMPLOYEE_ID=V_EMPLOYEE_ID;
   EXCEPTION
  WHEN no_data_found THEN
    NULL;
  END;
  
  -- check if employeewise reward type set in  end
  
  
  -- select employee reward type in  position and set in variable  if not found terminate
  IF(V_WOH_FLAG IS NULL OR (V_WOH_FLAG!='O' AND V_WOH_FLAG!='L'))
  THEN
  BEGIN
    SELECT P.WOH_FLAG
    INTO V_WOH_FLAG
    FROM HRIS_EMPLOYEES E
    JOIN HRIS_POSITIONS P
    ON (E.POSITION_ID   = P.POSITION_ID)
    WHERE E.EMPLOYEE_ID =V_EMPLOYEE_ID;
  EXCEPTION
  WHEN no_data_found THEN
    HRIS_RAISE_ERR(V_EMPLOYEE_ID,'Work on dayoff reward could not be given.','Employee position is not set');
  END;
  END IF;
  --
DELETE FROM HRIS_EMPLOYEE_LEAVE_ADDITION WHERE WOH_ID=P_ID;
       DELETE FROM HRIS_OVERTIME_DETAIL WHERE WOH_ID= P_ID;
      DELETE FROM HRIS_OVERTIME WHERE WOH_ID = P_ID;
  IF V_WOH_FLAG ='L' THEN
    HRIS_WOH_LEAVE_ADDITION(P_ID);
  ELSIF V_WOH_FLAG ='O' THEN
    HRIS_WOH_OT_ADDITION(P_ID);
  END IF;
END;/
            create or replace PROCEDURE HRIS_TRAINING_LEAVE_REWARD( p_employee_id NUMBER,p_training_id NUMBER ) AS

    v_shift_id              NUMBER;
    v_training_start_date   DATE;
    v_training_end_date     DATE;
    v_duration              NUMBER;
    v_training_type         hris_training_master_setup.training_type%TYPE;
    v_daily_training_hour   hris_training_master_setup.daily_training_hour%TYPE;
    v_is_present            NUMBER;
    v_weekday1              hris_shifts.weekday1%TYPE;
    v_weekday2              hris_shifts.weekday2%TYPE;
    v_weekday3              hris_shifts.weekday3%TYPE;
    v_weekday4              hris_shifts.weekday4%TYPE;
    v_weekday5              hris_shifts.weekday5%TYPE;
    v_weekday6              hris_shifts.weekday6%TYPE;
    v_weekday7              hris_shifts.weekday7%TYPE;
    v_dayoff                VARCHAR2(1 BYTE);
    v_holiday_count         NUMBER;
    v_holiday               CHAR(1 BYTE);
    v_sub_days              NUMBER := 0;
    v_sub_leave_id          NUMBER;
    v_increament            NUMBER;
    v_assign_status         CHAR(1 BYTE);      
BEGIN
    dbms_output.put_line('LEAVE ADDITION');

-- TO SELECT TRAINING DEATILS
    SELECT
        ms.start_date,
        ms.end_date,
        ms.duration,
        ms.training_type,
        CASE
            WHEN ms.daily_training_hour < 2  THEN 0
            WHEN
                ms.daily_training_hour >= 2
            AND
                ms.daily_training_hour < 4
            THEN 0.5
            WHEN ms.daily_training_hour >= 4 THEN 1
        END,
        case  ta.STATUS when 'E'
        then 'Y'
        else 'N'
        end
    INTO
        v_training_start_date,v_training_end_date,v_duration,v_training_type,v_increament,v_assign_status
    FROM
        hris_training_master_setup ms
        left join HRIS_EMPLOYEE_TRAINING_ASSIGN ta on (ta.training_id=ms.training_id and ta.status='E' and ta.employee_id=p_employee_id) 
    WHERE
        ms.training_id = p_training_id;

    IF
        ( v_training_type = 'CC' )
    THEN

     DELETE FROM hris_employee_leave_addition WHERE
                    employee_id = p_employee_id
                AND
                    training_id = p_training_id;


    IF v_assign_status='Y'
    THEN


        FOR i IN 0..v_duration - 1 LOOP

        IF((v_training_start_date+i)<=trunc(sysdate))
        THEN
            dbms_output.put_line(v_training_start_date + i);
            BEGIN
                SELECT
                    COUNT(*)
                INTO
                    v_is_present
                FROM
                    hris_emp_training_attendance
                WHERE
                        training_id = p_training_id
                    AND
                        employee_id = p_employee_id
                    AND
                        training_dt = v_training_start_date + i
                    AND
                        attendance_status = 'P';

            END;

            IF
                ( v_is_present > 0 )
            THEN
                BEGIN
                    SELECT
                        hs.shift_id,
                        weekday1,
                        weekday2,
                        weekday3,
                        weekday4,
                        weekday5,
                        weekday6,
                        weekday7
                    INTO
                        v_shift_id,v_weekday1,v_weekday2,v_weekday3,v_weekday4,v_weekday5,v_weekday6,v_weekday7
                    FROM
                        hris_employee_shift_roaster es,
                        hris_shifts hs
                    WHERE
                            1 = 1
                        AND
                            es.employee_id = p_employee_id
                        AND
                            trunc(es.for_date) = v_training_start_date + i
                        AND
                            hs.status = 'E'
                        AND
                            es.shift_id = hs.shift_id;

                EXCEPTION
                    WHEN no_data_found THEN
                        BEGIN
                            SELECT
                                hs.shift_id,
                                weekday1,
                                weekday2,
                                weekday3,
                                weekday4,
                                weekday5,
                                weekday6,
                                weekday7
                            INTO
                                v_shift_id,v_weekday1,v_weekday2,v_weekday3,v_weekday4,v_weekday5,v_weekday6,v_weekday7
                            FROM
                                (
                                    SELECT
                                        *
                                    FROM
                                        (
                                            SELECT
                                                *
                                            FROM
                                                hris_employee_shift_assign
                                            WHERE
                                                    employee_id = p_employee_id
                                                AND (
                                                        trunc(v_training_start_date + i) >= start_date
                                                    AND
                                                        trunc(v_training_start_date + i) <=
                                                            CASE
                                                                WHEN end_date IS NOT NULL THEN end_date
                                                                ELSE trunc(v_training_start_date + i)
                                                            END
                                                )
                                            ORDER BY start_date DESC,end_date ASC
                                        )
                                    WHERE
                                        ROWNUM = 1
                                ) es,
                                hris_shifts hs
                            WHERE
                                es.shift_id = hs.shift_id;

                        EXCEPTION
                            WHEN no_data_found THEN
                                BEGIN
                                    SELECT
                                        shift_id,
                                        weekday1,
                                        weekday2,
                                        weekday3,
                                        weekday4,
                                        weekday5,
                                        weekday6,
                                        weekday7
                                    INTO
                                        v_shift_id,v_weekday1,v_weekday2,v_weekday3,v_weekday4,v_weekday5,v_weekday6,v_weekday7
                                    FROM
                                        hris_shifts
                                    WHERE
                                            v_training_start_date + i BETWEEN start_date AND end_date
                                        AND
                                            default_shift = 'Y'
                                        AND
                                            status = 'E'
                                        AND
                                            ROWNUM = 1;

                                EXCEPTION
                                    WHEN no_data_found THEN
                                        raise_application_error(-20344,'No default and normal shift defined for this time period');
                                END;
                        END;
                END;

                dbms_output.put_line(v_shift_id);
                v_dayoff := 'N';
                BEGIN
                    IF
                        ( TO_CHAR(v_training_start_date + i,'D') = '1' )
                    THEN
                        IF
                            v_weekday1 = 'DAY_OFF'
                        THEN
                            v_dayoff := 'Y';
                        END IF;
                    ELSIF ( TO_CHAR(v_training_start_date + i,'D') = '2' ) THEN
                        IF
                            v_weekday2 = 'DAY_OFF'
                        THEN
                            v_dayoff := 'Y';
                        END IF;
                    ELSIF ( TO_CHAR(v_training_start_date + i,'D') = '3' ) THEN
                        IF
                            v_weekday3 = 'DAY_OFF'
                        THEN
                            v_dayoff := 'Y';
                        END IF;
                    ELSIF ( TO_CHAR(v_training_start_date + i,'D') = '4' ) THEN
                        IF
                            v_weekday4 = 'DAY_OFF'
                        THEN
                            v_dayoff := 'Y';
                        END IF;
                    ELSIF ( TO_CHAR(v_training_start_date + i,'D') = '5' ) THEN
                        IF
                            v_weekday5 = 'DAY_OFF'
                        THEN
                            v_dayoff := 'Y';
                        END IF;
                    ELSIF ( TO_CHAR(v_training_start_date + i,'D') = '6' ) THEN
                        IF
                            v_weekday6 = 'DAY_OFF'
                        THEN
                            v_dayoff := 'Y';
                        END IF;
                    ELSIF ( TO_CHAR(v_training_start_date + i,'D') = '7' ) THEN
                        IF
                            v_weekday7 = 'DAY_OFF'
                        THEN
                            v_dayoff := 'Y';
                        END IF;
                    END IF;
                END;
                dbms_output.put_line('DAYOFF ' || v_dayoff);
                dbms_output.put_line('SUBdAYS test ' || v_sub_days);
                IF
                    ( v_dayoff = 'Y' )
                THEN
                dbms_output.put_line('today is day off day');
                    v_sub_days := v_sub_days + v_increament;
                ELSE
                    BEGIN
                        SELECT
                            COUNT(*)
                        INTO
                            v_holiday_count
                        FROM
                            hris_holiday_master_setup hs
                            LEFT JOIN hris_employee_holiday eha ON (
                                hs.holiday_id = eha.holiday_id
                            )
                        WHERE
                                eha.employee_id = p_employee_id
                            AND
                                v_training_start_date + i BETWEEN start_date AND end_date;

                        IF
                            ( v_holiday_count > 0 )
                        THEN
                            dbms_output.put_line('HOLIDAY');
                            v_sub_days := v_sub_days + v_increament;
                        END IF;

                    END;
                END IF;

            END IF;

            dbms_output.put_line('-----------');
            END IF;
        END LOOP;

        dbms_output.put_line('SUBdAYS ' || v_sub_days);
        BEGIN
            SELECT
                leave_id
            INTO
                v_sub_leave_id
            FROM
                hris_leave_master_setup
            WHERE
                is_substitute = 'Y';



            IF
                ( v_sub_days > 0 )
            THEN
                INSERT INTO hris_employee_leave_addition VALUES (
                    p_employee_id,
                    v_sub_leave_id,
                    v_sub_days,
                    'WOT REWARD',
                    trunc(SYSDATE),
                    NULL,
                    NULL,
                    p_training_id,
                    NULL
                );

            END IF;

        END;


    END IF;

    END IF;

END;/
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
    

   
    
    
END;/
            create or replace PROCEDURE hris_attd_insert_exe (
    p_thumb_id          NUMBER,
    p_attendance_dt     DATE,
    p_ip_address        VARCHAR2,
    p_attendance_from   VARCHAR2,
    p_attendance_time   TIMESTAMP,
    p_remarks           VARCHAR2 := NULL
) AS
    v_employee_id   NUMBER := NULL;
    v_purpose       hris_attd_device_master.purpose%TYPE := 'I/0';
BEGIN
    BEGIN
        SELECT
            purpose
        INTO
            v_purpose
        FROM
            hris_attd_device_master
        WHERE
            device_ip = p_ip_address;

    EXCEPTION
        WHEN no_data_found THEN
            NULL;
    END;

    BEGIN
        SELECT
            employee_id
        INTO
            v_employee_id
        FROM
            hris_employees
        WHERE
        status='E' and
            id_thumb_id = to_char(p_thumb_id);

    EXCEPTION
        WHEN no_data_found THEN
           NULL;

        WHEN too_many_rows THEN
            NULL;

    END;




    BEGIN
        IF
            v_employee_id IS NOT NULL
        THEN

    UPDATE hris_attendance 
    SET
    employee_id=v_employee_id,
    CHECKED='Y'
    WHERE 
    ATTENDANCE_DT=p_attendance_dt
    and ATTENDANCE_TIME=p_attendance_time
    and THUMB_ID=p_thumb_id;


           hris_attendance_after_insert(
              v_employee_id,
               p_attendance_dt,
                p_attendance_time,
                p_remarks,
                v_purpose
           );
        END IF;
    END;

EXCEPTION
    WHEN OTHERS THEN
        dbms_output.put_line('THUMB_ID: '
         || p_thumb_id
         || 'ATTENDANCE_DT:'
         || p_attendance_dt
         || 'IP_ADDRESS: '
         || p_ip_address
         || 'P_ATTENDANCE_FROM: '
         || p_attendance_from);
END;
 
 /
            CREATE OR REPLACE PROCEDURE hris_loan_payment_flag_change (
    p_employee_id   hris_attendance_detail.employee_id%TYPE,
    p_sheet_no      hris_salary_sheet.sheet_no%TYPE
) AS
V_MONTH_ID INT;
    v_loan_amt      FLOAT;
    v_intrest_amt   FLOAT;
    v_loan_amt_pd      FLOAT;
    v_intrest_amt_pd   FLOAT;
BEGIN

SELECT MONTH_ID INTO V_MONTH_ID FROM Hris_Salary_Sheet where sheet_no=p_sheet_no;

    FOR loan_list IN (
        SELECT
            Loan_Id,
            pay_id_amt,
            pay_id_int
        FROM
            hris_loan_master_setup
        WHERE
            status = 'E'
    ) LOOP
        dbms_output.put_line('SDFSDF');
        BEGIN
            SELECT
                val
            INTO
                v_loan_amt
            FROM
                hris_salary_sheet_detail
            WHERE
                    sheet_no = p_sheet_no
                AND
                    employee_id = p_employee_id
                AND
                    pay_id = loan_list.pay_id_amt;

        EXCEPTION
            WHEN no_data_found THEN
                v_loan_amt := 0;
        END;

        BEGIN
            SELECT
                val
            INTO
                v_intrest_amt
            FROM
                hris_salary_sheet_detail
            WHERE
                    sheet_no = p_sheet_no
                AND
                    employee_id = p_employee_id
                AND
                    pay_id = loan_list.pay_id_int;

        EXCEPTION
            WHEN no_data_found THEN
                v_intrest_amt := 0;
        END;
        
        
        BEGIN
        select 
        sum(AMOUNT) INTO v_loan_amt_pd
        from Hris_Loan_Payment_Detail pd
        left join hris_employee_loan_request lr on (pd.Loan_Request_Id=lr.loan_request_id)
        join hris_month_code mc on (Mc.From_Date=trunc(Pd.From_Date,'month') and Mc.To_Date=Pd.To_Date)
        where 
        lr.loan_status='OPEN'
        and Lr.Employee_Id=p_employee_id
        and mc.month_id=V_MONTH_ID
        and lr.loan_id=loan_list.Loan_Id;
        EXCEPTION
            WHEN no_data_found THEN
                v_loan_amt_pd := 0;
        END;
        
       
        
        BEGIN
        select 
        sum(INTEREST_AMOUNT) INTO v_intrest_amt_pd
        from Hris_Loan_Payment_Detail pd
        left join hris_employee_loan_request lr on (pd.Loan_Request_Id=lr.loan_request_id)
        join hris_month_code mc on (Mc.From_Date=trunc(Pd.From_Date,'month') and Mc.To_Date=Pd.To_Date)
        where 
        lr.loan_status='OPEN'
        and Lr.Employee_Id=p_employee_id
        and mc.month_id=V_MONTH_ID
        and lr.loan_id=loan_list.Loan_Id;
        EXCEPTION
            WHEN no_data_found THEN
                v_intrest_amt_pd := 0;
        END;
        
      --  dbms_output.put_line(ROUND(v_loan_amt,2));
       --  dbms_output.put_line(ROUND(v_loan_amt_pd,2));
      --   dbms_output.put_line(ROUND(v_intrest_amt,2));
       --  dbms_output.put_line(ROUND(v_intrest_amt_pd,2));
        
        IF (ROUND(v_loan_amt,2)=ROUND(v_loan_amt_pd,2)  AND ROUND(v_intrest_amt,2)=ROUND(v_intrest_amt_pd,2))
        THEN
        UPDATE Hris_Loan_Payment_Detail SET Paid_Flag='Y' WHERE PAYMENT_ID IN (
        select 
        PAYMENT_ID
        from Hris_Loan_Payment_Detail pd
        left join hris_employee_loan_request lr on (pd.Loan_Request_Id=lr.loan_request_id)
        join hris_month_code mc on (Mc.From_Date=trunc(Pd.From_Date,'month') and Mc.To_Date=Pd.To_Date)
        where 
        lr.loan_status='OPEN'
        and Lr.Employee_Id=p_employee_id
        and mc.month_id=V_MONTH_ID
        and lr.loan_id=loan_list.Loan_Id);
        
        END IF;
        
        

    END LOOP;
END;/
            CREATE OR REPLACE PROCEDURE hris_weekly_ros_assign (
    p_employee_id   NUMBER,
    p_sun           NUMBER,
    p_mon           NUMBER,
    p_tue           NUMBER,
    p_wed           NUMBER,
    p_thu           NUMBER,
    p_fri           NUMBER,
    p_sat           NUMBER
) AS

    v_update   NUMBER := 1;
    v_sun      NUMBER;
    v_mon      NUMBER;
    v_tue      NUMBER;
    v_wed      NUMBER;
    v_thu      NUMBER;
    v_fri      NUMBER;
    v_sat      NUMBER;
BEGIN
    dbms_output.put_line('TEST');
    BEGIN
        SELECT
            sun,
            mon,
            tue,
            wed,
            thu,
            fri,
            sat
        INTO
            v_sun,v_mon,v_tue,v_wed,v_thu,v_fri,v_sat
        FROM
            hris_weekly_roaster
        WHERE
            employee_id = p_employee_id;

    EXCEPTION
        WHEN no_data_found THEN
            INSERT INTO hris_weekly_roaster VALUES (
                p_employee_id,
                p_sun,
                p_mon,
                p_tue,
                p_wed,
                p_thu,
                p_fri,
                p_sat,
                'E',
                trunc(SYSDATE),
                NULL,
                NULL,
                NULL,
                NULL,
                NULL
            );

            v_update := 0;
    END;

    IF
        ( v_update = 1 )
    THEN
        UPDATE hris_weekly_roaster
            SET
                sun = p_sun,
                mon = p_mon,
                tue = p_tue,
                wed = p_wed,
                thu = p_thu,
                fri = p_fri,
                sat = p_sat
        WHERE
            employee_id = p_employee_id;

    END IF;

END;/
            create or replace TRIGGER HRIS_AFTER_EMPLOYEE_PENALTY AFTER
  INSERT OR
  DELETE ON HRIS_EMPLOYEE_PENALTY_DAYS FOR EACH ROW BEGIN IF INSERTING THEN
  UPDATE HRIS_EMPLOYEE_LEAVE_ASSIGN
  SET BALANCE       = BALANCE-:new.NO_OF_DAYS
  WHERE EMPLOYEE_ID =:new.EMPLOYEE_ID
  AND LEAVE_ID      = :new.LEAVE_ID;
  NULL;
END IF;
IF DELETING THEN
  UPDATE HRIS_EMPLOYEE_LEAVE_ASSIGN
  SET BALANCE       = BALANCE+:old.NO_OF_DAYS
  WHERE EMPLOYEE_ID =:old.EMPLOYEE_ID
  AND LEAVE_ID      = :old.LEAVE_ID;
END IF;
END;/
            create or replace TRIGGER HRIS_AFTER_LEAVE_ADDITION AFTER
  INSERT OR
  DELETE ON HRIS_EMPLOYEE_LEAVE_ADDITION FOR EACH ROW BEGIN IF INSERTING THEN
  UPDATE HRIS_EMPLOYEE_LEAVE_ASSIGN
  SET BALANCE       = BALANCE   +:new.NO_OF_DAYS,
    TOTAL_DAYS      = TOTAL_DAYS+:new.NO_OF_DAYS
  WHERE EMPLOYEE_ID =:new.EMPLOYEE_ID
  AND LEAVE_ID      = :new.LEAVE_ID;
END IF;
IF DELETING THEN
  UPDATE HRIS_EMPLOYEE_LEAVE_ASSIGN
  SET BALANCE       = BALANCE   -:old.NO_OF_DAYS,
    TOTAL_DAYS      = TOTAL_DAYS-:old.NO_OF_DAYS
  WHERE EMPLOYEE_ID =:old.EMPLOYEE_ID
  AND LEAVE_ID      = :old.LEAVE_ID;
END IF;
END;/
            CREATE OR REPLACE TRIGGER HRIS_AFTER_LEAVE_DEDUCTION
AFTER INSERT OR UPDATE OR DELETE ON HRIS_EMPLOYEE_LEAVE_DEDUCTION

FOR EACH ROW

BEGIN
    IF
        INSERTING
    THEN
        INSERT INTO HRIS_EMPLOYEE_PENALTY_DAYS (EMPLOYEE_ID, ATTENDANCE_DT, LEAVE_ID, NO_OF_DAYS, REMARKS, CREATED_DATE, LD_ID)
        values (:new.employee_id, :new.DEDUCTION_DT, :new.LEAVE_ID, :new.NO_OF_DAYS, :new.REMARKS, :new.CREATED_DT, :new.ID);

    END IF;

    IF
        DELETING
    THEN
        DELETE FROM HRIS_EMPLOYEE_PENALTY_DAYS WHERE LD_ID = :old.ID;

    END IF;
    
    IF
        UPDATING
    THEN
        IF :new.status not in ('AP')
        THEN
        DELETE FROM HRIS_EMPLOYEE_PENALTY_DAYS WHERE LD_ID = :old.ID;
    END IF;
    END IF;

END;
/
            DROP TRIGGER APPRAISAL_STATUS_TRIGGER;

CREATE OR REPLACE TRIGGER HRIS_APPRAISAL_STAT_TRG AFTER
  INSERT ON HRIS_APPRAISAL_ASSIGN FOR EACH ROW BEGIN
  INSERT
  INTO HRIS_APPRAISAL_STATUS
    (
      EMPLOYEE_ID,
      APPRAISAL_ID
    )
    VALUES
    (
      :new.EMPLOYEE_ID,
      :new.APPRAISAL_ID
    );
END;/
            CREATE OR REPLACE TRIGGER HRIS_BEFORE_LEAVE_REQUEST BEFORE
    INSERT OR UPDATE ON hris_employee_leave_request
    FOR EACH ROW
DECLARE
  V_BALANCE                     NUMBER(3,1);
  V_IS_MONTHLY                  HRIS_LEAVE_MASTER_SETUP.IS_MONTHLY%TYPE;
  V_FISCAL_YEAR_MONTH_NO        HRIS_MONTH_CODE.FISCAL_YEAR_MONTH_NO%TYPE;
  V_CARRY_FORWARD               HRIS_LEAVE_MASTER_SETUP.CARRY_FORWARD%TYPE;
  V_OLD_LEAVE_TAKEN             NUMBER(3,1);
  V_TOTAL_MONTHLY_LEAVE_TAKEN   NUMBER(3,1);
  V_LEAVE_DIVIDE NUMBER(1,0):=1;

BEGIN
  SELECT
    IS_MONTHLY,
    CARRY_FORWARD
  INTO
    V_IS_MONTHLY,V_CARRY_FORWARD
  FROM
    HRIS_LEAVE_MASTER_SETUP
  WHERE
    LEAVE_ID =:NEW.LEAVE_ID;
    --

    IF
        updating
    THEN  -- start condition if updateing
        IF
    V_IS_MONTHLY = 'N'
  THEN
    IF
      (
        :NEW.HALF_DAY IN (
          'F','S'
        )
      )
    THEN
      V_BALANCE :=:NEW.NO_OF_DAYS / 2;
    ELSE
      V_BALANCE :=:NEW.NO_OF_DAYS;
    END IF;

    IF
      :OLD.STATUS != 'AP' AND :NEW.STATUS = 'AP' AND :OLD.STATUS NOT IN ('CP','CR')
    THEN
      UPDATE HRIS_EMPLOYEE_LEAVE_ASSIGN
        SET
          BALANCE = BALANCE - V_BALANCE
      WHERE
          EMPLOYEE_ID =:NEW.EMPLOYEE_ID
        AND
          LEAVE_ID =:NEW.LEAVE_ID;

    ELSIF :OLD.STATUS IN('AP','CP','CR') AND
      :NEW.STATUS IN (
        'C','R'
      )
    THEN
      UPDATE HRIS_EMPLOYEE_LEAVE_ASSIGN
        SET
          BALANCE = BALANCE + V_BALANCE
      WHERE
          EMPLOYEE_ID =:NEW.EMPLOYEE_ID
        AND
          LEAVE_ID =:NEW.LEAVE_ID;

    END IF;

  END IF;
    --

  IF
    V_IS_MONTHLY = 'Y'
  THEN
    SELECT
      LEAVE_YEAR_MONTH_NO
    INTO
      V_FISCAL_YEAR_MONTH_NO
    FROM
      HRIS_LEAVE_MONTH_CODE
    WHERE
      TRUNC(:NEW.START_DATE) BETWEEN FROM_DATE AND TO_DATE;

    SELECT
      TOTAL_DAYS - BALANCE
    INTO
      V_OLD_LEAVE_TAKEN
    FROM
      HRIS_EMPLOYEE_LEAVE_ASSIGN
    WHERE
        EMPLOYEE_ID =:NEW.EMPLOYEE_ID
      AND
        LEAVE_ID =:NEW.LEAVE_ID
      AND
        FISCAL_YEAR_MONTH_NO = V_FISCAL_YEAR_MONTH_NO;

            IF
      ( V_CARRY_FORWARD = 'N' )
    THEN
      --
      V_BALANCE :=:NEW.NO_OF_DAYS;
      --
      IF
        :OLD.STATUS != 'AP' AND :NEW.STATUS = 'AP'
      THEN
        UPDATE HRIS_EMPLOYEE_LEAVE_ASSIGN
          SET
            BALANCE = BALANCE - V_BALANCE
        WHERE
            EMPLOYEE_ID =:NEW.EMPLOYEE_ID
          AND
            LEAVE_ID =:NEW.LEAVE_ID
          AND
            FISCAL_YEAR_MONTH_NO = V_FISCAL_YEAR_MONTH_NO;

      ELSIF :OLD.STATUS = 'AP' AND
        :NEW.STATUS IN (
          'C','R'
        )
      THEN
        UPDATE HRIS_EMPLOYEE_LEAVE_ASSIGN
          SET
            BALANCE = BALANCE + V_BALANCE
        WHERE
            EMPLOYEE_ID =:NEW.EMPLOYEE_ID
          AND
            LEAVE_ID =:NEW.LEAVE_ID
          AND
            FISCAL_YEAR_MONTH_NO = V_FISCAL_YEAR_MONTH_NO;

      END IF;

                NULL;
            END IF;

            IF
      ( V_CARRY_FORWARD = 'Y' )
    THEN

    IF
      (
        :NEW.HALF_DAY IN (
          'F','S'
        )
      )
    THEN
      V_LEAVE_DIVIDE := 2;
    END IF;

      IF
      :OLD.STATUS != 'AP' AND :NEW.STATUS = 'AP'
      THEN
        FOR LEAVE_ASSIGN_DTL IN (
          SELECT
            *
          FROM
            HRIS_EMPLOYEE_LEAVE_ASSIGN
          WHERE
              EMPLOYEE_ID =:NEW.EMPLOYEE_ID
            AND
              LEAVE_ID =:NEW.LEAVE_ID
          ORDER BY FISCAL_YEAR_MONTH_NO
        ) LOOP
          IF
            ( ( V_OLD_LEAVE_TAKEN +(:NEW.NO_OF_DAYS/V_LEAVE_DIVIDE) ) >= LEAVE_ASSIGN_DTL.TOTAL_DAYS )
          THEN
            V_BALANCE := 0;
          ELSE
            V_BALANCE := LEAVE_ASSIGN_DTL.BALANCE -(:NEW.NO_OF_DAYS/V_LEAVE_DIVIDE);
          END IF;

          UPDATE HRIS_EMPLOYEE_LEAVE_ASSIGN
            SET
              BALANCE = V_BALANCE
          WHERE
              EMPLOYEE_ID = LEAVE_ASSIGN_DTL.EMPLOYEE_ID
            AND
              LEAVE_ID = LEAVE_ASSIGN_DTL.LEAVE_ID
            AND
              FISCAL_YEAR_MONTH_NO = LEAVE_ASSIGN_DTL.FISCAL_YEAR_MONTH_NO;

        END LOOP;

       ELSIF :OLD.STATUS = 'AP' AND
        :NEW.STATUS IN (
          'C','R'
        )
      THEN
        FOR LEAVE_ASSIGN_DTL IN (
          SELECT
            *
          FROM
            HRIS_EMPLOYEE_LEAVE_ASSIGN
          WHERE
              EMPLOYEE_ID =:NEW.EMPLOYEE_ID
            AND
              LEAVE_ID =:NEW.LEAVE_ID
          ORDER BY FISCAL_YEAR_MONTH_NO
        ) LOOP
          IF
            ( ( V_OLD_LEAVE_TAKEN -(:NEW.NO_OF_DAYS/V_LEAVE_DIVIDE) ) >= LEAVE_ASSIGN_DTL.TOTAL_DAYS )
          THEN
            V_BALANCE := 0;
          ELSE
            V_BALANCE := LEAVE_ASSIGN_DTL.TOTAL_DAYS-V_OLD_LEAVE_TAKEN+(:NEW.NO_OF_DAYS/V_LEAVE_DIVIDE);
          END IF;

          UPDATE HRIS_EMPLOYEE_LEAVE_ASSIGN
            SET
              BALANCE = V_BALANCE
          WHERE
              EMPLOYEE_ID = LEAVE_ASSIGN_DTL.EMPLOYEE_ID
            AND
              LEAVE_ID = LEAVE_ASSIGN_DTL.LEAVE_ID
            AND
              FISCAL_YEAR_MONTH_NO = LEAVE_ASSIGN_DTL.FISCAL_YEAR_MONTH_NO;

        END LOOP;
      END IF;
    END IF;

        END IF;

    END IF; -- end condition if updateing

    IF
        inserting
    THEN  -- START CONDITION IF INSERTING
        IF
            :new.status = 'AP'
        THEN -- IF INSERT IS AP
            IF
                v_is_monthly = 'N'
            THEN
                IF
                    (
                        :new.half_day IN (
                            'F','S'
                        )
                    )
                THEN
                    v_balance :=:new.no_of_days / 2;
                ELSE
                    v_balance :=:new.no_of_days;
                END IF;

                UPDATE hris_employee_leave_assign
                    SET
                        balance = balance - v_balance
                WHERE
                        employee_id =:new.employee_id
                    AND
                        leave_id =:new.leave_id;

            END IF;
            
            
            -- MONTHLY LEAVE START HERE
                  IF
            v_is_monthly = 'Y'
        THEN
            SELECT
                leave_year_month_no
            INTO
                v_fiscal_year_month_no
            FROM
                hris_leave_month_code
            WHERE
                trunc(:new.start_date) BETWEEN from_date AND TO_DATE;

            SELECT
                total_days - balance
            INTO
                v_old_leave_taken
            FROM
                hris_employee_leave_assign
            WHERE
                    employee_id =:new.employee_id
                AND
                    leave_id =:new.leave_id
                AND
                    fiscal_year_month_no = v_fiscal_year_month_no;

            IF
                ( v_carry_forward = 'N' )
            THEN
      --
                v_balance :=:new.no_of_days;
      --
                IF
                    :old.status != 'AP' AND :new.status = 'AP'
                THEN
                    UPDATE hris_employee_leave_assign
                        SET
                            balance = balance - v_balance
                    WHERE
                            employee_id =:new.employee_id
                        AND
                            leave_id =:new.leave_id
                        AND
                            fiscal_year_month_no = v_fiscal_year_month_no;

                ELSIF :old.status = 'AP' AND
                    :new.status IN (
                        'C','R'
                    )
                THEN
                    UPDATE hris_employee_leave_assign
                        SET
                            balance = balance + v_balance
                    WHERE
                            employee_id =:new.employee_id
                        AND
                            leave_id =:new.leave_id
                        AND
                            fiscal_year_month_no = v_fiscal_year_month_no;

                END IF;

                NULL;
            END IF;

            IF
                ( v_carry_forward = 'Y' )
            THEN
                IF
                    (
                        :new.half_day IN (
                            'F','S'
                        )
                    )
                THEN
                    v_leave_divide := 2;
                END IF;

                    FOR leave_assign_dtl IN (
                        SELECT
                            *
                        FROM
                            hris_employee_leave_assign
                        WHERE
                                employee_id =:new.employee_id
                            AND
                                leave_id =:new.leave_id
                        ORDER BY fiscal_year_month_no
                    ) LOOP
                        IF
                            ( ( v_old_leave_taken + (:new.no_of_days / v_leave_divide ) ) >= leave_assign_dtl.total_days )
                        THEN
                            v_balance := 0;
                        ELSE
                            v_balance := leave_assign_dtl.balance - (:new.no_of_days / v_leave_divide );
                        END IF;

                        UPDATE hris_employee_leave_assign
                            SET
                                balance = v_balance
                        WHERE
                                employee_id = leave_assign_dtl.employee_id
                            AND
                                leave_id = leave_assign_dtl.leave_id
                            AND
                                fiscal_year_month_no = leave_assign_dtl.fiscal_year_month_no;

                    END LOOP;


            END IF;

        END IF;
            
            
            -- MONTHLY LEAVE END HERE
            
            begin
            HRIS_QUEUE_REATTENDANCE(:new.START_DATE,:new.employee_id,:new.END_DATE);
            end;
            

        END IF; -- END ID INSERT AP
    END IF; -- END CONDITION IF INSERTING

END;/
            create or replace TRIGGER HRIS_CONTRACT_EMP_UPDATE
AFTER INSERT OR UPDATE OR DELETE 
   ON HRIS_CONTRACT_EMP_ASSIGN
    FOR EACH ROW
  BEGIN

  IF INSERTING  THEN



  DBMS_OUTPUT.PUT_LINE ('No Admin is defined!!!' );

  END IF;


   IF UPDATING THEN
   BEGIN

   IF(:new.STATUS='D') THEN

  DELETE FROM  HRIS_CONTRACT_EMP_ATTENDANCE
  WHERE  EMPLOYEE_ID      = :old.EMPLOYEE_ID 
  AND CUSTOMER_ID      = :old.CUSTOMER_ID 
  AND CONTRACT_ID      = :old.CONTRACT_ID 
  AND LOCATION_ID      = :old.LOCATION_ID 
  AND DUTY_TYPE_ID      = :old.DUTY_TYPE_ID
  AND EMP_ASSIGN_ID = :old.EMP_ASSIGN_ID
  AND ATTENDANCE_DATE BETWEEN :old.START_DATE AND :old.END_DATE;

   ELSE
    UPDATE   HRIS_CONTRACT_EMP_ATTENDANCE
     SET 
     EMPLOYEE_ID      = :new.EMPLOYEE_ID,
     CUSTOMER_ID      = :new.CUSTOMER_ID,
     CONTRACT_ID      = :new.CONTRACT_ID,
     LOCATION_ID      = :new.LOCATION_ID,
     DUTY_TYPE_ID      = :new.DUTY_TYPE_ID,
     EMP_ASSIGN_ID     = :new.EMP_ASSIGN_ID
  WHERE  EMPLOYEE_ID      = :old.EMPLOYEE_ID 
  AND CUSTOMER_ID      = :old.CUSTOMER_ID 
  AND CONTRACT_ID      = :old.CONTRACT_ID 
  AND LOCATION_ID      = :old.LOCATION_ID 
  AND DUTY_TYPE_ID     = :old.DUTY_TYPE_ID
  AND EMP_ASSIGN_ID    = :old.EMP_ASSIGN_ID
  AND ATTENDANCE_DATE BETWEEN :new.START_DATE AND :new.END_DATE;

   END IF;


   END;
   END IF;


   IF DELETING THEN


  DELETE FROM  HRIS_CONTRACT_EMP_ATTENDANCE
  WHERE  EMPLOYEE_ID      = :old.EMPLOYEE_ID 
  AND CUSTOMER_ID      = :old.CUSTOMER_ID 
  AND CONTRACT_ID      = :old.CONTRACT_ID 
  AND LOCATION_ID      = :old.LOCATION_ID 
  AND DUTY_TYPE_ID      = :old.DUTY_TYPE_ID
  AND EMP_ASSIGN_ID    = :old.EMP_ASSIGN_ID
  AND ATTENDANCE_DATE BETWEEN :old.START_DATE AND :old.END_DATE;

   END IF;



  END;/
            create or replace TRIGGER "HRIS_EMPLOYEE_ADD" AFTER
  INSERT ON HRIS_EMPLOYEES FOR EACH ROW DECLARE V_FISCAL_YEAR_ID HRIS_FISCAL_YEARS.FISCAL_YEAR_ID%TYPE;
  V_MONTH_ID HRIS_MONTH_CODE.MONTH_ID%TYPE;
  V_CURRENT_MONTH_COUNT NUMBER;
  V_DEFAULT_DAYS        NUMBER;
  V_PRODATA_DAYS        NUMBER;
  BEGIN
    BEGIN
      SELECT LEAVE_YEAR_ID,
        MONTH_ID
      INTO V_FISCAL_YEAR_ID,
        V_MONTH_ID
      FROM HRIS_LEAVE_MONTH_CODE
      WHERE TRUNC(SYSDATE) BETWEEN FROM_DATE AND TO_DATE;
    EXCEPTION
    WHEN no_data_found THEN
      SYS.DBMS_OUTPUT.PUT('No Current Month found.');
      RETURN;
    END;
    SELECT MONTH_ROWNUM
    INTO V_CURRENT_MONTH_COUNT
    FROM
      (SELECT ROWNUM AS MONTH_ROWNUM,
        MONTH_ID
      FROM HRIS_LEAVE_MONTH_CODE
      WHERE LEAVE_YEAR_ID= V_FISCAL_YEAR_ID
      ORDER BY FROM_DATE
      ) MONTHS
    WHERE MONTH_ID =V_MONTH_ID;
    BEGIN
      FOR leave IN
      (SELECT LEAVE_ID,
        DEFAULT_DAYS,
        IS_PRODATA_BASIS
      FROM HRIS_LEAVE_MASTER_SETUP
      WHERE STATUS                 ='E'
      AND ASSIGN_ON_EMPLOYEE_SETUP ='Y'
      AND  IS_MONTHLY='N'
      )
      LOOP
        V_DEFAULT_DAYS           := leave.DEFAULT_DAYS;
        V_PRODATA_DAYS           := leave.DEFAULT_DAYS;
        IF leave.IS_PRODATA_BASIS = 'Y' THEN
          V_PRODATA_DAYS         :=ROUND(V_DEFAULT_DAYS*((13-V_CURRENT_MONTH_COUNT)/12));
        END IF;
        INSERT
        INTO HRIS_EMPLOYEE_LEAVE_ASSIGN
          (
            EMPLOYEE_ID,
            LEAVE_ID,
            PREVIOUS_YEAR_BAL,
            TOTAL_DAYS,
            BALANCE,
           -- FISCAL_YEAR,
            CREATED_DT
          )
          VALUES
          (
            :new.EMPLOYEE_ID,
            leave.LEAVE_ID,
            0,
            V_PRODATA_DAYS,
            V_PRODATA_DAYS,
          --  V_FISCAL_YEAR_ID,
            TRUNC(SYSDATE)
          );
      END LOOP;
    END;
    BEGIN
      FOR holiday IN
      (SELECT HOLIDAY_ID
        FROM HRIS_HOLIDAY_MASTER_SETUP
        WHERE ASSIGN_ON_EMPLOYEE_SETUP = 'Y'
        AND STATUS                     ='E'
        AND START_DATE                >=TRUNC(SYSDATE)
      )
      LOOP
        INSERT
        INTO HRIS_EMPLOYEE_HOLIDAY
          (
            EMPLOYEE_ID,
            HOLIDAY_ID
          )
          VALUES
          (
            :new.EMPLOYEE_ID,
            holiday.HOLIDAY_ID
          );
      END LOOP;
    END;
  END;

/
            DROP TRIGGER UPDATE_FULL_NAME;

create or replace TRIGGER HRIS_UPDATE_FULL_NAME BEFORE
  UPDATE OR
  INSERT ON HRIS_EMPLOYEES FOR EACH ROW BEGIN IF INSERTING THEN :new.FULL_NAME := CONCAT(CONCAT(CONCAT(TRIM(:new.FIRST_NAME),' '),
    CASE
      WHEN :new.MIDDLE_NAME IS NOT NULL
      THEN CONCAT(TRIM(:new.MIDDLE_NAME), ' ')
      ELSE ''
    END ),TRIM(:new.LAST_NAME));
  RETURN;
ELSIF UPDATING THEN
  IF (:old.FIRST_NAME !=:new.FIRST_NAME OR :old.LAST_NAME !=:new.LAST_NAME OR (:old.MIDDLE_NAME IS NULL AND :new.MIDDLE_NAME IS NOT NULL) OR (:old.MIDDLE_NAME IS NOT NULL AND :new.MIDDLE_NAME IS NULL) OR :old.MIDDLE_NAME !=:new.MIDDLE_NAME ) THEN
    :new.FULL_NAME    := CONCAT(CONCAT(CONCAT(TRIM(:new.FIRST_NAME),' '),
    CASE
    WHEN :new.MIDDLE_NAME IS NOT NULL THEN
      CONCAT(TRIM(:new.MIDDLE_NAME), ' ')
    ELSE
      ''
    END ),TRIM(:new.LAST_NAME));
  END IF;
END IF;
END;/
            DROP TRIGGER USER_SETTING_TRIGGER;

CREATE OR REPLACE TRIGGER HRIS_USER_SETTING_TRG AFTER
  INSERT ON HRIS_USERS FOR EACH ROW BEGIN
  INSERT INTO HRIS_USER_SETTING
    (USER_ID
    ) VALUES
    (:new.USER_ID
    );
END;
/
            CREATE OR REPLACE FUNCTION BOOLEAN_DESC(
    P_FLAG CHAR)
  RETURN VARCHAR2
IS
  V_FLAG_DESC VARCHAR2(50 BYTE);
BEGIN
  V_FLAG_DESC:=
  (
    CASE P_FLAG
    WHEN 'Y' THEN
      'Yes'
    WHEN 'N'THEN
      'No'
    END);
  RETURN V_FLAG_DESC;
END; /
            create or replace FUNCTION CHARGE_TYPE_DESC(
    P_CHARGE_TYPE CHAR)
  RETURN VARCHAR2
IS
  V_CHARGE_TYPE_DESC VARCHAR2(50 BYTE);
BEGIN
  V_CHARGE_TYPE_DESC:=
  (
    CASE P_CHARGE_TYPE
    WHEN 'H' THEN
      'Hourly'
    WHEN 'D'THEN
      'Day wise'
    WHEN 'W' THEN
      'Weekly'
    WHEN 'M' THEN
      'Monthly'
    END);
  RETURN V_CHARGE_TYPE_DESC;
END;/
            create or replace FUNCTION FN_DECRYPT_PASSWORD(vText IN VARCHAR2)
RETURN VARCHAR2 IS
  nPasswordLength NUMBER;
  nIncrement NUMBER;
  vBodyText VARCHAR2(100);
  vFoundText VARCHAR2(100);
  vCharString CHAR(1);
  vPassword VARCHAR2(100);
  nPosition NUMBER;
  nStartPosition NUMBER;
BEGIN
 IF vText IS NOT NULL AND LENGTH(vText)=64 THEN
    nPasswordLength := TO_NUMBER(SUBSTR(vText, (LENGTH(vText)+1)-4,2));
    nIncrement := TO_NUMBER(SUBSTR(vText, (LENGTH(vText)+1)-2,2));
    nStartPosition := LENGTH(vText)-NVL(nPasswordLength,0)*3-3;
    vBodyText := SUBSTR(vText,nStartPosition,nPasswordLength * 3);

    nPosition := 1;
    vFoundText :='';

    FOR i IN 1..LENGTH(vBodyText/3) LOOP
      vCharString := CHR(TO_NUMBER(SUBSTR(vBodyText,nPosition,3))-567-nIncrement);
      vFoundText := vFoundText||vCharString;
      nPosition := nPosition + 3;
    END LOOP;


    vPassword := '';
    FOR i IN 0..LENGTH(vFoundText)-1 LOOP
       vPassword := vPassword||SUBSTR(vFoundText, LENGTH(vFoundText)-i,1);
    END LOOP;
    RETURN vPassword;
  ELSE
    RETURN NULL;
  END IF;
END;/
            create or replace FUNCTION FN_ENCRYPT_PASSWORD(vText IN VARCHAR2)
RETURN VARCHAR2 IS
  vFalsePart VARCHAR2(100);
  vEndPart VARCHAR2(10);
  vRetValue VARCHAR2(100);
  vMainBody VARCHAR2(100):='';
  vRevertedText VARCHAR2(100):='';
  vASCIIValue VARCHAR2(3);
  nDiffRange NUMBER(2);
  vAddParts    VARCHAR2(100);
  vTemporaryPassword VARCHAR2(30);
BEGIN
  --Taking time factor
  IF LENGTH(vText)< 10 THEN
     vEndPart := '0'||TO_CHAR(LENGTH(vText))||TO_CHAR(SYSDATE, 'SS');
  ELSIF LENGTH(vText)>= 10 THEN
     vEndPart := TO_CHAR(LENGTH(vText))||TO_CHAR(SYSDATE, 'SS');
  END IF;

  --Temporary part goes here
  vTemporaryPassword := 'MAHESWORMAHARJANISHERO';
  vFalsePart :='';
  FOR i IN 1..LENGTH(vTemporaryPassword) LOOP
      vFalsePart := vFalsePart||TO_CHAR(TO_NUMBER(ASCII(SUBSTR(vTemporaryPassword,i,1)))+ TO_NUMBER(vEndPart));
  END LOOP;
  --Main part goes here

  FOR i IN 0..LENGTH(vText)-1 LOOP
     vRevertedText := vRevertedText||SUBSTR(vText, LENGTH(vText)-i,1);
  END LOOP;

  FOR i IN 1..LENGTH(vRevertedText) LOOP
    vASCIIValue :=  TO_CHAR(TO_NUMBER(ASCII(SUBSTR(vRevertedText,i,1)))+ 567 + TO_NUMBER(TO_CHAR(SYSDATE, 'SS')));
    vMainBody := vMainBody||vASCIIValue;
  END LOOP;

  vRetValue := vMainBody||vEndPart;
  IF LENGTH(vRetValue)< 64 THEN
     nDiffRange := 64-LENGTH(vRetValue);
  END IF;

  vRetValue := SUBSTR(vFalsePart,1,nDiffRange)||vRetValue;
  RETURN vRetValue;
END;/
            create or replace FUNCTION HRIS_BEST_CASE_SHIFT(
    P_EMPLOYEE_ID HRIS_EMPLOYEES.EMPLOYEE_ID%TYPE,
    P_ATTENDANCE_DT DATE)
  RETURN NUMBER
AS
  V_SHIFT_ID HRIS_SHIFTS.SHIFT_ID%TYPE;
  V_IN_TIME           DATE;
  V_SHIFT_IN_TIME     DATE;
  V_IN_TIME_DIFF      NUMBER;
  V_IN_TIME_DIFF_MIN  NUMBER;
  V_MIN_IN_TIME       NUMBER;
  V_OUT_TIME          DATE;
  V_SHIFT_OUT_TIME    DATE;
  V_OUT_TIME_DIFF     NUMBER;
  V_OUT_TIME_DIFF_MIN NUMBER;
  V_MIN_IN_OUT_TIME   NUMBER;
BEGIN
  SELECT MIN(TO_DATE(TO_CHAR(ATTENDANCE_TIME,'HH:MI AM'),'HH:MI AM')) AS IN_TIME,
    MAX(TO_DATE(TO_CHAR(ATTENDANCE_TIME,'HH:MI AM'),'HH:MI AM'))      AS OUT_TIME
  INTO V_IN_TIME,
    V_OUT_TIME
  FROM HRIS_ATTENDANCE
  WHERE EMPLOYEE_ID =P_EMPLOYEE_ID
  AND ATTENDANCE_DT = P_ATTENDANCE_DT;
  --
  FOR shift IN
  (SELECT S.*
  FROM HRIS_EMPLOYEE_SHIFTS ES
  JOIN HRIS_SHIFTS S
  ON (ES.SHIFT_ID      = S.SHIFT_ID)
  WHERE ES.EMPLOYEE_ID = P_EMPLOYEE_ID
  AND (P_ATTENDANCE_DT BETWEEN ES.START_DATE AND ES.END_DATE)
  AND S.STATUS ='E'
  )
  LOOP
    V_SHIFT_IN_TIME    := shift.START_TIME+(.000694*NVL(shift.LATE_IN,0));
    V_SHIFT_IN_TIME    :=TO_DATE(TO_CHAR(V_SHIFT_IN_TIME,'HH:MI AM'),'HH:MI AM');
    V_IN_TIME_DIFF     :=ABS(V_SHIFT_IN_TIME-V_IN_TIME);
    V_IN_TIME_DIFF_MIN :=V_IN_TIME_DIFF     *24*60;
    --
    IF(V_IN_TIME          = V_OUT_TIME) THEN
      IF V_MIN_IN_TIME   IS NULL THEN
        V_MIN_IN_TIME    :=V_IN_TIME_DIFF;
        V_SHIFT_ID       :=shift.SHIFT_ID;
      ELSIF V_IN_TIME_DIFF<V_MIN_IN_TIME THEN
        V_MIN_IN_TIME    :=V_IN_TIME_DIFF;
        V_SHIFT_ID       :=shift.SHIFT_ID;
      END IF;
      CONTINUE;
    END IF;
    --
    V_SHIFT_OUT_TIME                       := shift.END_TIME-(.000694*NVL(shift.EARLY_OUT,0));
    V_SHIFT_OUT_TIME                       :=TO_DATE(TO_CHAR(V_SHIFT_OUT_TIME,'HH:MI AM'),'HH:MI AM');
    V_OUT_TIME_DIFF                        :=ABS(V_SHIFT_OUT_TIME-V_OUT_TIME);
    V_OUT_TIME_DIFF_MIN                    := V_OUT_TIME_DIFF    *24*60;
    IF(V_MIN_IN_OUT_TIME                   IS NULL ) THEN
      V_MIN_IN_OUT_TIME                    :=V_IN_TIME_DIFF+V_OUT_TIME_DIFF;
      V_SHIFT_ID                           :=shift.SHIFT_ID;
    ELSIF (V_IN_TIME_DIFF                                  +V_OUT_TIME_DIFF) <V_MIN_IN_OUT_TIME THEN
      V_MIN_IN_OUT_TIME                    :=V_IN_TIME_DIFF+V_OUT_TIME_DIFF;
      V_SHIFT_ID                           :=shift.SHIFT_ID;
    END IF;
  END LOOP;
RETURN V_SHIFT_ID;
END;/
            create or replace FUNCTION HRIS_CHECK_HOLIDAY_INBETWN(
    P_EMPLOYEE_ID HRIS_EMPLOYEES.EMPLOYEE_ID%TYPE,
    P_ATTENDANCE_DT DATE )
  RETURN NUMBER
AS
  V_START_DATE DATE := NULL;
  V_END_DATE   DATE := NULL;
  V_COUNT NUMBER:= NULL;
BEGIN

  SELECT max(ATTENDANCE_DT)
  INTO V_START_DATE
  FROM HRIS_ATTENDANCE_DETAIL
  WHERE OVERALL_STATUS = 'AB'
  AND EMPLOYEE_ID      = P_EMPLOYEE_ID
  AND ATTENDANCE_DT    < P_ATTENDANCE_DT;

  SELECT min(ATTENDANCE_DT)
  INTO V_END_DATE
  FROM HRIS_ATTENDANCE_DETAIL
  WHERE OVERALL_STATUS = 'AB'
  AND EMPLOYEE_ID      = P_EMPLOYEE_ID
  AND ATTENDANCE_DT    > P_ATTENDANCE_DT;

  IF(V_END_DATE is NULL) THEN
  V_END_DATE := trunc(sysdate);
  END IF;

  SELECT count(IN_TIME)
  into V_COUNT
  FROM HRIS_ATTENDANCE_DETAIL
  WHERE ATTENDANCE_DT BETWEEN V_START_DATE AND V_END_DATE
  AND employee_id = P_EMPLOYEE_ID
  AND IN_TIME   IS NOT NULL;

  IF(V_COUNT = 0) then
  return 1;
  else
  RETURN 0;
  end if;
END;/
            create or replace function HRIS_GET_BRANCH_JH
(
p_employee_id number,
p_attendance_dt date,
p_branch_id number
)
return Char
is
v_job_branch_name varchar2(200 byte):=null;
begin

select BRANCH_NAME into v_job_branch_name  from HRIS_BRANCHES where branch_id=p_branch_id;


return v_job_branch_name;
end;

/
            CREATE OR REPLACE FUNCTION HRIS_GET_FULL_FORM(
    P_SHORT_FORM VARCHAR2,
    P_OF         VARCHAR2)
  RETURN VARCHAR2
IS
  V_FULL_FORM VARCHAR2(255 BYTE);
BEGIN
  IF (P_OF      = 'TRANSPORT_TYPE') THEN
    V_FULL_FORM:=
    (
      CASE P_SHORT_FORM
      WHEN 'AP' THEN
        'AEROPLANE'
      WHEN 'OV'THEN
        'OFFICE VEHICLE'
      WHEN 'TI' THEN
        'TAXI'
      WHEN 'BS' THEN
        'BUS'
      WHEN 'OF' THEN
        'ON FOOT'
      END);
  END IF;
RETURN V_FULL_FORM;
END;/
            CREATE OR REPLACE FUNCTION HRIS_GET_SERVICE_STATUS(
    P_EMPLOYEE_ID NUMBER,
    V_DATE_ON     DATE )
  RETURN NUMBER
AS
  V_JOB_HISTORY_ID NUMBER:=NULL;
BEGIN
  BEGIN
    SELECT JOB_HISTORY_ID
    INTO V_JOB_HISTORY_ID
    FROM
      (SELECT H.JOB_HISTORY_ID
      FROM HRIS_JOB_HISTORY H
      WHERE H.START_DATE  <=TRUNC(V_DATE_ON) AND H.STATUS = 'E'
      AND TRUNC(V_DATE_ON)<= (
        CASE
          WHEN H.END_DATE IS NOT NULL
          THEN H.END_DATE
          ELSE V_DATE_ON
        END )
      AND H.EMPLOYEE_ID   = P_EMPLOYEE_ID
      AND H.DISABLED_FLAG ='N'
      ORDER BY H.START_DATE DESC,
        H.END_DATE ASC
      )
    WHERE ROWNUM =1;
  EXCEPTION
  WHEN NO_DATA_FOUND THEN
    V_JOB_HISTORY_ID:=NULL;
  END;
RETURN V_JOB_HISTORY_ID;
END;/
            CREATE OR REPLACE FUNCTION HRIS_IS_EMP_IN(
    P_EMPLOYEE_ID  NUMBER,
    P_TABLE_NAME   VARCHAR2,
    P_COLUMN_NAME  VARCHAR2,
    P_COLUMN_VALUE NUMBER )
  RETURN CHAR
AS
  V_COMPANY               VARCHAR2(4000 BYTE);
  V_BRANCH                VARCHAR2(4000 BYTE);
  V_DEPARTMENT            VARCHAR2(4000 BYTE);
  V_DESIGNATION           VARCHAR2(4000 BYTE);
  V_POSITION              VARCHAR2(4000 BYTE);
  V_SERVICE_TYPE          VARCHAR2(4000 BYTE);
  V_EMPLOYEE_TYPE         VARCHAR2(4000 BYTE);
  V_GENDER                VARCHAR2(4000 BYTE);
  V_EMPLOYEE              VARCHAR2(4000 BYTE);
  V_COND_COMPANY          VARCHAR2(3 BYTE);
  V_COND_BRANCH           VARCHAR2(3 BYTE);
  V_COND_DEPARTMENT       VARCHAR2(3 BYTE);
  V_COND_DESIGNATION      VARCHAR2(3 BYTE);
  V_COND_POSITION         VARCHAR2(3 BYTE);
  V_COND_SERVICE_TYPE     VARCHAR2(3 BYTE);
  V_COND_EMPLOYEE_TYPE    VARCHAR2(3 BYTE);
  V_COND_GENDER           VARCHAR2(3 BYTE);
  V_COND_EMPLOYEE         VARCHAR2(3 BYTE);
  V_IN_OPER_COMPANY       VARCHAR2(6 BYTE);
  V_IN_OPER_BRANCH        VARCHAR2(6 BYTE);
  V_IN_OPER_DEPARTMENT    VARCHAR2(6 BYTE);
  V_IN_OPER_DESIGNATION   VARCHAR2(6 BYTE);
  V_IN_OPER_POSITION      VARCHAR2(6 BYTE);
  V_IN_OPER_SERVICE_TYPE  VARCHAR2(6 BYTE);
  V_IN_OPER_EMPLOYEE_TYPE VARCHAR2(6 BYTE);
  V_IN_OPER_GENDER        VARCHAR2(6 BYTE);
  V_IN_OPER_EMPLOYEE      VARCHAR2(6 BYTE);
  V_WHERE_CLAUSE          VARCHAR2(4000 BYTE);
  V_QUERY_FIRST           VARCHAR2(4000 BYTE);
  V_QUERY_SECOND          VARCHAR2(4000 BYTE);
  V_RETURN                CHAR(1 BYTE);
BEGIN
  V_QUERY_FIRST :='SELECT COMPANY_ID,BRANCH_ID,DEPARTMENT_ID,DESIGNATION_ID,POSITION_ID,SERVICE_TYPE_ID,EMPLOYEE_TYPE,GENDER_ID,EMPLOYEE_ID,COND_COMPANY_ID,COND_BRANCH_ID,COND_DEPARTMENT_ID,COND_DESIGNATION_ID,COND_POSITION_ID,COND_SERVICE_TYPE_ID,COND_EMPLOYEE_TYPE,COND_GENDER_ID,COND_EMPLOYEE_ID,IN_OPER_COMPANY_ID,IN_OPER_BRANCH_ID,IN_OPER_DEPARTMENT_ID,IN_OPER_DESIGNATION_ID,IN_OPER_POSITION_ID,IN_OPER_SERVICE_TYPE_ID,IN_OPER_EMPLOYEE_TYPE,IN_OPER_GENDER_ID,IN_OPER_EMPLOYEE_ID,WHERE_CLAUSE FROM '||P_TABLE_NAME||' WHERE '||P_COLUMN_NAME||' ='||P_COLUMN_VALUE;
  EXECUTE IMMEDIATE V_QUERY_FIRST INTO V_COMPANY, V_BRANCH, V_DEPARTMENT, V_DESIGNATION, V_POSITION, V_SERVICE_TYPE, V_EMPLOYEE_TYPE, V_GENDER, V_EMPLOYEE,V_COND_COMPANY, V_COND_BRANCH, V_COND_DEPARTMENT, V_COND_DESIGNATION, V_COND_POSITION, V_COND_SERVICE_TYPE, V_COND_EMPLOYEE_TYPE, V_COND_GENDER, V_COND_EMPLOYEE,V_IN_OPER_COMPANY, V_IN_OPER_BRANCH, V_IN_OPER_DEPARTMENT, V_IN_OPER_DESIGNATION, V_IN_OPER_POSITION, V_IN_OPER_SERVICE_TYPE, V_IN_OPER_EMPLOYEE_TYPE, V_IN_OPER_GENDER, V_IN_OPER_EMPLOYEE,V_WHERE_CLAUSE;
  IF V_WHERE_CLAUSE IS NOT NULL THEN
    V_QUERY_SECOND  :='SELECT EMPLOYEE_ID FROM HRIS_EMPLOYEES WHERE '||V_WHERE_CLAUSE;
  ELSE
    V_QUERY_SECOND   :='SELECT EMPLOYEE_ID FROM HRIS_EMPLOYEES WHERE 1=1';
    IF(V_COMPANY     IS NOT NULL) THEN
      V_QUERY_SECOND := V_QUERY_SECOND||' '||V_COND_COMPANY||' COMPANY_ID '||V_IN_OPER_COMPANY||' ('||V_COMPANY||')';
    END IF;
    IF(V_BRANCH      IS NOT NULL) THEN
      V_QUERY_SECOND := V_QUERY_SECOND||' '||V_COND_BRANCH||' BRANCH_ID '||V_IN_OPER_BRANCH||' ('||V_BRANCH||')';
    END IF;
    IF(V_DEPARTMENT  IS NOT NULL) THEN
      V_QUERY_SECOND := V_QUERY_SECOND||' '||V_COND_DEPARTMENT||' DEPARTMENT_ID '||V_IN_OPER_DEPARTMENT||' ('||V_DEPARTMENT||')';
    END IF;
    IF(V_DESIGNATION IS NOT NULL) THEN
      V_QUERY_SECOND := V_QUERY_SECOND||' '||V_COND_DESIGNATION||' DESIGNATION_ID '||V_IN_OPER_DESIGNATION||' ('||V_DESIGNATION||')';
    END IF;
    IF(V_POSITION    IS NOT NULL) THEN
      V_QUERY_SECOND := V_QUERY_SECOND||' '||V_COND_POSITION||' POSITION_ID '||V_IN_OPER_POSITION||' ('||V_POSITION||')';
    END IF;
    IF(V_SERVICE_TYPE IS NOT NULL) THEN
      V_QUERY_SECOND  := V_QUERY_SECOND||' '||V_COND_SERVICE_TYPE||' SERVICE_TYPE_ID '||V_IN_OPER_SERVICE_TYPE||' ('||V_SERVICE_TYPE||')';
    END IF;
    IF(V_EMPLOYEE_TYPE IS NOT NULL) THEN
      V_QUERY_SECOND   := V_QUERY_SECOND||' '||V_COND_EMPLOYEE_TYPE||' EMPLOYEE_TYPE '||V_IN_OPER_EMPLOYEE_TYPE||' ('||V_EMPLOYEE_TYPE||')';
    END IF;
    IF(V_GENDER      IS NOT NULL) THEN
      V_QUERY_SECOND := V_QUERY_SECOND||' '||V_COND_GENDER||' GENDER_ID '||V_IN_OPER_GENDER||' ('||V_GENDER||')';
    END IF;
    IF(V_EMPLOYEE    IS NOT NULL) THEN
      V_QUERY_SECOND := V_QUERY_SECOND||' '||V_COND_EMPLOYEE||' EMPLOYEE_ID '||V_IN_OPER_EMPLOYEE||' ('||V_EMPLOYEE||')';
    END IF;
  END IF;
EXECUTE IMMEDIATE 'SELECT (CASE WHEN COUNT(*) >0 THEN  ''Y'' ELSE ''N'' END) FROM ('||V_QUERY_SECOND||') WHERE EMPLOYEE_ID = '||P_EMPLOYEE_ID INTO V_RETURN;
RETURN V_RETURN;
END;/
            create or replace FUNCTION HRIS_AVAILABLE_LEAVE_DAYS(
    P_START_DATE DATE,
    P_END_DATE   DATE,
    P_EMPLOYEE_ID HRIS_EMPLOYEES.EMPLOYEE_ID%TYPE,
    P_LEAVE_ID HRIS_LEAVE_MASTER_SETUP.LEAVE_ID%TYPE,    
    P_HALF_DAY HRIS_EMPLOYEE_LEAVE_REQUEST.HALF_DAY%TYPE:=NULL
    )
  RETURN NUMBER
AS
  V_WEEKDAY1 HRIS_SHIFTS.WEEKDAY1%TYPE;
  V_WEEKDAY2 HRIS_SHIFTS.WEEKDAY2%TYPE;
  V_WEEKDAY3 HRIS_SHIFTS.WEEKDAY3%TYPE;
  V_WEEKDAY4 HRIS_SHIFTS.WEEKDAY4%TYPE;
  V_WEEKDAY5 HRIS_SHIFTS.WEEKDAY5%TYPE;
  V_WEEKDAY6 HRIS_SHIFTS.WEEKDAY6%TYPE;
  V_WEEKDAY7 HRIS_SHIFTS.WEEKDAY7%TYPE;
  V_DATE_DIFF     NUMBER:= TRUNC(P_END_DATE)-TRUNC(P_START_DATE);
  V_WEEKDAY       NUMBER;
  V_DAYOFF_COUNT  NUMBER:=0;
  V_HOLIDAY_COUNT NUMBER:=0;
  V_HOLIDAY_ID    NUMBER:=NULL;
  V_INCLUDE_DAYOFF_AS_LEAVE HRIS_LEAVE_MASTER_SETUP.DAY_OFF_AS_LEAVE%TYPE;
  V_INCLUDE_HOLIDAY_AS_LEAVE HRIS_LEAVE_MASTER_SETUP.HOLIDAY_AS_LEAVE%TYPE;
BEGIN

  BEGIN
  SELECT DAY_OFF_AS_LEAVE,HOLIDAY_AS_LEAVE INTO
  V_INCLUDE_DAYOFF_AS_LEAVE,V_INCLUDE_HOLIDAY_AS_LEAVE
FROM HRIS_LEAVE_MASTER_SETUP 
WHERE LEAVE_ID=P_LEAVE_ID;

  EXCEPTION
  WHEN NO_DATA_FOUND THEN
    V_INCLUDE_DAYOFF_AS_LEAVE:='N';
    V_INCLUDE_HOLIDAY_AS_LEAVE:='N';
  END;
--
BEGIN
  SELECT S.WEEKDAY1,
    S.WEEKDAY2,
    S.WEEKDAY3,
    S.WEEKDAY4,
    S.WEEKDAY5,
    S.WEEKDAY6,
    S.WEEKDAY7
  INTO V_WEEKDAY1,
    V_WEEKDAY2,
    V_WEEKDAY3,
    V_WEEKDAY4,
    V_WEEKDAY5,
    V_WEEKDAY6,
    V_WEEKDAY7
  FROM HRIS_SHIFTS S
  JOIN HRIS_EMPLOYEE_SHIFT_ASSIGN SA
  ON (S.SHIFT_ID     =SA.SHIFT_ID)
  WHERE S.STATUS     ='E'
  AND SA.STATUS      ='E'
  AND SA.EMPLOYEE_ID = P_EMPLOYEE_ID
  AND ROWNUM         =1;
EXCEPTION
WHEN NO_DATA_FOUND THEN
  SELECT S.WEEKDAY1,
    S.WEEKDAY2,
    S.WEEKDAY3,
    S.WEEKDAY4,
    S.WEEKDAY5,
    S.WEEKDAY6,
    S.WEEKDAY7
  INTO V_WEEKDAY1,
    V_WEEKDAY2,
    V_WEEKDAY3,
    V_WEEKDAY4,
    V_WEEKDAY5,
    V_WEEKDAY6,
    V_WEEKDAY7
  FROM HRIS_SHIFTS S
  WHERE S.DEFAULT_SHIFT ='Y'
  AND STATUS            = 'E';
END;


FOR i IN 0..V_DATE_DIFF
LOOP
  SELECT TO_CHAR(TRUNC(P_START_DATE+i),'d') INTO V_WEEKDAY FROM DUAL;
  IF (V_WEEKDAY     = 1 AND V_WEEKDAY1 = 'DAY_OFF') THEN
    V_DAYOFF_COUNT :=V_DAYOFF_COUNT+1;
    CONTINUE;
  END IF;
  IF (V_WEEKDAY     = 2 AND V_WEEKDAY2 = 'DAY_OFF') THEN
    V_DAYOFF_COUNT :=V_DAYOFF_COUNT+1;
    CONTINUE;
  END IF;
  IF (V_WEEKDAY     = 3 AND V_WEEKDAY4 = 'DAY_OFF') THEN
    V_DAYOFF_COUNT :=V_DAYOFF_COUNT+1;
    CONTINUE;
  END IF;
  IF (V_WEEKDAY     = 4 AND V_WEEKDAY4 = 'DAY_OFF') THEN
    V_DAYOFF_COUNT :=V_DAYOFF_COUNT+1;
    CONTINUE;
  END IF;
  IF (V_WEEKDAY     = 5 AND V_WEEKDAY5 = 'DAY_OFF') THEN
    V_DAYOFF_COUNT :=V_DAYOFF_COUNT+1;
    CONTINUE;
  END IF;
  IF (V_WEEKDAY     = 6 AND V_WEEKDAY6 = 'DAY_OFF') THEN
    V_DAYOFF_COUNT :=V_DAYOFF_COUNT+1;
    CONTINUE;
  END IF;
  IF (V_WEEKDAY     = 7 AND V_WEEKDAY7 = 'DAY_OFF') THEN
    V_DAYOFF_COUNT :=V_DAYOFF_COUNT+1;
    CONTINUE;
  END IF;
  BEGIN
    SELECT H.HOLIDAY_ID
    INTO V_HOLIDAY_ID
    FROM HRIS_HOLIDAY_MASTER_SETUP H
    JOIN HRIS_EMPLOYEE_HOLIDAY EH
    ON (H.HOLIDAY_ID  = EH.HOLIDAY_ID )
    WHERE H.STATUS    ='E'
    AND EH.EMPLOYEE_ID= P_EMPLOYEE_ID
    AND ((P_START_DATE               +i) BETWEEN H.START_DATE AND H.END_DATE);
    V_HOLIDAY_COUNT :=V_HOLIDAY_COUNT+1;
  EXCEPTION
  WHEN no_data_found THEN
    NULL;
  WHEN TOO_MANY_ROWS THEN
    V_HOLIDAY_COUNT :=V_HOLIDAY_COUNT+1;
  END;
END LOOP;
RETURN ((V_DATE_DIFF+1)-
(
  CASE
  WHEN V_INCLUDE_DAYOFF_AS_LEAVE ='N' THEN
    V_DAYOFF_COUNT
  ELSE
    0
  END)-
(
  CASE
  WHEN V_INCLUDE_HOLIDAY_AS_LEAVE='N' THEN
    V_HOLIDAY_COUNT
  ELSE
    0
  END))
--  /
--(
--  CASE
--  WHEN P_HALF_DAY IS NULL OR P_HALF_DAY ='N' THEN
--    1
--  ELSE
--    2
--  END)
;
END;/
            create or replace FUNCTION "HRIS_VALIDATE_LEAVE_REQUEST"(
  P_START_DATE HRIS_EMPLOYEE_LEAVE_REQUEST.START_DATE%TYPE,
  P_END_DATE HRIS_EMPLOYEE_LEAVE_REQUEST.END_DATE%TYPE,
  P_EMPLOYEE_ID HRIS_EMPLOYEE_LEAVE_REQUEST.EMPLOYEE_ID%TYPE)
RETURN VARCHAR2
AS
  V_MONTH_FROM_DATE      DATE;
  V_MONTH_TO_DATE        DATE;
  V_OVERLAPPING_LEAVE_NO NUMBER:=0;
  V_ENABLE_PREV_MTH_LEAVE_REQ HRIS_PREFERENCES.VALUE%TYPE;
  V_LEAVE_YEAR_END DATE;
BEGIN
  BEGIN
    SELECT VALUE
    INTO V_ENABLE_PREV_MTH_LEAVE_REQ
    FROM HRIS_PREFERENCES
    WHERE KEY = 'ENABLE_PREV_MTH_LEAVE_REQ';
  EXCEPTION
  WHEN NO_DATA_FOUND THEN
    V_ENABLE_PREV_MTH_LEAVE_REQ:='N';
  END;
  --
  IF V_ENABLE_PREV_MTH_LEAVE_REQ ='N' THEN
    SELECT FROM_DATE,
      TO_DATE
    INTO V_MONTH_FROM_DATE,
      V_MONTH_TO_DATE
    FROM HRIS_LEAVE_MONTH_CODE
    WHERE TRUNC(SYSDATE) BETWEEN FROM_DATE AND TO_DATE;
    --
    IF(TRUNC(P_START_DATE)<TRUNC(V_MONTH_FROM_DATE)) THEN
      RETURN 'Leave Request to previous month is not allowed.';
    END IF;
  END IF;
  --
  SELECT COUNT(*)
  INTO V_OVERLAPPING_LEAVE_NO
  FROM HRIS_EMPLOYEE_LEAVE_REQUEST
  WHERE (( START_DATE between P_START_DATE and P_END_DATE )
  OR ( END_DATE between P_START_DATE and P_END_DATE ))
  AND STATUS IN  ('RQ','RC','AP','CP','CR')
  AND EMPLOYEE_ID = P_EMPLOYEE_ID ;
  --
  IF(V_OVERLAPPING_LEAVE_NO >0) THEN
    RETURN 'Leave Request is overlapping other leave request.';
  END IF;
  
  BEGIN
  SELECT END_DATE INTO V_LEAVE_YEAR_END FROM HRIS_LEAVE_YEARS WHERE 
           P_START_DATE  BETWEEN START_DATE AND END_DATE;
 EXCEPTION
 WHEN NO_DATA_FOUND THEN
      SELECT
        MAX(END_DATE)
        INTO
        V_LEAVE_YEAR_END
        FROM
        HRIS_LEAVE_YEARS;
    END;

           IF(P_END_DATE>V_LEAVE_YEAR_END) THEN
           RETURN 'You Are Requesting leave for next leave Year';
           END IF;

  
  RETURN NULL;
END;
/
            create or replace function hris_validate_overtime_attd(
    p_employee_id HRIS_ATTENDANCE_DETAIL.EMPLOYEE_ID%TYPE,
    p_attendance_dt HRIS_ATTENDANCE_DETAIL.ATTENDANCE_DT%TYPE
) 
return char
as
begin
return 'T';
end;/
            create or replace FUNCTION LATE_STATUS_DESC(
    P_STATUS HRIS_ATTENDANCE_DETAIL.LATE_STATUS%TYPE)
  RETURN VARCHAR2
IS
  V_STATUS_DESC VARCHAR2(50 BYTE);
BEGIN
  V_STATUS_DESC:=
  (
    CASE P_STATUS
    WHEN 'L' THEN
      '[Late In]'
    WHEN 'E'THEN
      '[Early Out]'
    WHEN 'B' THEN
      '[Late In and Early Out]'
    WHEN 'X' THEN
      '[Missed Punch]'
    WHEN 'Y' THEN
      '[Late In and Missed Punch]'
    END);
  RETURN V_STATUS_DESC;
END;
 /
            create or replace FUNCTION leave_status_desc ( p_status hris_employee_leave_request.status%TYPE ) RETURN VARCHAR2 IS
    v_status_desc   VARCHAR2(50 BYTE);
BEGIN
    v_status_desc := ( CASE p_status
        WHEN 'RQ' THEN 'Pending'
        WHEN 'RC' THEN 'Recommended'
        WHEN 'R' THEN 'Rejected'
        WHEN 'AP' THEN 'Approved'
        WHEN 'C' THEN 'Cancelled'
        WHEN 'CP' THEN 'C Pending'
        WHEN 'CR' THEN 'C Recommended'
    END );

    RETURN v_status_desc;
END;
/
            create or replace FUNCTION MIN_TO_HOUR(
    P_MIN NUMBER)
  RETURN VARCHAR2
IS
  V_HOUR NUMBER;
  V_MIN  NUMBER;
BEGIN
  IF(P_MIN IS NULL) THEN
    RETURN NULL;
  END IF;
V_HOUR :=TRUNC(P_MIN/60,0);
V_MIN  :=MOD(P_MIN,60) ;
RETURN V_HOUR||':'||V_MIN;
END;
 /
            create or replace FUNCTION NOTIFICATION_STATUS_DESC(
    P_STATUS HRIS_NOTIFICATION.STATUS%TYPE)
  RETURN VARCHAR2
IS
  V_STATUS_DESC VARCHAR2(50 BYTE);
BEGIN
  V_STATUS_DESC:=
  (
    CASE P_STATUS
    WHEN 'S' THEN
      'Seen'
    WHEN 'U'THEN
      'Unseen'
    END);
  RETURN V_STATUS_DESC;
END;/
            create or replace FUNCTION REC_APP_ROLE(
    P_EMPLOYEE_ID HRIS_EMPLOYEES.EMPLOYEE_ID%TYPE,
    P_RECOMMENDER_ID HRIS_RECOMMENDER_APPROVER.RECOMMEND_BY%TYPE,
    P_APPROVER_ID HRIS_RECOMMENDER_APPROVER.APPROVED_BY%TYPE )
  RETURN VARCHAR2
IS
  V_ROLE_TYPE NUMBER;
BEGIN
  IF(P_EMPLOYEE_ID = P_RECOMMENDER_ID AND P_EMPLOYEE_ID = P_APPROVER_ID)THEN
    V_ROLE_TYPE   :=4;
  ELSE
    IF(P_EMPLOYEE_ID = P_RECOMMENDER_ID) THEN
      V_ROLE_TYPE   :=2;
    END IF;
    IF(P_EMPLOYEE_ID= P_APPROVER_ID) THEN
      V_ROLE_TYPE  :=3;
    END IF;
  END IF;
RETURN V_ROLE_TYPE;
END;/
            create or replace FUNCTION REC_APP_ROLE_NAME(
    P_EMPLOYEE_ID HRIS_EMPLOYEES.EMPLOYEE_ID%TYPE,
    P_RECOMMENDER_ID HRIS_RECOMMENDER_APPROVER.RECOMMEND_BY%TYPE,
    P_APPROVER_ID HRIS_RECOMMENDER_APPROVER.APPROVED_BY%TYPE )
  RETURN VARCHAR2
IS
  V_ROLE_TYPE VARCHAR2(50 BYTE);
BEGIN
  IF(P_EMPLOYEE_ID = P_RECOMMENDER_ID AND P_EMPLOYEE_ID = P_APPROVER_ID)THEN
    V_ROLE_TYPE   :='Recommender/Approver';
  ELSE
    IF(P_EMPLOYEE_ID = P_RECOMMENDER_ID) THEN
      V_ROLE_TYPE   :='Recommender';
    END IF;
    IF(P_EMPLOYEE_ID= P_APPROVER_ID) THEN
      V_ROLE_TYPE  :='Approver';
    END IF;
  END IF;
RETURN V_ROLE_TYPE;
END;/
            CREATE OR REPLACE FUNCTION "ROLE_CONTROL_DESC"(
    P_FLAG HRIS_ROLES.CONTROL%TYPE)
  RETURN VARCHAR2
IS
  V_FLAG_DESC VARCHAR2(50 BYTE);
BEGIN
  V_FLAG_DESC:=
  (
    CASE P_FLAG
    WHEN 'F' THEN
      'Full'
    WHEN 'C'THEN
      'Company Specific'
    WHEN 'U'THEN
      'User Specific'
    WHEN 'B'THEN
      'Branch Specific'
    WHEN 'DP'THEN
      'Department Specific'
    WHEN 'DS'THEN
      'Designation Specific'
    WHEN 'P'THEN
      'Position Specific'
    END);
  RETURN V_FLAG_DESC;
END;/
            CREATE OR REPLACE FUNCTION STATUS_DESC(
    P_FLAG CHAR)
  RETURN VARCHAR2
IS
  V_FLAG_DESC VARCHAR2(50 BYTE);
BEGIN
  V_FLAG_DESC:=
  (
    CASE P_FLAG
    WHEN 'E' THEN
      'Enabled'
    WHEN 'D'THEN
      'Disabled'
    END);
  RETURN V_FLAG_DESC;
END; /
            create or replace FUNCTION TRAINING_TYPE_DESC(
    P_TRAINING_TYPE HRIS_EMPLOYEE_TRAINING_REQUEST.TRAINING_TYPE%TYPE)
  RETURN VARCHAR2
IS
  V_TRAINING_TYPE_DESC VARCHAR2(50 BYTE);
BEGIN
  V_TRAINING_TYPE_DESC:=
  (
    CASE P_TRAINING_TYPE
    WHEN 'CC' THEN
      'Company Contribution'
    WHEN 'RC'THEN
      'Personal'
    END);
  RETURN V_TRAINING_TYPE_DESC;
END;/
            CREATE OR REPLACE FUNCTION WORKING_CYCLE_DESC(
    P_WORKING_CYCLE CHAR)
  RETURN VARCHAR2
IS
  V_WORKING_CYCLE_DESC VARCHAR2(50 BYTE);
BEGIN
  V_WORKING_CYCLE_DESC:=
  (
    CASE P_WORKING_CYCLE
    WHEN 'W' THEN
      'Weekly'
    WHEN 'M'THEN
      'Monthly'
    WHEN 'R' THEN
      'Randomly'
    END);
  RETURN V_WORKING_CYCLE_DESC;
END; /
            create or replace function lunch_in_time
(p_employee_id in number,
p_attendnace_dt in date
,p_shift_id in number,
p_in_time in timestamp,
p_out_time in timestamp
) 
return Char 
is 
-- variables
v_half_time char(20 byte):=null;
v_count number:=0;
v_in_time timestamp:=p_in_time;
v_out_time timestamp:=p_out_time;
begin

if(v_in_time is null)
then
select 
to_timestamp(TO_CHAR(p_attendnace_dt,'DD-MON-YY')||TO_CHAR(start_time,'HH:MI AM'),'DD-MON-YY HH:MI AM')+ INTERVAL '30'
MINUTE into v_in_time
from hris_shifts where shift_id=p_shift_id;
end if;

if(v_out_time is null)
then
select 
to_timestamp(
case when two_day_shift='E'
then
TO_CHAR(p_attendnace_dt+1,'DD-MON-YY')
else
TO_CHAR(p_attendnace_dt,'DD-MON-YY')
end 
||TO_CHAR(end_time,'HH:MI AM'),'DD-MON-YY HH:MI AM')- INTERVAL '30' MINUTE
into v_out_time
from hris_shifts where shift_id=p_shift_id;
end if;

select Count(*) into v_count from Hris_Attendance where employee_id=p_employee_id and Attendance_Dt=trunc(p_attendnace_dt);

if v_count>2 and mod(v_count,2)=0 then

select 
half_out_time
into v_half_time
from (select 
to_char(Attendance_Time,'HH:MI AM') as half_out_time
from Hris_Attendance where employee_id=p_employee_id and Attendance_Dt=p_attendnace_dt
and attendance_time>v_in_time
and 
attendance_time<P_Out_Time
order by Attendance_Time DESC) where Rownum=1;

--select systimestamp into v_half_time from dual;
end if;

return v_half_time; 
end;
 /
            create or replace function lunch_out_time
(p_employee_id in number,
p_attendnace_dt in date,
p_shift_id in number,
p_in_time in timestamp,
p_out_time in timestamp
) 
return Char 
is 
-- variables
v_half_time char(20 byte):=null;
v_count number:=0;
v_in_time timestamp:=p_in_time;
v_out_time timestamp:=p_out_time;
begin

if(v_in_time is null)
then
select 
to_timestamp(TO_CHAR(p_attendnace_dt,'DD-MON-YY')||TO_CHAR(start_time,'HH:MI AM'),'DD-MON-YY HH:MI AM')+ INTERVAL '30'
MINUTE into v_in_time
from hris_shifts where shift_id=p_shift_id;
end if;

if(v_out_time is null)
then
select 
to_timestamp(
case when two_day_shift='E'
then
TO_CHAR(p_attendnace_dt+1,'DD-MON-YY')
else
TO_CHAR(p_attendnace_dt,'DD-MON-YY')
end 
||TO_CHAR(end_time,'HH:MI AM'),'DD-MON-YY HH:MI AM')- INTERVAL '30' MINUTE
into v_out_time
from hris_shifts where shift_id=p_shift_id;
end if;

select Count(*) into v_count from Hris_Attendance where employee_id=p_employee_id and Attendance_Dt=trunc(p_attendnace_dt);


if v_count>2 and mod(v_count,2)=0 then

select 
half_out_time
into v_half_time
from (select 
to_char(Attendance_Time,'HH:MI AM') as half_out_time
from Hris_Attendance where employee_id=p_employee_id 
and attendance_time>v_in_time
and 
attendance_time<v_out_time
order by Attendance_Time ASC) where Rownum=1;

--select systimestamp into v_half_time from dual;
end if;

return v_half_time; 
end;
 /
            CREATE OR REPLACE PROCEDURE hris_advance_request_proc(
    p_adv_req_id hris_employee_advance_request.advance_request_id%TYPE,
    p_link_to_synergy CHAR := 'N' )
AS
  v_employee_id hris_employee_advance_request.employee_id%TYPE;
  v_status hris_employee_advance_request.status%TYPE;
  v_requested_amount hris_employee_advance_request.requested_amount%TYPE;
  v_date_of_advance hris_employee_advance_request.date_of_advance%TYPE;
  v_deduction_in hris_employee_advance_request.deduction_in%TYPE;
  --
  v_form_code hris_preferences.value%TYPE;
  v_dr_acc_code hris_preferences.value%TYPE;
  v_cr_acc_code hris_preferences.value%TYPE; --
  v_company_code VARCHAR2(255 BYTE) := '07';
  v_branch_code  VARCHAR2(255 BYTE) :=-'07.01';
  v_created_by   VARCHAR2(255 BYTE) := 'ADMIN';
  v_voucher_no   VARCHAR2(255 BYTE);
BEGIN
  BEGIN
    SELECT tr.employee_id,
      tr.status,
      tr.requested_amount,
      tr.date_of_advance,
      tr.deduction_in,
      c.company_code,
      CASE WHEN e.EMPOWER_BRANCH_CODE IS NULL OR e.EMPOWER_BRANCH_CODE='-1'
      THEN c.company_code|| '.01'
      ELSE e.EMPOWER_BRANCH_CODE
      END,
      c.form_code,
      c.advance_dr_acc_code,
      c.advance_cr_acc_code,
      e.FULL_NAME
    INTO v_employee_id,
      v_status,
      v_requested_amount,
      v_date_of_advance,
      v_deduction_in,
      v_company_code,
      v_branch_code,
      v_form_code,
      v_dr_acc_code ,
      v_cr_acc_code,
      v_created_by
    FROM hris_employee_advance_request tr
    JOIN hris_employees e
    ON ( tr.employee_id = e.employee_id )
    JOIN hris_company c
    ON ( e.company_id           = c.company_id )
    WHERE tr.advance_request_id = p_adv_req_id;
  EXCEPTION
  WHEN no_data_found THEN
    dbms_output.put('NO DATA FOUND FOR ID =>' || p_adv_req_id);
    RETURN;
  END;
  --
  --
  IF p_link_to_synergy = 'Y' THEN
    SELECT fn_new_voucher_no( v_company_code, v_form_code, TRUNC(SYSDATE), 'FA_DOUBLE_VOUCHER' )
    INTO v_voucher_no
    FROM dual;
    --
    hris_travel_advance( v_company_code, v_form_code, TRUNC(SYSDATE), v_branch_code, v_created_by, TRUNC(SYSDATE), v_dr_acc_code, v_cr_acc_code, 'SALARY ADVANCE', v_requested_amount, 'E' || v_employee_id, v_voucher_no );
    --
    hris_advance_to_empower( v_company_code, v_branch_code, v_date_of_advance, v_date_of_advance, v_created_by, v_requested_amount, v_deduction_in, v_requested_amount / v_deduction_in, v_employee_id, v_dr_acc_code, v_cr_acc_code );
    --
    UPDATE hris_employee_advance_request
    SET voucher_no           = v_voucher_no
    WHERE advance_request_id = p_adv_req_id;
  END IF;
  --
END;/
            CREATE OR REPLACE PROCEDURE hris_advance_to_empower(
    company_code_v    VARCHAR2,
    branch_code_v     VARCHAR2,
    transaction_date  DATE,
    installment_date  DATE,
    created_by        VARCHAR2,
    advance_amount    NUMBER,
    no_of_installment NUMBER,
    per_month         VARCHAR2,
    vemployeecode     VARCHAR2,
    vdraccount        VARCHAR2,
    vcraccount        VARCHAR2 )
IS
  ireqno    NUMBER;
  dadvpfrom DATE;
  dadvpto   DATE;
  idays     NUMBER;
  vbsmonth  VARCHAR2(10);
  ddate     DATE;
BEGIN
  BEGIN
    SELECT NVL( MAX(NVL(to_number(request_no),0) ),0) + 1
    INTO ireqno
    FROM hr_advance_request
    WHERE company_code = company_code_v;
  EXCEPTION
  WHEN OTHERS THEN
    ireqno := 1;
  END;
  BEGIN
    SELECT start_date,
      end_date
    INTO dadvpfrom,
      dadvpto
    FROM hr_period_detail
    WHERE TO_DATE(transaction_date) BETWEEN start_date AND end_date
    AND company_code = company_code_v;
  EXCEPTION
  WHEN OTHERS THEN
    NULL;
  END;
  INSERT
  INTO hr_advance_request
    (
      request_no,
      request_date,
      employee_code,
      advance_type,
      request_amount,
      acc_code,
      repayment_start_date,
      repayment_count,
      repayment_period_flag,
      company_code,
      branch_code,
      created_by,
      created_date,
      deleted_flag,
      premium_type,
      acc_code_cr
    )
    VALUES
    (
      ireqno,
      transaction_date,
      vemployeecode,
      'R271',
      advance_amount,
      vdraccount,
      transaction_date,
      no_of_installment,
      'M',
      company_code_v,
      branch_code_v,
      created_by,
      SYSDATE,
      'N',
      0,
      vcraccount
    );
  INSERT
  INTO hr_advance_request_detail
    (
      request_no,
      serial_no,
      from_date,
      TO_DATE,
      amount,
      paid_flag,
      company_code,
      branch_code,
      created_by,
      created_date,
      deleted_flag
    )
    VALUES
    (
      ireqno,
      1,
      dadvpfrom,
      dadvpto,
      per_month,
      'N',
      company_code_v,
      branch_code_v,
      created_by,
      SYSDATE,
      'N'
    );
  FOR i IN 2..no_of_installment
  LOOP
    dadvpto := dadvpto + 1;
    SELECT bs_month,
      ad_date,
      days_no
    INTO vbsmonth,
      ddate,
      idays
    FROM calendar_setup
    WHERE bs_month = SUBSTR( bs_date(dadvpto), 1, 7 );
    SELECT ad_date(vbsmonth || '-01') INTO dadvpfrom FROM dual;
    SELECT ad_date(vbsmonth || '-' || idays) INTO dadvpto FROM dual;
    INSERT
    INTO hr_advance_request_detail
      (
        request_no,
        serial_no,
        from_date,
        TO_DATE,
        amount,
        paid_flag,
        company_code,
        branch_code,
        created_by,
        created_date,
        deleted_flag
      )
      VALUES
      (
        ireqno,
        i,
        dadvpfrom,
        dadvpto,
        per_month,
        'N',
        company_code_v,
        branch_code_v,
        created_by,
        SYSDATE,
        'N'
      );
  END LOOP;
EXCEPTION
WHEN OTHERS THEN
  raise_application_error( -20001, 'An error was encountered - ' || SQLCODE || ' -ERROR- ' || sqlerrm );
END;/
            CREATE OR REPLACE PROCEDURE HRIS_MIGRATE_ATTD_DATA
AS
BEGIN
  FOR attd_data IN
  (SELECT HR.*
  FROM
    (SELECT dc.card_no AS thumb_id,
      d_card           AS attendance_dt,
      TO_TIMESTAMP (dc.D_CARD
      ||DC.T_CARD, 'DD-MON-YY HH24:MI:SS.FF') ATTENDANCE_TIME,
      ds.IP_ADDRESS
    FROM data_card dc
    JOIN device_setup ds
    ON dc.NODE_NO =ds.DEVICE_NO
    ) HR
  JOIN
    (SELECT A.IP_ADDRESS,
      MAX(A.ATTENDANCE_TIME) AS ATTENDANCE_TIME
    FROM HRIS_ATTD_DEVICE_MASTER ADM
    LEFT JOIN HRIS_ATTENDANCE A
    ON(A.IP_ADDRESS=ADM.DEVICE_IP)
    GROUP BY A.IP_ADDRESS
    ) HRIS ON (HR.IP_ADDRESS=HRIS.IP_ADDRESS
  AND (HR.ATTENDANCE_TIME   > HRIS.ATTENDANCE_TIME
  OR HRIS.IP_ADDRESS       IS NULL) )
  )
  LOOP
    HRIS_ATTENDANCE_INSERT( attd_data.thumb_id,attd_data.attendance_dt,attd_data.IP_ADDRESS,'DATA_CARD',attd_data.attendance_time,'from data_card');
  END LOOP attd_data;
END;/
            create or replace PROCEDURE HRIS_TO_EMPOWER(
    V_FISCAL_YEAR_ID       NUMBER,
    V_FISCAL_YEAR_MONTH_NO NUMBER)
AS
  V_FROM_DATE DATE;
  V_TO_DATE   DATE;
BEGIN
  SELECT FROM_DATE,
    TO_DATE
  INTO V_FROM_DATE,
    V_TO_DATE
  FROM HRIS_MONTH_CODE
  WHERE FISCAL_YEAR_ID    =V_FISCAL_YEAR_ID
  AND FISCAL_YEAR_MONTH_NO=V_FISCAL_YEAR_MONTH_NO;
  DELETE
  FROM HR_MONTHLY_MODIFIED_PAY_VALUE
  WHERE PERIOD_DT_CODE=V_FISCAL_YEAR_MONTH_NO
  AND PAY_CODE       IN ('TD','PD','AD','HD','PL','UL','OT');
  FOR report         IN
  (SELECT C.COMPANY_CODE,
    C.COMPANY_CODE
    ||'.01' AS BRANCH_CODE,
    C.COMPANY_NAME,
    D.DEPARTMENT_NAME,
    A.EMPLOYEE_ID,
    E.FULL_NAME,
    A.DAYOFF,
    A.PRESENT,
    A.HOLIDAY,
    A.LEAVE,
    A.PAID_LEAVE,
    A.UNPAID_LEAVE,
    A.ABSENT,
    A.DAYOFF             +A.PRESENT+A.HOLIDAY+A.UNPAID_LEAVE+A.PAID_LEAVE+A.ABSENT AS TOTAL_DAYS,
    NVL(ROUND(A.TOTAL_MIN/60,2),0)                                                 AS OVERTIME_HOUR,
    A.TRAVEL,
    A.TRAINING,
    A.WORK_ON_HOLIDAY,
    A.WORK_ON_DAYOFF
  FROM
    (SELECT A.EMPLOYEE_ID,
      SUM(
      CASE
        WHEN A.OVERALL_STATUS IN( 'DO','WD')
        THEN 1
        ELSE 0
      END) AS DAYOFF,
      SUM(
      CASE
        WHEN A.OVERALL_STATUS IN ('PR','BA','LA','TV','VP','TN','TP','LP')
        THEN (
          CASE
            WHEN A.OVERALL_STATUS = 'LP'
            AND A.HALFDAY_PERIOD IS NOT NULL
            THEN 0.5
            ELSE 1
          END)
        ELSE 0
      END) AS PRESENT,
      SUM(
      CASE
        WHEN A.OVERALL_STATUS IN ('HD','WH')
        THEN 1
        ELSE 0
      END) AS HOLIDAY,
      SUM(
      CASE
        WHEN A.OVERALL_STATUS IN ('LV','LP')
        AND A.GRACE_PERIOD    IS NULL
        THEN (
          CASE
            WHEN A.OVERALL_STATUS = 'LP'
            AND A.HALFDAY_PERIOD IS NOT NULL
            THEN 0.5
            ELSE 1
          END)
        ELSE 0
      END) AS LEAVE,
      SUM(
      CASE
        WHEN A.OVERALL_STATUS IN ('LV','LP')
        AND A.GRACE_PERIOD    IS NULL
        AND L.PAID             = 'Y'
        THEN (
          CASE
            WHEN A.OVERALL_STATUS = 'LP'
            AND A.HALFDAY_PERIOD IS NOT NULL
            THEN 0.5
            ELSE 1
          END)
        ELSE 0
      END) AS PAID_LEAVE,
      SUM(
      CASE
        WHEN A.OVERALL_STATUS IN ('LV','LP')
        AND A.GRACE_PERIOD    IS NULL
        AND L.PAID             = 'N'
        THEN (
          CASE
            WHEN A.OVERALL_STATUS = 'LP'
            AND A.HALFDAY_PERIOD IS NOT NULL
            THEN 0.5
            ELSE 1
          END)
        ELSE 0
      END) AS UNPAID_LEAVE,
      SUM(
      CASE
        WHEN A.OVERALL_STATUS = 'AB'
        THEN 1
        ELSE 0
      END) AS ABSENT,
      SUM(
      CASE
        WHEN A.OVERALL_STATUS= 'TV'
        THEN 1
        ELSE 0
      END) AS TRAVEL,
      SUM(
      CASE
        WHEN A.OVERALL_STATUS ='TN'
        THEN 1
        ELSE 0
      END) AS TRAINING,
      SUM(
      CASE
        WHEN A.OVERALL_STATUS = 'WH'
        THEN 1
        ELSE 0
      END) WORK_ON_HOLIDAY,
      SUM(
      CASE
        WHEN A.OVERALL_STATUS ='WD'
        THEN 1
        ELSE 0
      END) WORK_ON_DAYOFF,
      SUM(
      CASE
        WHEN OTM.OVERTIME_HOUR IS NULL
        THEN OT.TOTAL_HOUR
        ELSE OTM.OVERTIME_HOUR*60
      END ) AS TOTAL_MIN
    FROM HRIS_ATTENDANCE_PAYROLL A
    LEFT JOIN (
    SELECT
    employee_id,
    overtime_date,
    SUM(total_hour) AS total_hour
FROM
    hris_overtime where status ='AP'
GROUP BY
    employee_id,
    overtime_date
    ) OT
    ON (A.EMPLOYEE_ID   =OT.EMPLOYEE_ID
    AND A.ATTENDANCE_DT =OT.OVERTIME_DATE)
    LEFT JOIN HRIS_OVERTIME_MANUAL OTM
    ON (A.EMPLOYEE_ID   =OTM.EMPLOYEE_ID
    AND A.ATTENDANCE_DT =OTM.ATTENDANCE_DATE)
    LEFT JOIN HRIS_LEAVE_MASTER_SETUP L
    ON (A.LEAVE_ID= L.LEAVE_ID)
    WHERE A.ATTENDANCE_DT BETWEEN V_FROM_DATE AND V_TO_DATE
    GROUP BY A.EMPLOYEE_ID
    ) A
  LEFT JOIN HRIS_EMPLOYEES E
  ON(A.EMPLOYEE_ID = E.EMPLOYEE_ID)
  LEFT JOIN HRIS_COMPANY C
  ON(E.COMPANY_ID= C.COMPANY_ID)
  LEFT JOIN HRIS_DEPARTMENTS D
  ON (E.DEPARTMENT_ID= D.DEPARTMENT_ID)
  ORDER BY C.COMPANY_NAME,
    D.DEPARTMENT_NAME
  )
  LOOP
    INSERT
    INTO HR_MONTHLY_MODIFIED_PAY_VALUE
      (
        PAY_CODE,
        EMPLOYEE_CODE,
        PERIOD_DT_CODE,
        MODIFY_VALUE,
        COMPANY_CODE,
        BRANCH_CODE,
        CREATED_BY,
        CREATED_DATE,
        SALARY_TYPE
      )
      VALUES
      (
        'TD',
        report.EMPLOYEE_ID,
        V_FISCAL_YEAR_MONTH_NO,
        report.DAYOFF+report.PRESENT+report.HOLIDAY+report.UNPAID_LEAVE+report.PAID_LEAVE+report.ABSENT,
        report.COMPANY_CODE,
        report.BRANCH_CODE,
        'SYSTEM',
        SYSDATE,
        0
      );
    --
    INSERT
    INTO HR_MONTHLY_MODIFIED_PAY_VALUE
      (
        PAY_CODE,
        EMPLOYEE_CODE,
        PERIOD_DT_CODE,
        MODIFY_VALUE,
        COMPANY_CODE,
        BRANCH_CODE,
        CREATED_BY,
        CREATED_DATE,
        SALARY_TYPE
      )
      VALUES
      (
        'PD',
        report.EMPLOYEE_ID,
        V_FISCAL_YEAR_MONTH_NO,
        report.PRESENT,
        report.COMPANY_CODE,
        report.BRANCH_CODE,
        'SYSTEM',
        SYSDATE,
        0
      );
    INSERT
    INTO HR_MONTHLY_MODIFIED_PAY_VALUE
      (
        PAY_CODE,
        EMPLOYEE_CODE,
        PERIOD_DT_CODE,
        MODIFY_VALUE,
        COMPANY_CODE,
        BRANCH_CODE,
        CREATED_BY,
        CREATED_DATE,
        SALARY_TYPE
      )
      VALUES
      (
        'AD',
        report.EMPLOYEE_ID,
        V_FISCAL_YEAR_MONTH_NO,
        report.ABSENT,
        report.COMPANY_CODE,
        report.BRANCH_CODE,
        'SYSTEM',
        SYSDATE,
        0
      );
    INSERT
    INTO HR_MONTHLY_MODIFIED_PAY_VALUE
      (
        PAY_CODE,
        EMPLOYEE_CODE,
        PERIOD_DT_CODE,
        MODIFY_VALUE,
        COMPANY_CODE,
        BRANCH_CODE,
        CREATED_BY,
        CREATED_DATE,
        SALARY_TYPE
      )
      VALUES
      (
        'HD',
        report.EMPLOYEE_ID,
        V_FISCAL_YEAR_MONTH_NO,
        report.HOLIDAY+report.DAYOFF,
        report.COMPANY_CODE,
        report.BRANCH_CODE,
        'SYSTEM',
        SYSDATE,
        0
      );
    IF(report.PAID_LEAVE !=0) THEN
      INSERT
      INTO HR_MONTHLY_MODIFIED_PAY_VALUE
        (
          PAY_CODE,
          EMPLOYEE_CODE,
          PERIOD_DT_CODE,
          MODIFY_VALUE,
          COMPANY_CODE,
          BRANCH_CODE,
          CREATED_BY,
          CREATED_DATE,
          SALARY_TYPE
        )
        VALUES
        (
          'PL',
          report.EMPLOYEE_ID,
          V_FISCAL_YEAR_MONTH_NO,
          report.PAID_LEAVE,
          report.COMPANY_CODE,
          report.BRANCH_CODE,
          'SYSTEM',
          SYSDATE,
          0
        );
    END IF;
    IF(report.UNPAID_LEAVE !=0) THEN
      INSERT
      INTO HR_MONTHLY_MODIFIED_PAY_VALUE
        (
          PAY_CODE,
          EMPLOYEE_CODE,
          PERIOD_DT_CODE,
          MODIFY_VALUE,
          COMPANY_CODE,
          BRANCH_CODE,
          CREATED_BY,
          CREATED_DATE,
          SALARY_TYPE
        )
        VALUES
        (
          'UL',
          report.EMPLOYEE_ID,
          V_FISCAL_YEAR_MONTH_NO,
          report.UNPAID_LEAVE,
          report.COMPANY_CODE,
          report.BRANCH_CODE,
          'SYSTEM',
          SYSDATE,
          0
        );
    END IF;
    IF(report.OVERTIME_HOUR !=0) THEN
      INSERT
      INTO HR_MONTHLY_MODIFIED_PAY_VALUE
        (
          PAY_CODE,
          EMPLOYEE_CODE,
          PERIOD_DT_CODE,
          MODIFY_VALUE,
          COMPANY_CODE,
          BRANCH_CODE,
          CREATED_BY,
          CREATED_DATE,
          SALARY_TYPE
        )
        VALUES
        (
          'OT',
          report.EMPLOYEE_ID,
          V_FISCAL_YEAR_MONTH_NO,
          report.OVERTIME_HOUR,
          report.COMPANY_CODE,
          report.BRANCH_CODE,
          'SYSTEM',
          SYSDATE,
          0
        );
    END IF;
  END LOOP;
END;
/
            CREATE OR REPLACE PROCEDURE HRIS_TRAVEL_ADVANCE(
    COMPANY_CODE     VARCHAR2,
    FORM_CODE        VARCHAR2,
    TRANSACTION_DATE DATE,
    BRANCH_CODE      VARCHAR2,
    CREATED_BY       VARCHAR2,
    CREATED_DATE     DATE,
    DR_ACC_CODE      VARCHAR2,
    CR_ACC_CODE      VARCHAR2,
    PARTICULARS      VARCHAR2,
    AMOUNT           NUMBER,
    SUB_CODE         VARCHAR2,
    VOUCHER_NO       VARCHAR2)
IS
  SESSION_ROWID NUMBER           :=-1;
  CURRENCY_CODE VARCHAR2(3 BYTE) :='NRS';
  EXCHANGE_RATE NUMBER           :=1;
  SERIAL_NO_1   NUMBER(5)        :=1;
  SERIAL_NO_2   NUMBER(5)        :=2;
BEGIN
  SAVEPOINT START_OF_FUNCTION;
  SELECT MYSEQUENCE.NEXTVAL INTO SESSION_ROWID FROM DUAL;
  INSERT
  INTO MASTER_TRANSACTION
    (
      VOUCHER_NO,
      VOUCHER_DATE,
      VOUCHER_AMOUNT,
      FORM_CODE,
      COMPANY_CODE,
      BRANCH_CODE,
      CREATED_BY,
      CREATED_DATE,
      DELETED_FLAG,
      CURRENCY_CODE,
      EXCHANGE_RATE,
      SESSION_ROWID
    )
    VALUES
    (
      VOUCHER_NO,
      TRANSACTION_DATE,
      AMOUNT,
      FORM_CODE,
      COMPANY_CODE,
      BRANCH_CODE,
      CREATED_BY,
      CREATED_DATE,
      'N',
      CURRENCY_CODE,
      EXCHANGE_RATE,
      SESSION_ROWID
    );
  INSERT
  INTO FA_DOUBLE_VOUCHER
    (
      VOUCHER_NO,
      VOUCHER_DATE,
      SERIAL_NO,
      ACC_CODE,
      PARTICULARS,
      TRANSACTION_TYPE,
      AMOUNT,
      FORM_CODE,
      COMPANY_CODE,
      BRANCH_CODE,
      CREATED_BY,
      CREATED_DATE,
      DELETED_FLAG,
      CURRENCY_CODE,
      EXCHANGE_RATE,
      SESSION_ROWID
    )
    VALUES
    (
      VOUCHER_NO,
      TRANSACTION_DATE,
      SERIAL_NO_1,
      DR_ACC_CODE,
      PARTICULARS,
      'DR',
      AMOUNT ,
      FORM_CODE,
      COMPANY_CODE,
      BRANCH_CODE,
      CREATED_BY,
      CREATED_DATE,
      'N',
      CURRENCY_CODE,
      EXCHANGE_RATE,
      SESSION_ROWID
    );
  INSERT
  INTO FA_VOUCHER_SUB_DETAIL
    (
      VOUCHER_NO,
      SERIAL_NO,
      ACC_CODE,
      TRANSACTION_TYPE,
      SUB_CODE,
      PARTICULARS,
      DR_AMOUNT,
      CR_AMOUNT,
      FORM_CODE,
      COMPANY_CODE,
      BRANCH_CODE,
      CREATED_BY,
      CREATED_DATE,
      DELETED_FLAG,
      CURRENCY_CODE,
      EXCHANGE_RATE,
      SESSION_ROWID
    )
    VALUES
    (
      VOUCHER_NO,
      SERIAL_NO_1 ,
      DR_ACC_CODE,
      'DR',
      SUB_CODE,
      PARTICULARS,
      AMOUNT,
      0,
      FORM_CODE,
      COMPANY_CODE,
      BRANCH_CODE,
      CREATED_BY,
      CREATED_DATE,
      'N',
      CURRENCY_CODE,
      EXCHANGE_RATE,
      SESSION_ROWID
    );
  INSERT
  INTO FA_DOUBLE_VOUCHER
    (
      VOUCHER_NO,
      VOUCHER_DATE,
      SERIAL_NO,
      ACC_CODE,
      PARTICULARS,
      TRANSACTION_TYPE,
      AMOUNT,
      FORM_CODE,
      COMPANY_CODE,
      BRANCH_CODE,
      CREATED_BY,
      CREATED_DATE,
      DELETED_FLAG,
      CURRENCY_CODE,
      EXCHANGE_RATE,
      SESSION_ROWID
    )
    VALUES
    (
      VOUCHER_NO,
      TRANSACTION_DATE,
      SERIAL_NO_2 ,
      CR_ACC_CODE,
      PARTICULARS,
      'CR',
      AMOUNT,
      FORM_CODE,
      COMPANY_CODE,
      BRANCH_CODE,
      CREATED_BY,
      CREATED_DATE,
      'N',
      CURRENCY_CODE,
      EXCHANGE_RATE,
      SESSION_ROWID
    );
EXCEPTION
WHEN OTHERS THEN
  ROLLBACK TO START_OF_FUNCTION;
  raise_application_error(-20001,'An error was encountered - '||SQLCODE||' -ERROR- '||SQLERRM);
END;/
            CREATE OR REPLACE PROCEDURE HRIS_TRAVEL_CANCELLATION(
    P_TRAVEL_ID HRIS_EMPLOYEE_TRAVEL_REQUEST.TRAVEL_ID%TYPE )
    AS
    V_VOUCHER_NO   VARCHAR2(255 BYTE);
    TABLE_COUNT NUMBER(1,0);
begin
SELECT 
voucher_no into V_VOUCHER_NO 
FROM HRIS_EMPLOYEE_TRAVEL_REQUEST
WHERE STATUS='C' and travel_id=P_TRAVEL_ID;

IF(V_VOUCHER_NO IS NOT NULL)
THEN 
-- TO SET DELETED FLAG IN MASTER_TRANSACTION
select count(*) INTO TABLE_COUNT from user_tables where table_name = upper('MASTER_TRANSACTION');
IF(TABLE_COUNT>0)THEN
UPDATE MASTER_TRANSACTION  SET DELETED_FLAG='Y',DELETED_BY='HRIS',DELETED_DATE=TRUNC(SYSDATE) WHERE  VOUCHER_NO=V_VOUCHER_NO;
END IF;

-- TO SET DELETED FLAG IN FA_DOUBLE_VOUCHER
TABLE_COUNT:=0;
select count(*) INTO TABLE_COUNT from user_tables where table_name = upper('FA_DOUBLE_VOUCHER');
IF(TABLE_COUNT>0)THEN
UPDATE FA_DOUBLE_VOUCHER  SET DELETED_FLAG='Y' WHERE  VOUCHER_NO=V_VOUCHER_NO;
END IF;

-- TO SET DELETED FLAG IN FA_VOUCHER_SUB_DETAIL
TABLE_COUNT:=0;
select count(*) INTO TABLE_COUNT from user_tables where table_name = upper('FA_VOUCHER_SUB_DETAIL');
IF(TABLE_COUNT>0)THEN
UPDATE FA_VOUCHER_SUB_DETAIL  SET DELETED_FLAG='Y' WHERE  VOUCHER_NO=V_VOUCHER_NO;
END IF;

-- TO SET DELETED FLAG IN FA_SUB_LEDGER
TABLE_COUNT:=0;
select count(*) INTO TABLE_COUNT from user_tables where table_name = upper('FA_SUB_LEDGER');
IF(TABLE_COUNT>0)THEN
UPDATE FA_SUB_LEDGER  SET DELETED_FLAG='Y' WHERE  VOUCHER_NO=V_VOUCHER_NO;
END IF;

-- TO SET DELETED FLAG IN FA_GENERAL_LEDGER
TABLE_COUNT:=0;
select count(*) INTO TABLE_COUNT from user_tables where table_name = upper('FA_GENERAL_LEDGER');
IF(TABLE_COUNT>0)THEN
UPDATE FA_GENERAL_LEDGER  SET DELETED_FLAG='Y' WHERE  VOUCHER_NO=V_VOUCHER_NO;
END IF;

END IF;




end;



/
            CREATE OR REPLACE PROCEDURE HRIS_TRAVEL_REQUEST_PROC(
    P_TRAVEL_ID HRIS_EMPLOYEE_TRAVEL_REQUEST.TRAVEL_ID%TYPE,
    P_LINK_TO_SYNERGY CHAR := 'N' )
AS
  V_FROM_DATE HRIS_EMPLOYEE_TRAVEL_REQUEST.FROM_DATE%TYPE;
  V_TO_DATE HRIS_EMPLOYEE_TRAVEL_REQUEST.TO_DATE%TYPE;
  V_EMPLOYEE_ID HRIS_EMPLOYEE_TRAVEL_REQUEST.EMPLOYEE_ID%TYPE;
  V_STATUS HRIS_EMPLOYEE_TRAVEL_REQUEST.STATUS%TYPE;
  V_ADVANCE_AMOUNT HRIS_EMPLOYEE_TRAVEL_REQUEST.REQUESTED_AMOUNT%TYPE;
  V_EXPENSE_AMOUNT FLOAT;
  V_REQUEST_TYPE HRIS_EMPLOYEE_TRAVEL_REQUEST.REQUESTED_TYPE%TYPE;
  --
  V_LINK_TRAVEL_TO_SYNERGY HRIS_PREFERENCES.VALUE%TYPE;
  V_FORM_CODE HRIS_PREFERENCES.VALUE%TYPE;
  V_DR_ACC_CODE HRIS_PREFERENCES.VALUE%TYPE;
  V_CR_ACC_CODE HRIS_PREFERENCES.VALUE%TYPE;
  V_EXCESS_CR_ACC_CODE HRIS_PREFERENCES.VALUE%TYPE;
  V_LESS_DR_ACC_CODE HRIS_PREFERENCES.VALUE%TYPE;
  V_EQUAL_DR_ACC_CODE HRIS_PREFERENCES.VALUE%TYPE;
  --
  V_COMPANY_CODE VARCHAR2(255 BYTE);
  V_BRANCH_CODE  VARCHAR2(255 BYTE);
  V_CREATED_BY   VARCHAR2(255 BYTE):='ADMIN';
  V_VOUCHER_NO   VARCHAR2(255 BYTE);
BEGIN
  BEGIN
    SELECT TR.FROM_DATE ,
      TR.TO_DATE,
      TR.EMPLOYEE_ID,
      TR.STATUS,
      CASE WHEN TR.REQUESTED_AMOUNT IS NULL
      THEN 0
      ELSE TR.REQUESTED_AMOUNT
      END,
      TR.REQUESTED_TYPE,
      C.COMPANY_CODE,
      CASE WHEN E.EMPOWER_BRANCH_CODE IS NULL OR E.EMPOWER_BRANCH_CODE='-1'
      THEN C.COMPANY_CODE||'.01'
      ELSE E.EMPOWER_BRANCH_CODE
      END,
      C.LINK_TRAVEL_TO_SYNERGY,
      C.FORM_CODE,
      C.DR_ACC_CODE,
      C.CR_ACC_CODE,
      C.EXCESS_CR_ACC_CODE,
      C.LESS_DR_ACC_CODE,
      C.equal_cr_acc_code,
      E.FULL_NAME
    INTO V_FROM_DATE,
      V_TO_DATE,
      V_EMPLOYEE_ID,
      V_STATUS,
      V_ADVANCE_AMOUNT,
      V_REQUEST_TYPE,
      V_COMPANY_CODE,
      V_BRANCH_CODE,
      V_LINK_TRAVEL_TO_SYNERGY,
      V_FORM_CODE,
      V_DR_ACC_CODE,
      V_CR_ACC_CODE,
      V_EXCESS_CR_ACC_CODE,
      V_LESS_DR_ACC_CODE,
      v_equal_dr_acc_code,
      V_CREATED_BY
    FROM HRIS_EMPLOYEE_TRAVEL_REQUEST TR
    JOIN HRIS_EMPLOYEES E
    ON (TR.EMPLOYEE_ID = E.EMPLOYEE_ID )
    JOIN HRIS_COMPANY C
    ON (E.COMPANY_ID= C.COMPANY_ID)
    WHERE TRAVEL_ID =P_TRAVEL_ID;
  EXCEPTION
  WHEN NO_DATA_FOUND THEN
    DBMS_OUTPUT.PUT('NO DATA FOUND FOR ID =>'|| P_TRAVEL_ID);
    RETURN;
  END;
  --
  IF V_STATUS IN ('AP','C') AND V_FROM_DATE <TRUNC(SYSDATE) THEN
    HRIS_REATTENDANCE(V_FROM_DATE,V_EMPLOYEE_ID,V_TO_DATE);
  END IF;
  --
  IF(LOWER(V_REQUEST_TYPE) ='ep') THEN
    SELECT SUM(TOTAL_AMOUNT)
    INTO V_EXPENSE_AMOUNT
    FROM HRIS_EMP_TRAVEL_EXPENSE_DTL
    WHERE TRAVEL_ID = P_TRAVEL_ID;
  END IF;
  --
  IF V_LINK_TRAVEL_TO_SYNERGY = 'Y' AND P_LINK_TO_SYNERGY = 'Y' THEN
    SELECT FN_NEW_VOUCHER_NO(V_COMPANY_CODE,V_FORM_CODE,TRUNC(SYSDATE),'FA_DOUBLE_VOUCHER')
    INTO V_VOUCHER_NO
    FROM DUAL;
    IF LOWER(V_REQUEST_TYPE) = 'ad' THEN
      HRIS_TRAVEL_ADVANCE(V_COMPANY_CODE,V_FORM_CODE,TRUNC(SYSDATE),V_BRANCH_CODE,V_CREATED_BY,TRUNC(SYSDATE),V_DR_ACC_CODE,V_CR_ACC_CODE,'TRAVEL ADVANCE',V_ADVANCE_AMOUNT,'E'||V_EMPLOYEE_ID,V_VOUCHER_NO);
    END IF;
    IF (LOWER(V_REQUEST_TYPE) ='ep') AND (V_ADVANCE_AMOUNT = V_EXPENSE_AMOUNT) THEN
      HRIS_TRAVEL_SETTLEMENT_EQUAL(V_COMPANY_CODE,V_FORM_CODE,TRUNC(SYSDATE),V_BRANCH_CODE,V_CREATED_BY,TRUNC(SYSDATE),v_equal_dr_acc_code,V_DR_ACC_CODE,'TRAVEL ADVANCE',V_EXPENSE_AMOUNT,'E'||V_EMPLOYEE_ID,V_VOUCHER_NO);
    END IF;
    IF (LOWER(V_REQUEST_TYPE) ='ep') AND (V_ADVANCE_AMOUNT < V_EXPENSE_AMOUNT) THEN
      HRIS_TRAVEL_SETTLEMENT_EXCESS(V_COMPANY_CODE,V_FORM_CODE,TRUNC(SYSDATE),V_BRANCH_CODE,V_CREATED_BY,TRUNC(SYSDATE),v_equal_dr_acc_code,V_DR_ACC_CODE,V_EXCESS_CR_ACC_CODE,V_ADVANCE_AMOUNT,V_EXPENSE_AMOUNT-V_ADVANCE_AMOUNT,'TRAVEL ADVANCE','E'||V_EMPLOYEE_ID,V_VOUCHER_NO);
    END IF;
    IF (LOWER(V_REQUEST_TYPE) ='ep') AND (V_ADVANCE_AMOUNT > V_EXPENSE_AMOUNT) THEN
      HRIS_TRAVEL_SETTLEMENT_LESS(V_COMPANY_CODE,V_FORM_CODE,TRUNC(SYSDATE),V_BRANCH_CODE,V_CREATED_BY,TRUNC(SYSDATE),v_equal_dr_acc_code,V_LESS_DR_ACC_CODE,V_DR_ACC_CODE,V_ADVANCE_AMOUNT,V_ADVANCE_AMOUNT-V_EXPENSE_AMOUNT,'TRAVEL ADVANCE','E'||V_EMPLOYEE_ID,V_VOUCHER_NO);
    END IF;
    UPDATE HRIS_EMPLOYEE_TRAVEL_REQUEST
    SET VOUCHER_NO  = V_VOUCHER_NO
    WHERE TRAVEL_ID = P_TRAVEL_ID;
  END IF;
  --
END;
/
            CREATE OR REPLACE PROCEDURE HRIS_TRAVEL_SETTLEMENT_EQUAL(
    COMPANY_CODE      VARCHAR2,
    FORM_CODE         VARCHAR2,
    TRANSACTION_DATE  DATE,
    BRANCH_CODE       VARCHAR2,
    CREATED_BY        VARCHAR2,
    CREATED_DATE      DATE,
    DR_ACC_CODE       VARCHAR2,
    CR_ACC_CODE       VARCHAR2,
    PARTICULARS       VARCHAR2,
    SETTLEMENT_AMOUNT NUMBER,
    SUB_CODE          VARCHAR2,
    VOUCHER_NO        VARCHAR2)
IS
  SESSION_ROWID NUMBER           :=-1;
  CURRENCY_CODE VARCHAR2(3 BYTE) :='NRS';
  EXCHANGE_RATE NUMBER           :=1;
  SERIAL_NO_1   NUMBER(5)        :=1;
  SERIAL_NO_2   NUMBER(5)        :=2;
BEGIN
  SAVEPOINT START_OF_FUNCTION;
  SELECT MYSEQUENCE.NEXTVAL INTO SESSION_ROWID FROM DUAL;
  INSERT
  INTO MASTER_TRANSACTION
    (
      VOUCHER_NO,
      VOUCHER_DATE,
      VOUCHER_AMOUNT,
      FORM_CODE,
      COMPANY_CODE,
      BRANCH_CODE,
      CREATED_BY,
      CREATED_DATE,
      DELETED_FLAG,
      CURRENCY_CODE,
      EXCHANGE_RATE,
      SESSION_ROWID
    )
    VALUES
    (
      VOUCHER_NO,
      TRANSACTION_DATE,
      SETTLEMENT_AMOUNT,
      FORM_CODE,
      COMPANY_CODE,
      BRANCH_CODE,
      CREATED_BY,
      CREATED_DATE,
      'N',
      CURRENCY_CODE,
      EXCHANGE_RATE,
      SESSION_ROWID
    );
  INSERT
  INTO FA_DOUBLE_VOUCHER
    (
      VOUCHER_NO,
      VOUCHER_DATE,
      SERIAL_NO,
      ACC_CODE,
      PARTICULARS,
      TRANSACTION_TYPE,
      AMOUNT,
      FORM_CODE,
      COMPANY_CODE,
      BRANCH_CODE,
      CREATED_BY,
      CREATED_DATE,
      DELETED_FLAG,
      CURRENCY_CODE,
      EXCHANGE_RATE,
      SESSION_ROWID
    )
    VALUES
    (
      VOUCHER_NO,
      TRANSACTION_DATE,
      SERIAL_NO_1,
      DR_ACC_CODE,
      PARTICULARS,
      'DR',
      SETTLEMENT_AMOUNT ,
      FORM_CODE,
      COMPANY_CODE,
      BRANCH_CODE,
      CREATED_BY,
      CREATED_DATE,
      'N',
      CURRENCY_CODE,
      EXCHANGE_RATE,
      SESSION_ROWID
    );
  INSERT
  INTO FA_DOUBLE_VOUCHER
    (
      VOUCHER_NO,
      VOUCHER_DATE,
      SERIAL_NO,
      ACC_CODE,
      PARTICULARS,
      TRANSACTION_TYPE,
      AMOUNT,
      FORM_CODE,
      COMPANY_CODE,
      BRANCH_CODE,
      CREATED_BY,
      CREATED_DATE,
      DELETED_FLAG,
      CURRENCY_CODE,
      EXCHANGE_RATE,
      SESSION_ROWID
    )
    VALUES
    (
      VOUCHER_NO,
      TRANSACTION_DATE,
      SERIAL_NO_2 ,
      CR_ACC_CODE,
      PARTICULARS,
      'CR',
      SETTLEMENT_AMOUNT,
      FORM_CODE,
      COMPANY_CODE,
      BRANCH_CODE,
      CREATED_BY,
      CREATED_DATE,
      'N',
      CURRENCY_CODE,
      EXCHANGE_RATE,
      SESSION_ROWID
    );
  INSERT
  INTO FA_VOUCHER_SUB_DETAIL
    (
      VOUCHER_NO,
      SERIAL_NO,
      ACC_CODE,
      TRANSACTION_TYPE,
      SUB_CODE,
      PARTICULARS,
      DR_AMOUNT,
      CR_AMOUNT,
      FORM_CODE,
      COMPANY_CODE,
      BRANCH_CODE,
      CREATED_BY,
      CREATED_DATE,
      DELETED_FLAG,
      CURRENCY_CODE,
      EXCHANGE_RATE,
      SESSION_ROWID
    )
    VALUES
    (
      VOUCHER_NO,
      SERIAL_NO_2 ,
      CR_ACC_CODE,
      'CR',
      SUB_CODE,
      PARTICULARS,
      0,
      SETTLEMENT_AMOUNT,
      FORM_CODE,
      COMPANY_CODE,
      BRANCH_CODE,
      CREATED_BY,
      CREATED_DATE,
      'N',
      CURRENCY_CODE,
      EXCHANGE_RATE,
      SESSION_ROWID
    );
EXCEPTION
WHEN OTHERS THEN
  ROLLBACK TO START_OF_FUNCTION;
  raise_application_error(-20001,'An error was encountered - '||SQLCODE||' -ERROR- '||SQLERRM);
END;
/
            CREATE OR REPLACE PROCEDURE HRIS_TRAVEL_SETTLEMENT_EXCESS(
    COMPANY_CODE      VARCHAR2,
    FORM_CODE         VARCHAR2,
    TRANSACTION_DATE  DATE,
    BRANCH_CODE       VARCHAR2,
    CREATED_BY        VARCHAR2,
    CREATED_DATE      DATE,
    DR_ACC_CODE       VARCHAR2,
    CR_ACC_CODE1      VARCHAR2,
    CR_ACC_CODE2      VARCHAR2,
    SETTLEMENT_AMOUNT NUMBER,
    EXCESS_AMOUNT     NUMBER,
    PARTICULARS       VARCHAR2,
    SUB_CODE          VARCHAR2,
    VOUCHER_NO        VARCHAR2 )
IS
  SESSION_ROWID NUMBER           :=-1;
  CURRENCY_CODE VARCHAR2(3 BYTE) :='NRS';
  EXCHANGE_RATE NUMBER           :=1;
  SERIAL_NO_1   NUMBER(5)        :=1;
  SERIAL_NO_2   NUMBER(5)        :=2;
BEGIN
  SAVEPOINT START_OF_FUNCTION;
  SELECT MYSEQUENCE.NEXTVAL INTO SESSION_ROWID FROM DUAL;
  INSERT
  INTO MASTER_TRANSACTION
    (
      VOUCHER_NO,
      VOUCHER_DATE,
      VOUCHER_AMOUNT,
      FORM_CODE,
      COMPANY_CODE,
      BRANCH_CODE,
      CREATED_BY,
      CREATED_DATE,
      DELETED_FLAG,
      CURRENCY_CODE,
      EXCHANGE_RATE,
      SESSION_ROWID
    )
    VALUES
    (
      VOUCHER_NO,
      TRANSACTION_DATE,
      SETTLEMENT_AMOUNT + EXCESS_AMOUNT,
      FORM_CODE,
      COMPANY_CODE,
      BRANCH_CODE,
      CREATED_BY,
      CREATED_DATE,
      'N',
      CURRENCY_CODE,
      EXCHANGE_RATE,
      SESSION_ROWID
    );
  INSERT
  INTO FA_DOUBLE_VOUCHER
    (
      VOUCHER_NO,
      VOUCHER_DATE,
      SERIAL_NO,
      ACC_CODE,
      PARTICULARS,
      TRANSACTION_TYPE,
      AMOUNT,
      FORM_CODE,
      COMPANY_CODE,
      BRANCH_CODE,
      CREATED_BY,
      CREATED_DATE,
      DELETED_FLAG,
      CURRENCY_CODE,
      EXCHANGE_RATE,
      SESSION_ROWID
    )
    VALUES
    (
      VOUCHER_NO,
      TRANSACTION_DATE,
      SERIAL_NO_1,
      DR_ACC_CODE,
      PARTICULARS,
      'DR',
      SETTLEMENT_AMOUNT + EXCESS_AMOUNT ,
      FORM_CODE,
      COMPANY_CODE,
      BRANCH_CODE,
      CREATED_BY,
      CREATED_DATE,
      'N',
      CURRENCY_CODE,
      EXCHANGE_RATE,
      SESSION_ROWID
    );
  INSERT
  INTO FA_DOUBLE_VOUCHER
    (
      VOUCHER_NO,
      VOUCHER_DATE,
      SERIAL_NO,
      ACC_CODE,
      PARTICULARS,
      TRANSACTION_TYPE,
      AMOUNT,
      FORM_CODE,
      COMPANY_CODE,
      BRANCH_CODE,
      CREATED_BY,
      CREATED_DATE,
      DELETED_FLAG,
      CURRENCY_CODE,
      EXCHANGE_RATE,
      SESSION_ROWID
    )
    VALUES
    (
      VOUCHER_NO,
      TRANSACTION_DATE,
      SERIAL_NO_2 ,
      CR_ACC_CODE1,
      PARTICULARS,
      'CR',
      SETTLEMENT_AMOUNT,
      FORM_CODE,
      COMPANY_CODE,
      BRANCH_CODE,
      CREATED_BY,
      CREATED_DATE,
      'N',
      CURRENCY_CODE,
      EXCHANGE_RATE,
      SESSION_ROWID
    );
  INSERT
  INTO FA_VOUCHER_SUB_DETAIL
    (
      VOUCHER_NO,
      SERIAL_NO,
      ACC_CODE,
      TRANSACTION_TYPE,
      SUB_CODE,
      PARTICULARS,
      DR_AMOUNT,
      CR_AMOUNT,
      FORM_CODE,
      COMPANY_CODE,
      BRANCH_CODE,
      CREATED_BY,
      CREATED_DATE,
      DELETED_FLAG,
      CURRENCY_CODE,
      EXCHANGE_RATE,
      SESSION_ROWID
    )
    VALUES
    (
      VOUCHER_NO,
      SERIAL_NO_2 ,
      CR_ACC_CODE1,
      'CR',
      SUB_CODE,
      PARTICULARS,
      0,
      SETTLEMENT_AMOUNT,
      FORM_CODE,
      COMPANY_CODE,
      BRANCH_CODE,
      CREATED_BY,
      CREATED_DATE,
      'N',
      CURRENCY_CODE,
      EXCHANGE_RATE,
      SESSION_ROWID
    );
  INSERT
  INTO FA_DOUBLE_VOUCHER
    (
      VOUCHER_NO,
      VOUCHER_DATE,
      SERIAL_NO,
      ACC_CODE,
      PARTICULARS,
      TRANSACTION_TYPE,
      AMOUNT,
      FORM_CODE,
      COMPANY_CODE,
      BRANCH_CODE,
      CREATED_BY,
      CREATED_DATE,
      DELETED_FLAG,
      CURRENCY_CODE,
      EXCHANGE_RATE,
      SESSION_ROWID
    )
    VALUES
    (
      VOUCHER_NO,
      TRANSACTION_DATE,
      3 ,
      CR_ACC_CODE2,
      PARTICULARS,
      'CR',
      EXCESS_AMOUNT,
      FORM_CODE,
      COMPANY_CODE,
      BRANCH_CODE,
      CREATED_BY,
      CREATED_DATE,
      'N',
      CURRENCY_CODE,
      EXCHANGE_RATE,
      SESSION_ROWID
    );
EXCEPTION
WHEN OTHERS THEN
  ROLLBACK TO START_OF_FUNCTION;
  raise_application_error(-20001,'An error was encountered - '||SQLCODE||' -ERROR- '||SQLERRM);
END;/
            CREATE OR REPLACE PROCEDURE HRIS_TRAVEL_SETTLEMENT_LESS(
    COMPANY_CODE      VARCHAR2,
    FORM_CODE         VARCHAR2,
    TRANSACTION_DATE  DATE,
    BRANCH_CODE       VARCHAR2,
    CREATED_BY        VARCHAR2,
    CREATED_DATE      DATE,
    DR_ACC_CODE1      VARCHAR2,
    DR_ACC_CODE2      VARCHAR2,
    CR_ACC_CODE       VARCHAR2,
    SETTLEMENT_AMOUNT NUMBER,
    LESS_AMOUNT       NUMBER,
    PARTICULARS       VARCHAR2,
    SUB_CODE          VARCHAR2,
    VOUCHER_NO        VARCHAR2 )
IS
  SESSION_ROWID   NUMBER           :=-1;
  CURRENCY_CODE   VARCHAR2(3 BYTE) :='NRS';
  EXCHANGE_RATE   NUMBER           :=1;
  SERIAL_NO_1     NUMBER(5)        :=1;
  SERIAL_NO_2     NUMBER(5)        :=2;
  nSubLedgerCount NUMBER;
  iReqNo          NUMBER;
  dAdvPFrom       VARCHAR2(15);
  dAdvPTo         VARCHAR2(15);
BEGIN
  SAVEPOINT START_OF_FUNCTION;
  SELECT MYSEQUENCE.NEXTVAL INTO SESSION_ROWID FROM DUAL;
  INSERT
  INTO MASTER_TRANSACTION
    (
      VOUCHER_NO,
      VOUCHER_DATE,
      VOUCHER_AMOUNT,
      FORM_CODE,
      COMPANY_CODE,
      BRANCH_CODE,
      CREATED_BY,
      CREATED_DATE,
      DELETED_FLAG,
      CURRENCY_CODE,
      EXCHANGE_RATE,
      SESSION_ROWID
    )
    VALUES
    (
      VOUCHER_NO,
      TRANSACTION_DATE,
      SETTLEMENT_AMOUNT ,
      FORM_CODE,
      COMPANY_CODE,
      BRANCH_CODE,
      CREATED_BY,
      CREATED_DATE,
      'N',
      CURRENCY_CODE,
      EXCHANGE_RATE,
      SESSION_ROWID
    );
  INSERT
  INTO FA_DOUBLE_VOUCHER
    (
      VOUCHER_NO,
      VOUCHER_DATE,
      SERIAL_NO,
      ACC_CODE,
      PARTICULARS,
      TRANSACTION_TYPE,
      AMOUNT,
      FORM_CODE,
      COMPANY_CODE,
      BRANCH_CODE,
      CREATED_BY,
      CREATED_DATE,
      DELETED_FLAG,
      CURRENCY_CODE,
      EXCHANGE_RATE,
      SESSION_ROWID
    )
    VALUES
    (
      VOUCHER_NO,
      TRANSACTION_DATE,
      SERIAL_NO_1,
      DR_ACC_CODE1,
      PARTICULARS,
      'DR',
      SETTLEMENT_AMOUNT - LESS_AMOUNT ,
      FORM_CODE,
      COMPANY_CODE,
      BRANCH_CODE,
      CREATED_BY,
      CREATED_DATE,
      'N',
      CURRENCY_CODE,
      EXCHANGE_RATE,
      SESSION_ROWID
    );
  INSERT
  INTO FA_DOUBLE_VOUCHER
    (
      VOUCHER_NO,
      VOUCHER_DATE,
      SERIAL_NO,
      ACC_CODE,
      PARTICULARS,
      TRANSACTION_TYPE,
      AMOUNT,
      FORM_CODE,
      COMPANY_CODE,
      BRANCH_CODE,
      CREATED_BY,
      CREATED_DATE,
      DELETED_FLAG,
      CURRENCY_CODE,
      EXCHANGE_RATE,
      SESSION_ROWID
    )
    VALUES
    (
      VOUCHER_NO,
      TRANSACTION_DATE,
      SERIAL_NO_2,
      DR_ACC_CODE2,
      PARTICULARS,
      'DR',
      LESS_AMOUNT ,
      FORM_CODE,
      COMPANY_CODE,
      BRANCH_CODE,
      CREATED_BY,
      CREATED_DATE,
      'N',
      CURRENCY_CODE,
      EXCHANGE_RATE,
      SESSION_ROWID
    );
  BEGIN
    SELECT COUNT(*)
    INTO nSubLedgerCount
    FROM FA_SUB_LEDGER_MAP
    WHERE ACC_CODE   = DR_ACC_CODE2
    AND COMPANY_CODE = COMPANY_CODE;
  EXCEPTION
  WHEN OTHERS THEN
    nSubLedgerCount := 0;
  END;
  IF nSubLedgerCount > 0 THEN
    INSERT
    INTO FA_VOUCHER_SUB_DETAIL
      (
        VOUCHER_NO,
        SERIAL_NO,
        ACC_CODE,
        TRANSACTION_TYPE,
        SUB_CODE,
        PARTICULARS,
        DR_AMOUNT,
        CR_AMOUNT,
        FORM_CODE,
        COMPANY_CODE,
        BRANCH_CODE,
        CREATED_BY,
        CREATED_DATE,
        DELETED_FLAG,
        CURRENCY_CODE,
        EXCHANGE_RATE,
        SESSION_ROWID
      )
      VALUES
      (
        VOUCHER_NO,
        SERIAL_NO_2 ,
        DR_ACC_CODE2,
        'DR',
        SUB_CODE,
        PARTICULARS,
        LESS_AMOUNT,
        0,
        FORM_CODE,
        COMPANY_CODE,
        BRANCH_CODE,
        CREATED_BY,
        CREATED_DATE,
        'N',
        CURRENCY_CODE,
        EXCHANGE_RATE,
        SESSION_ROWID
      );
    BEGIN
      SELECT MAX(NVL(TO_NUMBER(REQUEST_NO),0)) + 1
      INTO IREQNO
      FROM HR_ADVANCE_REQUEST
      WHERE COMPANY_CODE = COMPANY_CODE;
    EXCEPTION
    WHEN OTHERS THEN
      IREQNO := 1;
    END;
    BEGIN
      IF TO_NUMBER(SUBSTR(BS_DATE(TRANSACTION_DATE),8,2)) > 25 THEN
        SELECT START_DATE ,
          END_DATE
        INTO dAdvPFrom,
          dAdvPTo
        FROM HR_PERIOD_DETAIL
        WHERE TRANSACTION_DATE BETWEEN START_DATE AND END_DATE
        AND COMPANY_CODE = '01';
      ELSE
        SELECT START_DATE ,
          END_DATE
        INTO dAdvPFrom,
          dAdvPTo
        FROM HR_PERIOD_DETAIL
        WHERE TO_DATE(TRANSACTION_DATE) + 10 BETWEEN START_DATE AND END_DATE
        AND COMPANY_CODE = '01';
      END IF ;
    EXCEPTION
    WHEN OTHERS THEN
      IREQNO := 1;
    END;
    INSERT
    INTO HR_ADVANCE_REQUEST
      (
        REQUEST_NO,
        REQUEST_DATE,
        EMPLOYEE_CODE,
        ADVANCE_TYPE,
        REQUEST_AMOUNT,
        ACC_CODE,
        REPAYMENT_START_DATE,
        REPAYMENT_COUNT,
        REPAYMENT_PERIOD_FLAG,
        COMPANY_CODE,
        BRANCH_CODE,
        CREATED_BY,
        CREATED_DATE,
        DELETED_FLAG,
        PREMIUM_TYPE,
        ACC_CODE_CR
      )
      VALUES
      (
        IREQNO,
        TRANSACTION_DATE,
        REPLACE(SUB_CODE,'E','') ,
        'R019',
        LESS_AMOUNT,
        DR_ACC_CODE2,
        TRANSACTION_DATE ,
        1,
        'M',
        COMPANY_CODE,
        BRANCH_CODE,
        CREATED_BY,
        SYSDATE,
        'N',
        0 ,
        '1000'
      );
    INSERT
    INTO HR_ADVANCE_REQUEST_DETAIL
      (
        REQUEST_NO,
        SERIAL_NO,
        FROM_DATE,
        TO_DATE,
        AMOUNT,
        PAID_FLAG,
        COMPANY_CODE,
        BRANCH_CODE,
        CREATED_BY,
        CREATED_DATE,
        DELETED_FLAG
      )
      VALUES
      (
        IREQNO,
        1,
        dAdvPFrom,
        dAdvPTo,
        LESS_AMOUNT,
        'N',
        COMPANY_CODE ,
        BRANCH_CODE,
        CREATED_BY,
        SYSDATE,
        'N'
      );
  END IF;
  INSERT
  INTO FA_DOUBLE_VOUCHER
    (
      VOUCHER_NO,
      VOUCHER_DATE,
      SERIAL_NO,
      ACC_CODE,
      PARTICULARS,
      TRANSACTION_TYPE,
      AMOUNT,
      FORM_CODE,
      COMPANY_CODE,
      BRANCH_CODE,
      CREATED_BY,
      CREATED_DATE,
      DELETED_FLAG,
      CURRENCY_CODE,
      EXCHANGE_RATE,
      SESSION_ROWID
    )
    VALUES
    (
      VOUCHER_NO,
      TRANSACTION_DATE,
      3 ,
      CR_ACC_CODE,
      PARTICULARS,
      'CR',
      SETTLEMENT_AMOUNT ,
      FORM_CODE,
      COMPANY_CODE,
      BRANCH_CODE,
      CREATED_BY,
      CREATED_DATE,
      'N',
      CURRENCY_CODE,
      EXCHANGE_RATE,
      SESSION_ROWID
    );
  INSERT
  INTO FA_VOUCHER_SUB_DETAIL
    (
      VOUCHER_NO,
      SERIAL_NO,
      ACC_CODE,
      TRANSACTION_TYPE,
      SUB_CODE,
      PARTICULARS,
      DR_AMOUNT,
      CR_AMOUNT,
      FORM_CODE,
      COMPANY_CODE,
      BRANCH_CODE,
      CREATED_BY,
      CREATED_DATE,
      DELETED_FLAG,
      CURRENCY_CODE,
      EXCHANGE_RATE,
      SESSION_ROWID
    )
    VALUES
    (
      VOUCHER_NO,
      3 ,
      CR_ACC_CODE,
      'CR',
      SUB_CODE,
      PARTICULARS,
      0,
      SETTLEMENT_AMOUNT ,
      FORM_CODE,
      COMPANY_CODE,
      BRANCH_CODE,
      CREATED_BY,
      CREATED_DATE,
      'N',
      CURRENCY_CODE,
      EXCHANGE_RATE,
      SESSION_ROWID
    );
EXCEPTION
WHEN OTHERS THEN
  ROLLBACK TO START_OF_FUNCTION;
  raise_application_error(-20001,'An error was encountered - '||SQLCODE||' -ERROR- '||SQLERRM);
END;/
            