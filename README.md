# 🧩 Tarea 02 — INF239 (2025-2)

## 👥 Integrantes
- **Benjamín Severino** — ROL USM: *202404532-6*  
- **Josué Leiva** — ROL USM: *202430501-8*

---

## 🧠 Descripción del Proyecto
Aplicación web desarrollada en **PHP + MySQL**, destinada a la **gestión y revisión de solicitudes** tanto de *errores* como de *funcionalidades*, integrando distintos roles de usuario (estudiantes, ingenieros, administradores) y mecanismos de autenticación y validación de entradas.

---

## 1️⃣ Requisitos Previos
Antes de comenzar, asegúrate de contar con los siguientes componentes instalados en tu servidor (se recomienda un entorno Debian/Ubuntu/Raspberry Pi OS):

| Componente | Versión Recomendada | Descripción |
|-------------|--------------------|--------------|
| **Servidor Web** | Apache 2.x | Servidor HTTP |
| **Base de Datos** | MySQL o MariaDB | Almacenamiento de datos |
| **Lenguaje** | PHP 8.2 | Lenguaje backend principal |

### 📦 Librerías PHP necesarias:
```bash
sudo apt update
sudo apt install apache2 mariadb-server php8.2 libapache2-mod-php8.2 php8.2-mysql php8.2-mbstring php8.2-curl php8.2-gd php8.2-zip
```

---

## 2️⃣ Configuración del Servidor (Apache) 🖥️

### 📁 Crear el directorio del proyecto
```bash
sudo mkdir /var/www/t2inf239.com
```

### ⚙️ Crear VirtualHost
1. Deshabilita el sitio por defecto *(opcional)*:
   ```bash
   sudo a2dissite 000-default.conf
   ```

2. Crea el archivo de configuración:
   ```bash
   sudo nano /etc/apache2/sites-available/t2inf239.com.conf
   ```

3. Pega lo siguiente:
   ```apache
   <VirtualHost *:80>
       DocumentRoot /var/www/t2inf239.com
       ErrorLog ${APACHE_LOG_DIR}/t2inf239.com_error.log
       CustomLog ${APACHE_LOG_DIR}/t2inf239.com_access.log combined
   </VirtualHost>
   ```

4. Habilita el sitio y recarga Apache:
   ```bash
   sudo a2ensite t2inf239.com.conf
   sudo systemctl reload apache2
   ```

---

## 3️⃣ Configuración de la Base de Datos (MySQL) 🗃️

### 🔐 Acceder como root:
```bash
sudo mysql -u root -p
```

### 🏗️ Crear base de datos y usuario:
```sql
CREATE DATABASE example_database;
CREATE USER 't2inf239'@'localhost' IDENTIFIED BY 't2inf239password%%';
GRANT ALL PRIVILEGES ON example_database.* TO 't2inf239'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### 📜 Crear tablas desde scripts SQL
Asegúrate de ejecutar los siguientes scripts en orden (ubicados en la carpeta `SQL/`):

```bash
mysql -u t2inf239 -p example_database
```

```sql
USE example_database;
SOURCE SQL/usuarios.sql;
SOURCE SQL/ingenieros.sql;
SOURCE SQL/solicitud_error.sql;
SOURCE SQL/solicitud_func.sql;
SOURCE SQL/resena_error.sql;
SOURCE SQL/resena_func.sql;
SOURCE SQL/ingenieros_solicitud_error.sql;
SOURCE SQL/ingenieros_solicitud_func.sql;
SOURCE SQL/especialidad.sql;
SOURCE SQL/criterio.sql;
SOURCE SQL/stored_procedures.sql;
SOURCE SQL/triggers.sql;
SOURCE SQL/view.sql;
EXIT;
```

### 🧪 Poblar datos de prueba
Ejecuta el script Python `seed.py` (ubicado en `PY/`) para generar datos iniciales:
```bash
python3 PY/seed.py
```
> 💡 *Asegúrate de tener instalado el paquete `mysql-connector-python`:*  
> `pip install mysql-connector-python`

---

## 4️⃣ Instalación de la Aplicación (PHP) 📂

Copia los archivos del proyecto en `/var/www/t2inf239.com` manteniendo la siguiente estructura:

```
t2inf239.com/
├── assets/
│   ├── footer.php
│   ├── head.php
│   ├── navbar.php
│   └── toasts.php
├── includes/
│   ├── auth.php
│   ├── dbh.inc.php
│   └── pdoErrorInfoSnippet.php
├── internal/
│   ├── auth_login.php
│   ├── auth_signup.php
│   ├── error_create.php
│   ├── error_delete.php
│   ├── error_update.php
│   ├── func_create.php
│   ├── func_delete.php
│   ├── func_update.php
│   ├── review_create.php
│   ├── review_delete.php
│   └── review_update.php
├── index.php
├── logout.php
├── main.php
├── register.php
├── resena.php
├── search.php
├── sol_error.php
└── sol_func.php
```

> ⚠️ **Importante:**  
> Verifica que las credenciales de conexión en  
> `includes/dbh.inc.php` correspondan a las creadas en el paso anterior:  
> ```php
> $dbUser = 't2inf239';
> $dbPass = 't2inf239password%%';
> $dbName = 'example_database';
> ```

---

## 5️⃣ Supuestos y Reglas de Negocio ⚖️

| Rol | Permisos |
|------|-----------|
| **Ingeniero** | Puede eliminar o actualizar solicitudes (sin modificar autor ni fecha). |
| **Usuario estándar** | Puede crear solicitudes de error o funcionalidad. |
| **Ingeniero asignado** | Puede crear, modificar y eliminar reseñas de las solicitudes a su cargo. |
| **Cualquier ingeniero** | Puede visualizar reseñas de cualquier solicitud. |

> 🧩 Un ingeniero **no puede crear nuevas solicitudes**, pero sí gestionarlas.

---

## 6️⃣ Modelo Lógico Relacional 🧱
El sistema implementa un modelo relacional compuesto por tablas como `usuarios`, `ingenieros`, `solicitud_func`, `solicitud_error`, `resena_func`, `resena_error`, entre otras, conectadas mediante claves foráneas y vistas optimizadas para reportes.

*(Puedes incluir aquí una imagen o diagrama del modelo ER si lo deseas)*

---

## 7️⃣ Herramientas Recomendadas 🔧
- **PHPMyAdmin** — para la gestión visual de la base de datos  
  📘 [Guía de instalación (PiMyLifeUp)](https://pimylifeup.com/raspberry-pi-phpmyadmin/)
- **VS Code o PhpStorm** — para edición del código fuente  
- **Postman** — para testear endpoints `internal/`

---

## 🧾 Créditos
Proyecto desarrollado como parte de la asignatura **INF239 — Programación Web** (*Segundo Semestre 2025*)  
Pontificia Universidad Técnica Federico Santa María 🏫

---

### 🚀 Autoría
> Desarrollado con 💻 y ☕ por Benjamín Severino & Josué Leiva  
> *“Simple, funcional y mantenible — el espíritu de INF239”*
