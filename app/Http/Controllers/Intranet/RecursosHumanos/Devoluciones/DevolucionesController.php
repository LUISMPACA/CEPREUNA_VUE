<?php

namespace App\Http\Controllers\Intranet\RecursosHumanos\Devoluciones;

use App\Http\Controllers\Controller;
use App\Models\Ciclo;
use App\Models\User;
use App\Models\Devoluciones;
use App\VueTables\EloquentVueTables;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;

class DevolucionesController extends Controller
{

    private $dateTime;
    private $dateTimePartial;
    public function __construct()
    {
        date_default_timezone_set("America/Lima"); //Zona horaria de Peru
        $this->dateTime = date("Y-m-d H:i:s");
        $this->dateTimePartial = date("Y");
    }

    public function index()
    {
        $permissions = [];
        if (auth()->user()->hasRole('Super Admin')) {
            foreach (Permission::get() as $key => $value) {
                array_push($permissions, $value->name);
            }
        } else {
            foreach (Auth::user()->getAllPermissions() as $key => $value) {
                array_push($permissions, $value->name);
            }
        }

        $response['permisos'] = json_encode($permissions);
        $response['external_url'] = json_encode(env("EXTERNALURL"));
        $response['users'] = json_encode(User::select("id", "name")->get());
        return view("intranet.recursosHumanos.devoluciones.devoluciones", $response);
    }

    public function lista(Request $request)
    {
        $table = new EloquentVueTables;
        $data = $table->get(new Devoluciones(), ['*']);
        $response = $table->finish($data);
        return response()->json($response);
    }

    public function store(Request $request)
    {
        
        return $request;
        $rules = $request->validate([
            'denominacion' => 'required',
            'estado' => 'required',
        ], $messages = [
            'required' => '* El campo :attribute es obligatorio.',
        ]);
        DB::beginTransaction();
        try {

            $data = new Grupo;
            $data->denominacion = $request->denominacion;
            $data->estado = (string)$request->estado;
            $data->save();



            DB::commit();
            $response["message"] = 'Registro guardado correctamente';
            $response["status"] = true;
        } catch (\Exception $e) {
            DB::rollback();
            $response["message"] =  'Error al guardar registro, intentelo nuevamante.';
            $response["status"] =  false;
        }
        return $response;
    }
}