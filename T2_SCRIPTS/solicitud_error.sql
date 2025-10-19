CREATE TABLE solicitud_error(
    id INT UNSIGNED AUTO_INCREMENT NOT NULL,
    titulo VARCHAR(70) NOT NULL,
    descripcion VARCHAR(200) NOT NULL,
    pub_date DATE NOT NULL,
    topico VARCHAR(25) NOT NULL,
    estado ENUM('Abierto', 'En Progreso', 'Resuelto', 'Cerrado') DEFAULT 'Abierto',
    rut_autor CHAR(10) NOT NULL,
    PRIMARY KEY (id),
    CONSTRAINT fk_se_autor
      FOREIGN KEY (rut_autor) REFERENCES usuarios(rut)
      ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;