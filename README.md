# php-app
CRUD de tareas en PHP 8.3 puro + PDO + MySQL. Rutas: `/` y `/health`.
Variables: DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASSWORD, APP_MESSAGE (ver `.env.example`).
Puerto del contenedor: 80.
Probar en local:
    docker build -t php-app . && docker run --rm -p 8080:80 --env-file .env php-app
    # abrir http://localhost:8080 y http://localhost:8080/health
# php-app
