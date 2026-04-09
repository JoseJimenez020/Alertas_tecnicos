#!/usr/bin/env php
<?php
/**
 * rehash_passwords.php
 *
 * Utilidad de línea de comandos para re-hashear las contraseñas
 * existentes en la BD de un hash bcrypt de costo 10 a costo 12.
 *
 * USO:
 *   php rehash_passwords.php --password=NuevaContraseña --all
 *   php rehash_passwords.php --password=NuevaContraseña --id=3
 *
 * IMPORTANTE: Ejecutar solo desde CLI, nunca desde el navegador.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    die('Este script solo puede ejecutarse desde la línea de comandos.');
}

define('BASE_PATH', __DIR__);
require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/config/Security.php';

// ── Parsear argumentos ───────────────────────────────────────────────
$opts = getopt('', ['password:', 'all', 'id:', 'list', 'help']);

if (isset($opts['help']) || empty($opts)) {
    echo <<<HELP
Uso:
  php rehash_passwords.php --list
      Lista todos los usuarios y si su hash necesita actualización.

  php rehash_passwords.php --password=NUEVA_PASS --all
      Re-hashea la contraseña de TODOS los usuarios (útil para reset masivo).

  php rehash_passwords.php --password=NUEVA_PASS --id=NUM
      Re-hashea la contraseña de un usuario específico por ID.

HELP;
    exit(0);
}

$db = Database::getInstance()->getConnection();

// ── Listar usuarios y estado de hash ────────────────────────────────
if (isset($opts['list'])) {
    $stmt = $db->query("SELECT usu_id, nombre, usuario, password FROM tm_usuarios ORDER BY usu_id");
    $users = $stmt->fetchAll();
    echo str_pad('ID', 5) . str_pad('Usuario', 40) . "¿Necesita rehash?\n";
    echo str_repeat('-', 60) . "\n";
    foreach ($users as $u) {
        $needs = Security::needsRehash($u['password']) ? 'SÍ  ← actualizar' : 'No';
        echo str_pad($u['usu_id'], 5)
           . str_pad($u['usuario'], 40)
           . $needs . "\n";
    }
    exit(0);
}

// ── Re-hasheo ────────────────────────────────────────────────────────
if (!isset($opts['password']) || trim($opts['password']) === '') {
    echo "Error: debes indicar --password=VALOR\n";
    exit(1);
}

$newPlain = $opts['password'];
if (strlen($newPlain) > 72) {
    echo "Error: la contraseña no puede superar 72 caracteres.\n";
    exit(1);
}
$newHash = Security::hashPassword($newPlain);

if (isset($opts['all'])) {
    $stmt  = $db->query("SELECT usu_id, usuario FROM tm_usuarios");
    $users = $stmt->fetchAll();
    $upd   = $db->prepare("UPDATE tm_usuarios SET password = :h WHERE usu_id = :id");
    foreach ($users as $u) {
        $upd->execute([':h' => $newHash, ':id' => $u['usu_id']]);
        echo "✓ [{$u['usu_id']}] {$u['usuario']} actualizado\n";
    }
    echo "\nTotal: " . count($users) . " usuario(s) actualizados.\n";
    exit(0);
}

if (isset($opts['id'])) {
    $id   = (int) $opts['id'];
    $stmt = $db->prepare("SELECT usu_id, usuario FROM tm_usuarios WHERE usu_id = :id");
    $stmt->execute([':id' => $id]);
    $u = $stmt->fetch();
    if (!$u) {
        echo "Error: no existe usuario con ID {$id}.\n";
        exit(1);
    }
    $upd = $db->prepare("UPDATE tm_usuarios SET password = :h WHERE usu_id = :id");
    $upd->execute([':h' => $newHash, ':id' => $id]);
    echo "✓ [{$u['usu_id']}] {$u['usuario']} actualizado.\n";
    exit(0);
}

echo "Indica --all o --id=NUM junto con --password.\n";
exit(1);
