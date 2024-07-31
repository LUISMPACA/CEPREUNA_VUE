<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use App\Models\Estudiante;
use App\Models\Inscripciones;
use App\Models\Matricula;
use App\Models\TarifaEstudiante;
use App\Models\ChatLog;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;

class OpenAIController extends Controller
{
    protected $openAIClient;

    public function __construct()
    {
        $this->openAIClient = new Client([
            'base_uri' => 'https://api.openai.com/v1/',
            'headers'  => [
                'Authorization' => 'Bearer ' . env('OPENAI_API_KEY'),
                'Content-Type'  => 'application/json',
                'OpenAI-Beta'   => 'assistants=v2',
            ],
        ]);
    }

    public function createThreadAndRun(Request $request)
{
    try {

        $dni = $request->input('dni');
        if (!$dni) {
            return response()->json(['error' => 'DNI is required'], 400);
        }

        // Generate message content
        $datosDNI = $this->obtenerDatosPorDNI($dni);
        if (!($datosDNI['status'])) {
            return response()->json(['error' => 'No se encontro el DNI'], 500);
        }
        // Check if there is an existing thread ID in the session
        $threadId = Session::get('thread_id');

        // If no thread ID in the session, create a new thread
        if (!$threadId) {   
            $response = $this->openAIClient->post('threads', ['json' => []]);
            $threadData = json_decode($response->getBody()->getContents(), true);
            $threadId = $threadData['id'] ?? null;

            if (!$threadId) {
                return response()->json(['error' => 'Unable to create thread.'], 500);
            }

            // Store the thread ID in the session
            Session::put('thread_id', $threadId);
        }

        // Fetch previous messages if exist
        $previousMessages = [];
        $messagesResponse = $this->openAIClient->get("threads/{$threadId}/messages");
        $messagesData = json_decode($messagesResponse->getBody()->getContents(), true);
        
        if (isset($messagesData['data']) && count($messagesData['data']) > 0) {
            foreach ($messagesData['data'] as $message) {
                $previousMessages[] = $message['content'];
            }
        }

        

        $messageContent = $request->input('content') . " Datos para el DNI {$dni}: " . json_encode($datosDNI);

         // Verificar si el usuario ha alcanzado el límite de respuestas
         $chatLog = ChatLog::where('nro_documento', $dni)->orderBy('created_at', 'desc')->first();
         if ($chatLog && $chatLog->remaining_responses <= 0) {
             return response()->json(['error' => 'Has alcanzado el límite de 10 respuestas.'], 403);
         }    
        
        // Add previous messages to the current message
        $completeMessageContent = implode("\n", $previousMessages) . "\n" . $messageContent;

        // Add a message to the thread
        $messageResponse = $this->openAIClient->post("threads/{$threadId}/messages", [
            'json' => [
                'role' => 'user',
                'content' => $completeMessageContent,
            ],
        ]);

        // Create a Run to process the thread
        $runResponse = $this->openAIClient->post("threads/{$threadId}/runs", [
            'json' => [
                'assistant_id' => 'asst_vQFHRgbHNZeUjC77pX7gqmya', // Reemplaza con el ID de tu asistente
                'instructions' => $request->input('instructions', ''),
            ],
        ]);

        
        $runData = json_decode($runResponse->getBody()->getContents(), true);
        $runId = $runData['id'] ?? null;

        if (!$runId) {
            return response()->json(['error' => 'Unable to create run.'], 500);
        }

        // Polling for Run Completion
        $runStatus = $this->pollRunStatus($threadId, $runId);

        // Get messages from the thread
        $messagesResponse = $this->openAIClient->get("threads/{$threadId}/messages");
        $messagesData = json_decode($messagesResponse->getBody()->getContents(), true);

        // Calcular remaining_responses
        $remaining_responses = $chatLog ? max($chatLog->remaining_responses - 1, 0) : 9;

        // Obtener la respuesta del asistente
        $firstMessage = $messagesData['data'][0]['content'][0]['text']['value']; // Asegúrate de que esta línea sea correcta
        //return $firstMessage;
        $estu = Estudiante::select("estudiantes.id") ->where("nro_documento", $dni)->first();
        // Guardar el log en la base de datos
        ChatLog::create([
            'nro_documento' => $dni,
            'user_message' => $request->input('content'),
            'assistant_response' => $firstMessage,
            'remaining_responses' => $remaining_responses
        ]);

        return response()->json([
            'messages' => $messagesData,
        ]);
    } catch (RequestException $e) {
        return response()->json([
            'error' => 'Unable to process request.',
            'message' => $e->getMessage(),
        ], $e->getCode() ?: 500);
    }
}


