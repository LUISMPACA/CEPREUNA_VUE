<?php

namespace App\Http\Controllers\Intranet\RecursosHumanos\Devoluciones;

use App\Http\Controllers\Controller;
use App\Models\Ciclo;
use App\Models\User;
use App\Models\Pago;
use App\Models\BancoPago;
use App\Models\Periodo;
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
        //return $request;
        // Inicializar variables
        $tokens = $request->tokens;
        $pagoExistente = true;
        $documentoValidado = true;
        $sumaPagoDB = 0;
        $comisionBanco = 0;
    
        // Verificar si existen tokens
        if (!empty($tokens)) {
            foreach ($tokens as $token) {
                // Validar el pago en la base de datos
                $validarPago = Pago::where('token', $token)->first();
                $comisionBanco++;
    
                // Si no se encuentra el pago, marcar como inexistente
                if (!$validarPago) {
                    $pagoExistente = false;
                    break;
                }
    
                // Verificar que el estado del pago sea válido (estado '1')
                if ($validarPago->estado == '1') {
                    $sumaPagoDB += $validarPago->monto;
    
                    // Validar el pago con el número de documento del estudiante
                    $validarDocumento = BancoPago::where([
                        ['secuencia', $validarPago->secuencia],
                        ['imp_pag', $validarPago->monto],
                        ['fch_pag', $validarPago->fecha],
                        ['num_doc', str_pad($request->nro_documento, 15, '0', STR_PAD_LEFT)],
                    ])->first();
    
                    if (!$validarDocumento) {
                        $documentoValidado = false;
                        break;
                    }
                } else {
                    $pagoExistente = false;
                    break;
                }
            }
        } else {
            $pagoExistente = false;
        }
    
        // Definir el monto total a pagar
        $totalPagar = round(11, 2);
    
        // Si no existe el pago o hubo algún error de validación
        if (!$pagoExistente) {
            return response()->json([
                "message" => '* El pago no existe o no esta registrado en la base de datos.',
                "status" => false,
            ]);
        }
    
        // Validar que el documento sea correcto y que el monto cubra el total a pagar
        if ($documentoValidado) {
            // Usar transacción para guardar los cambios de manera atómica
            DB::beginTransaction();
            try {
                foreach ($tokens as $token) {
                    $pago = Pago::where('token', $token)->first();
    
                    if ($pago) {
                        // Actualizar estado del pago a '2'
                        $pago->estado = '2';
                        $pago->save();
    
                        // Aquí puedes guardar los detalles del pago en la misma u otra tabla si es necesario
                    }
                }
    
                //aqui llenar la tabla de devolciones
                Devoluciones::create([
                    'nombres' => $request->nombres,  // Asumiendo que 'nombres' viene en la request
                    'nro_documento' => $request->nro_documento,  // Documento del usuario
                    'fecha' => $request->fecha,  // Fecha actual
                    'nro_registro' => $request->nro_registro,  // O algún otro número de registro relevante
                    'procede' => $request->procede,  // En trámite
                    'pagos_id' => $pago->id,  // Relación con la tabla Pagos
                ]);

                DB::commit(); // Confirmar transacción
    
                return response()->json([
                    "message" => 'Registro de devolución de pago satifactorio.',
                    "status" => true,
                ]);
            } catch (\Exception $e) {
                DB::rollback(); // Revertir transacción en caso de error
                return response()->json([
                    "message" => 'Error al registrar devolución, inténtelo nuevamente.',
                    "status" => false,
                    "error" => $e->getMessage(),
                ]);
            }
        }
    
        // Si el documento no fue validado correctamente
        if (!$documentoValidado) {
            return response()->json([
                "message" => '* Error al validar pago, Ud. está intentando ingresar un pago que no corresponde al DNI.',
                "status" => false,
            ]);
        }
    
        // Si el monto total no es suficiente
        // return response()->json([
        //     "message" => '* El monto total de pago no es válido, ingrese nuevamente el pago correspondiente.',
        //     "status" => false,
        // ]);
    }
    
}