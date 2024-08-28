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

        $this->llamaClient = new Client([
            'base_uri' => 'https://api.llama-api.com/',
            'headers'  => [
                'Authorization' => 'Bearer ' . "LA-d59d6593e63c4e588573023502ce524e010552be957042b19fd1bad801d714dd",
                'Content-Type'  => 'application/json',
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

            //intentos
            $intentos = ChatLog::where('nro_documento', $dni)->orderBy('created_at', 'desc')->first();
            if ($intentos && $intentos->remaining_responses <= 0) {
                return response()->json(['error' => 'Has alcanzado el límite de respuestas diarias.'], 403);
            } 

            // Fetch previous conversation context
            $chatLog = ChatLog::where('nro_documento', $dni)->orderBy('created_at', 'asc')->get();
            
            $conversationHistory = $chatLog->map(function ($log) {
                return [
                    'role' => 'user',
                    'content' => $log->user_message,
                ];
            })->toArray();

            // Append the new user message to the conversation history
            $conversationHistory[] = [
                'role' => 'user',
                'content' => $request->input('content'),
            ];

            // Send the entire conversation history as individual messages to the API
            foreach ($conversationHistory as $message) {
                $this->openAIClient->post("threads/{$threadId}/messages", [
                    'json' => $message,
                ]);
            }

            // Create a Run to process the thread (without the conversation_history parameter)
            $runResponse = $this->openAIClient->post("threads/{$threadId}/runs", [
                'json' => [
                    'assistant_id' => 'asst_pjuDvtjfR0PpssuFYQWnvHs7', // Reemplaza con el ID de tu asistente
                    'instructions' => $request->input('instructions', ''),
                ],
            ]);
            
            $language = 'es';
            $question = $request->input('content');
            $filePath = public_path('images/archivo.txt');
            // Leer el contenido del archivo .txt
            $fileContent = file_get_contents($filePath);

            // Convertir el contenido a UTF-8
            $fileContent = mb_convert_encoding($fileContent, 'UTF-8', 'auto');

            // Limpiar caracteres que puedan causar problemas
            $fileContent = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $fileContent);
            $apiRequestJson = $this->prepareRequest($fileContent, $question, $language);

            // $response = $this->llamaClient->post('chat/completions', [
            //     'json' => $apiRequestJson,
            // ]);

            $runData = json_decode($runResponse->getBody()->getContents(), true);
            $runId = $runData['id'] ?? null;

            if (!$runId) {
                return response()->json(['error' => 'Unable to create run.'], 500);
            }

            // Polling for Run Completion
            $runStatus = $this->pollRunStatus($threadId, $runId);

            $promises = [
               'openai' => $this->openAIClient->getAsync("threads/{$threadId}/messages"),
                'llamaia' => $this->llamaClient->postAsync('chat/completions', [
                    'json' => $apiRequestJson
                ]),
            ];

            $results = \GuzzleHttp\Promise\settle($promises)->wait();

            return $results ;

            // Get messages from the thread
            //$messagesResponse = $this->openAIClient->get("threads/{$threadId}/messages");


            $messagesData = json_decode($messagesResponse->getBody()->getContents(), true);
            //return $messagesData;
    
            // Get the assistant's latest response
            $firstMessage = $messagesData['data'][0]['content'][0]['text']['value'];
            // Calcular remaining_responses
            $assistantResponse = '';
            if (isset($messagesData['data']) && is_array($messagesData['data'])) {
                foreach ($messagesData['data'] as $message) {
                    if (isset($message['content']) && is_array($message['content'])) {
                        foreach ($message['content'] as $contentPart) {
                            if (isset($contentPart['text']['value'])) {
                                $assistantResponse .= $contentPart['text']['value'] . "\n";
                            }
                        }
                    }
                }
            }

            // Calcular remaining_responses
            $remaining_responses = $chatLog->isNotEmpty() ? max($chatLog->last()->remaining_responses - 1, 0) : 9;
        
            //Guardar el log en la base de datos
            $chatLog = ChatLog::create([
                'nro_documento' => $dni,
                'user_message' => $request->input('content'),
                'assistant_response' => $firstMessage,
                'llamaia_response' => $reponsellamaia,
                'remaining_responses' => $remaining_responses,
            ]);

            return response()->json([
                'id' => $chatLog->id,
                'messagesOpenIA' => $messagesData,
                'messagesLlamaIA' => $reponsellamaia
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
        $delay = 3; // seconds

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

    public function processLLamaAssitans($question)
    {
        try {
            $language = 'es';

            $filePath = public_path('images/archivo.txt');
            // Leer el contenido del archivo .txt
            $fileContent = file_get_contents($filePath);

            // Convertir el contenido a UTF-8
            $fileContent = mb_convert_encoding($fileContent, 'UTF-8', 'auto');

            // Limpiar caracteres que puedan causar problemas
            $fileContent = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $fileContent);
            $apiRequestJson = $this->prepareRequest($fileContent, $question, $language);

            $response = $this->llamaClient->post('chat/completions', [
                'json' => $apiRequestJson,
            ]);

            $responseJson = json_decode($response->getBody()->getContents(), true);

            // Obtener el contenido de la respuesta del asistente
            if (isset($responseJson['choices'][0]['message']['content'])) {
                $assistantResponse = $responseJson['choices'][0]['message']['content'];
            } else {
                $assistantResponse = "No se encontró el contenido esperado.";
            }

            return $assistantResponse; // Devolver solo el contenido del asistente

        } catch (RequestException $e) {
            // Manejar el error de solicitud
            return 'Unable to process request: ' . $e->getMessage();
        } catch (\Exception $e) {
            // Manejar errores generales
            return 'An error occurred: ' . $e->getMessage();
        }
    }

    private function prepareRequest($fileContent, $question, $language)
    {
        return [
            "model" => "llama-13b-chat",
            "messages" => [
                [
                    "role" => "user",
                    "content" => "Por favor, responde en {$language} y con ayuda de emojis. Sobre esta información: '{$fileContent}', {$question}"
                ]
            ],
            "max_tokens" => 200,
            "stream" => false
        ];
    }


    private function obtenerDatosPorDNI($dni)
    {
        try {
            $estu = Estudiante::select("estudiantes.*")
                ->join("matriculas as m", "m.estudiantes_id", "estudiantes.id")
                ->where("nro_documento", $dni)
                ->first();

            $constancia = "";
            $preinscripcion = "";

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
                    $preinscripcion = 'https://inscripciones.admision.unap.edu.pe/pdf-solicitud/10/'.$estu->nro_documento;
                }

                return [
                    "constancia" => $constancia,
                    "preinscripcion" => $preinscripcion,
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
