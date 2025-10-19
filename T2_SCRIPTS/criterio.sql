CREATE TABLE criterio(
    id_solicitud INT UNSIGNED NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    PRIMARY KEY (id_solicitud),
    CONSTRAINT fk_cr_func
      FOREIGN KEY (id_solicitud) REFERENCES solicitud_func(id)
      ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;