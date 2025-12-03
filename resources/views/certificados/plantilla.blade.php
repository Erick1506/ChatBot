<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Certificado FIC - SENA</title>
    <style>
        /* Estilos generales basados en el Word convertido */
        body { 
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            font-size: 11px;
            color: #000000;
            text-align: center;
        }
        
        /* Contenedor principal */
        .page-container {
            width: 21cm;
            min-height: 29.7cm;
            margin: 0 auto;
            padding: 0 50px;
            box-sizing: border-box;
            position: relative;
        }
        
        /* Logo y encabezado */
        .header {
            text-align: center;
            margin-bottom: 10px;
            position: relative;
            margin-top: 100px;
        }
        
        .logo-container {
            position: absolute;
            left: 0;
            top: 0;
            margin-top: -95px;
            margin-left:43%
        }
        
        .logo {
            height: 98px;
            width: 98px;
        }
        
        .header-content {
            display: inline-block;
            text-align: center;
            margin-top: 0;
        }
        
        .institucion {
            font-size: 16px;
            font-family: 'Arial', sans-serif;
            line-height: 173%;
            margin-bottom: 0;
            padding-top: 0;
        }
        
        .ministerio {
            font-size: 11px;
            font-family: 'Arial', sans-serif;
            font-weight: bold;
            margin-top: 0;
            line-height: 8.95pt;
        }
        
        /* Título CERTIFICA */
        .certifica-title {
            text-align: center;
            font-size: 16px;
            font-family: 'Arial', sans-serif;
            font-weight: bold;
            margin: 20px 0 10px 0;
        }
        
        /* Párrafo de introducción - con margen izquierdo como en el Word */
        .intro-paragraph {
            text-align: left;
            margin: 10.9pt 0 0 6.5pt;
            font-size: 11px;
            line-height: 108%;
            font-family: 'Arial MT', sans-serif;
            padding: 10px;
        }
        
        /* Tabla de transacciones - estilo exacto del Word */
        .table-container {
            margin: 0 0 0 7.1pt;
            width: 700px;
            padding:10px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid black;
            margin: 0 auto;
        }
        
        th, td {
            border: 1px solid black;
            padding: 0;
            text-align: center;
            vertical-align: top;
            height: 19pt;
        }
        
        th {
            font-weight: bold;
            font-size: 11px;
            font-family: 'Arial', sans-serif;
        }
        
        td {
            font-size: 11px;
            font-family: 'Arial MT', sans-serif;
        }
        
        /* Estilos específicos para celdas de la tabla */
        .table-header {
            background-color: transparent;
        }
        
        .table-data {
            background-color: transparent;
        }
        
        /* Fila del total */
        .total-row td {
            height: 9pt;
            padding: 0;
        }
        
        /* Fecha de expedición - centrado como en el Word */
        .fecha-expedicion {
            margin: 1.3pt 0 0 0;
            font-size: 11px;
            font-family: 'Arial MT', sans-serif;
            text-align: center;
            padding: 10px;
        }
        
        /* Texto de advertencia - centrado y en negrita */
        .advertencia-texto {
            margin: 1.7pt 7.8pt 0.7pt 2.25pt;
            font-size: 11px;
            font-family: 'Arial', sans-serif;
            font-weight: bold;
            text-align: center;
            line-height: 108%;
            padding: 15px;
        }
        
        /* Sección de no validez - centrado */
        .no-validez {
            font-weight: bold;
            text-align: center;
            font-size: 11px;
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 15px;
        }
        
        /* Información adicional - centrado */
        .info-section {
            margin: 1.5pt 0 0 0;
            font-size: 11px;
            font-family: 'Arial MT', sans-serif;
            text-align: center;
            padding: 15px;
        }
        
        .info-section p {
            margin: 0;
            line-height: 108%;
        }
        
        /* Expedido por - centrado */
        .expedido-por {
            margin: 0.75pt 132.35pt 0.1pt 126.8pt;
            font-size: 11px;
            font-family: 'Arial MT', sans-serif;
            text-align: center;
            line-height: 216%;
            padding: 5px;

        }
        
        /* Generado por - centrado */
        .generado-por {
            margin: 0 132.35pt 0 126.8pt;
            font-size: 11px;
            font-family: 'Arial MT', sans-serif;
            text-align: center;
            line-height: 216%;
        }
        
        /* Código de verificación */
        .verificacion-box {
            margin: 0.75pt 7.85pt 0 2.25pt;
            font-size: 11px;
            font-family: 'Arial MT', sans-serif;
            text-align: center;
            line-height: 108%;
            padding:10px;
        }
        
        .verificacion-url {
            color: blue;
            text-decoration: underline;
        }
        
        /* Footer */
        .footer {
            position: absolute;
            bottom: 20px;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 9pt;
        }

        .footer-image{
            width:190px;
            height:80px;   
        }
        
        /* Número de página */
        .page-number {
            position: absolute;
            bottom: 10px;
            right: 50px;
            font-size: 9pt;
            color: #666;
        }
        
        /* Utilidades */
        .negrita {
            font-weight: bold;
        }
        
        .text-center {
            text-align: center;
        }
        
        .text-left {
            text-align: left;
        }
        
        .text-right {
            text-align: right;
        }
        
        .uppercase {
            text-transform: uppercase;
        }
    </style>
