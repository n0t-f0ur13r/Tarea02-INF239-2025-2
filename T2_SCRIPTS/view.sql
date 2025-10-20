CREATE VIEW v_solicitudes_func_sistema AS
SELECT
    sf.id,
    sf.titulo,
    sf.resumen,
    sf.pub_date,
    sf.topico,
    sf.dev_env,
    sf.estado,
    COALESCE(u.nombre, '—') AS autor
FROM solicitud_func AS sf
LEFT JOIN usuarios AS u
    ON u.rut = sf.rut_autor
ORDER BY sf.pub_date DESC, sf.id DESC;
CREATE VIEW v_solicitudes_error_sistema AS
SELECT
    se.id,
    se.titulo,
    se.descripcion,
    se.pub_date,
    se.topico,
    se.estado,
    COALESCE(u.nombre, '—') AS autor
FROM solicitud_error AS se
LEFT JOIN usuarios AS u
    ON u.rut = se.rut_autor
ORDER BY se.pub_date DESC, se.id DESC;