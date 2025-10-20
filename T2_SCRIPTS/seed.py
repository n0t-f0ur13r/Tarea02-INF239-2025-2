#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Puebla la base 'example_database' con muchos datos consistentes en:
- usuarios, ingenieros, especialidad
- solicitud_func, solicitud_error
- ingenieros_solicitud_func, ingenieros_solicitud_error
- criterio (vincula 1:1 con solicitud_func)
- resena_func, resena_error (varias por solicitud)

Requisitos:
    pip install mysql-connector-python

Credenciales:
    user: t2inf239
    pass: t2inf239password%%
"""
import random
import string
import datetime as dt
from itertools import islice, cycle
import mysql.connector

DB = {
    "host": "localhost",
    "user": "t2inf239",
    "password": "t2inf239password%%",
    "database": "example_database",
    "autocommit": False,
}

# ------------------------- Utilidades -------------------------
def rut_dv(num: int) -> str:
    """DV de RUT chileno (Módulo 11). Retorna '0-9' o 'K'."""
    s = 0
    m = 2
    while num > 0:
        s += (num % 10) * m
        num //= 10
        m = 2 if m == 7 else m + 1
    r = 11 - (s % 11)
    if r == 11: return "0"
    if r == 10: return "K"
    return str(r)

def make_rut(seed: int) -> str:
    """Genera RUT válido con formato XXXXXXXX-D."""
    # Rango realista 8 dígitos (10-30 millones aprox). Evita colisiones comunes.
    base = 10_000_000 + seed
    dv = rut_dv(base)
    return f"{base:08d}-{dv}"

FIRST_NAMES = [
    "María","Josefa","Camila","Sofía","Valentina","Isidora","Constanza","Antonia",
    "Javiera","Catalina","Ignacia","Fernanda","Paula","Alejandra","Daniela",
    "Juan","José","Pedro","Diego","Matías","Javier","Tomás","Felipe","Ignacio",
    "Rodrigo","Sebastián","Cristóbal","Benjamín","Martín","Nicolás","Álvaro"
]
LAST_NAMES = [
    "González","Muñoz","Rojas","Díaz","Pérez","Soto","Contreras","Silva","Martínez",
    "Sepúlveda","Morales","Rodríguez","López","Fuentes","Hernández","Torres","Araya",
    "Flores","Espinoza","Valenzuela","Castillo","Tapia","Vargas","Reyes","Gutiérrez"
]
DOMAINS = ["empresa.cl","servicios.cl","startup.cl","institucion.cl","colegio.cl","gmail.com","outlook.com"]

TOPICOS = ["Backend","Frontend","Mobile","QA","DevOps","Data","IoT","Seguridad","Soporte","Arquitectura"]
ESPECIALIDADES = ["Backend","Frontend","Mobile","QA","DevOps","Data","IoT","Seguridad","Soporte","Arquitectura"]
ESTADOS = ["Abierto","En Progreso","Resuelto","Cerrado"]  # solicitud_* enum
COMMON_HASH = "$2y$10$R9PVHwvPCQc2muEJliQdCuCVg2snge3t5UHbJkPDjmGksAfzDmVom"

FUNC_TITLE_SNIPPETS = [
    "Inicio de sesión con", "Integración", "Panel", "Notificaciones", "Búsqueda", "Modo offline",
    "Permisos por roles", "Historial de cambios", "Export", "Validación", "Carga de archivos",
    "Optimización de", "Soporte para", "Mejora de", "Refactor de"
]
FUNC_OBJECTS = [
    "SSO OAuth2","Webpay","reportes administrativos","push","por similitud","en app móvil",
    "roles y permisos","auditoría","CSV/XLS","RUT chileno","imágenes y PDF","rendimiento en consultas",
    "Dark Mode","accesibilidad","formularios"
]
ERROR_PREFIX = ["Error", "Fallo", "Excepción", "Timeout", "Crash", "Desbordamiento", "Bloqueo"]

def truncate(s: str, n: int) -> str:
    return s if len(s) <= n else s[: max(0, n-1)] + "…"

def random_sentence(min_words=8, max_words=16):
    words = [
        "El","sistema","presenta","un","comportamiento","intermitente","al","procesar",
        "peticiones","concurrentes","debido","a","una","condición","de","carrera","en",
        "la","capa","de","servicio","cuando","existe","alta","latencia","de","red","y",
        "los","reintentos","duplican","eventos","sobre","la","misma","cola","local"
    ]
    k = random.randint(min_words, max_words)
    return " ".join(random.choices(words, k=k)).rstrip(".") + "."

def random_date(days_back=365):
    d = dt.date.today() - dt.timedelta(days=random.randint(0, days_back))
    return d

def email_from_name(name: str) -> str:
    base = (
        name.lower()
        .replace("á","a").replace("é","e").replace("í","i").replace("ó","o").replace("ú","u")
        .replace("ñ","n")
        .replace(" ",".")
    )
    return f"{base}@{random.choice(DOMAINS)}"

def make_people(n, seed_offset=0):
    """Devuelve lista de (rut, nombre, email, hash) únicos y realistas."""
    people = []
    used_emails = set()
    for i in range(n):
        name = f"{random.choice(FIRST_NAMES)} {random.choice(LAST_NAMES)}"
        rut = make_rut(seed_offset + i + 1)
        email = email_from_name(name)
        # Evitar colisiones de email
        suffix = 1
        while email in used_emails:
            email = email.replace("@", f"{suffix}@")
            suffix += 1
        used_emails.add(email)
        people.append((rut, name, email, COMMON_HASH))
    return people

# ------------------------- Generación de datasets -------------------------
random.seed(239)

N_USUARIOS = 120
N_INGENIEROS = 35
N_FUNC = 140
N_ERR = 130
RESEÑAS_POR_FUNC = (1, 3)  # min, max
RESEÑAS_POR_ERR = (1, 2)

usuarios = make_people(N_USUARIOS, seed_offset=1_000)
ingenieros = make_people(N_INGENIEROS, seed_offset=50_000)

# Asignar especialidad para cada ingeniero
especialidad_rows = [(r, random.choice(ESPECIALIDADES)) for (r, _, __, ___) in ingenieros]

# ------------------------- Conexión -------------------------
conn = mysql.connector.connect(**DB)
cur = conn.cursor()

# ------------------------- Inserciones base (usuarios/ingenieros/especialidad) -------------------------
# usuarios(rut PK, nombre, email UNIQUE, hash)  ← según tu diccionario de datos
cur.executemany(
    "INSERT IGNORE INTO usuarios (rut, nombre, email, hash) VALUES (%s, %s, %s, %s)",
    usuarios
)

# ingenieros(rut PK, nombre, email, hash)
cur.executemany(
    "INSERT IGNORE INTO ingenieros (rut, nombre, email, hash) VALUES (%s, %s, %s, %s)",
    ingenieros
)

# especialidad(ing_rut PK -> ingenieros.rut, campo)
cur.executemany(
    "INSERT IGNORE INTO especialidad (ing_rut, campo) VALUES (%s, %s)",
    especialidad_rows
)

# ------------------------- Solicitudes de funcionalidad -------------------------
func_rows = []  # (titulo, dev_env, resumen, pub_date, topico, estado, rut_autor)
for i in range(N_FUNC):
    titulo = f"{random.choice(FUNC_TITLE_SNIPPETS)} {random.choice(FUNC_OBJECTS)}"
    dev_env = random.choice(["Web","Movil"])
    resumen = truncate(
        " ".join([
            "Se requiere", titulo.lower(), "para mejorar la experiencia y trazabilidad.",
            random_sentence(6, 10)
        ]),
        150
    )
    pub_date = random_date(360)
    topico = random.choice(TOPICOS)
    estado = random.choice(ESTADOS)
    rut_autor = random.choice(usuarios)[0] if random.random() > 0.05 else None  # a veces NULL permitido en sf
    func_rows.append((truncate(titulo, 70), dev_env, resumen, pub_date, truncate(topico, 25), estado, rut_autor))

sf_sql = """
INSERT INTO solicitud_func (titulo, dev_env, resumen, pub_date, topico, estado, rut_autor)
VALUES (%s, %s, %s, %s, %s, %s, %s)
"""
for row in func_rows:
    cur.execute(sf_sql, row)
conn.commit()

# Obtener IDs de solicitudes de funcionalidad creadas (usaremos todo el rango reciente)
cur.execute("SELECT id FROM solicitud_func ORDER BY id DESC LIMIT %s", (N_FUNC,))
func_ids = [r[0] for r in cur.fetchall()]
func_ids.sort()  # ascendente

# ------------------------- Criterio (1:1 con solicitud_func) -------------------------
criterio_rows = []
for sid in func_ids:
    nombre = random.choice([
        "Prioridad Alta","Cumple Requisitos Mínimos","Impacto en Usuario",
        "Viabilidad Técnica","Alineación con Roadmap","Costo/Beneficio"
    ])
    criterio_rows.append((sid, truncate(nombre, 100)))

cur.executemany(
    "INSERT IGNORE INTO criterio (id_solicitud, nombre) VALUES (%s, %s)",
    criterio_rows
)

# ------------------------- Solicitudes de error -------------------------
err_rows = []  # (titulo, descripcion, pub_date, topico, estado, rut_autor)
for i in range(N_ERR):
    pref = random.choice(ERROR_PREFIX)
    objeto = random.choice(["al crear usuario","en sincronización offline","en export CSV","en pasarela de pagos",
                            "al subir imágenes","en filtro por fecha","en notificaciones push","en inicio de sesión"])
    titulo = f"{pref} {objeto}"
    descripcion = truncate(
        " ".join([
            "Se observa", objeto, "con síntomas reproducibles en ambiente de pruebas.",
            random_sentence(8, 14),
            "Logs apuntan a validaciones y manejo de errores incompleto."
        ]),
        200
    )
    pub_date = random_date(360)
    topico = random.choice(TOPICOS)
    estado = random.choice(ESTADOS)
    rut_autor = random.choice(usuarios)[0]  # en se rut_autor es NOT NULL
    err_rows.append((truncate(titulo, 70), descripcion, pub_date, truncate(topico, 25), estado, rut_autor))

se_sql = """
INSERT INTO solicitud_error (titulo, descripcion, pub_date, topico, estado, rut_autor)
VALUES (%s, %s, %s, %s, %s, %s)
"""
cur.executemany(se_sql, err_rows)
conn.commit()

# Obtener IDs de solicitudes de error creadas
cur.execute("SELECT id FROM solicitud_error ORDER BY id DESC LIMIT %s", (N_ERR,))
err_ids = [r[0] for r in cur.fetchall()]
err_ids.sort()

# ------------------------- Asignación de ingenieros a solicitudes (tablas puente) -------------------------
# Cada solicitud de func/error tendrá 1-3 ingenieros asignados
ing_ruts = [r for (r, *_ ) in ingenieros]

isf_rows = []  # (rut_ingeniero, id_solicitud_func)
for sid in func_ids:
    for r in random.sample(ing_ruts, k=random.randint(1, 3)):
        isf_rows.append((r, sid))

ise_rows = []  # (rut_ingeniero, id_solicitud_error)
for sid in err_ids:
    for r in random.sample(ing_ruts, k=random.randint(1, 3)):
        ise_rows.append((r, sid))

cur.executemany(
    "INSERT IGNORE INTO ingenieros_solicitud_func (rut_ingeniero, id_solicitud_func) VALUES (%s, %s)",
    isf_rows
)
cur.executemany(
    "INSERT IGNORE INTO ingenieros_solicitud_error (rut_ingeniero, id_solicitud_error) VALUES (%s, %s)",
    ise_rows
)

# ------------------------- Reseñas (resena_func / resena_error) -------------------------
# NOTA: En tu diccionario el campo id no figura AUTO_INCREMENT, así que asignamos IDs explícitos.
# Si en tu server es AUTO_INCREMENT, puedes cambiar a INSERT sin 'id' y dejar que el motor asigne.
rf_rows = []  # (id, id_solicitud_func, fecha, mensaje)
re_rows = []  # (id, id_solicitud_error, fecha, mensaje)

next_id = 1
for sid in func_ids:
    n = random.randint(*RESEÑAS_POR_FUNC)
    for _ in range(n):
        rf_rows.append((
            next_id, sid, random_date(300),
            truncate("Comentario de seguimiento: " + random_sentence(6, 10), 400)
        ))
        next_id += 1

for sid in err_ids:
    n = random.randint(*RESEÑAS_POR_ERR)
    for _ in range(n):
        re_rows.append((
            next_id, sid, random_date(300),
            truncate("Observación técnica: " + random_sentence(6, 10), 400)
        ))
        next_id += 1

cur.executemany(
    "INSERT IGNORE INTO resena_func (id, id_solicitud_func, fecha, mensaje) VALUES (%s, %s, %s, %s)",
    rf_rows
)
cur.executemany(
    "INSERT IGNORE INTO resena_error (id, id_solicitud_error, fecha, mensaje) VALUES (%s, %s, %s, %s)",
    re_rows
)

# ------------------------- Confirmar -------------------------
conn.commit()
print("✅ Carga masiva completada.")
print(f"Usuarios insertados/ignorados: {len(usuarios)}")
print(f"Ingenieros insertados/ignorados: {len(ingenieros)}")
print(f"Especialidades insertadas/ignoradas: {len(especialidad_rows)}")
print(f"Solicitudes FUNC creadas: {len(func_ids)}")
print(f"Solicitudes ERR creadas: {len(err_ids)}")
print(f"Asociaciones isf: {len(isf_rows)}  |  ise: {len(ise_rows)}")
print(f"Reseñas func: {len(rf_rows)}  |  reseñas err: {len(re_rows)}")

# ------------------------- Limpieza -------------------------
cur.close()
conn.close()

