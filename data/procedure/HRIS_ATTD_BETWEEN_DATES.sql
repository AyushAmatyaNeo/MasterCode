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
END;