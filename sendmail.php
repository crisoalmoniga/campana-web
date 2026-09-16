<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// --- Cargar PHPMailer ---
require __DIR__ . '/phpmailer/src/Exception.php';
require __DIR__ . '/phpmailer/src/PHPMailer.php';
require __DIR__ . '/phpmailer/src/SMTP.php';

// --- Cargar configuración privada ---
$configPath = __DIR__ . '/../.smtp-config.php'; // un nivel arriba de public_html
if (!file_exists($configPath)) {
    http_response_code(500);
    exit;
}
require $configPath;

// --- Sanitizar entrada ---
function clean($v){ return trim(strip_tags($v)); }

$honeypot = $_POST['website'] ?? '';
$nombre   = clean($_POST['nombre'] ?? '');
$email    = clean($_POST['email'] ?? '');
$telefono = clean($_POST['telefono'] ?? '');
$consulta = trim($_POST['consulta'] ?? '');

// Anti-spam (campo trampa oculto, un bot lo completa, una persona no lo ve)
if ($honeypot !== '') { http_response_code(200); exit; }

// Validaciones básicas
if ($nombre === '' || $email === '' || $consulta === '') {
    http_response_code(400);
    exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    exit;
}

// --- Envío de correo ---
$mail = new PHPMailer(true);

try {
    // Configuración SMTP
    $mail->isSMTP();
    $mail->Host       = $SMTP_HOST;
    $mail->SMTPAuth   = true;
    $mail->Username   = $SMTP_USER;
    $mail->Password   = $SMTP_PASS;
    $mail->SMTPSecure = $SMTP_SECURE;
    $mail->Port       = $SMTP_PORT;

    // Datos del correo
    $mail->setFrom($SMTP_USER, 'Web Campana Fumigaciones');
    $mail->addAddress($CONTACT_TO, 'Campana Fumigaciones');
    $mail->addReplyTo($email, $nombre);

    $mail->Subject = 'Nuevo mensaje desde la web - Campana Fumigaciones';
    $mail->Body    = "Nuevo mensaje desde el sitio de Campana Fumigaciones:\n\n"
                   . "Nombre: $nombre\n"
                   . "Email: $email\n"
                   . "Teléfono: " . ($telefono !== '' ? $telefono : 'No indicado') . "\n\n"
                   . "Consulta:\n$consulta\n\n"
                   . "-----\nEnviado: " . date('d/m/Y H:i');

    $mail->CharSet = 'UTF-8';

    $mail->send();
    http_response_code(200);

} catch (Exception $e) {
    error_log('Error al enviar correo (Campana Fumigaciones): ' . $mail->ErrorInfo);
    http_response_code(500);
}
