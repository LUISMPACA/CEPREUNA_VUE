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
Route::group(['prefix' => 'v1/ficha'], function () {
    Route::post('/api-get-ficha', 'Api\Estudiante\FichaSimulacroController@getFicha');
});

Route::get('v1/{dni}', function (Request $request, $dni) {
    if ($request->header('Authorization') == "cepreuna_v1_api")
        return DB::select("SELECT concat(pe.inicio_ciclo,' - ',pe.fin_ciclo) AS periodo,pe.estado estado_periodo,
        es.nro_documento,es.nombres, es.paterno, es.materno,es.celular , sexo ,ub.departamento,
        ub.provincia,ub.distrito,ub.codigo_distrito ,anio_egreso,co.denominacion AS name_cole, co.direccion AS dir_cole, 
        co.departamento AS dep_cole,co.provincia AS pro_cole,co.distrito AS dis_cole,tc.denominacion AS tipo_cole, co.tipo_colegios_id AS tipo, 
        ap.nro_documento AS apo_documento,ap.nombres AS apo_nombres,ap.paterno AS apo_paterno,ap.materno AS apo_materno,ap.celular apo_celular,
        CASE WHEN ap.parentescos_id = 1 THEN 1 WHEN ap.parentescos_id = 2 THEN 2 ELSE 3 END AS apo_parentesco, ma.habilitado_estado AS habilitado FROM estudiantes es
        INNER JOIN inscripciones ins ON ins.estudiantes_id=es.id
        INNER JOIN periodos pe ON pe.id=ins.periodos_id
        LEFT  JOIN ubigeos ub ON es.ubigeos_id = ub.id
        INNER JOIN colegios co ON co.id = es.colegios_id
        INNER JOIN tipo_colegios tc ON tc.id = co.tipo_colegios_id
        LEFT JOIN estudiante_apoderados ea ON ea.estudiantes_id=es.id
        LEFT JOIN apoderados ap ON ap.id=ea.apoderados_id
        LEFT JOIN parentescos pa ON ap.parentescos_id=pa.id
        LEFT JOIN matriculas ma on  ma.estudiantes_id = es.id    
        WHERE es.nro_documento=?", [$dni]);
});

Route::get('v1/alumnos/inscritos', function (Request $request) {
    if ($request->header('Authorization') == "cepreuna_v1_api") {
        $response["sedes"] = DB::select("SELECT count(A.sedes_id) as cantidad, C.denominacion as sede  from inscripciones AS A
        join areas as B ON B.id = A.areas_id
        join sedes as C ON C.id = A.sedes_id
        join turnos as D on D.id = A.turnos_id
        group by A.sedes_id order by C.denominacion");

        $response["areas"] = DB::select("SELECT count(A.areas_id) as cantidad, B.denominacion as areas, C.denominacion as sede  from inscripciones AS A
        join areas as B ON B.id = A.areas_id
        join sedes as C ON C.id = A.sedes_id
        join turnos as D on D.id = A.turnos_id
        group by A.sedes_id, A.areas_id order by C.denominacion");

        $response["turnos"] = DB::select("SELECT count(A.turnos_id) as cantidad, D.denominacion, B.denominacion as areas, C.denominacion as sede  from inscripciones AS A
        join areas as B ON B.id = A.areas_id
        join sedes as C ON C.id = A.sedes_id
        join turnos as D on D.id = A.turnos_id
        group by A.sedes_id, A.areas_id, A.turnos_id order by C.denominacion");

        return $response;
    }
});
Route::get('v1/resultados/{dni}', function (Request $request, $dni) {
    if ($request->header('Authorization') == "cepreuna_v1_api")
        return DB::select("SELECT d.nro_documento, d.nombres, d.paterno, d.materno, id.apto, id.puntaje, id.modalidad  
        FROM inscripcion_docentes as id join docentes d on d.id = id.docentes_id
        where d.nro_documento=?", [$dni]);
});
Route::get('v1/inscripciones-consulta/{dni}', function ($dni) {
    if (request()->header('Authorization') !== "cepreuna_v1_api") {
        return response()->json(['error' => 'Unauthorized'], 401);
    }

    $results = DB::select("SELECT CONCAT(e.paterno, ' ', e.materno, ' ', e.nombres) 
    AS nombres, e.nro_documento, s.denominacion AS sede, l.denominacion 
    AS local, l.direccion, l.foto, a.codigo AS aula, g.denominacion AS grupo, t.denominacion 
    AS turno, ar.denominacion AS area FROM estudiantes AS e JOIN matriculas AS m ON m.estudiantes_id = e.id 
    JOIN grupo_aulas AS ga ON ga.id = m.grupo_aulas_id 
    JOIN grupos AS g ON g.id = ga.grupos_id 
    JOIN areas AS ar ON ar.id = ga.areas_id 
    JOIN turnos AS t ON t.id = ga.turnos_id 
    JOIN aulas AS a ON a.id = ga.aulas_id 
    JOIN locales AS l ON l.id = a.locales_id 
    JOIN sedes AS s ON s.id = l.sedes_id 
    WHERE e.nro_documento = ?", [$dni]);

    return response()->json($results);
});
Route::get('v1/alumnos/pagos', function (Request $request) {
    if ($request->header('Authorization') == "cepreuna_v1_api") {
        $response["sedes"] = DB::select("SELECT
        subquery.sedes AS sede,
        subquery.id_sede AS id_sede,
        COUNT(*) AS cantidad,
        CASE
            WHEN subquery.primera_mensualidad != subquery.monto1 AND subquery.primera_mensualidad != 0 THEN 'incompleto'
            WHEN subquery.primera_mensualidad = 0 THEN 'pagado'
            ELSE 'deuda'
        END AS denominacion,
        CASE
            WHEN subquery.primera_mensualidad != subquery.monto1 AND subquery.primera_mensualidad != 0 THEN 2
            WHEN subquery.primera_mensualidad = 0 THEN 1
            ELSE 3
        END AS estado
    FROM (
        SELECT
            estudiantes.nro_documento,
            s.denominacion AS sedes,
            s.id AS id_sede,
            tarifa_estudiantes1.monto AS monto1,
            SUM(tarifa_estudiantes.monto) AS pago_total,
            (tarifa_estudiantes1.monto - tarifa_estudiantes1.pagado) AS primera_mensualidad
        FROM inscripciones
        JOIN estudiantes ON estudiantes.id = inscripciones.estudiantes_id
        LEFT JOIN tarifa_estudiantes ON tarifa_estudiantes.estudiantes_id = estudiantes.id
        LEFT JOIN tarifa_estudiantes AS tarifa_estudiantes0 ON tarifa_estudiantes0.nro_cuota = 0 AND tarifa_estudiantes0.estudiantes_id = estudiantes.id
        LEFT JOIN tarifa_estudiantes AS tarifa_estudiantes1 ON tarifa_estudiantes1.nro_cuota = 1 AND tarifa_estudiantes1.estudiantes_id = estudiantes.id
        LEFT JOIN tarifa_estudiantes AS tarifa_estudiantes2 ON tarifa_estudiantes2.nro_cuota = 2 AND tarifa_estudiantes2.estudiantes_id = estudiantes.id
        LEFT JOIN tarifa_estudiantes AS tarifa_estudiantes3 ON tarifa_estudiantes3.nro_cuota = 3 AND tarifa_estudiantes3.estudiantes_id = estudiantes.id
        LEFT JOIN tarifa_estudiantes AS tarifa_estudiantes4 ON tarifa_estudiantes4.nro_cuota = 4 AND tarifa_estudiantes4.estudiantes_id = estudiantes.id
        JOIN sedes AS s ON s.id = inscripciones.sedes_id
        LEFT JOIN matriculas ON matriculas.estudiantes_id = estudiantes.id
        LEFT JOIN grupo_aulas ON grupo_aulas.id = matriculas.grupo_aulas_id
        LEFT JOIN areas ON areas.id = grupo_aulas.areas_id
        LEFT JOIN grupos ON grupos.id = grupo_aulas.grupos_id
        LEFT JOIN turnos ON turnos.id = grupo_aulas.turnos_id
        JOIN colegios ON colegios.id = estudiantes.colegios_id
        JOIN tipo_colegios ON tipo_colegios.id = colegios.tipo_colegios_id
        GROUP BY estudiantes.nro_documento, sedes
    ) AS subquery
    GROUP BY subquery.sedes, estado");

        // $response["areas"] = DB::select("SELECT count(A.areas_id) as cantidad, B.denominacion as areas, C.denominacion as sede  from inscripciones AS A
        // join areas as B ON B.id = A.areas_id
        // join sedes as C ON C.id = A.sedes_id
        // join turnos as D on D.id = A.turnos_id
        // group by A.sedes_id, A.areas_id order by C.denominacion");

        // $response["turnos"] = DB::select("SELECT count(A.turnos_id) as cantidad, D.denominacion, B.denominacion as areas, C.denominacion as sede  from inscripciones AS A
        // join areas as B ON B.id = A.areas_id
        // join sedes as C ON C.id = A.sedes_id
        // join turnos as D on D.id = A.turnos_id
        // group by A.sedes_id, A.areas_id, A.turnos_id order by C.denominacion");

        return $response;
    }
});

Route::get('examen-simulacro/asistencia', 'Web\Inscripcion\SimulacroController@tomarAsistencia');

Route::get('v1/resultados_simulacro/{dni}', function (Request $request, $dni) {
    if ($request->header('Authorization') == "cepreuna_v1_api") {
        // $fecha = '2023-12-30';

        //$resultados = DB::select("SELECT * FROM ingresantes WHERE fecha_examen = ? AND dni = ?", [$request->fecha, $dni]);
        $resultados = DB::table('ingresantes')
        ->where('fecha_examen', $request->fecha)
        ->where('dni', $dni)
        ->first();

        return response()->json($resultados);
    } else {
        return response()->json(['error' => 'Unauthorized'], 401);
    }
});

Route::get('v1/pagos/grupos/{ciclo}/{page}', function (Request $request, $ciclo, $page) {
    if ($request->header('Authorization') == "cepreuna_v1_api") {
        $limit = 80;
        $offset = ($page - 1) * $limit;
        $results = DB::select("SELECT id, fecha as fecha, created_at, nro_documento, secuencia, monto, voucher FROM pagos WHERE created_at>=? AND voucher NOT LIKE '%.pdf' order by fecha ", [$ciclo]);
        $total_count = count($results);
        $totalPages = ceil($total_count / $limit);
        $results = array_slice($results, $offset, $limit);

        $results_pdf = [];
        $results_pdf = DB::select("SELECT id, fecha as fecha, created_at, nro_documento, secuencia, monto, voucher FROM pagos WHERE created_at>=? AND voucher LIKE '%.pdf' order by fecha", [$ciclo]);
        $total_count_pdf = count($results_pdf);
        $totalPages_pdf = ceil($total_count_pdf / $limit);
        $results_pdf = array_slice($results_pdf, $offset, $limit);
        return response()->json([
            'total_pages' => $totalPages,
            'total_count' => $total_count,
            'results' => $results,
            'total_pages_pdf' => $totalPages_pdf,
            'total_count_pdf' => $total_count_pdf,
            'results_pdf' => $results_pdf
        ]);
    }
});

Route::get('v1/simulacro/datos', function (Request $request) {
    if ($request->header('Authorization') == "cepreuna_v1_api") {
        $pagosInRange = DB::table('banco_pagos')
                            ->where('imp_pag', '>=', 11)
                            ->where('imp_pag', '<', 20)
                            ->where('fch_pag', '>=', '2024-05-05')
                            ->count();

        $countSimulacros = DB::table('inscripcion_simulacros')->count();

        $countByArea = DB::table('inscripcion_simulacros as is2')
                            ->join('estudiantes as e', 'e.id', '=', 'is2.estudiantes_id')
                            ->join('inscripciones as i', 'e.id', '=', 'i.estudiantes_id')
                            ->join('areas as a', 'a.id', '=', 'i.areas_id')
                            ->select('a.denominacion as area', DB::raw('COUNT(a.id) as cantidad'))
                            ->groupBy('a.id')
                            ->get();

        $response = [
            'pagosInRange' => $pagosInRange,
            'countSimulacros' => $countSimulacros,
            'countByArea' => $countByArea,
        ];

        return response()->json($response);
    }
});
