<?php

namespace App\Services\WhatsApp;

class TemplateService
{
    // Agrega estos métodos a tu TemplateService existente:

    public function getMenu(bool $compact = false): string
    {
        $msg = "📌 *MENÚ PRINCIPAL - Chatbot FIC*\n\n";
        
        if (!$compact) {
            $msg .= "¡Bienvenido! Selecciona una opción:\n\n";
        }
        
        $msg .= "• *Requisitos*\n";
        $msg .= "• *Soporte*\n";
        $msg .= "• *Autenticarse*\n";
        $msg .= "• *Registro*\n\n";
        
        $msg .= "Escribe el nombre de la opción, ejemplo: (*Requisitos*).";
        
        return $msg;
    }

    // Método para cierre de sesión
    public function getLogoutMessage(string $userName = 'Usuario'): string
    {
        return "✅ *SESIÓN CERRADA*\n\n" .
            "*{$userName}*, Has cerrado sesión exitosamente.\n\n" .
            "Para usar las funciones de certificados, deberás autenticarte nuevamente.\n\n" .
            "Escribe *MENU* para ver las opciones.";
    }

    // Método para usuario ya autenticado
    public function getAlreadyAuthenticated(string $userName, string $nit): string
    {
        return "✅ *YA ESTÁS AUTENTICADO*\n\n" .
            "Hola *{$userName}* (NIT: *{$nit}*)\n\n" .
            "Puedes usar todas las funciones:\n" .
            "• Escribe *1* para Generar Certificado\n" .
            "• Escribe *2* para Consultar Certificados\n" .
            "• Escribe *CERRAR SESION* para salir\n" .
            "• Escribe *MENU* para ver todas las opciones";
    }

    public function getRequirements(): string
    {
        return "📋 *REQUISITOS PARA CERTIFICADOS FIC*\n\n" .
               "• *NIT o Cédula del empresario*\n" .
               "• *Tipo de certificado* (Ticket, NIT o Vigencia)\n" .
               "• *Para vigencia*: año específico (máx. 15 años atrás)\n\n" .
               "Escribe *MENU* para volver al inicio.";
    }

    public function getSupportInfo(): string
    {
        return "📞 *SOPORTE TÉCNICO*\n\n" .
               "Para asistencia técnica contacta:\n\n" .
               "• Email: soporte@sena.edu.co\n" .
               "• Web: www.sena.edu.co\n\n" .
               "Escribe *MENU* para volver al inicio.";
    }

    public function getRegistrationInfo(): string
    {
        return "📝 *REGISTRO DE NUEVO USUARIO*\n\n" .
               "Para registrarte en nuestro sistema, debes ir a la pagina de oficial:\n\n" .
               "• *Web:* www.fic.sena.edu.co/registro\n\n" .
               "Escribe *MENU* para volver al inicio.";
    }

    public function getConsultCertificateInfo(): string
    {
        return "🔍 *CONSULTAR CERTIFICADOS*\n\n" .
               "Puedes consultar y descargar certificados que ya has generado.\n\n" .
               "Para consultar, necesitas estar autenticado con tu usuario y contraseña.\n\n" .
               "Una vez autenticado, podrás:\n" .
               "• Ver tu historial de certificados\n" .
               "• Descargar certificados anteriores\n" .
               "• Ver estadísticas de uso\n\n" .
               "Escribe *CONSULTAR* para comenzar o *MENU* para volver al inicio.";
    }

    public function getCertificateOptions(): string
    {
        return "📄 *GENERAR CERTIFICADO FIC*\n\n" .
               "Por favor indica el *tipo* de certificado escribiendo su nombre o número:\n\n" .
               "• *TICKET* - Certificado específico por número de ticket\n" .
               "• *NIT* - Todos los certificados asociados a tu NIT\n" .
               "• *VIGENCIA* - Certificado filtrado por año de vigencia\n\n" .
               "Ejemplo: responde *NIT* para buscar todos tus certificados.";
    }

    public function getAuthPrompt(): string
    {
        return "🔐 *VALIDACIÓN DE USUARIO*\n\n" .
               "⚠️ *Debes validar tu información antes de generar o consultar certificados.*\n\n" .
               "Por favor, ingresa tu *USUARIO*:";
    }

    public function getAuthSuccess(string $representanteLegal, string $nit): string
    {
        return "✅ *AUTENTICACIÓN EXITOSA*\n\n" .
               "Bienvenido *{$representanteLegal}*\n" .
               "• NIT: *{$nit}*\n\n" .
               "Ahora puedes generar o consultar certificados.\n\n";
    }

    public function getUserNotFound(): string
    {
        return "❌ *USUARIO NO REGISTRADO*\n\n" .
               "No tienes usuario registrado con nosotros.\n\n" .
               "Por favor, *regístrate* y vuelve aquí!\n\n" .
               "Escribe *REGISTRO* para ver información de registro o *MENU* para volver al inicio.";
    }

    public function getWrongPassword(): string
    {
        return "❌ *CONTRASEÑA INCORRECTA*\n\n" .
               "La contraseña ingresada no es correcta.\n\n" .
               "Por favor, vuelve a ingresar tu *USUARIO* o escribe *MENU* para volver al inicio.";
    }

