INSERT INTO HRIS_MENUS
(MENU_ID,
MENU_NAME,
PARENT_MENU,
ROUTE,
STATUS,
CREATED_DT,
ICON_CLASS,
ACTION,
MENU_INDEX,
IS_VISIBLE)
VALUES
((SELECT MAX(MENU_ID)+1 FROM HRIS_MENUS),
'customer',
NULL,
'javascript::',
'E',
TRUNC(SYSDATE),
'fa fa-pencil',
'index',
(select max(menu_index)+1 from hris_menus where parent_menu is null),
'Y'
);


INSERT INTO HRIS_MENUS
(MENU_ID,
MENU_NAME,
PARENT_MENU,
ROUTE,
STATUS,
CREATED_DT,
ICON_CLASS,
ACTION,
MENU_INDEX,
IS_VISIBLE)
VALUES
((SELECT MAX(MENU_ID)+1 FROM HRIS_MENUS),
'customer Setup',
(select menu_id from hris_menus where menu_name='customer'),
'customer-setup',
'E',
TRUNC(SYSDATE),
'fa fa-pencil',
'index',
(select nvl(max(menu_index),0)+1 from hris_menus where parent_menu=342),
'Y'
);

INSERT INTO HRIS_MENUS
(MENU_ID,
MENU_NAME,
PARENT_MENU,
ROUTE,
STATUS,
CREATED_DT,
ICON_CLASS,
ACTION,
MENU_INDEX,
IS_VISIBLE)
VALUES
((SELECT MAX(MENU_ID)+1 FROM HRIS_MENUS),
'add Customer',
(select menu_id from hris_menus where lower(menu_name) like lower ('customer setup%')),
'customer-setup',
'E',
TRUNC(SYSDATE),
'fa fa-pencil',
'add',
1,
'N'
);

INSERT INTO HRIS_MENUS
(MENU_ID,
MENU_NAME,
PARENT_MENU,
ROUTE,
STATUS,
CREATED_DT,
ICON_CLASS,
ACTION,
MENU_INDEX,
IS_VISIBLE)
VALUES
((SELECT MAX(MENU_ID)+1 FROM HRIS_MENUS),
'edit Customer',
(select menu_id from hris_menus where lower(menu_name) like lower ('customer setup%')),
'customer-setup',
'E',
TRUNC(SYSDATE),
'fa fa-pencil',
'edit',
1,
'N'
);


INSERT INTO HRIS_MENUS
(MENU_ID,
MENU_NAME,
PARENT_MENU,
ROUTE,
STATUS,
CREATED_DT,
ICON_CLASS,
ACTION,
MENU_INDEX,
IS_VISIBLE)
VALUES
((SELECT MAX(MENU_ID)+1 FROM HRIS_MENUS),
'Customer Location',
(select menu_id from hris_menus where lower(menu_name) like lower ('customer setup%')),
'customer-location',
'E',
TRUNC(SYSDATE),
'fa fa-pencil',
'view',
1,
'N'
);

INSERT INTO HRIS_MENUS
(MENU_ID,
MENU_NAME,
PARENT_MENU,
ROUTE,
STATUS,
CREATED_DT,
ICON_CLASS,
ACTION,
MENU_INDEX,
IS_VISIBLE)
VALUES
((SELECT MAX(MENU_ID)+1 FROM HRIS_MENUS),
'add Customer Location',
(select menu_id from hris_menus where lower(menu_name) like lower ('customer setup%')),
'customer-location',
'E',
TRUNC(SYSDATE),
'fa fa-pencil',
'add',
1,
'N'
);

INSERT INTO HRIS_MENUS
(MENU_ID,
MENU_NAME,
PARENT_MENU,
ROUTE,
STATUS,
CREATED_DT,
ICON_CLASS,
ACTION,
MENU_INDEX,
IS_VISIBLE)
VALUES
((SELECT MAX(MENU_ID)+1 FROM HRIS_MENUS),
'edit Customer Location',
(select menu_id from hris_menus where lower(menu_name) like lower ('customer setup%')),
'customer-location',
'E',
TRUNC(SYSDATE),
'fa fa-pencil',
'edit',
1,
'N'
);



