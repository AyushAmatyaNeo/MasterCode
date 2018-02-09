CREATE OR REPLACE PROCEDURE HRIS_ADVANCE_REQUEST_PROC(
    P_ADV_REQ_ID HRIS_EMPLOYEE_ADVANCE_REQUEST.ADVANCE_REQUEST_ID%TYPE,
    P_LINK_TO_SYNERGY CHAR := 'N')
AS
  V_EMPLOYEE_ID HRIS_EMPLOYEE_ADVANCE_REQUEST.EMPLOYEE_ID%TYPE;
  V_STATUS HRIS_EMPLOYEE_ADVANCE_REQUEST.STATUS%TYPE;
  V_REQUESTED_AMOUNT HRIS_EMPLOYEE_ADVANCE_REQUEST.REQUESTED_AMOUNT%TYPE;
  V_DATE_OF_ADVANCE HRIS_EMPLOYEE_ADVANCE_REQUEST.DATE_OF_ADVANCE%TYPE;
  V_DEDUCTION_IN HRIS_EMPLOYEE_ADVANCE_REQUEST.DEDUCTION_ID%TYPE;
  --
  V_FORM_CODE HRIS_PREFERENCES.VALUE%TYPE;
  V_DR_ACC_CODE HRIS_PREFERENCES.VALUE%TYPE;
  V_CR_ACC_CODE HRIS_PREFERENCES.VALUE%TYPE; --
  V_COMPANY_CODE VARCHAR2(255 BYTE):='07';
  V_BRANCH_CODE  VARCHAR2(255 BYTE):=-'07.01';
  V_CREATED_BY   VARCHAR2(255 BYTE):='ADMIN';
  V_VOUCHER_NO   VARCHAR2(255 BYTE);
BEGIN
  BEGIN
    SELECT TR.EMPLOYEE_ID,
      TR.STATUS,
      TR.REQUESTED_AMOUNT,
      TR.DATE_OF_ADVANCE,
      TR.DEDUCTION_IN,
      C.COMPANY_CODE,
      C.COMPANY_CODE
      ||'.01',
      C.FORM_CODE,
      C.ADVANCE_DR_ACC_CODE,
      C.ADVANCE_CR_ACC_CODE
    INTO V_EMPLOYEE_ID,
      V_STATUS,
      V_REQUESTED_AMOUNT,
      V_DATE_OF_ADVANCE,
      V_DEDUCTION_IN,
      V_COMPANY_CODE,
      V_BRANCH_CODE,
      V_FORM_CODE,
      V_DR_ACC_CODE,
      V_CR_ACC_CODE
    FROM HRIS_EMPLOYEE_ADVANCE_REQUEST TR
    JOIN HRIS_EMPLOYEES E
    ON (TR.EMPLOYEE_ID = E.EMPLOYEE_ID )
    JOIN HRIS_COMPANY C
    ON (E.COMPANY_ID            = C.COMPANY_ID)
    WHERE TR.ADVANCE_REQUEST_ID =P_ADV_REQ_ID;
  EXCEPTION
  WHEN NO_DATA_FOUND THEN
    DBMS_OUTPUT.PUT('NO DATA FOUND FOR ID =>'|| P_ADV_REQ_ID);
    RETURN;
  END;
  --
  --
  IF P_LINK_TO_SYNERGY = 'Y' THEN
    SELECT FN_NEW_VOUCHER_NO(V_COMPANY_CODE,V_FORM_CODE,TRUNC(SYSDATE),'FA_DOUBLE_VOUCHER')
    INTO V_VOUCHER_NO
    FROM DUAL;
    --
    HRIS_TRAVEL_ADVANCE(V_COMPANY_CODE,V_FORM_CODE,TRUNC(SYSDATE),V_BRANCH_CODE,V_CREATED_BY,TRUNC(SYSDATE),V_DR_ACC_CODE,V_CR_ACC_CODE,'TEST',V_REQUESTED_AMOUNT,'E'||V_EMPLOYEE_ID,V_VOUCHER_NO);
    --
--     HRIS_ADVANCE_TO_EMPOWER( V_COMPANY_CODE, V_BRANCH_CODE, V_DATE_OF_ADVANCE, V_DATE_OF_ADVANCE, V_CREATED_BY, V_REQUESTED_AMOUNT, V_DEDUCTION_IN, PER_MONTH VARCHAR2, V_EMPLOYEE_ID, V_DR_ACC_CODE,V_CR_ACC_CODE)
    --
    UPDATE HRIS_EMPLOYEE_ADVANCE_REQUEST
    SET VOUCHER_NO           = V_VOUCHER_NO
    WHERE ADVANCE_REQUEST_ID = P_ADV_REQ_ID;
  END IF;
  --
END;