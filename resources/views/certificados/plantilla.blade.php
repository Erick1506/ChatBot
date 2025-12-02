<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Certificado FIC - SENA</title>
    <style>
        /* Estilos generales basados en plantillas oficiales */
        body { 
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 50px 60px;
            font-size: 11pt;
            line-height: 1.2;
            color: #000000;
        }
        
        /* Contenedor principal para simular página A4 */
        .page-container {
            max-width: 21cm;
            margin: 0 auto;
        }
        
        /* Encabezado con logo - basado en segunda plantilla */
        .header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .logo-line {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
        }
        
        .logo {
            height: 85px;
            width: auto;
            margin-right: 20px;
        }
        
        .institucion {
            font-size: 14pt;
            font-weight: bold;
            text-transform: uppercase;
            color: #000;
        }
        
        .ministerio {
            font-size: 12pt;
            font-weight: bold;
            text-align: center;
            margin: 20px 0;
        }
        
        /* Título CERTIFICA */
        .certifica-title {
            text-align: center;
            font-size: 12pt;
            font-weight: bold;
            text-transform: uppercase;
            margin: 30px 0 40px 0;
        }
        
        /* Párrafo de introducción */
        .intro-paragraph {
            text-align: justify;
            margin: 0 0 30px 0;
            font-size: 11pt;
            line-height: 1.3;
        }
        
        /* Tabla de transacciones - estilo oficial */
        .table-container {
            margin: 30px 0 40px 0;
            width: 100%;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10pt;
            border: 1px solid #000;
        }
        
        th {
            font-weight: bold;
            text-align: center;
            padding: 8px 5px;
            border: 1px solid #000;
            background-color: #ffffff;
            vertical-align: middle;
            font-size: 10pt;
        }
        
        td {
            padding: 8px 5px;
            border: 1px solid #000;
            vertical-align: middle;
            font-size: 10pt;
        }
        
        .table-header {
            background-color: #f0f0f0;
        }
        
        /* Fila del total */
        .total-row {
            font-weight: bold;
        }
        
        .total-row td {
            padding: 10px 5px;
        }
        
        /* Fecha de expedición */
        .fecha-expedicion {
            margin: 30px 0;
            text-align: right;
            font-size: 11pt;
        }
        
        /* Texto de advertencia */
        .advertencia-box {
            margin: 30px 0;
            padding: 20px;
            border: 1px solid #000;
            font-size: 10pt;
            text-align: center;
            font-weight: bold;
            background-color: #f9f9f9;
        }
        
        /* Sección de no validez */
        .no-validez {
            font-weight: bold;
            text-align: center;
            margin: 25px 0;
            font-size: 11pt;
            text-transform: uppercase;
        }
        
        /* Información adicional */
        .info-section {
            margin: 25px 0;
            font-size: 10pt;
            text-align: justify;
        }
        
        .info-section p {
            margin: 8px 0;
        }
        
        /* Código de verificación */
        .verificacion-box {
            margin: 30px 0;
            padding: 20px;
            border: 1px solid #000;
            background-color: #ffffff;
            font-size: 10pt;
        }
        
        .verificacion-title {
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .verificacion-url {
            text-decoration: underline;
            color: #0000EE;
            margin: 10px 0;
        }
        
        .codigos {
            margin-top: 15px;
            font-weight: bold;
        }
        
        /* Utilidades */
        .negrita {
            font-weight: bold;
        }
        
        .text-left {
            text-align: left;
        }
        
        .text-center {
            text-align: center;
        }
        
        .text-right {
            text-align: right;
        }
        
        .uppercase {
            text-transform: uppercase;
        }
        
        .underline {
            text-decoration: underline;
        }
        
        /* Espaciado específico */
        .spacing-1 {
            margin-top: 25px;
        }
        
        .spacing-2 {
            margin-top: 20px;
        }
        
        /* Marcadores para datos (como en plantillas) */
        .data-field {
            border-bottom: 1px solid #000;
            display: inline-block;
            min-width: 100px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="page-container">
        <!-- Encabezado con logo (como en segunda plantilla) -->
        <div class="header">
            <div class="logo-line">
                <!-- Logo del SENA - ajusta la ruta según corresponda -->
                <img src="https://www.sena.edu.co/Style%20Library/alayout/images/logoSena.png?rev=40" alt="Logo SENA" class="logo">
                <div class="institucion">Servicio Nacional de Aprendizaje SENA</div>
            </div>
            <div class="ministerio">MINISTERIO DE TRABAJO</div>
        </div>
        
        <!-- Título CERTIFICA -->
        <div class="certifica-title">CERTIFICA:</div>
        
        <!-- Contenido principal -->
        <div class="intro-paragraph">
            Que el constructor con razón social <span class="negrita">{{ $constructor->constructor_razon_social }}</span> 
            identificado con el NIT <span class="negrita">{{ $constructor->constructor_nit }}</span>, 
            por concepto de Fondo Nacional de Formación Profesional de la Industria de la Construcción FIC, 
            realizó a través del botón electrónico de pagos las siguientes transacciones:
        </div>
        
        <!-- Tabla de transacciones -->
        <div class="table-container">
            <table>
                <thead>
                    <tr class="table-header">
                        <th style="width: 16%">N°<br>Licencia/Contrato</th>
                        <th style="width: 22%">Nombre Obra</th>
                        <th style="width: 13%">Ciudad<br>Ejecución</th>
                        <th style="width: 14%">Valor Pago</th>
                        <th style="width: 10%">Periodo</th>
                        <th style="width: 10%">Fecha</th>
                        <th style="width: 15%">Ticket</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($certificados as $certificado)
                    <tr>
                        <td class="text-center">{{ $certificado->licencia_contrato ?? 'N/A' }}</td>
                        <td class="text-left">{{ $certificado->nombre_obra }}</td>
                        <td class="text-center">{{ $certificado->ciudad_ejecucion }}</td>
                        <td class="text-right">${{ number_format($certificado->valor_pago, 0, ',', '.') }}</td>
                        <td class="text-center">{{ $certificado->periodo }}</td>
                        <td class="text-center">{{ $certificado->fecha->format('d/m/Y') }}</td>
                        <td class="text-center">{{ $certificado->ticket }}</td>
                    </tr>
                    @endforeach
                    <!-- Fila del total -->
                    <tr class="total-row">
                        <td colspan="2"></td>
                        <td class="text-center negrita">Total</td>
                        <td class="text-right negrita">${{ number_format($total, 0, ',', '.') }}</td>
                        <td colspan="3"></td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <!-- Fecha de expedición -->
        <div class="fecha-expedicion">
            Expedido por el SENA, a los <span class="negrita">{{ $fecha_emision->day }}</span> 
            (<span class="negrita">{{ $fecha_emision->day }}</span>) días del mes de 
            <span class="negrita uppercase">{{ obtenerMesEspanol($fecha_emision->month) }}</span> de 
            <span class="negrita">{{ $fecha_emision->year }}</span>
        </div>
        
        <!-- Texto de advertencia -->
        <div class="advertencia-box">
            "LA EXPEDICIÓN DE ESTA CERTIFICACIÓN, NO IMPIDE QUE EL SENA VERIFIQUE LA BASE DE LIQUIDACIÓN DE FIC Y QUE CONSTATE EL CUMPLIMIENTO EN FONDO NACIONAL DE FORMACIÓN PROFESIONAL DE LA INDUSTRIA DE LA CONSTRUCCIÓN FIC."
        </div>
        
        <!-- Sección de no validez -->
        <div class="no-validez">NO TIENE VALIDEZ PARA FINES TRIBUTARIOS</div>
        
        <!-- Información adicional -->
        <div class="info-section">
            <p>Este documento no tiene validez en procesos de selección contractual con entidades del estado.</p>
            
            <p>La expedición de esta certificación no impide que el SENA verifique la base de liquidación de aportes y que constate el cumplimiento en Contrato de Aprendizaje.</p>
            
            <p class="spacing-1">Expedido por el Servicio Nacional de Aprendizaje – <span class="negrita">{{ $constructor->regional_sena }}</span></p>
            
            <p>Generado por: <span class="negrita">{{ $constructor->generado_por }}</span></p>
        </div>
        
        <!-- Código de verificación -->
        <div class="verificacion-box">
            <div class="verificacion-title">¿Desea saber si este certificado es auténtico?, por favor ingrese a la página web:</div>
            
            <div class="verificacion-url">https://certificadoempresarios.sena.edu.co/</div>
            
            <div>enlace CONSULTAR CODIGO CERTIFICADO y digite:</div>
            
            <div class="codigos">
                <p>el código de verificación: <span class="negrita">{{ $certificados->first()->codigo_verificacion }}</span></p>
                <p>y el Número de Certificado: <span class="negrita">{{ $certificados->first()->numero_certificado }}</span></p>
            </div>
        </div>
    </div>
</body>
</html>

<?php
// Función helper para obtener el mes en español
function obtenerMesEspanol($mes) {
    $meses = [
        1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril',
        5 => 'mayo', 6 => 'junio', 7 => 'julio', 8 => 'agosto',
        9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre'
    ];
    return $meses[$mes] ?? 'mes';
}
?>