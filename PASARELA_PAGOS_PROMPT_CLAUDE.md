# 🚀 GUÍA Y CONTEXTO MAESTRO PARA CLAUDE: IMPLEMENTACIÓN DE PASARELA DE PAGOS (18 EXPERIENCE WEB)

> **Instrucciones para ti, Claude:**  
> Estás trabajando sobre el repositorio **18 Experience Web** (`c:\Users\Usuario\Documents\Gestion\18experience_web\`), un sistema de gestión turística desarrollado para la agencia *18 Grados Sur* (Tacna, Perú).  
> **Tu misión:** Implementar la **Pasarela de Pagos Online automatizada (Checkout + Webhook)** respetando rigurosamente la arquitectura existente, sin romper los 35 tests que actualmente pasan al 100% y asegurando la integridad transaccional anti-overbooking.

---

## 1. STACK TECNOLÓGICO Y ARQUITECTURA EXISTENTE

* **Framework:** Laravel 12 sobre PHP 8.2 (usar siempre rutas completas de PHP si ejecutas comandos: `C:\xampp\php\php.exe`).
* **Base de Datos:** **PostgreSQL 18.6** (`127.0.0.1:5432`, base de datos `db_18_experience`, credenciales en `.env` — nunca escribirlas en documentos versionados).
* **Patrón Arquitectónico:** **Monolito Modular** estricto ubicado en `app/Modules/`:
  ```
  app/Modules/
  ├── Catalogo/       # Destinos, Tours, Servicios, Salidas (cupos)
  ├── Clientes/       # Personas y Clientes (validación DNI)
  ├── Finanzas/       # Pagos, Métodos de Pago, Comprobantes/Vouchers
  ├── PortalCliente/  # Interfaz B2C autoservicio del turista
  ├── Reportes/       # Reportes gerenciales y PDF
  ├── Seguridad/      # Usuarios, Roles, Auth, CAPTCHA, Auditoría
  └── Ventas/         # Cotizaciones, Reservas y Pasajeros
  ```
* **Estado Actual:** 35 pruebas automatizadas (126 aserciones) pasando en verde: `& "C:\xampp\php\php.exe" artisan test`.

---

## 2. ESQUEMA DE BASE DE DATOS RELEVANTE

### A. Tabla `metodo_pago`
```sql
CREATE TABLE metodo_pago (
    id_metodo_pago SERIAL PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL,
    requiere_validacion BOOLEAN DEFAULT TRUE,
    aplica_recargo BOOLEAN DEFAULT FALSE,
    porcentaje_recargo NUMERIC(5,2) DEFAULT 0.00,
    activo BOOLEAN DEFAULT TRUE
);
```
* **Nota Crítica:** En el seeder existen métodos como Efectivo, Transferencia, Yape y Tarjeta. Para la pasarela de pagos online (ej. MercadoPago / Culqi / Niubiz), `requiere_validacion` debe ser `FALSE` (o `0`), ya que el webhook del banco valida la transacción en milisegundos.

### B. Tabla `pago`
```sql
CREATE TABLE pago (
    id_pago SERIAL PRIMARY KEY,
    id_cotizacion INTEGER REFERENCES cotizacion(id_cotizacion),
    id_metodo_pago INTEGER REFERENCES metodo_pago(id_metodo_pago),
    id_usuario_registro INTEGER REFERENCES usuario(id_usuario),
    id_usuario_validacion INTEGER REFERENCES usuario(id_usuario),
    fecha_pago TIMESTAMP NOT NULL,
    moneda VARCHAR(3) DEFAULT 'PEN',
    monto_base NUMERIC(10,2) NOT NULL,
    recargo NUMERIC(10,2) DEFAULT 0.00,
    monto_total_pagado NUMERIC(10,2) NOT NULL,
    estado VARCHAR(25) NOT NULL, -- 'PENDIENTE_VALIDACION', 'VALIDADO', 'RECHAZADO'
    numero_operacion VARCHAR(100), -- Aquí se guarda el transaction_id / charge_id de la pasarela
    ruta_comprobante VARCHAR(255),
    fecha_validacion TIMESTAMP,
    observacion TEXT
);
```

### C. Vistas SQL y Reglas de Negocio
* **`vw_saldo_reserva` / `vw_disponibilidad_salida`**: Calculan saldo y cupos disponibles en vivo.
* **RN05 (Anticipo Mínimo):** Para reservar, la suma de pagos con estado `VALIDADO` debe ser $\ge 50\%$ del total en tours locales.
* **RN07 (Anti-Overbooking):** `ReservaService::confirmar()` bloquea la salida con `lockForUpdate()` pesimista para evitar colisiones.
* **RN16 (Auditoría):** Toda operación crítica inserta en la tabla `auditoria` usando `Auditoria::registrar($idUsuario, $accion, $tabla, $idRegistro, $detalle)`.

---

## 3. CÓDIGO ACTUAL DE FINANZAS Y VENTAS (PUNTOS DE CONTACTO)

### `app/Modules/Finanzas/Services/PagoService.php`
Ya implementa:
* `montoValidado(int $idCotizacion): float`
* `saldoPendiente(int $idCotizacion): float`
* `registrar(array $datos, ?UploadedFile $comprobante, int $idUsuario): Pago`: Si `$metodo->requiere_validacion == false`, nace automáticamente como `VALIDADO`.

### `app/Modules/Ventas/Services/ReservaService.php`
* Método `confirmar(int $idCotizacion, array $datosPasajeros, int $idUsuario): Reserva`:
  Verifica que `$pagoService->montoValidado($idCotizacion)` cubra el anticipo mínimo y ejecuta `lockForUpdate()` sobre la tabla `salida`.

---

## 4. LO QUE DEBES IMPLEMENTAR (PLAN DE ACCIÓN PASO A PASO)

Debes implementar la pasarela de pagos (se recomienda **Culqi** o **MercadoPago Checkout Pro / API**, muy populares en Perú):

### Paso 1: Variables de Entorno (`.env` y `config/services.php`)
Configurar credenciales de prueba (Sandbox):
```env
PAYMENT_GATEWAY=culqi # o mercadopago
CULQI_PUBLIC_KEY=pk_test_xxxxxx
CULQI_SECRET_KEY=sk_test_xxxxxx
# o para MercadoPago:
MERCADOPAGO_KEY=TEST-xxxxxx
MERCADOPAGO_TOKEN=TEST-xxxxxx
```

### Paso 2: Patrón Adapter en Finanzas
Crear la interfaz y adaptadores en `app/Modules/Finanzas/Gateways/`:
* `PaymentGatewayInterface.php`:
  ```php
  namespace App\Modules\Finanzas\Gateways;

  interface PaymentGatewayInterface {
      public function createCharge(array $data): array; // Procesa el cargo tokenizado
      public function handleWebhook(array $payload, array $headers): array; // Procesa notificación del banco
  }
  ```
* `CulqiPaymentGateway.php` o `MercadoPagoGateway.php`:
  Implementa la interfaz usando HTTP client (`Illuminate\Support\Facades\Http`) o SDK.

### Paso 3: Exención de CSRF para el Webhook
En Laravel 12 (`bootstrap/app.php`), los webhooks de pasarelas no deben ser bloqueados por CSRF:
```php
->withMiddleware(function (Middleware $middleware): void {
    $middleware->validateCsrfTokens(except: [
        'webhooks/*',
        'api/webhooks/*',
    ]);
})
```

### Paso 4: Controlador del Webhook y Ruta
* Crear `app/Modules/Finanzas/Controllers/WebhookPasarelaController.php`.
* Ruta en `routes/web.php` o `routes/api.php`:
  `POST /webhooks/pasarela`
* **Lógica del Webhook:**
  1. Recibe el evento de pago exitoso.
  2. Verifica firma criptográfica del webhook.
  3. Evita duplicados (Idempotencia): Si ya existe un pago con ese `numero_operacion`, retorna HTTP 200 sin duplicar.
  4. Registra el pago en estado `VALIDADO`.
  5. Si el pago cubre el 100% o el anticipo mínimo, puede disparar automáticamente la confirmación de reserva.

### Paso 5: Interfaz en el Portal del Cliente (`resources/views/portal/cotizaciones.blade.php`)
* En las cotizaciones en estado `ACEPTADA`, agregar el botón **"Pagar Online (Tarjeta / Yape)"**.
* Incluir el modal/script de Checkout (por ejemplo Culqi Checkout JS o MercadoPago Bricks) para tokenizar la tarjeta directamente en el navegador del cliente (Cumpliendo PCI-DSS, nunca enviando números de tarjeta al servidor Laravel).

### Paso 6: Tests Automatizados
Crear `tests/Feature/PasarelaPagoTest.php`:
* Test de procesamiento exitoso con Mock HTTP.
* Test de idempotencia del Webhook (evitar cobrar dos veces el mismo evento).
* Test de rechazo de transacciones no válidas.
* Asegurar que la suite completa (`artisan test`) mantenga todas las pruebas en verde.

---

## 5. PROMPT SUGERIDO PARA PEGARLE A CLAUDE

Copia y pega el siguiente bloque a Claude en tu nueva sesión:

```text
Hola Claude. Estoy desarrollando el sistema "18 Experience Web" en Laravel 12, PHP 8.2 y PostgreSQL 18, bajo arquitectura de Monolito Modular en app/Modules/.

He preparado un documento con todo el contexto, modelos y reglas de negocio en:
PASARELA_PAGOS_PROMPT_CLAUDE.md (está en la raíz del proyecto).

Necesito que me ayudes a implementar la Pasarela de Pagos Online (Checkout + Webhooks con patrón Adapter en app/Modules/Finanzas/Gateways/), asegurando que se integre con el módulo de Ventas y el Portal del Cliente, cumpliendo la norma PCI-DSS y dejando los tests en verde.

Por favor, revisa el archivo PASARELA_PAGOS_PROMPT_CLAUDE.md y comencemos con el Paso 1 y Paso 2.
```
