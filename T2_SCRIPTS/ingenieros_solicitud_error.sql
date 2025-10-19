CREATE TABLE ingenieros_solicitud_error(
    rut_ingeniero CHAR(10) NOT NULL,
    id_solicitud_error INT UNSIGNED NOT NULL,
    PRIMARY KEY (rut_ingeniero, id_solicitud_error),
    CONSTRAINT fk_ise_ing
      FOREIGN KEY (rut_ingeniero) REFERENCES ingenieros(rut)
      ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_ise_err
      FOREIGN KEY (id_solicitud_error) REFERENCES solicitud_error(id)
      ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;