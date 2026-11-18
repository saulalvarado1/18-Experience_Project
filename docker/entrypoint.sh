#!/bin/bash
set -e

echo "==> Iniciando 18 Experience Web en Docker..."

# Copiar .env de docker si no existe
if [ ! -f /var/www/html/.env ]; then
    echo "==> Creando .env desde .env.docker..."
    cp /var/www/html/.env.docker /var/www/html/.env
fi

# Permisos para storage y cache
echo "==> Ajustando permisos de carpetas de almacenamiento..."
chmod -R 777 /var/www/html/storage /var/www/html/bootstrap/cache

# Esperar a que PostgreSQL esté listo
echo "==> Esperando conexión con PostgreSQL (db:5432)..."
until php -r "try { new PDO('pgsql:host=db;port=5432;dbname=db_18_experience', 'postgres', 'postgres'); exit(0); } catch (Exception \$e) { exit(1); }" > /dev/null 2>&1; do
    echo "    PostgreSQL aún no está listo, esperando 2 segundos..."
    sleep 2
done
echo "==> PostgreSQL conectado exitosamente."

# Generar APP_KEY si falta
if grep -q "APP_KEY=$" /var/www/html/.env || ! grep -q "APP_KEY=" /var/www/html/.env || grep -q "APP_KEY=base64:" /var/www/html/.env; then
    if grep -q "APP_KEY=$" /var/www/html/.env; then
        echo "==> Generando APP_KEY..."
        php artisan key:generate --force
    fi
fi

# Enlace simbolico para storage
php artisan storage:link --force || true

# Limpieza de caches
php artisan route:clear
php artisan view:clear
php artisan config:clear

echo "==> 18 Experience Web lista en http://localhost:8000"

exec apache2-foreground
