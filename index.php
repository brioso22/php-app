<?php
require __DIR__ . '/db.php';

const VERSION = 'v1';

$ruta = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

if ($ruta === '/health') {
    $ok = conectar() !== null;
    http_response_code($ok ? 200 : 503);
    header('Content-Type: text/plain; charset=utf-8');
    echo $ok ? 'ok' : 'error';
    exit;
}
if ($ruta !== '/') {
    http_response_code(404);
    exit('No encontrado');
}

session_start();
$_SESSION['csrf'] ??= bin2hex(random_bytes(32));

function e($s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}

function ocultos(string $accion, int $id = 0): string
{
    return '<input type="hidden" name="csrf" value="' . e($_SESSION['csrf']) . '">'
        . '<input type="hidden" name="accion" value="' . e($accion) . '">'
        . '<input type="hidden" name="id" value="' . $id . '">';
}

$faltan = faltantes();
$pdo = $faltan ? null : conectar();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!is_string($_POST['csrf'] ?? null) || !hash_equals($_SESSION['csrf'], $_POST['csrf'])) {
        http_response_code(400);
        exit('Token CSRF inválido');
    }
    $id = (int) ($_POST['id'] ?? 0);
    $titulo = trim((string) ($_POST['titulo'] ?? ''));
    $tituloValido = $titulo !== '' && mb_strlen($titulo) <= 200;
    if ($pdo) {
        switch ($_POST['accion'] ?? '') {
            case 'crear':
                if ($tituloValido) {
                    $pdo->prepare('INSERT INTO tareas (titulo) VALUES (?)')->execute([$titulo]);
                }
                break;
            case 'editar':
                if ($tituloValido) {
                    $pdo->prepare('UPDATE tareas SET titulo = ? WHERE id = ?')->execute([$titulo, $id]);
                }
                break;
            case 'alternar':
                $pdo->prepare('UPDATE tareas SET hecha = 1 - hecha WHERE id = ?')->execute([$id]);
                break;
            case 'eliminar':
                $pdo->prepare('DELETE FROM tareas WHERE id = ?')->execute([$id]);
                break;
        }
    }
    header('Location: /', true, 303);
    exit;
}

$tareas = [];
if ($pdo) {
    $st = $pdo->prepare('SELECT id, titulo, hecha, creada FROM tareas ORDER BY id DESC');
    $st->execute();
    $tareas = $st->fetchAll();
}
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="generator" content="PHP">
<link rel="icon" href="data:,">
<title>Lisec PaaS - php</title>
<style>
body{font-family:sans-serif;max-width:720px;margin:2rem auto;padding:0 1rem}
.aviso{background:#fee;border:1px solid #c00;padding:.6rem}
.msg{background:#eef;padding:.6rem}
li{display:flex;gap:.4rem;align-items:center;margin:.3rem 0}
.hecha input[name=titulo]{text-decoration:line-through;color:#888}
form{display:inline-flex;gap:.3rem}
</style>
</head>
<body>
<h1>Tareas <small>(<?= e(VERSION) ?>)</small></h1>
<p class="msg">Mensaje: <?= e(getenv('APP_MESSAGE') ?: '(APP_MESSAGE no definido)') ?></p>
<?php if ($faltan): ?>
<p class="aviso">Faltan variables de entorno: <?= e(implode(', ', $faltan)) ?></p>
<?php elseif (!$pdo): ?>
<p class="aviso">No se pudo conectar a la base de datos.</p>
<?php else: ?>
<form method="post">
<?= ocultos('crear') ?>
<input name="titulo" maxlength="200" required placeholder="Nueva tarea">
<button>Crear</button>
</form>
<ul>
<?php foreach ($tareas as $t): ?>
<li class="<?= $t['hecha'] ? 'hecha' : '' ?>">
<form method="post"><?= ocultos('alternar', (int) $t['id']) ?><button title="Cambiar estado"><?= $t['hecha'] ? '✔' : '○' ?></button></form>
<form method="post"><?= ocultos('editar', (int) $t['id']) ?><input name="titulo" maxlength="200" required value="<?= e($t['titulo']) ?>"><button>Guardar</button></form>
<form method="post"><?= ocultos('eliminar', (int) $t['id']) ?><button>Eliminar</button></form>
<small><?= e($t['creada']) ?></small>
</li>
<?php endforeach; ?>
</ul>
<?php endif; ?>
</body>
</html>
