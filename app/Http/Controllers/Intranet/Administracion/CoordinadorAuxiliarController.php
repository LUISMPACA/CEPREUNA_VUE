<?php

namespace App\Http\Controllers\Intranet\Administracion;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Auxiliar;
use App\Models\AuxiliarCoordinador;
use App\VueTables\EloquentVueTables;
use DB;

class CoordinadorAuxiliarController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        return view('intranet.administracion.coordinador-auxiliar');
    }

    public function lista(Request $request)
    {
        $table = new EloquentVueTables;
        $data = $table->get(new User, [
            'users.id',
            'users.name',
            'users.paterno',
            'users.materno',
            'users.dni',
        ], []);

        $data = $data->join('model_has_roles as mr', 'mr.model_id', 'users.id')
            ->join('roles as r', 'r.id', 'mr.role_id')
            ->where('r.id', '5');


        if (!isset($request->orderBy)) {
            $data = $data->orderBy('users.id', 'asc');
        }
        $response = $table->finish($data);
        return response()->json($response);
    }
    public function lista_auxiliar(Request $request)
    {
        $table = new EloquentVueTables;
        $data = $table->get(new Auxiliar, [
            'auxiliares.id',
            'auxiliares.users_id',
        ], ['user']);

        $auxiliarNot = AuxiliarCoordinador::where("users_id","!=",$request->user)->get();
        $auxiliar = [];
        foreach ($auxiliarNot as $key => $value) {
            $auxiliar[] = $value->auxiliares_id;
        }

        $data = $data->whereNotIn("auxiliares.id",$auxiliar);

        if (!isset($request->orderBy)) {
            $data = $data->orderBy('auxiliares.id', 'asc');
        }
        $response = $table->finish($data);
        return response()->json($response);
    }
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // dd($request);
        $rules = $request->validate([
            'id' => 'required',

        ],$messages = [
            'required' => '* El campo :attribute es obligatorio.',
        ]);
        DB::beginTransaction();
        try {

            AuxiliarCoordinador::where("users_id",$request->id)->delete();
            if(count($request->auxiliares)>0)
            {
                foreach ($request->auxiliares as $key => $value) {
                    # code...
                    $data = new  AuxiliarCoordinador;
                    $data->auxiliares_id = $value;
                    $data->users_id = $request->id;
                    $data->save();
                }

            }



            DB::commit();
            $response["message"] = 'Guardado';
            $response["status"] = true;

        } catch (\Exception $e) {
            DB::rollback();
            $response["message"] =  'Error al Guardar, intentelo nuevamante.';
            $response["status"] =  false;
        }

        return $response;
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $data = AuxiliarCoordinador::where("users_id",$id)->get();
        $auxiliar = [];
        foreach ($data as $key => $value) {
            $auxiliar[] = $value->auxiliares_id;
        }
        $response["data"] = $auxiliar;
        return $response;
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
