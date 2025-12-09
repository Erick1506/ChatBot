<?php

namespace App\Services\WhatsApp;

use App\Models\CertificadoFIC;
use App\Models\CertificadoGenerado;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class CertificateService
{
    /**
     * Buscar certificados por ticket
     */
    public function searchByTicket(string $nit, string $ticket)
    {
        Log::info("🔍 Buscando certificados por ticket - NIT: {$nit}, Ticket: {$ticket}");
        return CertificadoFIC::where('nitempresa', $nit)
            ->where('ticketid', $ticket)
            ->get();
    }

    /**
     * Buscar certificados por NIT
     */
    public function searchByNit(string $nit)
    {
        Log::info("🔍 Buscando certificados por NIT: {$nit}");
        return CertificadoFIC::where('nitempresa', $nit)->get();
    }

    /**
     * Buscar certificados por vigencia
     */
    public function searchByVigencia(string $nit, int $year)
    {
        Log::info("🔍 Buscando certificados por vigencia - NIT: {$nit}, Año: {$year}");
        $pattern = $year . '-%';
        return CertificadoFIC::where('nitempresa', $nit)
            ->where('periodo_pagado', 'like', $pattern)
            ->get();
    }

    /**
     * Generar PDF y registrar certificado
     *
     * Devuelve array con file_path, file_name, serial y certificado_id
     */
    public function generatePDF($certificados, string $tipo, $empresa): array
    {
        Log::info("=== GENERATE PDF INICIADO ===");
        Log::info("Certificados count: " . $certificados->count());
        Log::info("Tipo: {$tipo}");
        Log::info("Empresa data: " . json_encode($this->sanitizeForLog($empresa), JSON_PARTIAL_OUTPUT_ON_ERROR));

        // Validar que haya certificados
        if ($certificados->isEmpty()) {
            Log::error("❌ Certificados vacío en generatePDF");
            throw new \Exception("No hay certificados para generar PDF");
        }

        // Obtener nombre del usuario
        $nombreUsuario = $this->obtenerNombreUsuario($empresa);

        // Calcular total
        $total = $this->calcularTotal($certificados);
        Log::info("📊 Total calculado: {$total}");

        $constructor = $certificados->first();

        if (!$constructor) {
            throw new \Exception("No se pudo obtener el constructor de los certificados");
        }

        // Generar serial único para el certificado
        $serial = $this->generarSerialUnico();
        Log::info("🔢 Serial generado: {$serial}");

        // Preparar datos para la vista
        $datos = [
            'certificados'   => $certificados,
            'constructor'    => $constructor,
            'total'          => $total,
            'fecha_emision'  => now(),
            'tipo_busqueda'  => $tipo,
            'nombre_usuario' => $nombreUsuario,
            'serial_certificado' => $serial,
        ];

        // Generar PDF (usa tu vista)
        $pdf = Pdf::loadView('certificados.plantilla', $datos)
                ->setPaper('a4', 'portrait')
                ->setOptions([
                    'defaultFont' => 'Arial',
                    'isHtml5ParserEnabled' => true,
                    'isRemoteEnabled' => true,
                    'isPhpEnabled' => true, // Importante para numeración de páginas
                    'dpi' => 150,
                ]);

        // Generar nombre de archivo (3er parámetro ahora opcional)
        $fileName = $this->generateFileName($constructor, $tipo, $serial);
        $filePath = storage_path('app/certificados/' . $fileName);

        // Crear directorio si no existe
        if (!file_exists(dirname($filePath))) {
            mkdir(dirname($filePath), 0755, true);
            Log::info("📁 Directorio creado: " . dirname($filePath));
        }

        // Guardar PDF
        $pdf->save($filePath);
        Log::info("✅ PDF guardado en: {$filePath}");

        // Registrar certificado en base de datos
        $certificadoRegistrado = $this->registrarCertificadoGenerado([
            'serial' => $serial,
            'certificados' => $certificados,
            'constructor' => $constructor,
            'total' => $total,
            'tipo' => $tipo,
            'nombre_usuario' => $nombreUsuario,
            'file_path' => $filePath,
            'file_name' => $fileName,
        ]);

        Log::info("✅ Certificado registrado en BD con ID: " . $certificadoRegistrado->id);

        return [
            'file_path' => $filePath,
            'file_name' => $fileName,
            'serial' => $serial,
            'certificado_id' => $certificadoRegistrado->id,
        ];
    }

    /**
     * Obtener nombre del usuario
     */
    private function obtenerNombreUsuario($empresa): string
    {
        if (is_string($empresa)) {
            return $empresa;
        } elseif ($empresa && is_object($empresa)) {
            return $empresa->Usuario ?? $empresa->representante_legal ?? 'Sistema SENA';
        }
        return 'Sistema SENA';
    }

    /**
     * Calcular total de certificados
     */
    private function calcularTotal($certificados): float
    {
        return $certificados->reduce(function ($carry, $item) {
            $val = 0;
            if (isset($item->valor_pagado)) $val = floatval($item->valor_pagado);
            elseif (isset($item->VALOR_PAGADO)) $val = floatval($item->VALOR_PAGADO);
            return $carry + $val;
        }, 0);
    }

    /**
     * Generar serial único
     */
    private function generarSerialUnico(): string
    {
        $timestamp = now()->format('YmdHis');
        $random = Str::random(6);
        return "CERT-{$timestamp}-{$random}";
    }

    /**
     * Generar nombre de archivo
     *
     * 3er parámetro $serial ahora es opcional para compatibilidad.
     */
    public function generateFileName($constructor, string $tipo, ?string $serial = null): string
    {
        // Si no viene serial, genera uno pequeño
        if (empty($serial)) {
            $serial = $this->generarSerialUnico();
        }

        $fecha = now()->format('Y-m-d');
        $nit = $constructor->nitempresa ?? $constructor->nit ?? 'SIN_NIT';

        // Normalizar nit/prefijo
        $prefix = preg_replace('/[^A-Za-z0-9\-]/', '', strtoupper((string)($nit)));

        // Formato: <serial>.pdf (mantengo compatibilidad)
        $fileName = "{$serial}.pdf";

        Log::info("📄 Nombre de archivo generado: {$fileName}");

        return $fileName;
    }

    /**
     * Validar año
     */
    public function validateYear(int $year): bool
    {
        $currentYear = date('Y');
        return $year > 0 && $year <= $currentYear && $year >= ($currentYear - 15);
    }

    /**
     * Obtener rango de años
     */
    public function getYearRange(): array
    {
        $currentYear = date('Y');
        return [
            'min' => $currentYear - 15,
            'max' => $currentYear
        ];
    }

    /**
     * Registrar certificado generado en la base de datos
     */
    private function registrarCertificadoGenerado(array $datos): CertificadoGenerado
    {
        $certificados = $datos['certificados'];
        $constructor = $datos['constructor'];
        $total = $datos['total'];
        $tipo = $datos['tipo'];
        $nombreUsuario = $datos['nombre_usuario'];
        $serial = $datos['serial'];

        // Extraer criterios de búsqueda
        $criterios = $this->extraerCriteriosBusqueda($tipo, $certificados->first());

        // Generar hash para el archivo
        $hash = $this->generarHashArchivo($datos['file_path']);

        // Crear registro
        $certificadoGenerado = CertificadoGenerado::create([
            'serial' => $serial,
            'nit_empresa' => $constructor->nitempresa ?? 'SIN_NIT',
            'nombre_empresa' => $constructor->nombre_empresa ?? 'Empresa no identificada',
            'tipo_certificado' => $tipo,
            'cantidad_registros' => $certificados->count(),
            'valor_total' => $total,
            'usuario_generador' => $nombreUsuario,
            'canal_generacion' => 'whatsapp',
            'criterios_busqueda' => $criterios,
            'ruta_archivo' => $datos['file_path'],
            'nombre_archivo' => $datos['file_name'],
            'hash_archivo' => $hash,
            'descargado' => false,
        ]);

        Log::info("📝 Certificado registrado en BD: " . json_encode([
            'serial' => $serial,
            'nit' => $constructor->nitempresa ?? null,
            'registros' => $certificados->count(),
            'valor_total' => $total,
        ], JSON_PARTIAL_OUTPUT_ON_ERROR));

        return $certificadoGenerado;
    }

    /**
     * Extraer criterios de búsqueda según el tipo
     */
    private function extraerCriteriosBusqueda(string $tipo, $certificado): array
    {
        $criterios = [];

        if ($tipo === 'nit_ticket' && $certificado) {
            $criterios['ticket'] = $certificado->ticketid ?? null;
        } elseif ($tipo === 'nit_vigencia' && $certificado) {
            if ($certificado->periodo_pagado) {
                $criterios['year'] = substr($certificado->periodo_pagado, 0, 4);
            }
        }

        return $criterios;
    }

    /**
     * Generar hash para el archivo
     */
    private function generarHashArchivo(string $filePath): string
    {
        if (file_exists($filePath)) {
            return hash_file('sha256', $filePath);
        }

        // Si no se puede leer el archivo, generar un hash único
        return Hash::make(Str::random(40) . microtime(true));
    }

    /**
     * Buscar certificados generados por NIT
     */
    public function buscarCertificadosGenerados(string $nit, int $limit = 10)
    {
        Log::info("🔍 Buscando certificados generados para NIT: {$nit}");

        $certificados = CertificadoGenerado::porNit($nit)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        Log::info("📊 Encontrados {$certificados->count()} certificados generados");

        return $certificados;
    }

    /**
     * Buscar certificado por serial
     */
    public function buscarCertificadoPorSerial(string $serial)
    {
        Log::info("🔍 Buscando certificado por serial: {$serial}");

        $certificado = CertificadoGenerado::porSerial($serial)->first();

        if ($certificado) {
            Log::info("✅ Certificado encontrado para serial: {$serial}");
        } else {
            Log::warning("❌ Certificado NO encontrado para serial: {$serial}");
        }

        return $certificado;
    }

    /**
     * Buscar certificado por hash
     */
    public function buscarCertificadoPorHash(string $hash)
    {
        return CertificadoGenerado::where('hash_archivo', $hash)->first();
    }

    /**
     * Eliminar certificado (físico y registro)
     */
    public function eliminarCertificado(string $serial): bool
    {
        $certificado = $this->buscarCertificadoPorSerial($serial);

        if (!$certificado) {
            Log::warning("❌ No se encontró certificado para eliminar: {$serial}");
            return false;
        }

        try {
            // Eliminar archivo físico
            if (file_exists($certificado->ruta_archivo)) {
                unlink($certificado->ruta_archivo);
                Log::info("🗑️ Archivo eliminado: {$certificado->ruta_archivo}");
            }

            // Eliminar registro
            $deleted = $certificado->delete();

            if ($deleted) {
                Log::info("✅ Certificado eliminado de BD: {$serial}");
                return true;
            }

        } catch (\Exception $e) {
            Log::error("❌ Error al eliminar certificado {$serial}: " . $e->getMessage());
        }

        return false;
    }

    /**
     * Obtener estadísticas de certificados
     */
    public function obtenerEstadisticas(string $nit = null): array
    {
        $query = CertificadoGenerado::query();

        if ($nit) {
            $query->where('nit_empresa', $nit);
        }

        $total = $query->count();
        $ultimaSemana = $query->where('created_at', '>=', now()->subDays(7))->count();
        $valorTotal = $query->sum('valor_total');
        $porTipo = $query->selectRaw('tipo_certificado, COUNT(*) as cantidad')
            ->groupBy('tipo_certificado')
            ->pluck('cantidad', 'tipo_certificado')
            ->toArray();

        // Agregar estadísticas por mes (últimos 6 meses)
        $estadisticasMensuales = $this->obtenerEstadisticasMensuales($nit);

        return [
            'total' => $total,
            'ultima_semana' => $ultimaSemana,
            'valor_total' => $valorTotal,
            'por_tipo' => $porTipo,
            'mensual' => $estadisticasMensuales,
        ];
    }

    private function obtenerEstadisticasMensuales(?string $nit): array
    {
        $query = CertificadoGenerado::query();

        if ($nit) {
            $query->where('nit_empresa', $nit);
        }

        // Últimos 6 meses
        $meses = [];
        for ($i = 5; $i >= 0; $i--) {
            $fecha = now()->subMonths($i);
            $mes = $fecha->format('Y-m');
            $mesNombre = $fecha->translatedFormat('F Y');

            $inicioMes = $fecha->copy()->startOfMonth()->toDateTimeString();
            $finMes = $fecha->copy()->endOfMonth()->toDateTimeString();

            $count = $query->clone()
                ->whereBetween('created_at', [$inicioMes, $finMes])
                ->count();

            $meses[] = [
                'mes' => $mes,
                'mes_nombre' => $mesNombre,
                'cantidad' => $count,
            ];
        }

        return $meses;
    }

    /**
     * Verificar si un archivo de certificado existe y es accesible
     */
    public function verificarCertificado(string $serial): array
    {
        $certificado = $this->buscarCertificadoPorSerial($serial);

        if (!$certificado) {
            return [
                'existe' => false,
                'mensaje' => 'Certificado no encontrado',
            ];
        }

        $existeArchivo = file_exists($certificado->ruta_archivo);

        return [
            'existe' => $existeArchivo,
            'certificado' => $certificado,
            'archivo_accesible' => $existeArchivo,
            'mensaje' => $existeArchivo ? 'Certificado disponible' : 'Archivo no encontrado',
        ];
    }

    /**
     * Limpiar certificados antiguos (más de 30 días)
     */
    public function limpiarCertificadosAntiguos(int $dias = 30): array
    {
        Log::info("🧹 Iniciando limpieza de certificados antiguos (> {$dias} días)");

        $fechaLimite = now()->subDays($dias);
        $certificados = CertificadoGenerado::where('created_at', '<', $fechaLimite)->get();

        $eliminados = 0;
        $errores = 0;

        foreach ($certificados as $certificado) {
            try {
                // Eliminar archivo físico
                if (file_exists($certificado->ruta_archivo)) {
                    unlink($certificado->ruta_archivo);
                }

                // Eliminar registro
                $certificado->delete();
                $eliminados++;

            } catch (\Exception $e) {
                Log::error("❌ Error al eliminar certificado {$certificado->serial}: " . $e->getMessage());
                $errores++;
            }
        }

        Log::info("✅ Limpieza completada: {$eliminados} eliminados, {$errores} errores");

        return [
            'eliminados' => $eliminados,
            'errores' => $errores,
            'total' => $certificados->count(),
        ];
    }

    /**
     * Generar reporte de actividad de certificados
     */
    public function generarReporteActividad(string $nit = null, Carbon $fechaInicio = null, Carbon $fechaFin = null): array
    {
        $query = CertificadoGenerado::query();

        if ($nit) {
            $query->where('nit_empresa', $nit);
        }

        if ($fechaInicio && $fechaFin) {
            $query->whereBetween('created_at', [$fechaInicio, $fechaFin]);
        }

        $actividad = $query->selectRaw('
                DATE(created_at) as fecha,
                COUNT(*) as total_certificados,
                SUM(cantidad_registros) as total_registros,
                SUM(valor_total) as valor_total
            ')
            ->groupBy('fecha')
            ->orderBy('fecha', 'desc')
            ->get();

        $usuarios = $query->clone()
            ->selectRaw('usuario_generador, COUNT(*) as cantidad')
            ->groupBy('usuario_generador')
            ->orderBy('cantidad', 'desc')
            ->get();

        return [
            'actividad_diaria' => $actividad,
            'usuarios' => $usuarios,
            'periodo' => [
                'inicio' => $fechaInicio ? $fechaInicio->format('Y-m-d') : null,
                'fin' => $fechaFin ? $fechaFin->format('Y-m-d') : null,
            ],
        ];
    }

    /**
     * Sanitizar objetos/arrays para logging (evita ciclos profundos)
     */
    private function sanitizeForLog($data)
    {
        if (is_null($data)) return null;
        if (is_string($data) || is_numeric($data) || is_bool($data)) return $data;

        // Si es objeto Eloquent o similar, convertir a array con campos clave mínimos
        if (is_object($data)) {
            $arr = [];
            foreach (['Usuario', 'representante_legal', 'nit', 'nitempresa', 'nombre_empresa'] as $k) {
                if (isset($data->{$k})) $arr[$k] = $data->{$k};
                // también manejar propiedades en minúscula
                $lk = strtolower($k);
                if (isset($data->{$lk})) $arr[$lk] = $data->{$lk};
            }
            return $arr ?: ['class' => get_class($data)];
        }

        if (is_array($data)) {
            // devolver solo claves simples
            $out = [];
            foreach ($data as $k => $v) {
                if (is_scalar($v)) $out[$k] = $v;
            }
            return $out;
        }

        return (string) $data;
    }
}