    private function pollRunStatus($threadId, $runId)
    {
        $status = null;
        $attempts = 0;
        $maxAttempts = 10;
        $delay = 5; // seconds

        while ($attempts < $maxAttempts) {
            try {
                $response = $this->openAIClient->get("threads/{$threadId}/runs/{$runId}");
                $statusData = json_decode($response->getBody()->getContents(), true);
                $status = $statusData['status'] ?? 'unknown';

                if ($status === 'completed') {
                    return $statusData;
                }
            } catch (RequestException $e) {
                // Handle exception or log error
            }

            sleep($delay);
            $attempts++;
        }

        return ['status' => 'failed', 'message' => 'Run did not complete within the expected time.'];
    }

    private function obtenerDatosPorDNI($dni)
    {
        try {
            $estu = Estudiante::select("estudiantes.*")
                ->join("matriculas as m", "m.estudiantes_id", "estudiantes.id")
                ->where("nro_documento", $dni)
                ->first();

            $constancia = "";

            if ($estu) {
                $inscripcion = Inscripciones::where("estudiantes_id", $estu->id)->first();
                $estudiante = $inscripcion->estudiante()->select('id','nombres', 'paterno', 'materno', 'nro_documento','usuario', 'password')->with('colegio')->first();
                $matricula =Matricula::select('id','habilitado as validado', 'habilitado_estado as habilitado','grupo_aulas_id')->where("estudiantes_id", $estu->id)->first();
                $auxiliar = DB::table('auxiliar_grupos as ag')->join('auxiliares as a', 'a.id', '=', 'ag.auxiliares_id')->join('users as u', 'u.id', '=', 'a.users_id')->where('ag.grupo_aulas_id', $matricula->grupo_aulas_id)->select('a.telefono as celular', 'u.name','u.paterno','u.materno')->first();
                $area = $inscripcion->area()->first();
                $sede = $inscripcion->sede()->first();
                $periodo = $inscripcion->periodo()->first();
                $turno = $inscripcion->turno()->first();
                $pago = $inscripcion->pago()->get();
                $inscripcionPagos = $inscripcion->inscripcionPago()->with('conceptoPago')->orderBy('concepto_pagos_id')->get();
                $sumaTotalPagos = $inscripcion->inscripcionPago()->sum('monto');
                $tarifaEstudiante = TarifaEstudiante::where("estudiantes_id", $estudiante->id)->get();
                
                if($matricula->habilitado == "1"){
                    $constancia = 'https://sistemas.cepreuna.edu.pe/dga/estudiantes/pdf-constancia/'.Crypt::encryptString($matricula->id);
                }

                return [
                    "constancia" => $constancia,
                    "estudiante" => $estudiante,
                    "matricula" => $matricula,
                    "area" => $area->denominacion,
                    "sede" => $sede->denominacion,
                    "auxiliar" => $auxiliar,
                    "turno" => $turno->denominacion,
                    "pagos" => $pago,
                    "inscripcionPagos" => $inscripcionPagos,
                    "sumaTotalPagos" => $sumaTotalPagos,
                    "tarifaEstudiante" => $tarifaEstudiante,
                    "status" => true,
                    "message" => "",
                ];
            } else {
                return [
                    "status" => false,
                    "message" => "Estudiante no encontrado",
                ];
            }
        } catch (\Exception $e) {
            return ['error' => 'Unable to fetch data from database.', 'message' => $e->getMessage()];
        }
    }

    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if ($estudiante = Estudiante::where('usuario', $credentials['email'])->first()) {

            // Verificar si se encontró el estudiante y la contraseña coincide
            if ($estudiante->usuario == $credentials['email'] && $estudiante->password == $credentials['password']) {
                // para el formulario
                $estudiante = Estudiante::join('inscripciones', 'estudiantes.id', '=', 'inscripciones.estudiantes_id')
                    ->select('estudiantes.nro_documento',)
                    ->where('estudiantes.usuario', $credentials['email'])
                    ->where('estudiantes.password', $credentials['password'])
                    ->first();

                $response = json_decode($estudiante, true);
                return view('web.asistente.chat', ['estudiante' => $response]);
                
            }
        }

        return redirect()->back()->with('error', 'El correo o la contraseña ingresados son incorrectos. Por favor, verifica tus credenciales y vuelve a intentarlo.');
    }
}
