<?php

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/dbh.inc.php';

require_login();

$id = empty(filter_input(INPUT_GET, "id", FILTER_SANITIZE_NUMBER_INT)) ? null : filter_input(INPUT_GET, "id", FILTER_SANITIZE_NUMBER_INT);

if (isset($id)) {
    $isView = true;

    $pdo = db();

    $query = "SELECT id, rut_autor, pub_date, titulo, resumen, topico, dev_env, estado, DATEDIFF(CURDATE(), pub_date) as dias_abierto 
              FROM solicitud_func WHERE id = :id;";

    $statement = $pdo->prepare($query);
    $statement->bindParam(":id", $id, PDO::PARAM_INT);

    $statement->execute();

    $solicitud_obtenida = $statement->fetch();
    $statement->closeCursor();

    // SELECCIONAR RESENAS DE LA SOLICITUD

    $query_resenas  = "SELECT id, fecha, mensaje FROM resena_func WHERE id_solicitud_func = :id_solicitud ORDER BY fecha DESC;";

    $statement = $pdo->prepare($query_resenas);
    $statement->bindParam(":id_solicitud", $id);

    $statement->execute();

    $resenas = $statement->fetchAll();
    $statement->closeCursor();



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
                    <?= $isView ? "Edición de la solicitud #{$id} - Fecha de publicación: {$solicitud_obtenida['pub_date']}. <b>Dias abierto: {$solicitud_obtenida["dias_abierto"]}</b>" : 'Completa el formulario para crear una nueva solicitud' ?>
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
                            <button class="btn btn-primary" type="button" data-bs-toggle="offcanvas" data-bs-target="#offCanvasResenas" aria-controls="offcanvasWithBothOptions">Ver reseñas</button>
                            <button type="button" class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#modalResena">Nueva Reseña</button>
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

    <?php if ($isView): ?>
        <div class="offcanvas offcanvas-start" data-bs-scroll="true" tabindex="-1" id="offCanvasResenas" aria-labelledby="offcanvasWithBothOptionsLabel">
            <div class="offcanvas-header">
                <h5 class="offcanvas-title" id="offcanvasWithBothOptionsLabel">
                    Reseñas solicitud #<?= $id ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
            </div>
            <div class="offcanvas-body">

                <?php if (!empty($resenas)): ?>

                    <ul class="list-group">

                        <?php foreach ($resenas as $resena): ?>

                            <li class="list-group-item">
                                <div class="fw-semibold">Resena #<?= $resena['id'] ?></div>
                                <div><?= htmlspecialchars($resena['mensaje']) ?></div>
                                <small class="text-muted"><?= $resena['fecha'] ?></small>
                                <a href="/resena.php?id=<?= $resena['id'] ?>&kind=func" class="text-reset text-muted">
                                    editar reseña
                                </a>

                            </li>

                        <?php endforeach; ?>

                    </ul>

                <?php else: ?>
                    No hay reseñas registradas.
                <?php endif ?>
            </div>
        </div>

        <!-- Modal -->
        <div class="modal fade" id="modalResena" tabindex="-1" aria-labelledby="modalResenaLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content shadow">
                    <div class="modal-header">
                        <h5 class="modal-title fw-semibold" id="modalResenaLabel">Crear Nueva Reseña</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>

                    <div class="modal-body">
                        <form id="formResena" method="post" action="/internal/review_create.php">
                            <input type="hidden" name="type" value="func">
                            <input type="hidden" name="target" value="<?=$id?>">
                            <div class="mb-3">
                                <label for="mensaje" class="form-label">Mensaje de la reseña</label>
                                <textarea class="form-control" id="mensaje" name="mensaje" maxlength="400" rows="4" placeholder="Escribe tu reseña aquí..." required></textarea>
                            </div>
                        </form>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" form="formResena" class="btn btn-primary">Guardar Reseña</button>
                    </div>
                </div>
            </div>
        </div>

    <?php endif; ?>


    <?php require_once __DIR__ . '/assets/toasts.php'; ?>
    <?php require_once __DIR__ . '/assets/footer.php'; ?>
</body>

</html>