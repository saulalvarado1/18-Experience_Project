<?php

namespace App\Providers;

use App\Modules\Finanzas\Gateways\CulqiPaymentGateway;
use App\Modules\Finanzas\Gateways\DemoPaymentGateway;
use App\Modules\Finanzas\Gateways\PaymentGatewayInterface;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Pasarela de pagos online (Finanzas): se elige con PAYMENT_GATEWAY.
        $this->app->bind(PaymentGatewayInterface::class, function () {
            $driver = config('services.pasarela.driver');

            // En testing o si hay clave secreta de Culqi configurada, usar CulqiPaymentGateway
            if ($driver === 'culqi' && (app()->environment('testing') || !empty(config('services.culqi.secret_key')))) {
                return new CulqiPaymentGateway(config('services.culqi'));
            }

            // Modo demo / simulador local para pruebas y sustentaciones
            if ($driver === 'demo' || $driver === 'culqi') {
                return new DemoPaymentGateway();
            }

            throw new \InvalidArgumentException('Pasarela de pagos no soportada: ' . $driver);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFive();
    }
}
