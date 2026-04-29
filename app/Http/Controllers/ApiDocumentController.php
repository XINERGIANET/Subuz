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

        $response = Http::timeout(15)->get((string) config('apireniec.url'), [
            'document' => $dni,
            'key' => (string) config('apireniec.key'),
        ]);

        if (!$response->successful()) {
            return response()->json([
                'status' => false,
                'message' => 'No se pudo consultar RENIEC.',
            ], 422);
        }

        $data = (array) $response->json();
        $estado = (bool) ($data['estado'] ?? $data['status'] ?? false);
        $resultado = (array) ($data['resultado'] ?? []);
        $mensaje = (string) ($data['mensaje'] ?? $data['message'] ?? '');

        if ($estado && $resultado === []) {
            $hasPersonFields = ($data['nombres'] ?? '') !== ''
                || ($data['apellido_paterno'] ?? '') !== ''
                || ($data['apellido_materno'] ?? '') !== ''
                || ($data['nombre_completo'] ?? '') !== ''
                || ($data['name'] ?? '') !== '';
            if ($hasPersonFields) {
                $resultado = $data;
            }
        }

        if (!$estado || $resultado === []) {
            return response()->json([
                'status' => false,
                'message' => $mensaje !== '' ? $mensaje : 'No se encontró información en RENIEC.',
            ], 422);
        }

        $id = (string) ($resultado['id'] ?? $dni);
        $nombres = trim((string) ($resultado['nombres'] ?? ''));
        $apellidoPaterno = trim((string) ($resultado['apellido_paterno'] ?? ($resultado['apellidoPaterno'] ?? '')));
        $apellidoMaterno = trim((string) ($resultado['apellido_materno'] ?? ($resultado['apellidoMaterno'] ?? '')));

        if ($nombres === '' && $apellidoPaterno === '' && $apellidoMaterno === '') {
            $full = trim((string) ($resultado['nombre_completo'] ?? ($resultado['name'] ?? '')));
            if ($full !== '') {
                $parts = preg_split('/\s+/', $full) ?: [];
                if (count($parts) >= 4) {
                    $nombres = trim(implode(' ', array_slice($parts, 0, count($parts) - 2)));
                    $apellidoPaterno = (string) ($parts[count($parts) - 2] ?? '');
                    $apellidoMaterno = (string) ($parts[count($parts) - 1] ?? '');
                } elseif (count($parts) === 3) {
                    $nombres = (string) ($parts[0] ?? '');
                    $apellidoPaterno = (string) ($parts[1] ?? '');
                    $apellidoMaterno = (string) ($parts[2] ?? '');
                } elseif (count($parts) === 2) {
                    $nombres = (string) ($parts[0] ?? '');
                    $apellidoPaterno = (string) ($parts[1] ?? '');
                } elseif (count($parts) === 1) {
                    $nombres = (string) ($parts[0] ?? '');
                }
            }
        }

        $nombreCompleto = trim(implode(' ', array_filter([$nombres, $apellidoPaterno, $apellidoMaterno])));
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
            'nombre_completo' => (string) ($resultado['nombre_completo'] ?? $nombreCompleto),
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

        $response = Http::timeout(15)->get((string) config('apireniec.ruc_url'), [
            'document' => $ruc,
            'key' => (string) config('apireniec.key'),
        ]);

        if (!$response->successful()) {
            return response()->json([
                'status' => false,
                'message' => 'No se pudo consultar RUC.',
            ], 422);
        }

        $data = (array) $response->json();
        $estado = (bool) ($data['estado'] ?? $data['status'] ?? false);
        $resultado = (array) ($data['resultado'] ?? []);
        $mensaje = (string) ($data['mensaje'] ?? $data['message'] ?? '');

        if (!$estado || empty($resultado)) {
            return response()->json([
                'status' => false,
                'message' => $mensaje !== '' ? $mensaje : 'No se encontró información para el RUC ingresado.',
            ], 422);
        }

        return response()->json([
            'status' => true,
            'message' => $mensaje !== '' ? $mensaje : 'Encontrado',
            'ruc' => (string) ($resultado['id'] ?? $ruc),
            'legal_name' => trim((string) ($resultado['razon_social'] ?? ($resultado['nombre'] ?? ''))),
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
