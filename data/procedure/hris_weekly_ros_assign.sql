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

END;