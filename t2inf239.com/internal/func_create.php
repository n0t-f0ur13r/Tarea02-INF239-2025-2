<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/dbh.inc.php';

require_login();

if (filter_input(INPUT_SERVER, "REQUEST_METHOD") !== "POST") {
    if (empty($_SESSION['user'])) {
        header("Location: /index.php");
    } else {
        header("Location: /main.php");
    }
    exit;
}

$titulo   = filter_input(INPUT_POST, "titulo", FILTER_SANITIZE_STRING);
$resumen  = filter_input(INPUT_POST, "resumen", FILTER_SANITIZE_STRING);
$topico   = filter_input(INPUT_POST, "topico", FILTER_SANITIZE_STRING);
$dev_env  = filter_input(INPUT_POST, "dev_env", FILTER_SANITIZE_STRING);

if (empty($titulo)) {
    flash_warning("Debe ingresar un título.");
    header("Location: /main.php");
    exit;
}
if (empty($resumen)) {
    flash_warning("Debe ingresar un resumen.");
    header("Location: /main.php");
    exit;
}
if (empty($topico)) {
    flash_warning("Debe seleccionar un tópico.");
    header("Location: /main.php");
    exit;
}
if (empty($dev_env)) {
    flash_warning("Debe seleccionar un entorno.");
    header("Location: /main.php");
    exit;
}

if (mb_strlen($titulo) > 70) {
    flash_warning("Título muy grande. Máximo 70 caracteres.");
    header("Location: /main.php");
    exit;
}
if (mb_strlen($resumen) > 150) {
    flash_warning("Resumen muy grande. Máximo 150 caracteres.");
    header("Location: /main.php");
    exit;
}
if (mb_strlen($topico) > 25) {
    flash_warning("Tópico inválido (máximo 25 caracteres).");
    header("Location: /main.php");
    exit;
}
if (mb_strlen($dev_env) > 25) {
    flash_warning("Entorno inválido (máximo 25 caracteres).");
    header("Location: /main.php");
    exit;
}

$rut      = $_SESSION['user']['rut'];
$pub_date = date('Y-m-d');
$estado   = "Abierto";

try {
    $pdo = db();
    $pdo->beginTransaction();

    $sql = "INSERT INTO solicitud_func (titulo, dev_env, resumen, pub_date, topico, estado, rut_autor)
            VALUES (:titulo, :dev_env, :resumen, :pub_date, :topico, :estado, :rut_autor)";
    $st = $pdo->prepare($sql);
    $st->bindParam(":titulo", $titulo);
    $st->bindParam(":dev_env", $dev_env);
    $st->bindParam(":resumen", $resumen);
    $st->bindParam(":pub_date", $pub_date);
    $st->bindParam(":topico", $topico);
    $st->bindParam(":estado", $estado);
    $st->bindParam(":rut_autor", $rut);
    $st->execute();

    $last_id = $pdo->lastInsertId();
    $pdo->commit();

    flash_success("Solicitud de funcionalidad #{$last_id} creada exitosamente.");
    header("Location: /main.php");
    exit;
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    if (file_exists(__DIR__ . "/../includes/pdoErrorInfoSnippet.php")) {
        require_once __DIR__ . "/../includes/pdoErrorInfoSnippet.php";
        checkPDOErrorInfo($e);
    }
    error_log('PDOException - ' . $e->getMessage(), 0);
    flash_danger("Error fatal interno, la solicitud no pudo ser creada.");
    header("Location: /main.php");
    exit;
}