INSERT INTO HRIS_MENUS
(MENU_ID,
MENU_NAME,
PARENT_MENU,
ROUTE,
STATUS,
CREATED_DT,
ICON_CLASS,
ACTION,
MENU_INDEX,
IS_VISIBLE)
VALUES
((SELECT MAX(MENU_ID)+1 FROM HRIS_MENUS),
'customer Contract',
(select menu_id from hris_menus where menu_name='customer'),
'customer-contract',
'E',
TRUNC(SYSDATE),
'fa fa-pencil',
'index',
(select nvl(max(menu_index),0)+1 from hris_menus where parent_menu=342),
'Y'
);


INSERT INTO HRIS_MENUS
(MENU_ID,
MENU_NAME,
PARENT_MENU,
ROUTE,
STATUS,
CREATED_DT,
ICON_CLASS,
ACTION,
MENU_INDEX,
IS_VISIBLE)
VALUES
((SELECT MAX(MENU_ID)+1 FROM HRIS_MENUS),
'add',
(select menu_id from hris_menus where lower(menu_name) like lower('customer contract%')),
'customer-contract',
'E',
TRUNC(SYSDATE),
'fa fa-pencil',
'add',
1,
'N'
);

INSERT INTO HRIS_MENUS
(MENU_ID,
MENU_NAME,
PARENT_MENU,
ROUTE,
STATUS,
CREATED_DT,
ICON_CLASS,
ACTION,
MENU_INDEX,
IS_VISIBLE)
VALUES
((SELECT MAX(MENU_ID)+1 FROM HRIS_MENUS),
'edit',
(select menu_id from hris_menus where lower(menu_name) like lower('customer contract%')),
'customer-contract',
'E',
TRUNC(SYSDATE),
'fa fa-pencil',
'edit',
1,
'N'
);



INSERT INTO HRIS_MENUS
(MENU_ID,
MENU_NAME,
PARENT_MENU,
ROUTE,
STATUS,
CREATED_DT,
ICON_CLASS,
ACTION,
MENU_INDEX,
IS_VISIBLE)
VALUES
((SELECT MAX(MENU_ID)+1 FROM HRIS_MENUS),
'Contract Details',
(select menu_id from hris_menus where lower(menu_name) like lower('customer contract%')),
'customer-contract-details',
'E',
TRUNC(SYSDATE),
'fa fa-pencil',
'view',
1,
'N'
);


INSERT INTO HRIS_MENUS
(MENU_ID,
MENU_NAME,
PARENT_MENU,
ROUTE,
STATUS,
CREATED_DT,
ICON_CLASS,
ACTION,
MENU_INDEX,
IS_VISIBLE)
VALUES
((SELECT MAX(MENU_ID)+1 FROM HRIS_MENUS),
'Contract Employee Assign',
(select menu_id from hris_menus where lower(menu_name) like lower('customer%') and parent_menu is null),
'contract-employees',
'E',
TRUNC(SYSDATE),
'fa fa-pencil',
'index',
1,
'Y'
);


INSERT INTO HRIS_MENUS
(MENU_ID,
MENU_NAME,
PARENT_MENU,
ROUTE,
STATUS,
CREATED_DT,
ICON_CLASS,
ACTION,
MENU_INDEX,
IS_VISIBLE)
VALUES
((SELECT MAX(MENU_ID)+1 FROM HRIS_MENUS),
'Contract Attendance',
(select menu_id from hris_menus where lower(menu_name) like lower('customer%') and parent_menu is null),
'contract-attendance',
'E',
TRUNC(SYSDATE),
'fa fa-pencil',
'index',
(select nvl(max(menu_index),0)+1 from hris_menus where parent_menu=(select menu_id from hris_menus where lower(menu_name) like lower('customer%') and parent_menu is null)),
'Y'
);


