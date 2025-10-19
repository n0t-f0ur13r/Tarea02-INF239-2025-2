
DELIMITER //

CREATE PROCEDURE sp_solicitudes_error_sistema()
BEGIN
	SELECT se.id, se.titulo, se.descripcion, se.pub_date, se.topico, u.nombre AS autor, se.estado 
	FROM solicitud_error se 
	INNER JOIN usuarios u 
		ON se.rut_autor = u.rut
	ORDER BY se.pub_date DESC;
END //
DELIMITER ;


DELIMITER //

CREATE PROCEDURE sp_solicitudes_funcionalidad_sistema()
BEGIN
	SELECT id, titulo, resumen, pub_date, topico, dev_env, estado, usuarios.nombre AS autor
	FROM solicitud_func
	LEFT JOIN usuarios ON usuarios.rut = rut_autor
	ORDER BY pub_date DESC;
END //

DELIMITER ;
