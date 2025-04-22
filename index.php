<?php
session_start();

// Redirigir al usuario al dashboard si está autenticado, o a la página de login si no lo está
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
} else {
    header('Location: login.php');
}
exit(); 