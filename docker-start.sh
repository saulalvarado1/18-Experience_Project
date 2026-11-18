#!/bin/bash
echo "========================================================"
echo "  Iniciando 18 Experience Web con Docker..."
echo "========================================================"
docker compose up -d --build
echo ""
echo "========================================================"
echo "  Aplicacion lista en: http://localhost:8000"
echo "========================================================"
