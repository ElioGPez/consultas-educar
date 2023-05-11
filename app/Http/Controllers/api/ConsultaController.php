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

                $response2 = Http::get('https://sime.educaciontuc.gov.ar/Vacantes/ObtenerVacante/'.$vacante['id'].'?origen=0&postulante=-1');
                $response3 = Http::get('https://sime.educaciontuc.gov.ar/Vacantes/ObtenerPadrones/'.$vacante['id']);
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
        $preferencia->user_id = 1;
        $preferencia->cargo_id = $request->cargo_id;
        $preferencia->save();

        $crircuitos = json_decode($request->circuitos, true);
        foreach ($crircuitos as $key => $circuito) {
            $new_circuito = new Circuito();
            $new_circuito->nombre = $circuito["value"];
            $new_circuito->preference_id = $preferencia->id;
            $new_circuito->save();
        }

        return "Preferencia guardada";
    }

    public function getVacantes(Request $request){
        $preferencia = Preferencia::select(
            'c.nombre',
            'n.id as nivel_id',
            'circuitos'
        )
        ->where('user_id',1)
        ->join('cargos as c','preferencias.cargo_id','c.id')
        ->leftJoin('nivels as n','c.nivel_id','n.id')
        ->get();
        
        $circuitos = json_decode($preferencia[0]->circuitos, true);
        $circuitos["circuitos"][0];

        $response = Http::get('https://sime.educaciontuc.gov.ar/Vacantes/ObtenerVacantes/'.$preferencia[0]->nivel_id);
        $array = [];

        foreach ($response['vacantes'] as $key => $vacante) {
            if(strpos($vacante['cargos'], strtoupper($preferencia[0]->nombre)) !== false){
                $vacante_obj = new stdClass();

                $response2 = Http::get('https://sime.educaciontuc.gov.ar/Vacantes/ObtenerVacante/'.$vacante['id'].'?origen=0&postulante=-1');
                $response3 = Http::get('https://sime.educaciontuc.gov.ar/Vacantes/ObtenerPadrones/'.$vacante['id']);

                $vacante_obj->vacante = $vacante;
                $vacante_obj->vacante_detalle = $response2['vacante'];
                $vacante_obj->padrones = $response3['padrones'];
                //
                //$vacante_obj->padrones[0]["Organizacion"];

                $existen_circuitos = false;

                foreach ( $circuitos["circuitos"] as $key => $circuito) {
                    Log::debug($circuito . ' - '.  $vacante_obj->padrones[0]["Organizacion"]);
                    if($this->checkCircuito($circuito, $vacante_obj->padrones[0]["Organizacion"])){
                        //$existen_circuitos = true;
                        array_push($array,$vacante_obj);
                        continue;
                    }
                    //return;
                }
                //return 
                //array_push($array,$vacante_obj);
            }
        }
        
        //}https://sime.educaciontuc.gov.ar/Vacantes/ObtenerVacante/36743?origen=0&postulante=-1 

        return $array;
    }

    public function checkCircuito($circuito,$circuito_vacante){
        if($circuito == 'I'){
            if (strpos($circuito_vacante, 'CIRCUITO '.$circuito.' ') !== false || strpos($circuito_vacante, 'CIRCUITO '.$circuito.'(') !== false)  {
                return true;
            }
        }else
        if($circuito == 'II'){
            if (strpos($circuito_vacante, 'CIRCUITO '.$circuito.' ') !== false || strpos($circuito_vacante, 'CIRCUITO '.$circuito.'(') !== false)  {
                return true;
            }
        }else
        if($circuito == 'III'){
            if (strpos($circuito_vacante, 'CIRCUITO '.$circuito.' ') !== false || strpos($circuito_vacante, 'CIRCUITO '.$circuito.'(') !== false)  {
                return true;
            }
        }else
        if($circuito == 'IV'){
            if (strpos($circuito_vacante, 'CIRCUITO '.$circuito.' ') !== false || strpos($circuito_vacante, 'CIRCUITO '.$circuito.'(') !== false)  {
                return true;
            }
        }else
        if($circuito == 'V'){
            if (strpos($circuito_vacante, 'CIRCUITO '.$circuito.' ') !== false || strpos($circuito_vacante, 'CIRCUITO '.$circuito.'(') !== false)  {
                return true;
			}
        }
    }
}
