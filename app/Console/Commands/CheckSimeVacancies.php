<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Preferencia;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Google\Auth\Credentials\ServiceAccountCredentials;
use Google\Auth\HttpHandler\HttpHandlerFactory;

class CheckSimeVacancies extends Command
{
    protected $signature = 'sime:check';
    protected $description = 'Consulta el SIME y notifica nuevas vacantes usando FCM v1';

    public function handle()
    {
        $this->info('Iniciando chequeo de vacantes SIME (FCM v1)...');

        $users = User::whereNotNull('fcm_token')->get();

        foreach ($users as $user) {
            $this->info("Procesando usuario: {$user->email}");
            
            $preferencias = Preferencia::where('user_id', $user->id)->with(['cargo', 'cargo.nivel'])->get();
            
            foreach ($preferencias as $pref) {
                if (!$pref->cargo) continue;

                $nivel_id = $pref->cargo->nivel_id;
                $cargo_nombre = strtoupper($pref->cargo->nombre);
                $circuitos_decodificados = json_decode($pref->circuitos, true) ?? [];
                $mis_circuitos = array_map(fn($c) => is_array($c) ? ($c['value'] ?? $c) : $c, $circuitos_decodificados);

                try {
                    $response = Http::withoutVerifying()->get('https://sime.educaciontuc.gov.ar/Vacantes/ObtenerVacantes/'.$nivel_id);
                    
                    if($response->successful()){
                        $vacantes = $response->json()['vacantes'] ?? [];
                        
                        foreach ($vacantes as $v) {
                            if(strpos(strtoupper($v['cargos']), $cargo_nombre) !== false){
                                
                                $alreadySent = DB::table('sent_notifications')
                                    ->where('user_id', $user->id)
                                    ->where('vacante_id', $v['id'])
                                    ->exists();

                                if(!$alreadySent){
                                    $respPadrones = Http::withoutVerifying()->get('https://sime.educaciontuc.gov.ar/Vacantes/ObtenerPadrones/'.$v['id']);
                                    if($respPadrones->successful()){
                                        $padrones = $respPadrones->json()['padrones'] ?? [];
                                        $circuito_vacante = $padrones[0]["Organizacion"] ?? '';
                                        
                                        if($this->checkCircuitoMatch($mis_circuitos, $circuito_vacante)){
                                            $this->sendNotificationV1($user, $v);
                                            
                                            DB::table('sent_notifications')->insert([
                                                'user_id' => $user->id,
                                                'vacante_id' => $v['id'],
                                                'created_at' => now(),
                                                'updated_at' => now(),
                                            ]);
                                        }
                                    }
                                }
                            }
                        }
                    }
                } catch (\Exception $e) {
                    $this->error("Error consultando SIME para nivel {$nivel_id}: " . $e->getMessage());
                }
            }
        }

        $this->info('Chequeo completado.');
    }

    private function checkCircuitoMatch($mis_circuitos, $circuito_vacante) {
        if (empty($mis_circuitos)) return true; // Si no específico circuitos, asumo que quiere todos
        
        if (stripos($circuito_vacante, 'CIRCUITO') === false) {
            return true;
        }

        foreach ($mis_circuitos as $mi_circuito) {
            $pattern = "/CIRCUITO\s+{$mi_circuito}\b/i";
            if (preg_match($pattern, $circuito_vacante)) return true;
        }
        return false;
    }

    private function sendNotificationV1($user, $vacante) {
        $this->warn("Enviando notificación v1 a {$user->email} por vacante id: {$vacante['id']}");
        
        $jsonPath = storage_path('app/firebase-auth.json');
        if (!file_exists($jsonPath)) {
            $this->error('Archivo firebase-auth.json no encontrado en storage/app/');
            return;
        }

        // 1. Obtener Token de Acceso OAuth2
        $scopes = ['https://www.googleapis.com/auth/firebase.messaging'];
        $credentials = new ServiceAccountCredentials($scopes, $jsonPath);
        $tokenArray = $credentials->fetchAuthToken(HttpHandlerFactory::build());
        $accessToken = $tokenArray['access_token'];

        // 2. Obtener el Project ID del JSON
        $firebaseConfig = json_decode(file_get_contents($jsonPath), true);
        $projectId = $firebaseConfig['project_id'];

        // 3. Enviar mensaje usando estructura v1
        $url = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";
        
        $response = Http::withToken($accessToken)->post($url, [
            'message' => [
                'token' => $user->fcm_token,
                'notification' => [
                    'title' => '¡Nueva Vacante Encontrada! ✨',
                    'body' => "Se publicó: " . ($vacante['cargos'] ?? 'Nuevo cargo'),
                ],
                'data' => [
                    'vacante_id' => (string)$vacante['id'],
                ],
                'android' => [
                    'priority' => 'high',
                    'notification' => [
                        'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                    ]
                ]
            ]
        ]);

        if ($response->successful()) {
            $this->info("Notificación enviada con éxito.");
        } else {
            $this->error("Error al enviar notificación: " . $response->body());
        }
    }
}
