<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Certificado FIC - SENA</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            margin: 0;
            padding: 30px;
            font-size: 12px;
            line-height: 1.3;
        }
        .ministerio {
            text-align: center;
            font-weight: bold;
            font-size: 14px;
            margin-bottom: 10px;
        }
        .certifica {
            text-align: center;
            font-weight: bold;
            font-size: 12px;
            margin: 20px 0;
        }
        .contenido {
            text-align: justify;
            margin: 15px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            font-size: 10px;
        }
        table, th, td {
            border: 1px solid black;
        }
        th, td {
            padding: 8px;
            text-align: center;
            vertical-align: middle;
        }
        th {
            background-color: #f0f0f0;
            font-weight: bold;
        }
        .total-row {
            font-weight: bold;
            background-color: #e0e0e0;
        }
        .footer {
            margin-top: 25px;
            font-size: 10px;
            text-align: justify;
        }
        .footer p {
            margin: 6px 0;
        }
        .advertencia {
            font-style: italic;
            text-align: center;
            margin: 15px 0;
            padding: 0 20px;
        }
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
        .firma {
            margin-top: 40px;
            border-top: 1px solid #000;
            width: 300px;
            padding-top: 5px;
            text-align: center;
            font-size: 10px;
        }
        .codigo-verificacion {
            margin-top: 15px;
            padding: 10px;
            border: 1px solid #000;
            background-color: #f9f9f9;
        }
    </style>
</head>
<body>
    <!-- Encabezado -->
    <div class="ministerio">MINISTERIO DE TRABAJO</div>
    
    <div class="certifica">CERTIFICA:</div>

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
    <table>
        <thead>
            <tr>
                <th style="width: 15%">N°<br>Licencia/Contrato</th>
                <th style="width: 20%">Nombre Obra</th>
                <th style="width: 15%">Ciudad Ejecución</th>
                <th style="width: 15%">Valor Pago</th>
                <th style="width: 10%">Periodo</th>
                <th style="width: 10%">Fecha</th>
                <th style="width: 15%">Ticket</th>
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
                <td colspan="2"></td>
                <td class="text-right"><strong>Total</strong></td>
                <td class="text-right"><strong>${{ number_format($total, 0, ',', '.') }}</strong></td>
                <td colspan="3"></td>
            </tr>
        </tbody>
    </table>

    <!-- Fecha de expedición -->
    <div class="footer">
        <p>
            Expedido por el SENA, a los <span class="negrita">{{ $fecha_emision->day }}</span> 
            (<span class="negrita">{{ $fecha_emision->day }}</span>) días del mes de 
            <span class="negrita">{{ obtenerMesEspanol($fecha_emision->month) }}</span> de 
            <span class="negrita">{{ $fecha_emision->year }}</span>
        </p>
    </div>

    <!-- Texto de advertencia -->
    <div class="advertencia">
        "LA EXPEDICIÓN DE ESTA CERTIFICACIÓN, NO IMPIDE QUE EL SENA VERIFIQUE LA BASE DE LIQUIDACIÓN DE FIC Y QUE CONSTATE EL CUMPLIMIENTO EN FONDO NACIONAL DE FORMACIÓN PROFESIONAL DE LA INDUSTRIA DE LA CONSTRUCCIÓN FIC."
    </div>

    <!-- Información adicional -->
    <div class="footer">
        <p class="negrita">NO TIENE VALIDEZ PARA FINES TRIBUTARIOS</p>
        
        <p>Este documento no tiene validez en procesos de selección contractual con entidades del estado.</p>
        
        <p>La expedición de esta certificación no impide que el SENA verifique la base de liquidación de aportes y que constate el cumplimiento en Contrato de Aprendizaje.</p>
        
        <p>Expedido por el Servicio Nacional de Aprendizaje – <span class="negrita">{{ $constructor->regional_sena }}</span></p>
        
        <p>Generado por: <span class="negrita">{{ $constructor->generado_por }}</span></p>
        
        <p>
            ¿Desea saber si este certificado es auténtico?, por favor ingrese a la página web 
            <span class="subrayado">https://certificadoempresarios.sena.edu.co/</span> 
            enlace CONSULTAR CODIGO CERTIFICADO y dígite:
        </p>
        
        <div class="codigo-verificacion">
            <p>el código de verificación: <span class="negrita">{{ $certificados->first()->codigo_verificacion }}</span></p>
            <p>y el Número de Certificado: <span class="negrita">{{ $certificados->first()->numero_certificado }}</span></p>
        </div>

        <!-- Firma -->
        <div class="firma">
            <p>___________________________________</p>
            <p>Firma y Sello</p>
            <p>Servicio Nacional de Aprendizaje - SENA</p>
            <p>{{ $constructor->regional_sena }}</p>
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