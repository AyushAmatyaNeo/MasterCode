BEGIN
  FOR c IN ( SELECT table_name FROM user_tables WHERE table_name LIKE 'HRIS_%' )
  LOOP
    EXECUTE IMMEDIATE 'DROP TABLE ' || c.table_name||' cascade constraints' ;
  END LOOP;
END;


select * from HRIS_COUNTRIES;
select * from HRIS_ZONES;
select * from HRIS_DISTRICTS;
select * from HRIS_MENUS;
select * from HRIS_GENDERS;
select * from HRIS_BLOOD_GROUPS;
select * from HRIS_FILE_TYPE;
select * from HRIS_FISCAL_YEARS;
select * from HRIS_MONTH_CODE;
select * from HRIS_RELIGIONS;
select * from HRIS_VDC_MUNICIPALITIES;