<?php

namespace App\Http\Controllers\Intranet\RecursosHumanos\Devoluciones;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\TPControlCron;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class DevolucionesController extends Controller
{
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
}