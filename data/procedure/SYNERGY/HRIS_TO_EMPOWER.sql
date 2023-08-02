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
