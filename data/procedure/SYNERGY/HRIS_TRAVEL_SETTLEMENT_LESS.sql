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
END;