<?php

namespace App\Services;

use Illuminate\Support\Str;

class SimeParser
{
    /**
     * Extrae y normaliza los nombres de los cargos a partir del string de SIME.
     *
     * @param string $cargosRaw
     * @return array
     */
    public function extractCargos(string $cargosRaw): array
    {
        // Separa si vienen múltiples registros en la misma vacante
        $individualCargos = preg_split('/(?<=),\s*(?=\[\d{7}\/\d{2}\])/', $cargosRaw);
        
        $extracted = [];
        $pattern = '/(?:-\s*\d+HS\.\s+DE\s+([^\[\-]+)|\[([^\]]+)\])(?=\s*\[)/ui';

        foreach ($individualCargos as $cargoStr) {
            if (preg_match($pattern, $cargoStr, $matches)) {
                $cargoName = trim($matches[1] ?: $matches[2]);
                if (!empty($cargoName) && !str_contains($cargoName, '/')) {
                    $extracted[] = mb_strtoupper($cargoName, 'UTF-8');
                }
            } else {
                // Fallback para Preceptores, Asesores, etc.
                if (preg_match('/-\s*([A-Z\.\sÑ]+?)\s*(?:-|\s\[)/u', $cargoStr, $fallbackMatches)) {
                    $cargoName = trim($fallbackMatches[1]);
                    if (!empty($cargoName) && !str_contains($cargoName, 'HS.')) {
                        $extracted[] = mb_strtoupper($cargoName, 'UTF-8');
                    }
                }
            }
        }

        return array_values(array_unique($extracted));
    }
}