</head>
<body>
    <div class="page-container">
        <!-- Logo (posición similar al Word) -->
        <div class="logo-container">
            <img src="https://www.sena.edu.co/Style%20Library/alayout/images/logoSena.png?rev=40" alt="Logo SENA" class="logo">
        </div>
        
        <!-- Encabezado -->
        <div class="header">
            <div class="header-content">
                <h1 class="institucion">Servicio Nacional de Aprendizaje SENA</h1>
                <p class="ministerio">MINISTERIO DE TRABAJO</p>
            </div>
        </div>
        
        <!-- Título CERTIFICA -->
        <div class="certifica-title">CERTIFICA:</div>
        
        <!-- Contenido principal -->
        <div class="intro-paragraph">
            @if($certificados->count() > 0)
                Que el constructor con razón social <span class="negrita">{{ $certificados->first()->constructor_razon_social }}</span> 
                identificado con el NIT <span class="negrita">{{ $certificados->first()->constructor_nit }}</span>, 
                por concepto de Fondo Nacional de Formación Profesional de la Industria de la Construcción FIC, 
                realizó a través del botón electrónico de pagos las siguientes transacciones:
            @else
                Que el constructor con razón social <span class="negrita">NOMBRE DE EMPRESA</span> 
                identificado con el NIT <span class="negrita">XXXXXXXX</span>, 
                por concepto de Fondo Nacional de Formación Profesional de la Industria de la Construcción FIC, 
                realizó a través del botón electrónico de pagos las siguientes transacciones:
            @endif
        </div>
        
        <!-- Tabla de transacciones -->
        <div class="table-container">
            <table>
                <thead>
                    <tr class="table-header">
                        <td style="width: 75.3pt;">
                            <p style="margin: 0; text-align: center; line-height: 9.15pt;">
                                <strong>N°</strong>
                            </p>
                            <p style="margin: 0.8pt 0 0 0; text-align: center; line-height: 8.05pt;">
                                <strong>Licencia/Contrato</strong>
                            </p>
                        </td>
                        <td style="width: 75.3pt;">
                            <p style="margin: 0; text-align: left; line-height: 9.15pt; padding-left: 11.95pt;">
                                <strong>Nombre Obra</strong>
                            </p>
                        </td>
                        <td style="width: 75.3pt;">
                            <p style="margin: 0; text-align: center; line-height: 9.15pt;">
                                <strong>Ciudad Ejecución</strong>
                            </p>
                        </td>
                        <td style="width: 75.3pt;">
                            <p style="margin: 0; text-align: center; line-height: 9.15pt;">
                                <strong>Valor Pago</strong>
                            </p>
                        </td>
                        <td style="width: 75.3pt;">
                            <p style="margin: 0; text-align: center; line-height: 9.15pt;">
                                <strong>Periodo</strong>
                            </p>
                        </td>
                        <td style="width: 75.3pt;">
                            <p style="margin: 0; text-align: center; line-height: 9.15pt;">
                                <strong>Fecha</strong>
                            </p>
                        </td>
                        <td style="width: 75.3pt;">
                            <p style="margin: 0; text-align: center; line-height: 9.15pt;">
                                <strong>Ticket</strong>
                            </p>
                        </td>
                    </tr>
                </thead>
                <tbody>
                    @forelse($certificados as $certificado)
                    <tr class="table-data">
                        <td style="width: 75.3pt;">
                            <p style="margin: 0; text-align: center; line-height: 9.0pt;">
                                {{ $certificado->licencia_contrato ?? 'N/A' }}
                            </p>
                        </td>
                        <td style="width: 75.3pt;">
                            <p style="margin: 0.8pt 0.05pt 0.0001pt 0.75pt; text-align: center; line-height: 8.2pt;">
                                {{ $certificado->nombre_obra ?? '' }}
                            </p>
                        </td>
                        <td style="width: 75.3pt;">
                            <p style="margin: 0; text-align: center; line-height: 9.0pt;">
                                {{ $certificado->ciudad_ejecucion ?? '' }}
                            </p>
                        </td>
                        <td style="width: 75.3pt;">
                            <p style="margin: 0; text-align: center; line-height: 9.0pt;">
                                ${{ number_format($certificado->valor_pago ?? 0, 0, ',', '.') }}
                            </p>
                        </td>
                        <td style="width: 75.3pt;">
                            <p style="margin: 0; text-align: center; line-height: 9.0pt;">
                                {{ $certificado->periodo ?? '' }}
                            </p>
                        </td>
                        <td style="width: 75.3pt;">
                            <p style="margin: 0; text-align: center; line-height: 9.0pt;">
                                {{ optional($certificado->fecha)->format('d/m/Y') ?? '' }}
                            </p>
                        </td>
                        <td style="width: 75.3pt;">
                            <p style="margin: 0; text-align: center; line-height: 9.0pt;">
                                {{ $certificado->ticket ?? '' }}
                            </p>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center">
                            No hay transacciones para mostrar
                        </td>
                    </tr>
                    @endforelse
                    
                    <!-- Fila del total -->
                    <tr class="total-row">
                        <td style="width: 75.3pt;">
                            <p style="margin: 0; text-align: left; line-height: normal;">
                                &nbsp;
                            </p>
                        </td>
                        <td style="width: 75.3pt;">
                            <p style="margin: 0; text-align: left; line-height: normal;">
                                &nbsp;
                            </p>
                        </td>
                        <td style="width: 75.3pt;">
                            <p style="margin: 0; text-align: center; line-height: 8.0pt;">
                                Total
                            </p>
                        </td>
                        <td style="width: 75.3pt;">
                            <p style="margin: 0; text-align: center; line-height: 8.0pt;" class="negrita">
                                ${{ number_format($total ?? 0, 0, ',', '.') }}
                            </p>
                        </td>
                        <td style="width: 75.3pt;">
                            <p style="margin: 0; text-align: left; line-height: normal;">
                                &nbsp;
                            </p>
                        </td>
                        <td style="width: 75.3pt;">
                            <p style="margin: 0; text-align: left; line-height: normal;">
                                &nbsp;
                            </p>
                        </td>
                        <td style="width: 75.3pt;">
                            <p style="margin: 0; text-align: left; line-height: normal;">
                                &nbsp;
                            </p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <!-- Fecha de expedición -->
        <div class="fecha-expedicion">
            Expedido por el SENA, a los <span class="negrita">{{ $fecha_emision->day ?? now()->day }}</span> 
            (<span class="negrita">{{ $fecha_emision->day ?? now()->day }}</span>) días del mes de 
            <span class="negrita uppercase">{{ obtenerMesEspanol($fecha_emision->month ?? now()->month) }}</span> de 
            <span class="negrita">{{ $fecha_emision->year ?? now()->year }}</span>
        </div>
        
        <!-- Texto de advertencia -->
        <div class="advertencia-texto">
            "LA EXPEDICIÓN DE ESTA CERTIFICACIÓN, NO IMPIDE QUE EL SENA VERIFIQUE LA BASE DE LIQUIDACIÓN DE FIC Y QUE CONSTATE EL CUMPLIMIENTO EN FONDO NACIONAL DE FORMACIÓN PROFESIONAL DE LA INDUSTRIA DE LA CONSTRUCCIÓN FIC."
        </div>
        
        <!-- Sección de no validez -->
        <div class="no-validez">NO TIENE VALIDEZ PARA FINES TRIBUTARIOS</div>
        
        <!-- Información adicional -->
        <div class="info-section">
            <p>Este documento no tiene validez en procesos de selección contractual con entidades del estado.</p>
            <br>
            <p>La expedición de esta certificación no impide que el SENA verifique la base de liquidación de aportes y que constate el cumplimiento en Contrato de Aprendizaje.</p>
        </div>
        
        <!-- Expedido por -->
        <div class="expedido-por">
            @if($certificados->count() > 0)
                Expedido por el Servicio Nacional de Aprendizaje – <span class="negrita">{{ $certificados->first()->regional_sena ?? 'BOGOTÁ' }}</span>
            @else
                Expedido por el Servicio Nacional de Aprendizaje – <span class="negrita">BOGOTÁ</span>
            @endif
        </div>
        
        <!-- Generado por -->
        <div class="generado-por">
            @if($certificados->count() > 0)
                Generado por: <span class="negrita">{{ $certificados->first()->generado_por ?? 'Sistema SENA' }}</span>
            @else
                Generado por: <span class="negrita">Sistema SENA</span>
            @endif
        </div>
        
        <!-- Código de verificación -->
        <div class="verificacion-box">
            <p style="margin: 0;">
                ¿Desea saber si este certificado es auténtico?, por favor ingrese a la página web 
                <a href="https://certificadoempresarios.sena.edu.co/" class="verificacion-url">https://certificadoempresarios.sena.edu.co/</a> 
                enlace CONSULTAR CODIGO CERTIFICADO y digite:
            </p>
            <p style="margin-top: 0.75pt 0 0 0;">
                @if($certificados->count() > 0)
                    el código de verificación: <span class="negrita">{{ $certificados->first()->codigo_verificacion ?? 'CV001' }}</span> 
                    y el Número de Certificado: <span class="negrita">{{ $certificados->first()->numero_certificado ?? 'NC001' }}</span>
                @else
                    el código de verificación: <span class="negrita">CV001</span> 
                    y el Número de Certificado: <span class="negrita">NC001</span>
                @endif
            </p>
        </div>
        
        <!-- Footer -->
    <div class="footer">
        <img src="{{ asset('images/footer.jpg') }}" alt="@SENAComunica www.sena.edu.co" class="footer-image">
    </div>
        
        <!-- Número de página -->
        <div class="page-number">Pág. 1</div>
    </div>
</body>
</html>

<?php
function obtenerMesEspanol($mes) {
    $meses = [
        1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril',
        5 => 'mayo', 6 => 'junio', 7 => 'julio', 8 => 'agosto',
        9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre'
    ];
    return $meses[$mes] ?? 'mes';
}



?>