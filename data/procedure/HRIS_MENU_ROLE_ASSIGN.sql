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
END;