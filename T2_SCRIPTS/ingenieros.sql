CREATE TABLE ingenieros(
    rut CHAR(10) NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    hash VARCHAR(255) NOT NULL,
    PRIMARY KEY(rut)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;