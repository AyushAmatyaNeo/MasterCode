CREATE OR REPLACE PROCEDURE HRIS_MIGRATE_ATTD_DATA
AS
BEGIN
  FOR attd_data IN
  (SELECT HR.*
  FROM
    (SELECT dc.card_no AS thumb_id,
      d_card           AS attendance_dt,
      TO_TIMESTAMP (dc.D_CARD
      ||DC.T_CARD, 'DD-MON-YY HH24:MI:SS.FF') ATTENDANCE_TIME,
      ds.IP_ADDRESS
    FROM data_card dc
    JOIN device_setup ds
    ON dc.NODE_NO =ds.DEVICE_NO
    ) HR
  JOIN
    (SELECT A.IP_ADDRESS,
      MAX(A.ATTENDANCE_TIME) AS ATTENDANCE_TIME
    FROM HRIS_ATTD_DEVICE_MASTER ADM
    LEFT JOIN HRIS_ATTENDANCE A
    ON(A.IP_ADDRESS=ADM.DEVICE_IP)
    GROUP BY A.IP_ADDRESS
    ) HRIS ON (HR.IP_ADDRESS=HRIS.IP_ADDRESS
  AND (HR.ATTENDANCE_TIME   > HRIS.ATTENDANCE_TIME
  OR HRIS.IP_ADDRESS       IS NULL) )
  )
  LOOP
    HRIS_ATTENDANCE_INSERT( attd_data.thumb_id,attd_data.attendance_dt,attd_data.IP_ADDRESS,'DATA_CARD',attd_data.attendance_time,'from data_card');
  END LOOP attd_data;
END;