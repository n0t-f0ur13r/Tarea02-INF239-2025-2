<?php

// logout.php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

// Usa la utilidad centralizada para cerrar sesión y redirigir
logout('/index.php');
