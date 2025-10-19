CREATE TABLE resena_func(
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_solicitud_func INT UNSIGNED NOT NULL,
    fecha DATE NOT NULL,
    mensaje VARCHAR(400),
    PRIMARY KEY (id),
    CONSTRAINT fk_rf_func
      FOREIGN KEY (id_solicitud_func) REFERENCES solicitud_func(id)
      ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
