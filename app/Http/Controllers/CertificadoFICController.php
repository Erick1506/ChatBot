<?php

namespace App\Http\Controllers;

use App\Models\CertificadoFIC;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class CertificadoFICController extends Controller
{
    public function apiGenerarCertificado(Request $request)
    {
        try {
            $tipo = $request->input('tipo');
            $nit = $request->input('nit');
            $ticket = $request->input('ticket');
            $vigencia = $request->input('vigencia');
            $periodo = $request->input('periodo');
            
            // Validar parámetros requeridos
            if (!$nit) {
                return response()->json([
                    'error' => 'El parámetro NIT es requerido'
                ], 400);
            }
            
            // Buscar certificados según los parámetros
            $certificados = $this->buscarCertificados($tipo, $nit, $ticket, $vigencia, $periodo);
            
            if ($certificados->isEmpty()) {
                return response()->json([
                    'error' => 'No se encontraron certificados con los criterios especificados'
                ], 404);
            }
            
            // Generar PDF
            return $this->generarPdf($certificados, $tipo);
            
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error interno del servidor: ' . $e->getMessage()
            ], 500);
        }
    }
    
    private function buscarCertificados($tipo, $nit, $ticket = null, $vigencia = null, $periodo = null)
    {
        $query = CertificadoFIC::where('constructor_nit', $nit);
        
        switch ($tipo) {
            case 'nit_ticket':
                return $query->where('ticket', $ticket)->get();
                
            case 'nit_vigencia':
                return $query->where('periodo', 'like', $vigencia . '-%')->get();
                
            case 'nit_periodo':
                return $query->where('periodo', $periodo)->get();
                
            case 'nit_anio_fecha':
                return $query->whereYear('fecha', $vigencia)->get();
                
            case 'nit_general':
            default:
                return $query->get();
        }
    }
    
    private function generarPdf($certificados, $tipo)
    {
        $constructor = $certificados->first();
        $total = $certificados->sum('valor_pago');
        
        $datos = [
            'certificados' => $certificados,
            'constructor' => $constructor,
            'total' => $total,
            'fecha_emision' => now(),
            'tipo_busqueda' => $tipo
        ];
        
        // Pasar la función helper a la vista
        $pdf = Pdf::loadView('certificados.plantilla', $datos)
                  ->setPaper('a4', 'portrait')
                  ->setOptions([
                      'defaultFont' => 'Arial',
                      'isHtml5ParserEnabled' => true,
                      'isRemoteEnabled' => true
                  ]);
        
        $nombreArchivo = $this->generarNombreArchivo($constructor, $tipo);
        
        return $pdf->download($nombreArchivo);
    }
    
    private function generarNombreArchivo($constructor, $tipo)
    {
        $fecha = now()->format('Y-m-d');
        $nit = $constructor->constructor_nit;
        return "Certificado_FIC_{$nit}_{$tipo}_{$fecha}.pdf";
    }
    
    // También el método para web por si lo necesitas
    public function generarCertificado(Request $request)
    {
        return $this->apiGenerarCertificado($request);
    }
}