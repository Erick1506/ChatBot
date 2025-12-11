<?php

namespace App\Services\WhatsApp;

class TemplateService
{
    public function getMenu(bool $compact = false): string
    {
        $msg = "🤖 *MENÚ PRINCIPAL - Chatbot FIC*\n\n";
        $msg .= "Selecciona una opción escribiendo su nombre:\n\n";
        $msg .= "• *1* - Generar Certificado \n";
        $msg .= "• *2* - Requisitos \n";
        $msg .= "• *3* - Soporte \n";
        $msg .= "• *4* - Registro \n\n";
        $msg .= "Ejemplo: Escribe *Generar Certificado* para iniciar.";

        return $msg;
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
               "📧 Email: certiaportes@sena.edu.co\n" .
               "🌐 Web: www.sena.edu.co\n\n" .
               "Escribe *MENU* para volver al inicio.";
    }

    public function getRegistrationInfo(): string
    {
        return "📝 *REGISTRO DE NUEVO USUARIO*\n\n" .
               "Para registrarte en nuestro sistema, debes ir a la pagina de oficial:\n\n" .
               "🌐 *Web:* www.fic.sena.edu.co/registro\n\n" .
               "Escribe *MENU* para volver al inicio.";
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
               "⚠️ *Debes validar tu información antes de generar un certificado.*\n\n" .
               "Por favor, ingresa tu *USUARIO*:";
    }

    public function getAuthSuccess(string $representanteLegal, string $nit): string
    {
        return "✅ *AUTENTICACIÓN EXITOSA*\n\n" .
               "Bienvenido *{$representanteLegal}*\n" .
               "📄 NIT: *{$nit}*\n\n" .
               "Ahora puedes generar tu certificado.\n\n";
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
                return "🎫 *Certificado por TICKET*\n\nPor favor ingresa el número de *TICKET*:";
            case 'vigencia':
                $yearRange = app(CertificateService::class)->getYearRange();
                return "📅 *Certificado por VIGENCIA*\n\nIngresa el *AÑO* de la vigencia (ejemplo: 2025). Solo se permiten 15 años atrás desde el actual ({$yearRange['min']} - {$yearRange['max']}).";
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
        return "No entendí 🤔. Puedes escribir: *MENU* para ver las opciones, *Generar Certificado*, *Requisitos*, *Soporte* o *Registro*.";
    }

    public function getErrorSystem(): string
    {
        return "❌ *Error del sistema*\n\nPor favor intenta nuevamente o contacta a soporte.";
    }

    public function getNotAuthenticated(): string
    {
        return "❌ Debes autenticarte primero para generar certificados.";
    }

    public function getCompanyInfoNotFound(): string
    {
        return "❌ Error: No se encontró información de la empresa. Por favor, autentícate nuevamente.";
    }
}