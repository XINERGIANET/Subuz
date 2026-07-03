<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ApiDocumentController extends Controller
{
    public function apiReniec(Request $request)
    {
        $dni = (string) $request->query('dni', '');
        if (!preg_match('/^\d{8}$/', $dni)) {
            return response()->json([
                'status' => false,
                'message' => 'DNI inválido.',
            ], 422);
        }

        $response = Http::withToken((string) config('apireniec.key'))
            ->timeout(15)
            ->post((string) config('apireniec.url'), [
                'dni' => $dni,
            ]);

        if (!$response->successful()) {
            return response()->json([
                'status' => false,
                'message' => 'No se pudo consultar RENIEC.',
            ], 422);
        }

        $data = (array) $response->json();
        $estado = (bool) ($data['success'] ?? false);
        $resultado = (array) ($data['data'] ?? []);
        $mensaje = (string) ($data['message'] ?? '');

        if (!$estado || empty($resultado)) {
            return response()->json([
                'status' => false,
                'message' => $mensaje !== '' ? $mensaje : 'No se encontró información en RENIEC.',
            ], 422);
        }

        $id = (string) ($resultado['numero'] ?? $dni);
        $nombres = trim((string) ($resultado['nombres'] ?? ''));
        $apellidoPaterno = trim((string) ($resultado['apellido_paterno'] ?? ''));
        $apellidoMaterno = trim((string) ($resultado['apellido_materno'] ?? ''));
        $nombreCompleto = trim((string) ($resultado['nombre_completo'] ?? ''));

        if ($nombreCompleto === '') {
            return response()->json([
                'status' => false,
                'message' => 'No se encontró información en RENIEC.',
            ], 422);
        }

        $apellidosUnificados = trim(implode(' ', array_filter([$apellidoPaterno, $apellidoMaterno])));

        return response()->json([
            'status' => true,
            'message' => $mensaje !== '' ? $mensaje : 'Encontrado',
            'id' => $id,
            'nombres' => $nombres,
            'apellido_paterno' => $apellidoPaterno,
            'apellido_materno' => $apellidoMaterno,
            'nombre_completo' => $nombreCompleto,
            'first_name' => $nombres,
            'last_name' => $apellidosUnificados,
            'name' => $nombreCompleto,
        ]);
    }

    public function apiRuc(Request $request)
    {
        $ruc = (string) $request->query('ruc', '');
        if (!preg_match('/^\d{11}$/', $ruc)) {
            return response()->json([
                'status' => false,
                'message' => 'RUC inválido.',
            ], 422);
        }

        $response = Http::withToken((string) config('apireniec.key'))
            ->timeout(15)
            ->post((string) config('apireniec.ruc_url'), [
                'ruc' => $ruc,
            ]);

        if (!$response->successful()) {
            return response()->json([
                'status' => false,
                'message' => 'No se pudo consultar RUC.',
            ], 422);
        }

        $data = (array) $response->json();
        $estado = (bool) ($data['success'] ?? false);
        $resultado = (array) ($data['data'] ?? []);
        $mensaje = (string) ($data['message'] ?? '');

        if (!$estado || empty($resultado)) {
            return response()->json([
                'status' => false,
                'message' => $mensaje !== '' ? $mensaje : 'No se encontró información para el RUC ingresado.',
            ], 422);
        }

        return response()->json([
            'status' => true,
            'message' => $mensaje !== '' ? $mensaje : 'Encontrado',
            'ruc' => (string) ($resultado['ruc'] ?? $ruc),
            'legal_name' => trim((string) ($resultado['nombre_o_razon_social'] ?? '')),
            'address' => trim((string) ($resultado['direccion'] ?? '')),
            'department' => trim((string) ($resultado['departamento'] ?? '')),
            'province' => trim((string) ($resultado['provincia'] ?? '')),
            'district' => trim((string) ($resultado['distrito'] ?? '')),
            'condition' => trim((string) ($resultado['condicion'] ?? '')),
            'taxpayer_status' => trim((string) ($resultado['estado'] ?? '')),
            'raw' => $resultado,
        ]);
    }
}
