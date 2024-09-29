<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Services\GWorkspace;
use App\Models\ControlCron;

class MatricularDocentes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'docente:matricular';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Matricular docentes en Google Classroom';

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
        $url = "matricular-docente-" . time() . ".txt";
        Storage::disk("crons")->append($url, "Iniciando sincronización...");

        $apiGsuite = new GWorkspace();

        $consulta = DB::select("SELECT ca.id, ca.idclassroom, da.idgsuite 
        FROM carga_academicas AS ca 
        JOIN docente_aptos AS da ON da.docentes_id = ca.docentes_id 
        JOIN grupo_aulas AS ga ON ga.id = ca.grupo_aulas_id");
        // LIMIT 10000 OFFSET 1990;");
              // -- WHERE ga.turnos_id = 1");

        $totalRegistros = count($consulta);
        $ejecutadoRegistros = 0;

        $control = new ControlCron;
        $control->total_registros = $totalRegistros;
        $control->ejecutado_registros = $ejecutadoRegistros;
        $control->tipo = 20;
        $control->estado = '0';
        $control->url = $url;
        $control->users_id = 6;
        $control->save();

        foreach ($consulta as $value) {

            // Verificar si idclassroom es null
            if ($value->idclassroom === null) {
                continue; // Saltar este elemento y pasar al siguiente
            }

            $datos = json_encode([
                "courseId" => $value->idclassroom,
                "userId" => $value->idgsuite,
            ]);
            
            $correoGenerado = json_decode($apiGsuite->matricularDocente($datos));

            
            //var_dump($correoGenerado);
            //var_dump($deco); 
            
            if (isset($correoGenerado)) {
                $deco = $correoGenerado->message;
                if ($correoGenerado->success) {
                    $this->info($value->id . ' - ' ."se genero");
                    $status = true;
                    $message = "se genero classRoom";
                } else {
                    $deco = json_decode($correoGenerado->message);
                    $this->error($value->id . ' - ' . $deco->error->message);
                    $status = false;
                    $message = $deco->error->message;
                }
            } else {
                $this->error('No se pudo obtener el estado del correo generado.');
                $status = false;
                $message = 'Error desconocido al obtener el estado del correo generado.';
            }
            

            $texto = "[" . date('Y-m-d H:i:s') . "]:  registration synchronization with id:" . $value->id . ' status: ' . ($status ? 'Success' : 'Error') . ' message: ' . $message;
            Storage::disk("crons")->append($url, $texto);

            $ejecutadoRegistros++;
            $control->ejecutado_registros = $ejecutadoRegistros;
            $control->save();
        }

        $control->estado = '1';
        $control->save();
    }
}
