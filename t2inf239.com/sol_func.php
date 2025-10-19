<?php

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/dbh.inc.php';

require_login();

$id = empty(filter_input(INPUT_GET, "id", FILTER_SANITIZE_NUMBER_INT)) ? null : filter_input(INPUT_GET, "id", FILTER_SANITIZE_NUMBER_INT);

if (isset($id)) {
    $isView = true;

    $pdo = db();

    $query = "SELECT id, rut_autor, pub_date, titulo, resumen, topico, dev_env, estado
              FROM solicitud_func WHERE id = :id;";

    $statement = $pdo->prepare($query);
    $statement->bindParam(":id", $id, PDO::PARAM_INT);

    $statement->execute();

    $solicitud_obtenida = $statement->fetch();

    if (empty($solicitud_obtenida)) {
        flash_info("No existe solicitud de funcionalidad con tal id.");
        session_write_close();
        header("Location: /main.php");
        exit;
    }

    flash_success("Solicitud obtenida exitosamente.");
} else {
    $isView = false;
}



try {
    $pdo = db();
    $sql_topics = "
    SELECT topico FROM (
      SELECT DISTINCT TRIM(topico) AS topico
      FROM solicitud_func
      WHERE topico IS NOT NULL AND topico <> ''
      UNION
      SELECT DISTINCT TRIM(topico) AS topico
      FROM solicitud_error
      WHERE topico IS NOT NULL AND topico <> ''
    ) AS t
    ORDER BY topico
    ";

    $topicos = $pdo->query($sql_topics)->fetchAll(PDO::FETCH_COLUMN);

    $sql_envs = "
    SELECT DISTINCT TRIM(dev_env) AS dev_env
    FROM solicitud_func
    WHERE dev_env IS NOT NULL AND dev_env <> ''
    ORDER BY dev_env
    ";
    $envs = $pdo->query($sql_envs)->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $ex) {

}