    public function getCertificatePrompt(string $type): string
    {
        switch ($type) {
            case 'ticket':
                return "• *Certificado por TICKET*\n\nPor favor ingresa el número de *TICKET*:";
            case 'vigencia':
                $yearRange = app(CertificateService::class)->getYearRange();
                return "• *Certificado por VIGENCIA*\n\nIngresa el *AÑO* de la vigencia (ejemplo: 2025). Solo se permiten 15 años atrás desde el actual ({$yearRange['min']} - {$yearRange['max']}).";
            default:
                return "";
        }
    }

    public function getCertificateGenerated(): string
    {
        return "✅ *Certificado generado exitosamente!*\n\nTu certificado FIC ha sido generado y enviado.\n\n" .
               "¿Necesitas algo más? Escribe *MENU* para ver las opciones.";
    }

    public function getCertificateNotFound(): string
    {
        return "❌ *No se encontraron certificados*\n\nNo hay certificados con los criterios especificados.";
    }

    public function getProcessingCertificate(): string
    {
        return "⏳ *Generando certificado...*\n\nPor favor espera unos segundos.";
    }

    public function getUnknownCommand(): string
    {
        return "No entendí 🤔. Puedes escribir: *MENU* para ver las opciones, *Generar Certificado*, *Consultar Certificados*, *Requisitos*, *Soporte* o *Registro*.";
    }

    public function getErrorSystem(): string
    {
        return "❌ *Error del sistema*\n\nPor favor intenta nuevamente o contacta a soporte.";
    }

    public function getNotAuthenticated(): string
    {
        return "❌ *Debes autenticarte primero*\n\n" .
            "Para generar o consultar certificados necesitas iniciar sesión.\n\n" .
            "📋 *Opciones disponibles:*\n" .
            "• Escribe *AUTENTICAR* para iniciar sesión\n" .
            "• Escribe *MENU* para ver todas las opciones\n" .
            "• Escribe *REGISTRO* si no tienes cuenta\n\n";
    }

    public function getCompanyInfoNotFound(): string
    {
        return "❌ Error: No se encontró información de la empresa. Por favor, autentícate nuevamente.";
    }

    public function getConsultCertificateList(array $certificados): string
    {
        if (empty($certificados)) {
            return "📭 *No hay certificados generados*\n\nNo se encontraron certificados generados para tu empresa.\n\n" .
                   "Genera un certificado nuevo escribiendo *Generar Certificado*.";
        }

        $msg = "📋 *Tus Certificados Generados*\n\n";
        
        foreach ($certificados as $index => $cert) {
            $numero = $index + 1;
            $fecha = $cert['fecha'] ?? 'Fecha no disponible';
            $serial = $cert['serial'] ?? 'N/A';
            
            $msg .= "*{$numero}.* 📄 *{$serial}*\n";
            $msg .= "   📅 {$fecha}\n";
            
            if (isset($cert['tipo'])) {
                $tipo = match($cert['tipo']) {
                    'nit_general' => 'General',
                    'nit_ticket' => 'Ticket',
                    'nit_vigencia' => 'Vigencia',
                    default => $cert['tipo']
                };
                $msg .= "   🏷️ Tipo: {$tipo}\n";
            }            
            
            $msg .= "\n";
        }
        
        $msg .= "Responde con el *número* del certificado que deseas descargar.\n";
        $msg .= "Escribe *0* para volver al menú principal.";
        
        return $msg;
    }

    public function getCertificateDetails(array $certificado): string
    {
        $serial = $certificado['serial'] ?? 'N/A';
        $fecha = $certificado['fecha'] ?? 'Fecha no disponible';
        $tipo = $certificado['tipo'] ?? 'Desconocido';
        $registros = $certificado['registros'] ?? 0;
        $valorTotal = $certificado['valor_total'] ?? 0;
        
        $tipoTexto = match($tipo) {
            'nit_general' => 'General',
            'nit_ticket' => 'Ticket',
            'nit_vigencia' => 'Vigencia',
            default => $tipo
        };
        
        return "✅ *Certificado seleccionado*\n\n" .
               "• *Serial:* {$serial}\n" .
               "• *Fecha generación:* {$fecha}\n" .
               "• *Tipo:* {$tipoTexto}\n" .
               "¿Deseas descargar este certificado?\n\n" .
               "Responde *SI* para confirmar o *NO* para cancelar.";
    }

    public function getDownloadConfirmed(string $serial): string
    {
        return "✅ *Certificado descargado*\n\n" .
               "El certificado *{$serial}* ha sido descargado exitosamente.\n\n" .
               "¿Necesitas algo más? Escribe *MENU* para ver las opciones.";
    }

    public function getDownloadCancelled(): string
    {
        return "❌ Descarga cancelada.\n\n" .
               "Puedes seleccionar otro certificado o escribir *MENU* para volver al inicio.";
    }

    public function getNoCertificatesAvailable(): string
    {
        return "📭 *No hay certificados disponibles*\n\n" .
               "No se encontraron certificados generados para tu empresa.\n\n" .
               "Puedes generar uno nuevo seleccionando la opción *Generar Certificado*.";
    }
}