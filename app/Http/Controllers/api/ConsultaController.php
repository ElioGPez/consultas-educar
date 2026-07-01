<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use stdClass;
use App\Models\Nivel;
use App\Models\Cargo;
use App\Models\Circuito;
use App\Models\Preferencia;
use Illuminate\Support\Facades\Log;

class ConsultaController extends Controller
{
    public function all(Request $request){
        $user_id = $request->user_id;
        
        //return $response = Http::get('https://sime.educaciontuc.gov.ar/Vacantes/ObtenerVacantes/3');
        $array = [];
        return "llega";
        foreach ($response['vacantes'] as $key => $vacante) {
            if(strpos($vacante['cargos'], 'PLASTICA') !== false){
                $vacante_obj = new stdClass();

                $response2 = Http::withoutVerifying()->get('https://sime.educaciontuc.gov.ar/Vacantes/ObtenerVacante/'.$vacante['id'].'?origen=0&postulante=-1');
                $response3 = Http::withoutVerifying()->get('https://sime.educaciontuc.gov.ar/Vacantes/ObtenerPadrones/'.$vacante['id']);
                return $response3;
                $vacante_obj->vacante = $vacante;
                $vacante_obj->vacante_detalle = $response2['vacante'];
                $vacante_obj->padrones = $response3['padrones'];
                $vacante_obj;
                array_push($array,$vacante_obj);
            }
        }
        
        //}https://sime.educaciontuc.gov.ar/Vacantes/ObtenerVacante/36743?origen=0&postulante=-1 

        return $array;


    }

    public function getNivel(){
        return Nivel::all();
    }
    public function getCargos(Request $request){
        return Cargo::where('nivel_id',$request->nivel_id)->get();
    }

    public function savePreference(Request $request){
        $preferencia = new Preferencia();
        $preferencia->user_id = $request->user_id ?? 1;
        $preferencia->cargo_id = $request->cargo_id;
        $preferencia->circuitos = $request->circuitos ?? '[]'; // Fallback para evitar error 1364
        $preferencia->save();

        $circuitos_json = $request->circuitos ?? '[]';
        $circuitos = json_decode($circuitos_json, true);
        foreach ($circuitos as $key => $circuito) {
            $new_circuito = new Circuito();
            $new_circuito->nombre = $circuito["value"];
            $new_circuito->preference_id = $preferencia->id;
            $new_circuito->save();
        }

        return response()->json(["message" => "Preferencia guardada", "id" => $preferencia->id]);
    }

    public function getPreferences(Request $request){
        return Preferencia::where('user_id', $request->user_id)
            ->with(['cargo', 'cargo.nivel', 'circuitos'])
            ->get();
    }

    public function deletePreference($id){
        $preferencia = Preferencia::find($id);
        if($preferencia){
            Circuito::where('preference_id', $id)->delete();
            $preferencia->delete();
            return response()->json(["message" => "Preferencia eliminada"]);
        }
        return response()->json(["message" => "No encontrada"], 404);
    }

