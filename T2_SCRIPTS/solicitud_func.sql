CREATE TABLE solicitud_func (
    id INT UNSIGNED AUTO_INCREMENT NOT NULL,
    titulo VARCHAR(70) NOT NULL,
    dev_env VARCHAR(25) NOT NULL,
    resumen VARCHAR(150) NOT NULL,
    pub_date DATE NOT NULL,
    topico VARCHAR(25) NOT NULL,
    estado ENUM('Abierto', 'En Progreso', 'Resuelto', 'Cerrado') DEFAULT 'Abierto',
    rut_autor CHAR(10),
    PRIMARY KEY(id),
    CONSTRAINT fk_sf_autor
      FOREIGN KEY (rut_autor) REFERENCES usuarios(rut)
      ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;