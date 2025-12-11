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
        return CertificadoFIC::where('nitempresa', $nit) 
            ->where('ticketid', $ticket) 
            ->get();
    }

    public function searchByNit(string $nit)
    {
        Log::info("🔍 Buscando certificados por NIT: {$nit}");
        return CertificadoFIC::where('nitempresa', $nit)->get(); 
    }

    public function searchByVigencia(string $nit, int $year)
    {
        Log::info("🔍 Buscando certificados por vigencia - NIT: {$nit}, Año: {$year}");
        $pattern = $year . '-%';
        return CertificadoFIC::where('nitempresa', $nit) 
            ->where('fecha_pago', 'like', $pattern) 
            ->get();
    }

    public function generatePDF($certificados, string $tipo, $empresa): string
    {
        $nombreUsuario = null;
        if (is_string($empresa)) {
            $nombreUsuario = $empresa;
        } elseif ($empresa && is_object($empresa)) {
            $nombreUsuario = $empresa->Usuario ?? $empresa->representante_legal ?? 'Sistema SENA';
        } else {
            $nombreUsuario = 'Sistema SENA';
        }

        Log::info("📊 Generando PDF para {$certificados->count()} certificados, tipo: {$tipo}, Usuario: {$nombreUsuario}");

        $total = $certificados->reduce(function ($carry, $item) {
            $val = 0;
            if (isset($item->valor_pagado)) $val = floatval($item->valor_pagado);
            elseif (isset($item->VALOR_PAGADO)) $val = floatval($item->VALOR_PAGADO);
            return $carry + $val;
        }, 0);

        $constructor = $certificados->first();

        $datos = [
            'certificados'   => $certificados,
            'constructor'    => $constructor,
            'total'          => $total,
            'fecha_emision'  => now(),
            'tipo_busqueda'  => $tipo,
            'nombre_usuario' => $nombreUsuario,
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

        if (!file_exists(dirname($filePath))) mkdir(dirname($filePath), 0755, true);

        $pdf->save($filePath);

        return $filePath;
    }

    public function generateFileName($constructor, string $tipo): string
    {
        $fecha = now()->format('Y-m-d');
        $nit = $constructor->nitempresa; 
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