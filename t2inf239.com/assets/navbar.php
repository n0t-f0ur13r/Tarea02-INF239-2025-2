<?php
require_once __DIR__ . '/../includes/auth.php';

$user = auth_user(); // null si no hay sesión
$role = $user['role'] ?? null;
?>
<nav class="navbar navbar-expand-lg bg-body-tertiary border-bottom">
    <div class="container">
        <a class="navbar-brand fw-semibold" href="main.php">ZeroPressure</a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav"
                aria-controls="mainNav" aria-expanded="false" aria-label="Alternar navegación">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav ms-auto mb-2 mb-lg-0">

                <?php if (!$user): ?>
                    <!-- Visitante -->
                    <li class="nav-item"><a class="nav-link" href="index.php">Login</a></li>
                    <li class="nav-item"><a class="nav-link" href="register.php">Registro</a></li>
                <?php endif; ?>

                <li class="nav-item"><a class="nav-link" href="search.php">Búsqueda Avanzada</a></li>

                <?php if ($user && $role === 'user'): ?>
                    <!-- Menú de Usuario -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">Mis Solicitudes</a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="my_func.php">Mis Funcionalidades</a></li>
                            <li><a class="dropdown-item" href="my_err.php">Mis Errores</a></li>
                        </ul>
                    </li>
                <?php endif; ?>

                <?php if ($user && $role === 'engineer'): ?>
                    <!-- Menú de Ingeniero -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">Panel Ingeniero</a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="func.php">Funcionalidades (CRUD)</a></li>
                            <li><a class="dropdown-item" href="err.php">Errores (CRUD)</a></li>
                            <li><a class="dropdown-item" href="my_solis.php">Asignadas a mí</a></li>
                        </ul>
                    </li>
                <?php endif; ?>

                <?php if ($user): ?>
                    <!-- Menú de Cuenta -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                            <?= htmlspecialchars($user['name'] ?? 'Cuenta') ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li class="dropdown-item-text text-muted small">
                                RUT: <?= htmlspecialchars($user['rut'] ?? '-') ?><br>
                                Rol: <?= htmlspecialchars($user['role'] ?? '-') ?><br>
                                Email: <?= htmlspecialchars($user['email'] ?? '-') ?>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php">
                                    <i class="bi bi-box-arrow-right me-1"></i>Salir
                                </a></li>
                        </ul>
                    </li>
                <?php endif; ?>

                <?php if ($user): ?>
                    <li class="nav-item"><a class="nav-link" href="logout.php">Cerrar sesión</a></li>
                <?php endif; ?>


            </ul>
        </div>
    </div>
</nav>
