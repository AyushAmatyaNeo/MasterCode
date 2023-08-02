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
END;