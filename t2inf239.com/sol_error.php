<?php
// sol_error.php — Frontend estático con 2 formularios (create vs update/delete), sin JS.
// Requiere Bootstrap cargado en assets/head.php y navbar en assets/navbar.php.

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/dbh.inc.php';

require_login();

$id = empty(filter_input(INPUT_GET, "id", FILTER_SANITIZE_NUMBER_INT)) ? null : filter_input(INPUT_GET, "id", FILTER_SANITIZE_NUMBER_INT);

if (isset($id)) {
    $isView = true;

    $pdo = db();

    $query = "SELECT id, rut_autor, pub_date, titulo, descripcion, topico, estado, DATEDIFF(CURDATE(), pub_date) as dias_abierto FROM solicitud_error WHERE id = :id;";

    $statement = $pdo->prepare($query);
    $statement->bindParam(":id", $id);

    $statement->execute();

    $solicitud_obtenida = $statement->fetch();

    if (empty($solicitud_obtenida)) {
        flash_info("No existe solicitud de error con tal id.");
        session_write_close();
        header("Location: /main.php");
        exit;
    }


    flash_success("Solicitud obtenida exitosamente.");
} else {
    $isView = false;
}


// Dummies para precargar en modo edición (reemplaza luego por fetch real desde DB)
// Opciones dummy para selects

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
                    <h1 class="h3 mb-0"><?= $isView ? 'Solicitud de Error' : 'Nueva Solicitud de Error' ?></h1>
                    <small class="text-muted">
                        <?= $isView ? "Edición de la solicitud #{$id} - Fecha de publicación: {$solicitud_obtenida['pub_date']}. <b>Dias abierto: {$solicitud_obtenida["dias_abierto"]}</b>" : 'Completa el formulario para crear una nueva solicitud' ?>
                    </small>
                </div>
                <a href="/main.php" class="btn btn-outline-secondary">← Volver</a>
            </div>


            <?php if (!$isView): ?>
                <!-- ===== MODO CREACIÓN ===== -->
                <div class="card shadow-sm">
                    <div class="card-header fw-semibold">Formulario de creación</div>
                    <div class="card-body">
                        <form method="post" action="/internal/error_create.php">

                            <div class="mb-3">
                                <label for="titulo" class="form-label">Título <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="titulo" name="titulo" maxlength="70" required
                                       placeholder="Ej: Error 500 al guardar formulario"
                                       >
                                <div class="form-text">Sé específico y breve. Máx. 70 caracteres.</div>
                            </div>

                            <div class="mb-3">
                                <label for="descripcion" class="form-label">Descripción <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="descripcion" name="descripcion" rows="6" required
                                          placeholder="Describe esperado vs observado y pasos para reproducir." maxlength="200"></textarea>
                                <div class="form-text">Incluye pasos, evidencia y mensajes de error. Máx. 200 caracteres.</div>
                            </div>

                            <div class="row g-3">
                                <div class="col-12 col-md-6">
                                    <label for="topico" class="form-label">Tópico <span class="text-danger">*</span></label>
                                    <select class="form-select" id="topico" name="topico" required>
                                        <option disabled>Selecciona un tópico…</option>
                                        <?php foreach ($topicos as $t): ?>
                                            <option value="<?= htmlspecialchars($t, ENT_QUOTES) ?>">
                                                <?= htmlspecialchars($t) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="form-text">Luego puebla este select dinámicamente desde la DB.</div>
                                </div>

                                <div class="col-12 col-md-6">
                                    <label for="estado" class="form-label">Estado <span class="text-danger">*</span></label>
                                    <select class="form-select" id="estado" name="estado" required>
                                        <?php foreach ($estados as $estado): ?>
                                            <option value="<?= $estado ?>">
                                                <?= $estado ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="form-text">Flujo: Abierta → En progreso → Resuelta → Cerrada.</div>
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
                        <form method="post" action="/internal/error_update.php">
                            <input type="hidden" name="id" value="<?= $id ?>">

                            <div class="mb-3">
                                <label for="titulo" class="form-label">Título <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="titulo" name="titulo" maxlength="70" required
                                       value="<?= htmlspecialchars($solicitud_obtenida['titulo'], ENT_QUOTES) ?>">
                            </div>

                            <div class="mb-3">
                                <label for="descripcion" class="form-label">Descripción <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="descripcion" name="descripcion" rows="6" maxlength="200" required><?= htmlspecialchars($solicitud_obtenida['descripcion']) ?></textarea>
                            </div>

                            <div class="row g-3">
                                <div class="col-12 col-md-6">
                                    <label for="topico" class="form-label">Tópico <span class="text-danger">*</span></label>
                                    <select class="form-select" id="topico" name="topico" required>
                                        <option value="" disabled>Selecciona un tópico…</option>
                                        <?php foreach ($topicos as $t): ?>
                                            <option value="<?= htmlspecialchars($t, ENT_QUOTES) ?>"
                                                    <?= $solicitud_obtenida['topico'] === $t ? 'selected' : '' ?>><?= htmlspecialchars($t) ?></option>
                                                <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-12 col-md-6">
                                    <label for="estado" class="form-label">Estado <span class="text-danger">*</span></label>
                                    <select class="form-select" id="estado" name="estado" required>
                                        <?php foreach ($estados as $estado): ?>
                                            <option value="<?= $estado ?>" <?= $solicitud_obtenida['estado'] === $estado ? 'selected' : '' ?>><?= $estado ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="d-flex gap-2 mt-4">
                                <button type="submit" class="btn btn-success">Guardar cambios</button>
                                <a href="/main.php" class="btn btn-outline-secondary">Cancelar</a>
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
                        <form method="post" action="/internal/error_delete.php" class="ms-3">
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
