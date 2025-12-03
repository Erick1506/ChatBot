<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Certificado FIC - SENA</title>
    <style>
        /* RESET Y ESTILOS GENERALES */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body { 
            font-family: 'Arial', sans-serif;
            font-size: 11px;
            color: #000000;
            background-color: #fff;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            min-height: 100vh;
            width: 100%;
            padding: 20px 0;
        }
        
        /* CONTENEDOR PRINCIPAL (HOJA A4) */
        .page-container {
            width: 21cm;
            min-height: 29.7cm;
            background: white;
            margin: 0 auto;
            padding: 40px 50px;
            position: relative;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        
        /* LOGO - CENTRADO Y AJUSTADO */
        .logo-container {
            text-align: center;
            margin: 0 auto 15px auto;
            width: 100%;
        }
        
        .logo {
            height: 100px;
            width: auto;
            max-width: 120px;
            margin-bottom: 5px;
        }
        
        /* ENCABEZADO */
        .header {
            text-align: center;
            margin-bottom: 20px;
            padding-top: 0;
        }
        
        .institucion {
            font-size: 16px;
            font-family: 'Arial', sans-serif;
            line-height: 1.3;
            margin-bottom: 5px;
        }
        
        .ministerio {
            font-size: 11px;
            font-family: 'Arial', sans-serif;
            font-weight: bold;
            line-height: 1.2;
        }
        
        /* TÍTULO CERTIFICA */
        .certifica-title {
            text-align: center;
            font-size: 16px;
            font-weight: bold;
            margin: 20px 0;
        }
        
        /* PÁRRAFO DE INTRODUCCIÓN */
        .intro-paragraph {
            text-align: left;
            margin: 15px 0 20px 0;
            font-size: 11px;
            line-height: 1.2;
            padding: 0 10px;
        }
        
        /* TABLA */
        .table-container {
            margin: 20px auto;
            width: 100%;
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid black;
            margin: 0 auto;
            font-size: 10px;
        }
        
        th, td {
            border: 1px solid black;
            padding: 5px 3px;
            text-align: center;
            vertical-align: middle;
        }
        
        th {
            font-weight: bold;
            background-color: #f5f5f5;
        }
        
        /* INFORMACIÓN INFERIOR */
        .fecha-expedicion,
        .advertencia-texto,
        .no-validez,
        .info-section,
        .expedido-por,
        .generado-por,
        .verificacion-box {
            text-align: center;
            margin: 10px auto;
            padding: 0 10px;
            line-height: 1.3;
        }
        
        .fecha-expedicion {
            margin-top: 25px;
        }
        
        .advertencia-texto {
            font-weight: bold;
            margin: 15px auto;
        }
        
        .no-validez {
            font-weight: bold;
            margin: 10px auto;
        }
        
        /* FOOTER */
        .footer {
            text-align: center;
            margin-top: 40px;
            padding: 10px;
        }
        
        .footer-image {
            width: 200px;
            height: auto;
            max-height: 80px;
        }
        
        /* NÚMERO DE PÁGINA */
        .page-number {
            position: absolute;
            bottom: 20px;
            right: 50px;
            font-size: 9px;
            color: #666;
        }
        
        /* UTILIDADES */
        .negrita {
            font-weight: bold;
        }
        
        .uppercase {
            text-transform: uppercase;
        }
        
        /* RESPONSIVE PARA IMPRESIÓN */
        @media print {
            body {
                padding: 0;
                margin: 0;
            }
            
            .page-container {
                box-shadow: none;
                padding: 30px 40px;
                margin: 0;
                width: 100%;
                min-height: 100vh;
            }
            
            .footer {
                margin-top: 30px;
            }
        }
    </style>
</head>
<body>
    <div class="page-container">
        <!-- LOGO CENTRADO -->
        <div class="logo-container">
            <img src="https://www.sena.edu.co/Style%20Library/alayout/images/logoSena.png?rev=40" 
                 alt="Logo SENA" 
                 class="logo">
        </div>
        
        <!-- ENCABEZADO -->
        <div class="header">
            <h1 class="institucion">Servicio Nacional de Aprendizaje SENA</h1>
            <p class="ministerio">MINISTERIO DE TRABAJO</p>
        </div>
        
        <!-- TÍTULO CERTIFICA -->
        <div class="certifica-title">CERTIFICA:</div>
        
        <!-- CONTENIDO PRINCIPAL -->
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
        
        <!-- TABLA DE TRANSACCIONES -->
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th style="width: 12%;">
                            <div>N°</div>
                            <div style="font-size: 9px;">Licencia/Contrato</div>
                        </th>
                        <th style="width: 20%;">Nombre Obra</th>
                        <th style="width: 15%;">Ciudad Ejecución</th>
                        <th style="width: 15%;">Valor Pago</th>
                        <th style="width: 10%;">Periodo</th>
                        <th style="width: 10%;">Fecha</th>
                        <th style="width: 10%;">Ticket</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($certificados as $certificado)
                    <tr>
                        <td>{{ $certificado->licencia_contrato ?? 'N/A' }}</td>
                        <td>{{ $certificado->nombre_obra ?? '' }}</td>
                        <td>{{ $certificado->ciudad_ejecucion ?? '' }}</td>
                        <td>${{ number_format($certificado->valor_pago ?? 0, 0, ',', '.') }}</td>
                        <td>{{ $certificado->periodo ?? '' }}</td>
                        <td>{{ optional($certificado->fecha)->format('d/m/Y') ?? '' }}</td>
                        <td>{{ $certificado->ticket ?? '' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" style="text-align: center;">
                            No hay transacciones para mostrar
                        </td>
                    </tr>
                    @endforelse
                    
                    <!-- FILA DEL TOTAL -->
                    <tr style="font-weight: bold;">
                        <td colspan="2"></td>
                        <td style="text-align: center;">Total</td>
                        <td style="text-align: center;">${{ number_format($total ?? 0, 0, ',', '.') }}</td>
                        <td colspan="3"></td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <!-- FECHA DE EXPEDICIÓN -->
        <div class="fecha-expedicion">
            Expedido por el SENA, a los <span class="negrita">{{ $fecha_emision->day ?? now()->day }}</span> 
            (<span class="negrita">{{ $fecha_emision->day ?? now()->day }}</span>) días del mes de 
            <span class="negrita uppercase">{{ obtenerMesEspanol($fecha_emision->month ?? now()->month) }}</span> de 
            <span class="negrita">{{ $fecha_emision->year ?? now()->year }}</span>
        </div>
        
        <!-- TEXTO DE ADVERTENCIA -->
        <div class="advertencia-texto">
            "LA EXPEDICIÓN DE ESTA CERTIFICACIÓN, NO IMPIDE QUE EL SENA VERIFIQUE LA BASE DE LIQUIDACIÓN DE FIC Y QUE CONSTATE EL CUMPLIMIENTO EN FONDO NACIONAL DE FORMACIÓN PROFESIONAL DE LA INDUSTRIA DE LA CONSTRUCCIÓN FIC."
        </div>
        
        <!-- SECCIÓN DE NO VALIDEZ -->
        <div class="no-validez">NO TIENE VALIDEZ PARA FINES TRIBUTARIOS</div>
        
        <!-- INFORMACIÓN ADICIONAL -->
        <div class="info-section">
            <p>Este documento no tiene validez en procesos de selección contractual con entidades del estado.</p>
            <p>La expedición de esta certificación no impide que el SENA verifique la base de liquidación de aportes y que constate el cumplimiento en Contrato de Aprendizaje.</p>
        </div>
        
        <!-- EXPEDIDO POR -->
        <div class="expedido-por">
            @if($certificados->count() > 0)
                Expedido por el Servicio Nacional de Aprendizaje – <span class="negrita">{{ $certificados->first()->regional_sena ?? 'BOGOTÁ' }}</span>
            @else
                Expedido por el Servicio Nacional de Aprendizaje – <span class="negrita">BOGOTÁ</span>
            @endif
        </div>
        
        <!-- GENERADO POR -->
        <div class="generado-por">
            @if($certificados->count() > 0)
                Generado por: <span class="negrita">{{ $certificados->first()->generado_por ?? 'Sistema SENA' }}</span>
            @else
                Generado por: <span class="negrita">Sistema SENA</span>
            @endif
        </div>
        
        <!-- CÓDIGO DE VERIFICACIÓN -->
        <div class="verificacion-box">
            <p>
                ¿Desea saber si este certificado es auténtico?, por favor ingrese a la página web 
                <a href="https://certificadoempresarios.sena.edu.co/" style="color: blue; text-decoration: underline;">
                    https://certificadoempresarios.sena.edu.co/
                </a> 
                enlace CONSULTAR CODIGO CERTIFICADO y digite:
            </p>
            <p style="margin-top: 5px;">
                @if($certificados->count() > 0)
                    el código de verificación: <span class="negrita">{{ $certificados->first()->codigo_verificacion ?? 'CV001' }}</span> 
                    y el Número de Certificado: <span class="negrita">{{ $certificados->first()->numero_certificado ?? 'NC001' }}</span>
                @else
                    el código de verificación: <span class="negrita">CV001</span> 
                    y el Número de Certificado: <span class="negrita">NC001</span>
                @endif
            </p>
        </div>
        
        <!-- FOOTER -->
        <div class="footer">
            <img src="{{ asset('images/footer.jpg') }}" alt="@SENAComunica www.sena.edu.co" class="footer-image">
        </div>
        
        <!-- NÚMERO DE PÁGINA -->
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