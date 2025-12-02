<?php

namespace App\Services\WhatsApp;

use App\Models\CertificadoFIC;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;

class CertificateService
{
    public function searchByTicket(string $nit, string $ticket)
    {
        Log::info("🔍 Buscando certificados por ticket - NIT: {$nit}, Ticket: {$ticket}");
        return CertificadoFIC::where('constructor_nit', $nit)
            ->where('ticket', $ticket)
            ->get();
    }

    public function searchByNit(string $nit)
    {
        Log::info("🔍 Buscando certificados por NIT: {$nit}");
        return CertificadoFIC::where('constructor_nit', $nit)->get();
    }

    public function searchByVigencia(string $nit, int $year)
    {
        Log::info("🔍 Buscando certificados por vigencia - NIT: {$nit}, Año: {$year}");
        $pattern = $year . '-%';
        return CertificadoFIC::where('constructor_nit', $nit)
            ->where('periodo', 'like', $pattern)
            ->get();
    }

    public function generatePDF($certificados, string $tipo): string
    {
        Log::info("📊 Generando PDF para {$certificados->count()} certificados, tipo: {$tipo}");

        $constructor = $certificados->first();
        $total = $certificados->sum('valor_pago');

        Log::info("Constructor: {$constructor->constructor_razon_social}, Total: {$total}");

        $datos = [
            'certificados' => $certificados,
            'constructor' => $constructor,
            'total' => $total,
            'fecha_emision' => now(),
            'tipo_busqueda' => $tipo
        ];

        $pdf = Pdf::loadView('certificados.plantilla', $datos)
                  ->setPaper('a4', 'portrait')
                  ->setOptions([
                      'defaultFont' => 'Arial',
                      'isHtml5ParserEnabled' => true,
                      'isRemoteEnabled' => true
                  ]);

        $fileName = $this->generateFileName($constructor, $tipo);
        $filePath = storage_path('app/temp/' . $fileName);

        Log::info("Guardando PDF en: {$filePath}");

        if (!file_exists(dirname($filePath))) {
            Log::info("Creando directorio: " . dirname($filePath));
            mkdir(dirname($filePath), 0755, true);
        }

        $pdf->save($filePath);
        Log::info("✅ PDF guardado exitosamente");

        return $filePath;
    }

    public function generateFileName($constructor, string $tipo): string
    {
        $fecha = now()->format('Y-m-d');
        $nit = $constructor->constructor_nit;
        $fileName = "Certificado_FIC_{$nit}_{$tipo}_{$fecha}.pdf";
        Log::info("Nombre de archivo generado: {$fileName}");
        return $fileName;
    }

    public function validateYear(int $year): bool
    {
        $currentYear = date('Y');
        return $year > 0 && $year <= $currentYear && $year >= ($currentYear - 15);
    }

    public function getYearRange(): array
    {
        $currentYear = date('Y');
        return [
            'min' => $currentYear - 15,
            'max' => $currentYear
        ];
    }
}