    public function getVacantes(Request $request){
        $user_id = $request->user_id ?? 1;
        $preferencias = Preferencia::where('user_id', $user_id)->with(['cargo', 'cargo.nivel'])->get();
        Log::debug($preferencias);
        if($preferencias->isEmpty()) return [];

        $array = [];
        $ids_procesadas = [];
        $niveles_consultados = []; 

        foreach ($preferencias as $pref) {
            $nivel_id = $pref->cargo->nivel_id;
            $cargo_nombre = strtoupper($pref->cargo->nombre);
            $circuitos_decodificados = json_decode($pref->circuitos, true) ?? [];
            $mis_circuitos = array_map(fn($c) => $c['value'] ?? $c, $circuitos_decodificados);

            // CACHE: Guardamos la lista del nivel por 10 minutos para no saturar al SIME
            $vacantes_nivel = \Cache::remember("vacantes_nivel_$nivel_id", 600, function() use ($nivel_id) {
                $response = Http::withoutVerifying()->get('https://sime.educaciontuc.gov.ar/Vacantes/ObtenerVacantes/'.$nivel_id);
                return $response->successful() ? $response->json()['vacantes'] : [];
            });

            // Filtramos candidatos que coincidan con el cargo
            foreach ($vacantes_nivel as $v) {
                if(in_array($v['id'], $ids_procesadas)) continue;
                if(strpos(strtoupper($v['cargos']), $cargo_nombre) !== false) {
                    $v['mis_circuitos'] = $mis_circuitos; // Pasamos los circuitos para el siguiente paso
                    $array[] = $v;
                    $ids_procesadas[] = $v['id'];
                }
            }
        }

        if(empty($array)) return [];

        // PARALELISMO: Pedimos los datos básicos (Padrones) para filtrar por circuito de todas las vacantes juntas
        $responses = Http::pool(function ($pool) use ($array) {
            return array_map(function($v) use ($pool) {
                return $pool->as($v['id'])->withoutVerifying()->get('https://sime.educaciontuc.gov.ar/Vacantes/ObtenerPadrones/'.$v['id']);
            }, $array);
        });

        $final_results = [];
        foreach ($array as $v) {
            $resp_padrones = $responses[$v['id']];
            if($resp_padrones->successful()){
                $padrones = $resp_padrones->json()['padrones'] ?? [];
                $circuito_vacante = $padrones[0]["Organizacion"] ?? '';
                
                foreach ($v['mis_circuitos'] as $mi_circuito) {
                    if($this->checkCircuito($mi_circuito, $circuito_vacante)){
                        // Solo si pasa el filtro de circuito, armamos el objeto mínimo para la lista
                        $vacante_obj = new stdClass();
                        $vacante_obj->vacante = $v;
                        $vacante_obj->padrones = $padrones;
                        // Nota: No traemos horarios todavía para que la lista vuele. 
                        // Se cargarán al entrar al detalle.
                        $final_results[] = $vacante_obj;
                        break;
                    }
                }
            }
        }

        return $final_results;
    }

    public function getVacanteDetalle($id) {
        // Este endpoint es nuevo y lo usaremos cuando el usuario haga clic en una vacante
        $responses = Http::pool(function ($pool) use ($id) {
            return [
                $pool->as('detalle')->withoutVerifying()->get('https://sime.educaciontuc.gov.ar/Vacantes/ObtenerVacante/'.$id.'?origen=0&postulante=-1'),
                $pool->as('especificos')->withoutVerifying()->get('https://sime.educaciontuc.gov.ar/Vacantes/ObtenerDetalleVacante/'.$id),
            ];
        });

        $vacante_detalle = $responses['detalle']->json()['vacante'] ?? null;
        $vacantes_especificos = $responses['especificos']->json()['vacantes'] ?? [];

        // Traemos horarios en paralelo para todos los establecimientos de esta vacante
        $horarios_responses = Http::pool(function ($pool) use ($vacantes_especificos) {
            $reqs = [];
            foreach ($vacantes_especificos as $idx => $e) {
                $reqs[] = $pool->as($idx)->withoutVerifying()->get('https://sime.educaciontuc.gov.ar/Vacantes/ObtenerHorarios/'.$e['cargos_id']);
            }
            return $reqs;
        });

        foreach ($vacantes_especificos as $idx => &$e) {
             $e['horarios'] = $horarios_responses[$idx]->json()['horarios'] ?? [];
        }

        return response()->json([
            'vacante_detalle' => $vacante_detalle,
            'vacantes_detalle' => $vacantes_especificos
        ]);
    }

    public function checkCircuito($circuito, $circuito_vacante){
        if (stripos($circuito_vacante, 'CIRCUITO') === false) {
            return true;
        }
        $pattern = '/CIRCUITO\s+'.$circuito.'(\s+|\(|$)/i';
        return preg_match($pattern, $circuito_vacante);
    }
}
