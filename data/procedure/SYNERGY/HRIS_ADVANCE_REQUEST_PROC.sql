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
END;