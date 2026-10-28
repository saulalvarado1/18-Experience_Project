<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DemoTacnaSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Destinos emblemáticos de Tacna
        $destinos = [
            [
                'nombre' => 'Petroglifos de Miculla y Pachía',
                'descripcion' => 'Complejo arqueológico de petroglifos milenarios sobre roca volcánica en el desierto tacneño y puente colgante.',
                'ubicacion' => 'Distrito de Pachía, a 22 km de Tacna',
                'altitud_msnm' => 1200.00,
                'como_llegar' => 'Por carretera asfaltada Tacna - Collpa - La Paz en 30 minutos.',
                'temporada_recomendada' => 'Todo el año, preferente mañana',
                'recomendaciones' => 'Llevar sombrero, bloqueador y zapatillas cómodas.',
                'activo' => true,
            ],
            [
                'nombre' => 'Campo de la Alianza y Museo Histórico',
                'descripcion' => 'Monumento histórico nacional y museo de sitio en honor a los defensores de la Batalla del Alto de la Alianza (1880).',
                'ubicacion' => 'Meseta del cerro Intiorko, Tacna',
                'altitud_msnm' => 750.00,
                'como_llegar' => 'A 15 minutos en bus turístico desde el Centro Cívico.',
                'temporada_recomendada' => 'Todo el año',
                'recomendaciones' => 'Respetar el monumento y las áreas ceremoniales.',
                'activo' => true,
            ],
            [
                'nombre' => 'Ruta del Pisco y Bodegas de Pocollay',
                'descripcion' => 'Tradicional circuito enoturístico por los valles de Pocollay y Magollo para degustación de vinos y pisco de uva negra criolla.',
                'ubicacion' => 'Valle de Pocollay, Tacna',
                'altitud_msnm' => 670.00,
                'como_llegar' => 'A 10 minutos de la Plaza de Armas de Tacna.',
                'temporada_recomendada' => 'Marzo a Diciembre (Vendimia en Marzo)',
                'recomendaciones' => 'Degustación responsable de piscos y macerados de damasco.',
                'activo' => true,
            ],
            [
                'nombre' => 'Mirador y Andenería de Tarata',
                'descripcion' => 'Pintoresca provincia andina con impresionante andenería preincaica, iglesias coloniales y baños termales medicinales de Tikaco.',
                'ubicacion' => 'Provincia de Tarata, región Tacna',
                'altitud_msnm' => 3070.00,
                'como_llegar' => 'Carretera Tacna - Tarata en 1 hora y 45 minutos.',
                'temporada_recomendada' => 'Abril a Noviembre',
                'recomendaciones' => 'Ropa abrigadora para la tarde y pastillas para el soroche.',
                'activo' => true,
            ],
        ];

        foreach ($destinos as $d) {
            DB::table('destino')->updateOrInsert(['nombre' => $d['nombre']], $d);
        }

        $idMiculla = DB::table('destino')->where('nombre', 'like', '%Miculla%')->value('id_destino');
        $idAlianza = DB::table('destino')->where('nombre', 'like', '%Alianza%')->value('id_destino');
        $idPocollay = DB::table('destino')->where('nombre', 'like', '%Pocollay%')->value('id_destino');
        $idTarata = DB::table('destino')->where('nombre', 'like', '%Tarata%')->value('id_destino');

        // 2. Tours y Paquetes Turísticos de Tacna
        $tours = [
            [
                'codigo' => 'TL-CITY-001',
                'nombre' => 'City Tour Tacna Histórica y Campo de la Alianza',
                'tipo_producto' => 'TOUR_LOCAL',
                'descripcion' => 'Recorrido guiado por el Centro Cívico, Catedral de Tacna, Pila Ornamental francesa, Arco Parabólico y Museo del Alto de la Alianza.',
                'itinerario' => '09:00 Partida desde Calle San Martín | 09:30 Centro Cívico | 11:00 Campo de la Alianza | 13:00 Retorno al centro.',
                'duracion_dias' => 1,
                'precio_base' => 45.00,
                'moneda' => 'PEN',
                'porcentaje_reserva' => 50.00,
                'minimo_pasajeros' => 2,
                'activo' => true,
                'id_destino' => $idAlianza,
            ],
            [
                'codigo' => 'TL-VALLE-002',
                'nombre' => 'Tour Valle Viejo, Miculla y Puente Colgante',
                'tipo_producto' => 'TOUR_LOCAL',
                'descripcion' => 'Visita a los petroglifos de Miculla, cruce de puentes colgantes, degustación de pastelería tradicional en Calana y gastronomía típica en Pachía.',
                'itinerario' => '14:00 Partida | 14:45 Complejo Miculla y caminata por puente | 16:30 Visita a bodega en Calana | 18:00 Retorno.',
                'duracion_dias' => 1,
                'precio_base' => 60.00,
                'moneda' => 'PEN',
                'porcentaje_reserva' => 50.00,
                'minimo_pasajeros' => 2,
                'activo' => true,
                'id_destino' => $idMiculla,
            ],
            [
                'codigo' => 'TL-PISCO-003',
                'nombre' => 'Ruta Enológica del Pisco y Bodegas Tacneñas',
                'tipo_producto' => 'TOUR_LOCAL',
                'descripcion' => 'Circuito exclusivo para conocer el proceso de elaboración del Pisco tacneño con cata guiada de uvas pisqueras y macerados de damasco.',
                'itinerario' => '10:00 Salida | 10:30 Bodega Santa Elena | 12:00 Bodega Cuneo | 13:30 Almuerzo campestre | 15:30 Retorno.',
                'duracion_dias' => 1,
                'precio_base' => 75.00,
                'moneda' => 'PEN',
                'porcentaje_reserva' => 50.00,
                'minimo_pasajeros' => 2,
                'activo' => true,
                'id_destino' => $idPocollay,
            ],
            [
                'codigo' => 'TL-TARATA-004',
                'nombre' => 'Excursión Andina Tarata y Aguas Termales de Tikaco',
                'tipo_producto' => 'TOUR_LOCAL',
                'descripcion' => 'Aventura andina por los miradores de la cordillera, andenería preincaica de Tarata y baños termales naturales medicinales.',
                'itinerario' => '07:30 Partida | 09:30 Mirador Apacheta | 11:00 Tarata y templo San Benedicto | 12:30 Termas Tikaco | 17:00 Retorno a Tacna.',
                'duracion_dias' => 1,
                'precio_base' => 110.00,
                'moneda' => 'PEN',
                'porcentaje_reserva' => 50.00,
                'minimo_pasajeros' => 3,
                'activo' => true,
                'id_destino' => $idTarata,
            ],
        ];

        foreach ($tours as $t) {
            $destId = $t['id_destino'];
            unset($t['id_destino']);

            DB::table('producto_turistico')->updateOrInsert(['codigo' => $t['codigo']], $t);
            $idProd = DB::table('producto_turistico')->where('codigo', $t['codigo'])->value('id_producto');

            if ($destId && $idProd) {
                DB::table('producto_destino')->updateOrInsert(
                    ['id_producto' => $idProd, 'id_destino' => $destId],
                    ['orden_visita' => 1, 'observacion' => 'Destino principal del itinerario']
                );
            }

            // Programar salidas para hoy y los próximos días
            for ($i = 0; $i <= 5; $i++) {
                $fechaSalida = now()->addDays($i)->toDateString();
                DB::table('salida')->updateOrInsert(
                    [
                        'id_producto' => $idProd,
                        'fecha' => $fechaSalida,
                        'hora' => ($t['codigo'] === 'TL-TARATA-004') ? '07:30:00' : '09:00:00',
                    ],
                    [
                        'capacidad' => 15,
                        'estado' => 'PROGRAMADA',
                        'observacion' => 'Salida garantizada con movilidad turística climatizada.',
                    ]
                );
            }
        }
    }
}
