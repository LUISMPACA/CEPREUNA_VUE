<?php

namespace App\Console\Commands;

use App\Models\AsistenciaEstudianteDetalle;
use App\Models\ControlCron;
use App\Models\Estudiante;
use App\Models\Inscripciones;
use App\Models\Matricula;
use App\Models\Criterio;
use App\Models\InscripcionDocente;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class MinCalificacionDocentes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'minCalificacion:task';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reprogramacion de calificacion de minimo nota docentes';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // $apiGsuite = new GWorkspace();

        $puntaje = Criterio::where([["denominacion","puntaje"],["tipo","1"],["estado","1"]])->first();
        $data =  InscripcionDocente::where([["puntaje",">=",$puntaje->puntaje],["apto","0"]])->get();
        $url = "minCalificacion-" . time() . ".txt";

        Storage::disk("crons")->append($url, "Iniciando sincronización...");

        $control = new ControlCron();
        $control->total_registros = count($data);
        $control->ejecutado_registros = 0;
        $control->tipo = 10;
        $control->estado = '0';
        $control->url = $url;
        $control->users_id = Auth::user()->id;
        $control->save();

        foreach ($data as $key => $value) {

            
            DB::beginTransaction();
            try {

                DB::commit();
                $message = 'Sincronización realizada con exito.';
                $status = true;

                $notaDocente = InscripcionDocente::find($value->id);
                $notaDocente->apto =  '1';
                $notaDocente->save();
            } catch (\Exception $e) {
                DB::rollback();
                $message = 'Error al sincronizar - ' . $e->getMessage();
                $status = false;

                $notaDocente = InscripcionDocente::find($value->id);
                $notaDocente->apto =  "0";
                $notaDocente->save();
            }

            $response["message"] =  $message;
            $response["status"] =  $status;

            $cronActual = ControlCron::find($control->id);
            $cronActual->ejecutado_registros = $key + 1;
            $cronActual->save();

            $texto = "[" . date('Y-m-d H:i:s') . "]:  registration synchronization with id:" . $value->id . ' status: ' . $response["status"] . ' message: ' . $response["message"];
            Storage::disk("crons")->append($url, $texto);
        }

        $cronActual = ControlCron::find($control->id);
        $cronActual->estado = '1';
        $cronActual->save();
    }
}
