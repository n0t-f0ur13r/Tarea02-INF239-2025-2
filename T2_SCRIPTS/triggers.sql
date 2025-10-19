DELIMITER $$

CREATE TRIGGER trg_auto_assign_func
AFTER INSERT ON solicitud_func
FOR EACH ROW
BEGIN
  INSERT INTO ingenieros_solicitud_func (rut_ingeniero, id_solicitud_func)
  SELECT e.ing_rut, NEW.id
  FROM especialidad e
  WHERE e.campo = NEW.topico
  ORDER BY e.ing_rut
  LIMIT 1;
END$$

CREATE TRIGGER trg_auto_assign_error
AFTER INSERT ON solicitud_error
FOR EACH ROW
BEGIN
  INSERT INTO ingenieros_solicitud_error (rut_ingeniero, id_solicitud_error)
  SELECT e.ing_rut, NEW.id
  FROM especialidad e
  WHERE e.campo = NEW.topico
  ORDER BY e.ing_rut
  LIMIT 1;
END$$

DELIMITER ;