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
            padding: 60px 70px 100px; /* Más padding abajo para el footer */
            font-size: 11pt;
            line-height: 1.2;
            color: #000000;
            position: relative;
            min-height: 29.7cm; /* Altura aproximada A4 */
        }
        
        /* Contenedor principal */
        .page-container {
            max-width: 21cm;
            margin: 0 auto;
            position: relative;
        }
        
        /* Encabezado con logo */
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
        
        /* Fecha de expedición - CENTRADA como en la imagen */
        .fecha-expedicion {
            margin: 30px 0;
            text-align: center;
            font-size: 11pt;
        }
        
        /* Texto de advertencia - SIN CUADRO, centrado como en la imagen */
        .advertencia-texto {
            margin: 25px 0;
            font-size: 10pt;
            text-align: center;
            font-weight: bold;
        }
        
        .advertencia-linea {
            display: block;
            margin: 2px 0;
        }
        
        /* Sección de no validez */
        .no-validez {
            font-weight: bold;
            text-align: center;
            margin: 25px 0;
            font-size: 11pt;
            text-transform: uppercase;
        }
        
        /* Información adicional - ALINEADO A LA IZQUIERDA como en la imagen */
        .info-section {
            margin: 25px 0;
            font-size: 10pt;
            text-align: left;
        }
        
        .info-section p {
            margin: 8px 0;
        }
        
        /* Expedido por - CENTRADO Y EN NEGRITA como en la imagen */
        .expedido-por {
            font-weight: bold;
            text-align: center;
            margin: 25px 0;
            font-size: 11pt;
        }
        
        /* Código de verificación - MANTENER BORDE como en las plantillas originales */
        .verificacion-box {
            margin: 30px 0 50px 0;
            padding: 20px;
            border: 1px solid #000;
            background-color: #ffffff;
            font-size: 10pt;
            text-align: left;
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
        
        /* FOOTER - Basado en la imagen proporcionada */
        .footer {
            position: absolute;
            bottom: 40px;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 9pt;
            color: #000;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            margin-top: 50px;
        }
        
        .footer-social {
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .footer-website {
            color: #0054a6;
            font-weight: bold;
        }
        
        /* Número de página en cada hoja */
        .page-number {
            position: absolute;
            bottom: 20px;
            right: 70px;
            font-size: 9pt;
            color: #666;
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
        
        /* Para manejar múltiples páginas si es necesario */
        .page-break {
            page-break-after: always;
        }
        
        /* Estilos para impresión */
        @media print {
            body {
                padding: 50px 60px 80px;
            }
            
            .footer {
                position: fixed;
                bottom: 20px;
                left: 60px;
                right: 60px;
            }
            
            .page-number {
                position: fixed;
                bottom: 20px;
                right: 60px;
            }
        }
    </style>
</head>
<body>
    <div class="page-container">
        <!-- Encabezado con logo -->
        <div class="header">
            <div class="logo-line">
                <!-- Logo del SENA -->
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
        
        <!-- Fecha de expedición - CENTRADA -->
        <div class="fecha-expedicion">
            Expedido por el SENA, a los <span class="negrita">{{ $fecha_emision->day }}</span> 
            (<span class="negrita">{{ $fecha_emision->day }}</span>) días del mes de 
            <span class="negrita uppercase">{{ obtenerMesEspanol($fecha_emision->month) }}</span> de 
            <span class="negrita">{{ $fecha_emision->year }}</span>
        </div>
        
        <!-- Texto de advertencia - SIN CUADRO, dividido en líneas -->
        <div class="advertencia-texto">
            <span class="advertencia-linea">"LA EXPEDICIÓN DE ESTA CERTIFICACIÓN, NO IMPIDE QUE EL SENA VERIFIQUE</span>
            <span class="advertencia-linea">LA BASE DE LIQUIDACIÓN DE FIC Y QUE CONSTATE EL CUMPLIMIENTO EN</span>
            <span class="advertencia-linea">FONDO NACIONAL DE FORMACIÓN PROFESIONAL DE LA INDUSTRIA DE LA</span>
            <span class="advertencia-linea">CONSTRUCCIÓN FIC."</span>
        </div>
        
        <!-- Sección de no validez -->
        <div class="no-validez">NO TIENE VALIDEZ PARA FINES TRIBUTARIOS</div>
        
        <!-- Información adicional - ALINEADO A LA IZQUIERDA -->
        <div class="info-section">
            <p>Este documento no tiene validez en procesos de selección contractual con entidades del estado.</p>
            
            <p>La expedición de esta certificación no impide que el SENA verifique la base de liquidación de
            aportes y que constate el cumplimiento en Contrato de Aprendizaje.</p>
        </div>
        
        <!-- Expedido por - CENTRADO Y EN NEGRITA -->
        <div class="expedido-por">
            Expedido por el Servicio Nacional de Aprendizaje – <span class="negrita">{{ $constructor->regional_sena }}</span>
        </div>
        
        <!-- Generado por - CENTRADO -->
        <div class="text-center spacing-1">
            <p>Generado por: <span class="negrita">{{ $constructor->generado_por }}</span></p>
        </div>
        
        <!-- Código de verificación - MANTENER BORDE como en plantillas originales -->
        <div class="verificacion-box">
            <div class="verificacion-title">¿Desea saber si este certificado es auténtico?, por favor ingrese a la página web:</div>
            
            <div class="verificacion-url">https://certificadoempresarios.sena.edu.co/</div>
            
            <div>enlace CONSULTAR CODIGO CERTIFICADO y digite:</div>
            
            <div class="codigos">
                <p>el código de verificación: <span class="negrita">{{ $certificados->first()->codigo_verificacion }}</span></p>
                <p>y el Número de Certificado: <span class="negrita">{{ $certificados->first()->numero_certificado }}</span></p>
            </div>
        </div>
        
        <!-- Footer fijo en cada página -->
        <div class="footer">
            <div class="footer-social">@SENAComunica</div>
            <div class="footer-website">www.sena.edu.co</div>
        </div>
        
        <!-- Número de página (en cada hoja) -->
        <div class="page-number">Pág. 1</div>
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