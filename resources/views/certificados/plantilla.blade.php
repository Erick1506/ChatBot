<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Certificado FIC - SENA</title>
    <style>
        /* Estilos generales */
        body { 
            font-family: 'Times New Roman', Times, serif;
            margin: 0;
            padding: 40px 50px;
            font-size: 12px;
            line-height: 1.4;
            color: #000;
        }
        
        /* Encabezado con logo */
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 2px solid #0054a6;
        }
        
        .logo-container {
            margin-bottom: 15px;
        }
        
        .logo {
            max-height: 80px;
            margin-bottom: 10px;
        }
        
        .institucion {
            font-weight: bold;
            font-size: 16px;
            color: #0054a6;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 5px;
        }
        
        .ministerio {
            font-weight: bold;
            font-size: 14px;
            margin-bottom: 10px;
        }
        
        /* Título certifica */
        .certifica-container {
            text-align: center;
            margin: 30px 0 40px 0;
        }
        
        .certifica {
            font-weight: bold;
            font-size: 14px;
            text-transform: uppercase;
            display: inline-block;
            padding: 8px 30px;
            border-bottom: 2px solid #000;
        }
        
        /* Contenido principal */
        .contenido {
            text-align: justify;
            margin: 20px 0 25px 0;
            font-size: 12px;
        }
        
        /* Tabla de transacciones */
        .table-container {
            margin: 25px 0 30px 0;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
            border: 1px solid #000;
        }
        
        th {
            background-color: #e6eef7;
            font-weight: bold;
            text-align: center;
            padding: 10px 5px;
            border: 1px solid #000;
            vertical-align: middle;
        }
        
        td {
            padding: 8px 5px;
            border: 1px solid #000;
            vertical-align: middle;
        }
        
        .total-row {
            font-weight: bold;
            background-color: #d9e6f7;
        }
        
        /* Fecha de expedición */
        .fecha-expedicion {
            margin: 25px 0;
            text-align: right;
            font-size: 12px;
        }
        
        /* Advertencia */
        .advertencia {
            margin: 25px 0;
            padding: 15px;
            border: 1px solid #ccc;
            background-color: #f9f9f9;
            font-size: 11px;
            text-align: justify;
            font-style: italic;
        }
        
        /* Información adicional */
        .info-adicional {
            margin: 25px 0;
            font-size: 11px;
            text-align: justify;
        }
        
        .no-validez {
            font-weight: bold;
            color: #cc0000;
            text-align: center;
            margin: 15px 0;
            font-size: 12px;
        }
        
        /* Código de verificación */
        .codigo-verificacion {
            margin: 25px 0;
            padding: 15px;
            border: 2px solid #0054a6;
            background-color: #f0f7ff;
            text-align: center;
            font-size: 12px;
        }
        
        .codigo-verificacion p {
            margin: 5px 0;
        }
        
        /* Firma y sello */
        .firma-container {
            margin-top: 50px;
            text-align: center;
        }
        
        .firma {
            display: inline-block;
            text-align: center;
            padding-top: 20px;
            border-top: 1px solid #000;
            width: 350px;
            margin: 0 auto;
        }
        
        .firma p {
            margin: 3px 0;
            font-size: 11px;
        }
        
        .sello {
            font-weight: bold;
            margin-top: 10px;
        }
        
        /* Utilidades */
        .negrita {
            font-weight: bold;
        }
        
        .subrayado {
            text-decoration: underline;
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
        
        /* Número de página */
        .page-number {
            position: absolute;
            bottom: 20px;
            right: 50px;
            font-size: 10px;
            color: #666;
        }
    </style>
</head>
<body>
    <!-- Encabezado con logo del SENA -->
    <div class="header">
        <div class="logo-container">
            <!-- Reemplaza el src con la URL o ruta de tu logo del SENA -->
            <img src="https://www.sena.edu.co/Images/sena2020/logo-sena.png" alt="Logo SENA" class="logo">
            <!-- O si no tienes imagen, usa texto estilizado -->
            <!-- <div class="logo-text" style="font-size: 24px; font-weight: bold; color: #0054a6;">SENA</div> -->
        </div>
        
        <div class="institucion">SERVICIO NACIONAL DE APRENDIZAJE - SENA</div>
        <div class="ministerio">MINISTERIO DE TRABAJO</div>
    </div>
    
    <!-- Título CERTIFICA -->
    <div class="certifica-container">
        <div class="certifica">CERTIFICA:</div>
    </div>
    
    <!-- Contenido principal -->
    <div class="contenido">
        <p>
            Que el constructor con razón social <span class="negrita">{{ $constructor->constructor_razon_social }}</span> 
            identificado con el NIT <span class="negrita">{{ $constructor->constructor_nit }}</span>, 
            por concepto de Fondo Nacional de Formación Profesional de la Industria de la Construcción FIC, 
            realizó a través del botón electrónico de pagos las siguientes transacciones:
        </p>
    </div>
    
    <!-- Tabla de transacciones -->
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th style="width: 15%">N°<br>Licencia/Contrato</th>
                    <th style="width: 20%">Nombre Obra</th>
                    <th style="width: 12%">Ciudad<br>Ejecución</th>
                    <th style="width: 15%">Valor Pago</th>
                    <th style="width: 10%">Periodo</th>
                    <th style="width: 10%">Fecha</th>
                    <th style="width: 18%">Ticket</th>
                </tr>
            </thead>
            <tbody>
                @foreach($certificados as $certificado)
                <tr>
                    <td>{{ $certificado->licencia_contrato ?? 'N/A' }}</td>
                    <td class="text-left">{{ $certificado->nombre_obra }}</td>
                    <td>{{ $certificado->ciudad_ejecucion }}</td>
                    <td class="text-right">${{ number_format($certificado->valor_pago, 0, ',', '.') }}</td>
                    <td>{{ $certificado->periodo }}</td>
                    <td>{{ $certificado->fecha->format('d/m/Y') }}</td>
                    <td>{{ $certificado->ticket }}</td>
                </tr>
                @endforeach
                <!-- Fila del total -->
                <tr class="total-row">
                    <td colspan="3" class="text-right"><strong>TOTAL</strong></td>
                    <td class="text-right"><strong>${{ number_format($total, 0, ',', '.') }}</strong></td>
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
    <div class="advertencia">
        <p>"LA EXPEDICIÓN DE ESTA CERTIFICACIÓN, NO IMPIDE QUE EL SENA VERIFIQUE LA BASE DE LIQUIDACIÓN DE FIC Y QUE CONSTATE EL CUMPLIMIENTO EN FONDO NACIONAL DE FORMACIÓN PROFESIONAL DE LA INDUSTRIA DE LA CONSTRUCCIÓN FIC."</p>
    </div>
    
    <!-- Información adicional -->
    <div class="info-adicional">
        <div class="no-validez">NO TIENE VALIDEZ PARA FINES TRIBUTARIOS</div>
        
        <p>Este documento no tiene validez en procesos de selección contractual con entidades del estado.</p>
        
        <p>La expedición de esta certificación no impide que el SENA verifique la base de liquidación de aportes y que constate el cumplimiento en Contrato de Aprendizaje.</p>
        
        <p>Expedido por el Servicio Nacional de Aprendizaje – <span class="negrita">{{ $constructor->regional_sena }}</span></p>
        
        <p>Generado por: <span class="negrita">{{ $constructor->generado_por }}</span></p>
    </div>
    
    <!-- Código de verificación -->
    <div class="codigo-verificacion">
        <p><span class="negrita">¿Desea saber si este certificado es auténtico?</span></p>
        <p>Por favor ingrese a la página web: <span class="subrayado">https://certificadoempresarios.sena.edu.co/</span></p>
        <p>Enlace: <span class="negrita">CONSULTAR CÓDIGO CERTIFICADO</span> y digite:</p>
        <br>
        <p>El código de verificación: <span class="negrita">{{ $certificados->first()->codigo_verificacion }}</span></p>
        <p>Y el Número de Certificado: <span class="negrita">{{ $certificados->first()->numero_certificado }}</span></p>
    </div>
    
    <!-- Firma y sello -->
    <div class="firma-container">
        <div class="firma">
            <p>___________________________________</p>
            <p class="sello">FIRMA Y SELLO</p>
            <p>Servicio Nacional de Aprendizaje - SENA</p>
            <p>{{ $constructor->regional_sena }}</p>
        </div>
    </div>
    
    <!-- Número de página (opcional) -->
    <div class="page-number">Pág. 1</div>
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