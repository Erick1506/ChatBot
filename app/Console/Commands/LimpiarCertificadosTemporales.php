<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\CertificadoGenerado;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class LimpiarCertificadosTemporales extends Command
{
    protected $signature = 'certificados:limpiar {--dias=30 : Días de antigüedad}';
    protected $description = 'Limpiar certificados generados antiguos';

    public function handle()
    {
        $dias = $this->option('dias');
        $fechaLimite = Carbon::now()->subDays($dias);
        
        $this->info("Buscando certificados anteriores a: {$fechaLimite->format('Y-m-d')}");
        
        // Buscar certificados antiguos
        $certificados = CertificadoGenerado::where('created_at', '<', $fechaLimite)
            ->where('descargado', true)
            ->get();
        
        $eliminados = 0;
        
        foreach ($certificados as $certificado) {
            // Eliminar archivo físico
            if (file_exists($certificado->ruta_archivo)) {
                unlink($certificado->ruta_archivo);
            }
            
            // Eliminar registro
            $certificado->delete();
            $eliminados++;
        }
        
        $this->info("Eliminados {$eliminados} certificados antiguos.");
        
        return 0;
    }
}