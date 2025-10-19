CREATE TABLE especialidad(
    ing_rut CHAR(10) NOT NULL,
    campo VARCHAR(40) NOT NULL,
    PRIMARY KEY (ing_rut),
    CONSTRAINT fk_es_ing
      FOREIGN KEY (ing_rut) REFERENCES ingenieros(rut)
      ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;