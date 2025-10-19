
<!doctype html>
<html lang="es">
    <?php require_once __DIR__ . '/assets/head.php'; ?>
    <body>
        <?php require_once __DIR__ . '/assets/navbar.php'; ?>
        <main class="container py-5">
            <div class="row justify-content-center">
                <div class="col-12 col-sm-10 col-md-8 col-lg-5">
                    <div class="card shadow-sm">
                        <div class="card-body p-4">
                            <div class="page-header text-center mb-3">
                                <h1>Iniciar sesión</h1>
                            </div>
                            <form action="internal/auth_login.php" method="post" novalidate>
                                <div class="mb-3">
                                    <label for="rut" class="form-label">RUT</label>
                                    <input type="text" class="form-control" id="rut" name="rut" required autocomplete="username">
                                </div>
                                <div class="mb-3">
                                    <label for="password" class="form-label">Contraseña</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="password" name="password" required minlength="6" autocomplete="current-password">
                                        <button class="btn btn-outline-secondary" type="button" id="togglePassword" aria-label="Mostrar/Ocultar">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </div>
                                    <div class="invalid-feedback">La contraseña es obligatoria.</div>
                                </div>
                                <button type="submit" class="btn btn-primary w-100">Ingresar</button>
                                <div class="text-center mt-3">
                                    <span class="text-secondary small">¿No tienes cuenta?</span>
                                    <a href="register.php" class="small">Regístrate</a>
                                </div>
                            </form>
                        </div>
                    </div>
                    <p class="text-center text-muted small mt-3 mb-0">
                        Al continuar, aceptas los Términos y la Política de Privacidad.
                    </p>
                </div>
            </div>
        </main>
        <?php require_once __DIR__ . '/assets/footer.php'; ?>
        <?php require_once __DIR__ . '/assets/toasts.php'; ?>
        <script>
            // Validador
            (() => {
                const forms = document.querySelectorAll('form');
                forms.forEach(f => f.addEventListener('submit', e => {
                        if (!f.checkValidity()) {
                            e.preventDefault();
                            e.stopPropagation();
                        }
                        f.classList.add('was-validated');
                    }));
            })();
            // Toggle password
            const btn = document.getElementById('togglePassword');
            const pwd = document.getElementById('password');
            if (btn && pwd)
                btn.addEventListener('click', () => {
                    const t = pwd.type === 'text' ? 'password' : 'text';
                    pwd.type = t;
                    btn.querySelector('i').classList.toggle('bi-eye');
                    btn.querySelector('i').classList.toggle('bi-eye-slash');
                });
        </script>
    </body>
</html>