INSERT INTO HRIS_MENUS
(MENU_ID,
MENU_NAME,
PARENT_MENU,
ROUTE,
STATUS,
CREATED_DT,
ICON_CLASS,
ACTION,
MENU_INDEX,
IS_VISIBLE)
VALUES
((SELECT MAX(MENU_ID)+1 FROM HRIS_MENUS),
'Duty Type',
(select menu_id from hris_menus where lower(menu_name) like lower('customer%') and parent_menu is null),
'duty-type',
'E',
TRUNC(SYSDATE),
'fa fa-pencil',
'index',
1,
'Y'
);

INSERT INTO HRIS_MENUS
(MENU_ID,
MENU_NAME,
PARENT_MENU,
ROUTE,
STATUS,
CREATED_DT,
ICON_CLASS,
ACTION,
MENU_INDEX,
IS_VISIBLE)
VALUES
((SELECT MAX(MENU_ID)+1 FROM HRIS_MENUS),
'add',
(select menu_id from hris_menus where lower(menu_name) like lower('Duty Type%')),
'duty-type',
'E',
TRUNC(SYSDATE),
'fa fa-pencil',
'add',
1,
'N'
);

INSERT INTO HRIS_MENUS
(MENU_ID,
MENU_NAME,
PARENT_MENU,
ROUTE,
STATUS,
CREATED_DT,
ICON_CLASS,
ACTION,
MENU_INDEX,
IS_VISIBLE)
VALUES
((SELECT MAX(MENU_ID)+1 FROM HRIS_MENUS),
'edit',
(select menu_id from hris_menus where lower(menu_name) like lower('Duty Type%')),
'duty-type',
'E',
TRUNC(SYSDATE),
'fa fa-pencil',
'edit',
1,
'N'
);
















//till herer

INSERT INTO HRIS_MENUS
(MENU_ID,
MENU_NAME,
PARENT_MENU,
ROUTE,
STATUS,
CREATED_DT,
ICON_CLASS,
ACTION,
MENU_INDEX,
IS_VISIBLE)
VALUES
((SELECT MAX(MENU_ID)+1 FROM HRIS_MENUS),
'customer Contract',
(select menu_id from hris_menus where menu_name='customer'),
'customer-contract',
'E',
TRUNC(SYSDATE),
'fa fa-pencil',
'index',
(select nvl(max(menu_index),0)+1 from hris_menus where parent_menu=342),
'Y'
);


INSERT INTO HRIS_MENUS
(MENU_ID,
MENU_NAME,
PARENT_MENU,
ROUTE,
STATUS,
CREATED_DT,
ICON_CLASS,
ACTION,
MENU_INDEX,
IS_VISIBLE)
VALUES
((SELECT MAX(MENU_ID)+1 FROM HRIS_MENUS),
'Contract Attendance',
(select menu_id from hris_menus where lower(menu_name) like lower('customer%') and parent_menu is null),
'contract-attendance',
'E',
TRUNC(SYSDATE),
'fa fa-pencil',
'index',
(select nvl(max(menu_index),0)+1 from hris_menus where parent_menu=(select menu_id from hris_menus where lower(menu_name) like lower('customer%') and parent_menu is null)),
'Y'
);


INSERT INTO HRIS_MENUS
(MENU_ID,
MENU_NAME,
PARENT_MENU,
ROUTE,
STATUS,
CREATED_DT,
ICON_CLASS,
ACTION,
MENU_INDEX,
IS_VISIBLE)
VALUES
((SELECT MAX(MENU_ID)+1 FROM HRIS_MENUS),
'Contract Employees Assign',
(select menu_id from hris_menus where lower(menu_name) like lower('customer%') and parent_menu is null),
'contract-employees',
'E',
TRUNC(SYSDATE),
'fa fa-pencil',
'index',
(select nvl(max(menu_index),0)+1 from hris_menus where parent_menu=(select menu_id from hris_menus where lower(menu_name) like lower('customer%') and parent_menu is null)),
'Y'
);


