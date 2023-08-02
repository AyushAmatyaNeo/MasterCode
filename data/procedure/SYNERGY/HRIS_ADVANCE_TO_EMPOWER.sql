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
END;