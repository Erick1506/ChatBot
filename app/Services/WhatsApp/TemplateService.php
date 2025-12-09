<?php

namespace App\Services\WhatsApp;

use App\Services\WhatsApp\CertificateService;

class TemplateService
{
    // ========== MENÚS ==========
    public function getMenu(bool $compact = false): string
    {
        $msg = "📌 *MENÚ PRINCIPAL - Chatbot FIC*\n\n";
        
        if (!$compact) {
            $msg .= "¡Bienvenido! Escribe el nombre de una opción:\n\n";
        }
        
        $msg .= "• *Requisitos*\n";
        $msg .= "• *Soporte*\n";
        $msg .= "• *Autenticarse*\n";
        $msg .= "• *Registro*\n\n";
        
        if (!$compact) {
            $msg .= "🔒 *Nota:* Para Generar o Consultar Certificados necesitas autenticarte primero.\n";
            $msg .= "Usa la opción *Autenticarse* para iniciar sesión.\n\n";
        }
        
        $msg .= "Escribe el nombre de la opción, ejemplo: (*Requisitos*).";
        
        return $msg;
    }

    public function getAuthenticatedMenu(string $userName, string $nit): string
    {
        return "👋 ¡Hola *{$userName}*! (NIT: *{$nit}*)\n\n" .
            "Selecciona una opción:\n\n" .
            "• *Generar Certificado*\n" .
            "• *Consultar Certificados*\n" .
            "• *Requisitos*\n" .
            "• *Soporte*\n" .
            "• *Cerrar Sesión*\n" .
            "• *Registro*\n\n" .
            "Escribe el nombre de la opción.";
    }

    // ========== AUTENTICACIÓN ==========
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

    public function getLogoutMessage(string $userName = 'Usuario'): string
    {
        return "✅ *SESIÓN CERRADA*\n\n" .
               "Adiós *{$userName}*. Has cerrado sesión exitosamente.\n\n" .
               "Para usar las funciones de certificados, deberás autenticarte nuevamente.\n\n" .
               "Escribe *MENU* para ver las opciones.";
    }

    public function getNotAuthenticated(): string
    {
        return "❌ *Debes autenticarte primero*\n\n" .
               "Para generar o consultar certificados necesitas iniciar sesión.\n\n" .
               "📋 *Opciones disponibles:*\n" .
               "• Escribe *AUTENTICAR* para iniciar sesión\n" .
               "• Escribe *MENU* para ver todas las opciones\n" .
               "• Escribe *REGISTRO* si no tienes cuenta";
    }

    public function getNoAuthenticationMessage(): string
    {
        return "❌ *No hay sesión activa*\n\n" .
               "No tienes una sesión iniciada.\n\n" .
               "Para usar esta función, primero debes autenticarte.\n\n" .
               "Escribe *AUTENTICAR* para iniciar sesión.";
    }

    public function getCompanyInfoNotFound(): string
    {
        return "❌ Error: No se encontró información de la empresa. Por favor, autentícate nuevamente.";
    }

    // ========== INFORMACIÓN GENERAL ==========
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

    // ========== CERTIFICADOS ==========
    public function getCertificateOptions(): string
    {
        return "📄 *GENERAR CERTIFICADO FIC*\n\n" .
               "Por favor indica el *tipo* de certificado escribiendo su nombre o número:\n\n" .
               "• *TICKET* - Certificado específico por número de ticket\n" .
               "• *NIT* - Todos los certificados asociados a tu NIT\n" .
               "• *VIGENCIA* - Certificado filtrado por año de vigencia\n\n" .
               "Ejemplo: responde *NIT* para buscar todos tus certificados.";
    }

    public function getCertificatePrompt(string $type): string
    {
        switch ($type) {
            case 'ticket':
                return "🎫 *Certificado por TICKET*\n\nPor favor ingresa el número de *TICKET*:";
            case 'vigencia':
                $certificateService = app(CertificateService::class);
                $yearRange = $certificateService->getYearRange();
                return "📅 *Certificado por VIGENCIA*\n\nIngresa el *AÑO* de la vigencia (ejemplo: 2025). Solo se permiten 15 años atrás desde el actual ({$yearRange['min']} - {$yearRange['max']}).";
            default:
                return "";
        }
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

    public function getProcessingCertificate(): string
    {
        return "⏳ *Generando certificado...*\n\nPor favor espera unos segundos.";
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

    // ========== ERRORES Y AYUDA ==========
    public function getUnknownCommand(): string
    {
        return "🤔 *No entendí*\n\n" .
               "Comandos disponibles:\n\n" .
               "• *MENU* - Ver opciones principales\n" .
               "• *REQUISITOS* - Ver requisitos para certificados\n" .
               "• *SOPORTE* - Información de contacto\n" .
               "• *AUTENTICAR* - Iniciar sesión\n" .
               "• *REGISTRO* - Información de registro\n\n" .
               "Escribe el nombre de la opción que necesitas.";
    }

    public function getErrorSystem(): string
    {
        return "❌ *Error del sistema*\n\nPor favor intenta nuevamente o contacta a soporte.";
    }
}