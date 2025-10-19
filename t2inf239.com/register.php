<!doctype html>
<html lang="es">
    <?php require_once __DIR__ . '/assets/head.php'; ?>
    <body>
        <?php require_once __DIR__ . '/assets/navbar.php'; ?>
        <main class="container py-5">
            <div class="row justify-content-center">
                <div class="col-12 col-md-8 col-lg-6">
                    <div class="card shadow-sm">
                        <div class="card-body p-4">
                            <div class="page-header mb-3"><h1>Registro</h1></div>
                            <form action="internal/auth_signup.php" method="post" novalidate>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label" for="name">Nombre</label>
                                        <input class="form-control" id="name" name="name" required>
                                        <div class="invalid-feedback">Tu nombre es requerido.</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label" for="email">Correo</label>
                                        <input type="email" class="form-control" id="email" name="email" required>
                                        <div class="invalid-feedback">Correo inválido.</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label" for="rut">RUT</label>
                                        <input class="form-control" id="rut" name="rut" placeholder="12.345.678-9" required>
                                        <div class="invalid-feedback">RUT requerido.</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label" for="role">Rol</label>
                                        <select class="form-select" id="role" name="account" required>
                                            <option value="" selected disabled>Selecciona rol</option>
                                            <option value="usuario">Usuario</option>
                                            <option value="ingeniero">Ingeniero</option>
                                        </select>
                                        <div class="invalid-feedback">Selecciona un rol.</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label" for="password">Contraseña</label>
                                        <input type="password" class="form-control" id="password" name="password" minlength="6" required>
                                        <div class="invalid-feedback">Mínimo 6 caracteres.</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label" for="password2">Repite Contraseña</label>
                                        <input type="password" class="form-control" id="password2" name="password2" minlength="6" required>
                                        <div class="invalid-feedback">Confirma la contraseña.</div>
                                    </div>
                                    <div class="col-12">
                                        <button class="btn btn-primary w-100" type="submit">Crear cuenta</button>
                                    </div>
                                    <div class="col-12 text-center">
                                        <a href="index.php" class="small">¿Ya tienes cuenta? Inicia sesión</a>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </main>
        <?php require_once __DIR__ . '/assets/footer.php'; ?>
        <?php require_once __DIR__ . '/assets/toasts.php'; ?>

        <script>
            (() => {
                const f = document.querySelector('form');
                f.addEventListener('submit', e => {
                    if (!f.checkValidity()) {
                        e.preventDefault();
                        e.stopPropagation();
                    }
                    const p1 = document.getElementById('password'), p2 = document.getElementById('password2');
                    if (p1.value !== p2.value) {
                        e.preventDefault();
                        e.stopPropagation();
                        alert('Las contraseñas no coinciden');
                    }
                    f.classList.add('was-validated');
                });
            })();
        </script>
    </body>
</html>
