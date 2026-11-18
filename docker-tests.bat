@echo off
echo ========================================================
echo   Ejecutando suite de pruebas (111 tests) en Docker...
echo ========================================================
docker compose exec app php artisan test
pause
