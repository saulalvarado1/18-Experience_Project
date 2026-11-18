@echo off
echo ========================================================
echo   Iniciando 18 Experience Web con Docker...
echo ========================================================
docker compose up -d --build
echo.
echo ========================================================
echo   Los contenedores estan iniciando.
echo   La aplicacion estara disponible en:
echo   http://localhost:8000
echo ========================================================
pause
