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
