<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use App\Models\Estudiante;
use App\Models\Inscripciones;
use App\Models\Matricula;
use App\Models\TarifaEstudiante;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;

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

            // Generate message content
            $dni = $request->input('dni');
            $datosDNI = $this->obtenerDatosPorDNI($dni);
            $messageContent = $request->input('content') . " Datos para el DNI {$dni}: " . json_encode($datosDNI);
            //return $datosDNI;
            // Add a message to the thread
            $messageResponse = $this->openAIClient->post("threads/{$threadId}/messages", [
                'json' => [
                    'role' => 'user',
                    'content' => $messageContent,
                ],
            ]);

            $messageData = json_decode($messageResponse->getBody()->getContents(), true);

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

            return response()->json([
                'thread' => $threadData,
                'message' => $messageData,
                'runStatus' => $runStatus,
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

            if ($estu) {
                $inscripcion = Inscripciones::where("estudiantes_id", $estu->id)->first();
                $estudiante = $inscripcion->estudiante()->select('id','nombres', 'paterno', 'materno', 'nro_documento','usuario', 'password')->with('colegio')->first();
                $matricula =Matricula::select('habilitado as validado', 'habilitado_estado as habilitado','grupo_aulas_id')->where("estudiantes_id", $estu->id)->first();
                $auxiliar = DB::table('auxiliar_grupos as ag')->join('auxiliares as a', 'a.id', '=', 'ag.auxiliares_id')->join('users as u', 'u.id', '=', 'a.users_id')->where('ag.grupo_aulas_id', $matricula->grupo_aulas_id)->select('a.telefono as celular', 'u.name','u.paterno','u.materno')->first();
                $area = $inscripcion->area()->first();
                $sede = $inscripcion->sede()->first();
                $periodo = $inscripcion->periodo()->first();
                $turno = $inscripcion->turno()->first();
                $pago = $inscripcion->pago()->get();
                $inscripcionPagos = $inscripcion->inscripcionPago()->with('conceptoPago')->orderBy('concepto_pagos_id')->get();
                $sumaTotalPagos = $inscripcion->inscripcionPago()->sum('monto');
                $tarifaEstudiante = TarifaEstudiante::where("estudiantes_id", $estudiante->id)->get();

                return [
                    //"inscripcion" => $inscripcion,
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
}
