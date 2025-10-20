DELIMITER $$

CREATE TRIGGER bi_isf_guard
BEFORE INSERT ON ingenieros_solicitud_func
FOR EACH ROW
BEGIN
  DECLARE t_clean VARCHAR(50);
  DECLARE carga INT DEFAULT 0;
  SET t_clean = LOWER(TRIM(REPLACE(REPLACE(REPLACE((SELECT topico FROM solicitud_func WHERE id = NEW.id_solicitud_func), '\r',''), '\n',''), '  ',' ')));
  IF NOT EXISTS (
      SELECT 1
      FROM especialidad e
      WHERE e.ing_rut = NEW.rut_ingeniero
        AND LOWER(TRIM(REPLACE(REPLACE(REPLACE(e.campo, '\r',''), '\n',''), '  ',' '))) = t_clean
  ) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Ingeniero no posee la especialidad del topico';
  END IF;
  SELECT COUNT(*) INTO carga
  FROM (
    SELECT rut_ingeniero FROM ingenieros_solicitud_func
    UNION ALL
    SELECT rut_ingeniero FROM ingenieros_solicitud_error
  ) x
  WHERE x.rut_ingeniero = NEW.rut_ingeniero;
  IF carga >= 20 THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Ingeniero con carga maxima (>=20)';
  END IF;
END $$

CREATE TRIGGER bi_ise_guard
BEFORE INSERT ON ingenieros_solicitud_error
FOR EACH ROW
BEGIN
  DECLARE t_clean VARCHAR(50);
  DECLARE carga INT DEFAULT 0;
  SET t_clean = LOWER(TRIM(REPLACE(REPLACE(REPLACE((SELECT topico FROM solicitud_error WHERE id = NEW.id_solicitud_error), '\r',''), '\n',''), '  ',' ')));
  IF NOT EXISTS (
      SELECT 1
      FROM especialidad e
      WHERE e.ing_rut = NEW.rut_ingeniero
        AND LOWER(TRIM(REPLACE(REPLACE(REPLACE(e.campo, '\r',''), '\n',''), '  ',' '))) = t_clean
  ) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Ingeniero no posee la especialidad del topico';
  END IF;
  SELECT COUNT(*) INTO carga
  FROM (
    SELECT rut_ingeniero FROM ingenieros_solicitud_func
    UNION ALL
    SELECT rut_ingeniero FROM ingenieros_solicitud_error
  ) x
  WHERE x.rut_ingeniero = NEW.rut_ingeniero;
  IF carga >= 20 THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Ingeniero con carga maxima (>=20)';
  END IF;
END $$

CREATE TRIGGER trg_auto_assign_func
AFTER INSERT ON solicitud_func
FOR EACH ROW
BEGIN
  DECLARE t_clean VARCHAR(50);
  SET t_clean = LOWER(TRIM(REPLACE(REPLACE(REPLACE(NEW.topico, '\r',''), '\n',''), '  ',' ')));
  INSERT INTO ingenieros_solicitud_func (rut_ingeniero, id_solicitud_func)
  SELECT cand.ing_rut, NEW.id
  FROM (
    SELECT DISTINCT e.ing_rut
    FROM especialidad e
    WHERE LOWER(TRIM(REPLACE(REPLACE(REPLACE(e.campo, '\r',''), '\n',''), '  ',' '))) = t_clean
  ) cand
  LEFT JOIN (
    SELECT rut_ingeniero, COUNT(*) cnt
    FROM (
      SELECT rut_ingeniero FROM ingenieros_solicitud_func
      UNION ALL
      SELECT rut_ingeniero FROM ingenieros_solicitud_error
    ) x GROUP BY rut_ingeniero
  ) carga ON carga.rut_ingeniero = cand.ing_rut
  WHERE COALESCE(carga.cnt,0) < 20
    AND NOT EXISTS (
      SELECT 1 FROM ingenieros_solicitud_func isf
      WHERE isf.rut_ingeniero = cand.ing_rut AND isf.id_solicitud_func = NEW.id
    )
  ORDER BY COALESCE(carga.cnt,0) ASC, cand.ing_rut
  LIMIT 3;
END $$

CREATE TRIGGER trg_auto_assign_error
AFTER INSERT ON solicitud_error
FOR EACH ROW
BEGIN
  DECLARE t_clean VARCHAR(50);
  SET t_clean = LOWER(TRIM(REPLACE(REPLACE(REPLACE(NEW.topico, '\r',''), '\n',''), '  ',' ')));
  INSERT INTO ingenieros_solicitud_error (rut_ingeniero, id_solicitud_error)
  SELECT cand.ing_rut, NEW.id
  FROM (
    SELECT DISTINCT e.ing_rut
    FROM especialidad e
    WHERE LOWER(TRIM(REPLACE(REPLACE(REPLACE(e.campo, '\r',''), '\n',''), '  ',' '))) = t_clean
  ) cand
  LEFT JOIN (
    SELECT rut_ingeniero, COUNT(*) cnt
    FROM (
      SELECT rut_ingeniero FROM ingenieros_solicitud_func
      UNION ALL
      SELECT rut_ingeniero FROM ingenieros_solicitud_error
    ) x GROUP BY rut_ingeniero
  ) carga ON carga.rut_ingeniero = cand.ing_rut
  WHERE COALESCE(carga.cnt,0) < 20
    AND NOT EXISTS (
      SELECT 1 FROM ingenieros_solicitud_error ise
      WHERE ise.rut_ingeniero = cand.ing_rut AND ise.id_solicitud_error = NEW.id
    )
  ORDER BY COALESCE(carga.cnt,0) ASC, cand.ing_rut
  LIMIT 3;
END $$

DELIMITER ;
