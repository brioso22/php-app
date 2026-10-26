<?php
const DB_VARS = ['DB_HOST', 'DB_PORT', 'DB_NAME', 'DB_USER', 'DB_PASSWORD'];

function faltantes(): array
{
    return array_values(array_filter(DB_VARS, fn($v) => getenv($v) === false || getenv($v) === ''));
}

function conectar(int $intentos = 1): ?PDO
{
    if (faltantes()) {
        return null;
    }
    $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', getenv('DB_HOST'), getenv('DB_PORT'), getenv('DB_NAME'));
    for ($i = 1; $i <= $intentos; $i++) {
        try {
            $pdo = new PDO($dsn, getenv('DB_USER'), getenv('DB_PASSWORD'), [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_TIMEOUT => 3,
            ]);
            $pdo->prepare('CREATE TABLE IF NOT EXISTS tareas (
                id INT AUTO_INCREMENT PRIMARY KEY,
                titulo VARCHAR(200) NOT NULL,
                hecha TINYINT(1) NOT NULL DEFAULT 0,
                creada TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )')->execute();
            return $pdo;
        } catch (PDOException $e) {
            error_log("BD intento $i/$intentos fallido: " . $e->getMessage());
            if ($i < $intentos) {
                sleep(3);
            }
        }
    }
    return null;
}

// Ejecutado por CLI al arrancar el contenedor: espera a la BD y crea la tabla.
if (PHP_SAPI === 'cli' && realpath($argv[0] ?? '') === __FILE__) {
    if ($f = faltantes()) {
        echo 'Faltan variables de entorno: ' . implode(', ', $f) . "\n";
    } else {
        echo conectar(10) ? "BD lista\n" : "BD no disponible, se arranca igualmente\n";
    }
}
