<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Vacante;
use App\Models\Cargo;
use App\Services\SimeParser;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class SyncSimeVacancies extends Command
{
    protected $signature = 'sime:sync';
    protected $description = 'Extrae y almacena cargos y vacantes desde la API SIME';

    public function handle(SimeParser $parser): void
    {
        $this->info('Iniciando sincronización de cargos y vacantes SIME...');

        // Niveles reportados (1 al 4)
        $niveles = [1, 2, 3, 4];

        foreach ($niveles as $nivel_id) {
            $this->info("Consultando vacantes para Nivel {$nivel_id}...");
            try {
                $response = Http::withoutVerifying()->get('https://sime.educaciontuc.gov.ar/Vacantes/ObtenerVacantes/'.$nivel_id);
                
                if($response->successful()){
                    $vacantes = $response->json()['vacantes'] ?? [];
                    $this->info("Se encontraron " . count($vacantes) . " vacantes en Nivel {$nivel_id}. Sincronizando...");
                    
                    foreach ($vacantes as $v) {
                        $vacanteModel = Vacante::updateOrCreate(
                            ['id' => $v['id']],
                            ['raw_data' => $v]
                        );

                        $nombresExtraidos = $parser->extractCargos($v['cargos'] ?? '');
                        
                        $cargoIds = [];
                        foreach ($nombresExtraidos as $name) {
                            $cargo = Cargo::firstOrCreate(
                                ['nombre' => $name],
                                ['slug' => Str::slug($name), 'nivel_id' => $nivel_id]
                            );
                            if (empty($cargo->slug)) {
                                $cargo->update(['slug' => Str::slug($name)]);
                            }
                            $cargoIds[] = $cargo->id;
                        }

                        $vacanteModel->cargos()->sync($cargoIds);
                    }
                } else {
                    $this->error("La API de SIME devolvió un error HTTP para el Nivel {$nivel_id}.");
                }
            } catch (\Exception $e) {
                $this->error("Error consultando SIME para nivel {$nivel_id}: " . $e->getMessage());
            }
        }

        $this->info('Sincronización de vacantes completada con éxito.');
    }
}
