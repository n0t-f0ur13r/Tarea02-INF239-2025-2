CREATE TABLE ingenieros_solicitud_func(
    rut_ingeniero CHAR(10) NOT NULL,
    id_solicitud_func INT UNSIGNED NOT NULL,
    PRIMARY KEY (rut_ingeniero, id_solicitud_func),
    CONSTRAINT fk_isf_ing
      FOREIGN KEY (rut_ingeniero) REFERENCES ingenieros(rut)
      ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_isf_func
      FOREIGN KEY (id_solicitud_func) REFERENCES solicitud_func(id)
      ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;