INSERT
INTO HRIS_MENUS
  (
    MENU_ID,
    MENU_NAME,
    PARENT_MENU,
    ROUTE,
    STATUS,
    CREATED_DT,
    ICON_CLASS,
    ACTION,
    MENU_INDEX,
    IS_VISIBLE
  )
  VALUES
  (
    (SELECT MAX(MENU_ID)+1 FROM HRIS_MENUS
    )
    ,
    'Customer Location',
    (SELECT menu_id
    FROM hris_menus
    WHERE lower(menu_name) LIKE lower ('customer setup%')
    ),
    'customer-location',
    'E',
    TRUNC(SYSDATE),
    'fa fa-pencil',
    'view',
    1,
    'N'
  );
  
  
  INSERT
INTO HRIS_MENUS
  (
    MENU_ID,
    MENU_NAME,
    PARENT_MENU,
    ROUTE,
    STATUS,
    CREATED_DT,
    ICON_CLASS,
    ACTION,
    MENU_INDEX,
    IS_VISIBLE
  )
  VALUES
  (
    (SELECT MAX(MENU_ID)+1 FROM HRIS_MENUS
    )
    ,
    'Contract Details',
    (SELECT menu_id
    FROM hris_menus
    WHERE lower(menu_name) LIKE lower('customer contract%')
    ),
    'customer-contract-details',
    'E',
    TRUNC(SYSDATE),
    'fa fa-pencil',
    'view',
    1,
    'N'
  );
  
  UPDATE HRIS_MENUS SET ROUTE='javascript::',ACTION='javascript::' WHERE LOWER(MENU_NAME) LIKE LOWER('CONTRACT ATT%ENDANCE');

  
  
  INSERT INTO HRIS_MENUS
(MENU_ID,
MENU_NAME,
PARENT_MENU,
ROUTE,
STATUS,
CREATED_DT,
ICON_CLASS,
ACTION,
MENU_INDEX,
IS_VISIBLE)
VALUES
((SELECT MAX(MENU_ID)+1 FROM HRIS_MENUS),
'Attendance',
(SELECT MENU_ID FROM HRIS_MENUS WHERE LOWER(MENU_NAME) LIKE LOWER('CONTRACT ATTENDANCE%')),
'contract-attendance',
'E',
TRUNC(SYSDATE),
'fa fa-pencil',
'index',
1,
'Y'
);

INSERT INTO HRIS_MENUS
(MENU_ID,
MENU_NAME,
PARENT_MENU,
ROUTE,
STATUS,
CREATED_DT,
ICON_CLASS,
ACTION,
MENU_INDEX,
IS_VISIBLE)
VALUES
((SELECT MAX(MENU_ID)+1 FROM HRIS_MENUS),
'Report',
(SELECT MENU_ID FROM HRIS_MENUS WHERE LOWER(MENU_NAME) LIKE LOWER('CONTRACT ATTENDANCE%')),
'contract-attendance',
'E',
TRUNC(SYSDATE),
'fa fa-pencil',
'report',
2,
'Y'
);


INSERT INTO HRIS_MENUS
(MENU_ID,
MENU_NAME,
PARENT_MENU,
ROUTE,
STATUS,
CREATED_DT,
ICON_CLASS,
ACTION,
MENU_INDEX,
IS_VISIBLE)
VALUES
((SELECT MAX(MENU_ID)+1 FROM HRIS_MENUS),
'Employee Wise Report',
(SELECT MENU_ID FROM HRIS_MENUS WHERE LOWER(MENU_NAME) LIKE LOWER('CONTRACT ATTENDANCE%')),
'contract-attendance',
'E',
TRUNC(SYSDATE),
'fa fa-pencil',
'emp-wise-report',
3,
'Y'
);


