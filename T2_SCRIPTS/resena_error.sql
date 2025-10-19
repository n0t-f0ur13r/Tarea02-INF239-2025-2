CREATE TABLE resena_error(
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_solicitud_error INT UNSIGNED NOT NULL,
    fecha DATE NOT NULL,
    mensaje VARCHAR(400),
    PRIMARY KEY (id),
    CONSTRAINT fk_re_err
      FOREIGN KEY (id_solicitud_error) REFERENCES solicitud_error(id)
      ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;