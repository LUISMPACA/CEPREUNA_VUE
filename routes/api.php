<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Route::group(['prefix' => 'estudiante'], function () {
//     Route::get('/get-estudiante', 'Api\Estudiante\PerfilController@getEstudiante');
//     Route::get('/get-carga', 'Api\Estudiante\CursosController@getCarga');
// });
// Route::group(['prefix' => 'horario'], function () {
//     Route::get('/get-horario', 'Api\Estudiante\HorarioController@getHorario');
// });
// Route::group(['prefix' => 'social'], function () {
//     // Route::get('/token', 'Api\RedSocialController@token');
//     Route::get('/validate', 'Api\RedSocialController@validator');
// });
// Route::group(['prefix' => 'asistencia-estudiante'], function () {
//     Route::get('/get-asistencia', 'Api\Estudiante\AsistenciaController@getAsistencia');
// });
Route::group(['prefix' => 'pagos'], function () {
    // Route::get('/get-data', 'Api\Estudiante\PagoController@getDataPagos');
    Route::post('/validar-pago-cuota/{id}', 'Api\Estudiante\PagoController@validarPagoCuota');
});
Route::group(['prefix' => 'perfil'], function () {
    // Route::get('/get-data', 'Api\Estudiante\PagoController@getDataPagos');
    Route::post('/guardar-foto/{id}', 'Api\Estudiante\PerfilController@guardarFoto');
    Route::get('/encrypt/{id}', 'Api\Estudiante\PerfilController@encrypt');
});

Route::get('v1/{dni}',function(Request $request, $dni){
    if($request->header('Authorization')=="cepreuna_v1_api")
        return DB::select("SELECT concat(pe.inicio_ciclo,' - ',pe.fin_ciclo) AS periodo,pe.estado estado_periodo ,es.nro_documento,es.nombres, es.paterno, es.materno,es.celular ,case sexo when 1 then 'M' when 2 then 'F' END AS sexo ,ub.departamento,ub.provincia,ub.distrito ,
        anio_egreso,co.denominacion AS name_cole, co.direccion AS dir_cole, co.departamento AS dep_cole,co.provincia AS pro_cole,co.distrito AS dis_cole,tc.denominacion AS tipo_cole,
        ap.nro_documento AS apo_documento,ap.nombres AS apo_nombres,ap.paterno AS apo_paterno,ap.materno AS apo_materno,ap.celular apo_celular,pa.denominacion as apo_parentesco  FROM estudiantes es
        INNER JOIN inscripciones ins ON ins.estudiantes_id=es.id
        INNER JOIN periodos pe ON pe.id=ins.periodos_id
        INNER JOIN ubigeos ub ON es.ubigeos_id = ub.id
        INNER JOIN colegios co ON co.id = es.colegios_id
        INNER JOIN tipo_colegios tc ON tc.id = co.tipo_colegios_id
        INNER JOIN estudiante_apoderados ea ON ea.estudiantes_id=es.id
        INNER JOIN apoderados ap ON ap.id=ea.apoderados_id
        INNER JOIN parentescos pa ON ap.parentescos_id=pa.id
        where es.nro_documento=?",[$dni]);
});