$estados = [
    'Abierto',
    'Cerrado',
    'En Progreso',
    'Resuelto'
];
?>
<!doctype html>
<html lang="es">
    <?php require_once __DIR__ . '/assets/head.php'; ?>
    <body>
        <?php require_once __DIR__ . '/assets/navbar.php'; ?>

        <main class="container py-4">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <div>
                    <h1 class="h3 mb-0"><?= $isView ? 'Solicitud de Funcionalidad' : 'Nueva Solicitud de Funcionalidad' ?></h1>
                    <small class="text-muted">
                        <?= $isView ? "Edición de la solicitud #{$id} - Fecha de publicación: {$solicitud_obtenida['pub_date']}." : 'Completa el formulario para crear una nueva solicitud' ?>
                    </small>
                </div>
                <a href="func_solis.php" class="btn btn-outline-secondary">← Volver</a>
            </div>

            <?php if (!$isView): ?>
                <!-- ===== MODO CREACIÓN ===== -->
                <div class="card shadow-sm">
                    <div class="card-header fw-semibold">Formulario de creación</div>
                    <div class="card-body">
                        <form method="post" action="/internal/func_create.php">

                            <div class="mb-3">
                                <label for="titulo" class="form-label">Título <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="titulo" name="titulo" maxlength="70" required
                                       placeholder="Ej: Exportación de reportes a PDF">
                                <div class="form-text">Sé específico y breve. Máx. 70 caracteres.</div>
                            </div>

                            <div class="mb-3">
                                <label for="resumen" class="form-label">Resumen <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="resumen" name="resumen" rows="5" maxlength="150" required
                                          placeholder="Describe brevemente el objetivo y el valor de la funcionalidad."></textarea>
                                <div class="form-text">Máx. 150 caracteres.</div>
                            </div>

                            <div class="row g-3">
                                <div class="col-12 col-md-4">
                                    <label for="topico" class="form-label">Tópico <span class="text-danger">*</span></label>
                                    <select class="form-select" id="topico" name="topico" required>
                                        <option value="" disabled selected>Selecciona un tópico…</option>
                                        <?php foreach ($topicos as $t): ?>
                                            <option value="<?= htmlspecialchars($t, ENT_QUOTES) ?>"><?= htmlspecialchars($t) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="form-text">Se carga desde DB.</div>
                                </div>

                                <div class="col-12 col-md-4">
                                    <label for="dev_env" class="form-label">Entorno <span class="text-danger">*</span></label>
                                    <select class="form-select" id="dev_env" name="dev_env" required>
                                        <option value="" disabled selected>Selecciona un entorno…</option>
                                        <?php foreach ($envs as $e): ?>
                                            <option value="<?= htmlspecialchars($e, ENT_QUOTES) ?>"><?= htmlspecialchars($e) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-12 col-md-4">
                                    <label for="estado" class="form-label">Estado</label>
                                    <select class="form-select" id="estado" name="estado" disabled>
                                        <?php foreach ($estados as $estado): ?>
                                            <option value="<?= $estado ?>"><?= $estado ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="form-text">Se crea como <b>Abierto</b>.</div>
                                </div>
                            </div>

                            <div class="d-flex gap-2 mt-4">
                                <button type="submit" class="btn btn-primary">Crear solicitud</button>
                                <button type="reset" class="btn btn-outline-secondary">Limpiar</button>
                            </div>

                        </form>
                    </div>
                </div>

            <?php else: ?>
                <!-- ===== MODO EDICIÓN (READ/UPDATE + DELETE) ===== -->
                <div class="card shadow-sm mb-3">
                    <div class="card-header fw-semibold">Detalles de la solicitud</div>
                    <div class="card-body">
                        <!-- Formulario de actualización -->
                        <form method="post" action="/internal/func_update.php">
                            <input type="hidden" name="id" value="<?= $id ?>">

                            <div class="mb-3">
                                <label for="titulo" class="form-label">Título <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="titulo" name="titulo" maxlength="70" required
                                       value="<?= htmlspecialchars($solicitud_obtenida['titulo'], ENT_QUOTES) ?>">
                            </div>

                            <div class="mb-3">
                                <label for="resumen" class="form-label">Resumen <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="resumen" name="resumen" rows="5" maxlength="150" required><?= htmlspecialchars($solicitud_obtenida['resumen']) ?></textarea>
                            </div>

                            <div class="row g-3">
                                <div class="col-12 col-md-4">
                                    <label for="topico" class="form-label">Tópico <span class="text-danger">*</span></label>
                                    <select class="form-select" id="topico" name="topico" required>
                                        <option value="" disabled>Selecciona un tópico…</option>
                                        <?php foreach ($topicos as $t): ?>
                                            <option value="<?= htmlspecialchars($t, ENT_QUOTES) ?>"
                                                <?= $solicitud_obtenida['topico'] === $t ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($t) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-12 col-md-4">
                                    <label for="dev_env" class="form-label">Entorno <span class="text-danger">*</span></label>
                                    <select class="form-select" id="dev_env" name="dev_env" required>
                                        <option value="" disabled>Selecciona un entorno…</option>
                                        <?php foreach ($envs as $e): ?>
                                            <option value="<?= htmlspecialchars($e, ENT_QUOTES) ?>"
                                                <?= $solicitud_obtenida['dev_env'] === $e ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($e) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-12 col-md-4">
                                    <label for="estado" class="form-label">Estado <span class="text-danger">*</span></label>
                                    <select class="form-select" id="estado" name="estado" required>
                                        <?php foreach ($estados as $estado): ?>
                                            <option value="<?= $estado ?>" <?= $solicitud_obtenida['estado'] === $estado ? 'selected' : '' ?>>
                                                <?= $estado ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="d-flex gap-2 mt-4">
                                <button type="submit" class="btn btn-success">Guardar cambios</button>
                                <a href="func_solis.php" class="btn btn-outline-secondary">Cancelar</a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Formulario de eliminación (separado, sin JS) -->
                <div class="card border-danger-subtle">
                    <div class="card-body d-flex align-items-center justify-content-between">
                        <div>
                            <div class="fw-semibold text-danger mb-1">Eliminar solicitud</div>
                            <div class="small text-muted">Esta acción no se puede deshacer.</div>
                        </div>
                        <form method="post" action="/internal/func_delete.php" class="ms-3">
                            <input type="hidden" name="id" value="<?= $id ?>">
                            <button type="submit" class="btn btn-outline-danger">Eliminar definitivamente</button>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        </main>
        <?php require_once __DIR__ . '/assets/toasts.php'; ?>
        <?php require_once __DIR__ . '/assets/footer.php'; ?>
    </body>
</html>
