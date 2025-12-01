<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Carbon;
use App\Models\CertificadoFIC;
use App\Models\Empresa;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;

class WhatsAppController extends Controller
{
    // Verificar el webhook (Meta GET challenge)
    public function verifyWebhook(Request $request)
    {
        Log::info('🔐 === WHATSAPP WEBHOOK VERIFICATION STARTED ===');
        Log::info('Raw query string: ' . $request->getQueryString());
        Log::info('All query params:', $request->query());

        // Intentar leer con underscore y dotted keys (robusto)
        $mode = $request->query('hub_mode') ?? $request->query('hub.mode') ?? $request->get('hub.mode');
        $token = $request->query('hub_verify_token') ?? $request->query('hub.verify_token') ?? $request->get('hub.verify_token');
        $challenge = $request->query('hub_challenge') ?? $request->query('hub.challenge') ?? $request->get('hub.challenge');

        $expectedToken = env('WHATSAPP_VERIFY_TOKEN', 'chatbotwhatsapp');

        Log::info("Parsed verify values: mode=" . var_export($mode, true) .
                  ", token=" . var_export($token, true) .
                  ", challenge=" . var_export($challenge, true));

        if (! $mode || ! $token || ! $challenge) {
            Log::error('❌ Faltan parámetros requeridos en verificación de webhook', $request->query());
            return response('Bad Request - Missing parameters', 400);
        }

        if ($mode !== 'subscribe') {
            Log::warning("❌ Modo incorrecto. Esperado: 'subscribe', Recibido: '{$mode}'");
            return response('Forbidden - Invalid mode', 403);
        }

        if (strcasecmp(trim($token), trim($expectedToken)) !== 0) {
            Log::warning('❌ Token de verificación incorrecto', ['received' => $token, 'expected' => $expectedToken]);
            return response('Forbidden - Token mismatch', 403);
        }

        Log::info('✅ WEBHOOK VERIFICADO EXITOSAMENTE! Devolviendo challenge: ' . $challenge);
        return response($challenge, 200)->header('Content-Type', 'text/plain');
    }

    // Recibir mensajes y statuses desde Meta
    public function webhook(Request $request)
    {
        Log::info('=== WEBHOOK INICIADO ===');
        Log::info('Headers:', $request->headers->all());

        $rawBody = $request->getContent();
        Log::info('📨 Raw body recibido: ' . $rawBody);

        $data = json_decode($rawBody, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error('❌ Error decodificando JSON: ' . json_last_error_msg());
            // Intentar $request->all()
            $data = $request->all();
            if (empty($data)) {
                Log::info('=== WEBHOOK FINALIZADO (ERROR JSON) ===');
                return response('Error en JSON', 400);
            }
        }

        Log::info('Webhook data recibida:', $data);

        // 1) Manejar eventos de "statuses" (delivered/read/failed) primero
        if (!empty($data['entry'][0]['changes'][0]['value']['statuses'][0])) {
            $status = $data['entry'][0]['changes'][0]['value']['statuses'][0];
            Log::info('🔔 Status event recibido:', $status);
            // Aquí podrías guardar en DB o reaccionar según status
            return response('Status received', 200);
        }

        // 2) Buscar mensaje (varias estructuras posibles)
        $message = null;
        if (isset($data['entry'][0]['changes'][0]['value']['messages'][0])) {
            $message = $data['entry'][0]['changes'][0]['value']['messages'][0];
            Log::info('✅ Mensaje encontrado en estructura estándar');
        } elseif (isset($data['entry'][0]['changes'][0]['value']['message'])) {
            $message = $data['entry'][0]['changes'][0]['value']['message'];
            Log::info('✅ Mensaje encontrado en estructura alternativa (message)');
        } elseif (isset($data['entry'][0]['messaging'][0]['message'])) {
            $message = $data['entry'][0]['messaging'][0]['message'];
            Log::info('✅ Mensaje encontrado en estructura messaging');
        } elseif (isset($data['messages'][0])) {
            $message = $data['messages'][0];
            Log::info('✅ Mensaje encontrado en raíz messages');
        }

        if ($message) {
            // Normalizar origen y texto (soporta text y interactive)
            $rawFrom = $message['from'] ?? $message['wa_id'] ?? '';
            $userPhone = preg_replace('/\D+/', '', $rawFrom);

            $messageText = '';
            $interactiveId = null;
            $interactiveTitle = null;

            // Texto plano
            if (isset($message['text']['body'])) {
                $messageText = $message['text']['body'];
            }

            // legacy "button" key
            elseif (!empty($message['button']) && isset($message['button']['text'])) {
                $messageText = $message['button']['text'];
            }

            // interactive (reply button or list)
            elseif (!empty($message['interactive'])) {
                $interactive = $message['interactive'];
                // button_reply (v24)
                if (isset($interactive['button_reply']['id'])) {
                    $interactiveId = $interactive['button_reply']['id'];
                    $interactiveTitle = $interactive['button_reply']['title'] ?? null;
                } elseif (isset($interactive['button_reply']['title'])) {
                    $interactiveTitle = $interactive['button_reply']['title'];
                }
                // list_reply
                if (isset($interactive['list_reply']['id'])) {
                    $interactiveId = $interactive['list_reply']['id'];
                    $interactiveTitle = $interactive['list_reply']['title'] ?? null;
                } elseif (isset($interactive['list_reply']['title'])) {
                    $interactiveTitle = $interactive['list_reply']['title'];
                }

                // Preferir id para flujo (más seguro)
                if ($interactiveId) {
                    $messageText = $interactiveId;
                } elseif ($interactiveTitle) {
                    $messageText = $interactiveTitle;
                } else {
                    $messageText = ''; // fallback
                }
            }

            // fallback: 'body' directo
            elseif (isset($message['body'])) {
                $messageText = $message['body'];
            }

            Log::info("📱 Mensaje recibido - De: {$userPhone}, Texto(normalizado): {$messageText}");
            Log::info("📋 Detalles del mensaje (original):", $message);

            if (!empty($userPhone) && $messageText !== '') {
                // Actualizar last interaction (inbound)
                $this->setLastInteraction($userPhone, now());

                // Determinar si enviar plantilla (primera vez o >24h)
                $last = $this->getLastInteraction($userPhone);
                $needTemplate = false;
                if (!$last) {
                    $needTemplate = true;
                } else {
                    $hours = Carbon::now()->diffInHours($last);
                    if ($hours >= 24) $needTemplate = true;
                }

                $sentTemplate = false;
                if ($needTemplate) {
                    Log::info("🔔 Enviando plantilla welcome_short a {$userPhone}");
                    // Intentamos enviar plantilla; si falla, igualmente enviamos menu por texto
                    if ($this->sendTemplate($userPhone, 'welcome_short')) {
                        $sentTemplate = true;
                        Log::info('✅ Plantilla enviada: welcome_short');
                    } else {
                        Log::warning('❌ No se pudo enviar plantilla welcome_short');
                    }

                    // Después de la plantilla, enviamos el menú interactivo
                    $this->sendMenuInteractive($userPhone);
                    // Guardar que ya enviamos menu y plantilla para evitar saludo duplicado
                    $this->updateUserState($userPhone, ['saw_welcome_template' => true]);
                }

                // Procesar message: si enviamos plantilla+menu, suprimir envío del welcome textual dentro de processMessage
                $this->processMessage($userPhone, $messageText, $sentTemplate);
            } else {
                Log::warning('❌ Número de teléfono o mensaje vacío');
            }
        } else {
            Log::warning('❌ No se encontró mensaje en el webhook');
            Log::info('Estructura completa recibida:', $data);
            Log::info('🔍 Claves disponibles en data:', array_keys($data));
            if (isset($data['entry'][0])) {
                Log::info('🔍 Estructura de entry[0]:', $data['entry'][0]);
            }
        }

        Log::info('=== WEBHOOK FINALIZADO ===');
        return response('Mensaje recibido', 200);
    }

    /**
     * Procesa mensajes entrantes y controla flujos.
     * El tercer parámetro ($suppressWelcome) evita reenviar el menú si ya se envió la plantilla.
     */
    private function processMessage($userPhone, $messageText, $suppressWelcome = false)
    {
        Log::info("=== PROCESS MESSAGE INICIADO ===");
        Log::info("Procesando mensaje - Usuario: {$userPhone}, Texto: {$messageText}");

        // Números de prueba de Meta (ignorar)
        $testNumbers = [
            '16315551181',
            '16505551111',
        ];

        if (in_array($userPhone, $testNumbers)) {
            Log::info("🔧 Ignorando mensaje de prueba de Meta: {$userPhone}");
            return;
        }

        $raw = trim($messageText);
        $messageLower = strtolower($raw);

        $userState = $this->getUserState($userPhone);
        Log::info("Estado actual del usuario:", $userState);

        // Si está en flujos de autenticación
        $authSteps = ['awaiting_username', 'awaiting_password'];
        if (!empty($userState['step']) && in_array($userState['step'], $authSteps)) {
            Log::info("Estado de autenticación detectado ({$userState['step']}) — manejando por flujo de auth");
            $this->handleAuthFlow($userPhone, $raw, $userState);
            Log::info("=== PROCESS MESSAGE FINALIZADO (por auth flow) ===");
            return;
        }

        // Flujos de certificados
        $certificateSteps = [
            'choosing_certificate_type',
            'awaiting_nit_ticket',
            'awaiting_ticket',
            'awaiting_nit_general',
            'awaiting_nit_vigencia',
            'awaiting_year'
        ];
        if (!empty($userState['step']) && in_array($userState['step'], $certificateSteps)) {
            Log::info("Estado activo detectado ({$userState['step']}) — manejando por flujo de certificado");
            $this->handleCertificateFlow($userPhone, $messageLower, $userState);
            Log::info("=== PROCESS MESSAGE FINALIZADO (por flujo activo) ===");
            return;
        }

        // --- Map de botones interactivos (id) a acciones ---
        $buttonMap = [
            'opt_generate_certificate' => 'GENERAR_CERT',
            'opt_requirements' => 'REQUISITOS',
            'opt_support' => 'SOPORTE',
            'opt_registration' => 'REGISTRO'
        ];

        if (isset($buttonMap[$messageLower])) {
            $action = $buttonMap[$messageLower];
            Log::info("🔘 Botón interactivo presionado: {$messageLower} -> {$action}");
            switch ($action) {
                case 'GENERAR_CERT':
                    $this->startAuthentication($userPhone);
                    return;
                case 'REQUISITOS':
                    $this->sendRequirements($userPhone);
                    return;
                case 'SOPORTE':
                    $this->sendSupportInfo($userPhone);
                    return;
                case 'REGISTRO':
                    $this->sendRegistrationInfo($userPhone);
                    return;
            }
        }

        // Comandos globales / menú (si el usuario escribe texto o número)
        if ($messageLower === 'menu' || $messageLower === '1' || str_contains($messageLower, 'inicio') || str_contains($messageLower, 'hola')) {
            Log::info("🤖 Comando MENU/HOLA recibido - suppressWelcome={$suppressWelcome}");
            // Si ya se envió la plantilla, evitamos mandar el mensaje textual adicional (para no duplicar)
            if (! $suppressWelcome && empty($userState['saw_welcome_template'])) {
                // Enviar plantilla ya fue gestionado en webhook; si llegamos aquí y no se envió plantilla, mandamos menú interactivo
                $this->sendMenuInteractive($userPhone);
            } else {
                // Compact menu (enviar interactivo igualmente)
                $this->sendMenuInteractive($userPhone);
            }
            $this->updateUserState($userPhone, ['step' => 'main_menu']);
            return;
        }

        if ($messageLower === '2' || str_contains($messageLower, 'requisitos')) {
            $this->sendRequirements($userPhone);
            return;
        }

        if ($messageLower === '3' || str_contains($messageLower, 'soporte') || str_contains($messageLower, 'ayuda') || str_contains($messageLower, 'contacto')) {
            $this->sendSupportInfo($userPhone);
            return;
        }

        if ($messageLower === '4' || str_contains($messageLower, 'registro') || str_contains($messageLower, 'registrarse')) {
            $this->sendRegistrationInfo($userPhone);
            return;
        }

        if (str_contains($messageLower, 'generar certificado') || $messageLower === 'generar' || str_contains($messageLower, 'certificado')) {
            $this->startAuthentication($userPhone);
            return;
        }

        // Si no se reconoce
        Log::info("❓ No se reconoció comando global, enviando ayuda corta");
        // En vez de doble saludo, enviamos una ayuda corta y recordatorio de menú
        $this->sendMessage($userPhone, "No entendí 🤔. Puedes escribir: *MENU* para ver las opciones o seleccionar una opción del menú. También puedes escribir *Generar Certificado*, *Requisitos*, *Soporte* o *Registro*.");
        Log::info("=== PROCESS MESSAGE FINALIZADO ===");
    }

    // ---------------- AUTH / CERTIFICATE FLOWS (mantenidos) ----------------

    private function handleAuthFlow($userPhone, $messageText, $userState)
    {
        Log::info("=== HANDLE AUTH FLOW INICIADO ===");
        Log::info("Paso actual: " . ($userState['step'] ?? 'none'));
        Log::info("Mensaje: {$messageText}");

        $step = $userState['step'] ?? '';

        switch ($step) {
            case 'awaiting_username':
                Log::info("👤 Usuario ingresando username: {$messageText}");
                $this->processUsername($userPhone, $messageText);
                break;

            case 'awaiting_password':
                Log::info("🔐 Usuario ingresando password");
                $this->processPassword($userPhone, $messageText);
                break;

            default:
                Log::info("🔀 Estado de auth no reconocido, reiniciando");
                $this->startAuthentication($userPhone);
                break;
        }

        Log::info("=== HANDLE AUTH FLOW FINALIZADO ===");
    }

    private function startAuthentication($userPhone)
    {
        Log::info("🔐 Iniciando autenticación para usuario: {$userPhone}");

        $message = "🔐 *VALIDACIÓN DE USUARIO*\n\n";
        $message .= "⚠️ *Debes validar tu información antes de generar un certificado.*\n\n";
        $message .= "Por favor, ingresa tu *USUARIO*:";

        $this->sendMessage($userPhone, $message);
        $this->updateUserState($userPhone, ['step' => 'awaiting_username']);
    }

    private function processUsername($userPhone, $username)
    {
        Log::info("🔍 Buscando usuario en BD: {$username}");

        $empresa = Empresa::buscarPorUsuario($username);

        if (!$empresa) {
            Log::warning("❌ Usuario no encontrado: {$username}");
            $message = "❌ *USUARIO NO REGISTRADO*\n\n";
            $message .= "No tienes usuario registrado con nosotros.\n\n";
            $message .= "Por favor, *regístrate* y vuelve aquí!\n\n";
            $message .= "Escribe *REGISTRO* para ver información de registro o *MENU* para volver al inicio.";

            $this->sendMessage($userPhone, $message);
            $this->clearUserState($userPhone);
            return;
        }

        Log::info("✅ Usuario encontrado: " . $empresa->representante_legal);

        $message = "✅ Usuario encontrado.\n\n";
        $message .= "👤 *" . $empresa->representante_legal . "*\n\n";
        $message .= "Ahora ingresa tu *CONTRASEÑA*:";

        $this->sendMessage($userPhone, $message);
        $this->updateUserState($userPhone, [
            'step' => 'awaiting_password',
            'username' => $username,
            'empresa_id' => $empresa->id,
            'nit' => $empresa->nit
        ]);
    }

    private function processPassword($userPhone, $password)
    {
        $userState = $this->getUserState($userPhone);
        $username = $userState['username'] ?? null;

        if (!$username) {
            Log::error("❌ No se encontró username en el estado");
            $this->sendMessage($userPhone, "❌ Error en la autenticación. Por favor, inicia nuevamente.");
            $this->clearUserState($userPhone);
            return;
        }

        Log::info("🔐 Validando contraseña para usuario: {$username}");

        $empresa = Empresa::buscarPorUsuario($username);

        if (!$empresa) {
            Log::error("❌ Empresa no encontrada para usuario: {$username}");
            $this->sendMessage($userPhone, "❌ Error en la autenticación. Por favor, inicia nuevamente.");
            $this->clearUserState($userPhone);
            return;
        }

        if (!$empresa->verificarContraseña($password)) {
            Log::warning("❌ Contraseña incorrecta para usuario: {$username}");
            $message = "❌ *CONTRASEÑA INCORRECTA*\n\n";
            $message .= "La contraseña ingresada no es correcta.\n\n";
            $message .= "Por favor, vuelve a ingresar tu *USUARIO* o escribe *MENU* para volver al inicio.";

            $this->sendMessage($userPhone, $message);
            $this->updateUserState($userPhone, [
                'step' => 'awaiting_username',
                'username' => null
            ]);
            return;
        }

        Log::info("✅ Autenticación exitosa para: " . $empresa->representante_legal);

        $message = "✅ *AUTENTICACIÓN EXITOSA*\n\n";
        $message .= "Bienvenido *{$empresa->representante_legal}*\n";
        $message .= "📄 NIT: *{$empresa->nit}*\n\n";
        $message .= "Ahora puedes generar tu certificado.\n\n";

        $this->sendMessage($userPhone, $message);

        // Enviar opciones de certificado (texto) tras login
        $this->sendCertificateOptions($userPhone);
        $this->updateUserState($userPhone, [
            'step' => 'choosing_certificate_type',
            'authenticated' => true,
            'empresa_nit' => $empresa->nit,
            'representante_legal' => $empresa->representante_legal
        ]);
    }

    private function handleCertificateFlow($userPhone, $messageText, $userState)
    {
        Log::info("=== HANDLE CERTIFICATE FLOW INICIADO ===");
        Log::info("Paso actual: " . ($userState['step'] ?? 'none'));
        Log::info("Mensaje: {$messageText}");

        $step = $userState['step'] ?? '';

        if (!isset($userState['authenticated']) || !$userState['authenticated']) {
            Log::warning("❌ Usuario no autenticado intentando generar certificado");
            $this->sendMessage($userPhone, "❌ Debes autenticarte primero para generar certificados.");
            $this->startAuthentication($userPhone);
            return;
        }

        $nit = $userState['empresa_nit'] ?? null;
        if (!$nit) {
            Log::error("❌ No se encontró NIT en el estado del usuario autenticado");
            $this->sendMessage($userPhone, "❌ Error: No se encontró información de la empresa. Por favor, autentícate nuevamente.");
            $this->startAuthentication($userPhone);
            return;
        }

        switch ($step) {
            case 'choosing_certificate_type':
                Log::info("🔀 Usuario eligiendo tipo de certificado (por texto)");
                if (str_contains($messageText, 'ticket')) {
                    Log::info("🎫 Usuario seleccionó Ticket");
                    $this->updateUserState($userPhone, [
                        'step' => 'awaiting_ticket',
                        'type' => 'ticket'
                    ]);
                    $this->sendMessage($userPhone, "🎫 *Certificado por TICKET*\n\nPor favor ingresa el número de *TICKET*:");
                } elseif (str_contains($messageText, 'nit') && !str_contains($messageText, 'vigencia')) {
                    Log::info("🏢 Usuario seleccionó NIT - Generando certificado general");
                    $this->generateAndSendCertificate($userPhone, 'nit_general', [
                        'nit' => $nit
                    ]);
                } elseif (str_contains($messageText, 'vigencia') || str_contains($messageText, 'vigente')) {
                    Log::info("📅 Usuario seleccionó Vigencia");
                    $this->updateUserState($userPhone, [
                        'step' => 'awaiting_year',
                        'type' => 'vigencia'
                    ]);
                    $this->sendMessage($userPhone, "📅 *Certificado por VIGENCIA*\n\nIngresa el *AÑO* de la vigencia (ejemplo: 2025). Solo se permiten 15 años atrás desde el actual.");
                } else {
                    Log::info("❌ Opción no reconocida en choosing_certificate_type, reenviando instrucciones");
                    $this->sendMessage($userPhone, "No reconocí la opción. Responde con *TICKET*, *NIT* o *VIGENCIA* según corresponda.");
                }
                break;

            case 'awaiting_ticket':
                Log::info("🎟️ Usuario ingresando ticket: {$messageText}");
                $this->generateAndSendCertificate($userPhone, 'nit_ticket', [
                    'nit' => $nit,
                    'ticket' => $messageText
                ]);
                break;

            case 'awaiting_year':
                Log::info("📅 Usuario ingresando año: {$messageText}");
                $year = intval(preg_replace('/[^0-9]/','',$messageText));
                $currentYear = date('Y');

                if ($year <= 0 || $year > $currentYear || $year < ($currentYear - 15)) {
                    Log::warning("❌ Año fuera de rango: {$year}");
                    $this->sendMessage($userPhone, "❌ *Año fuera de rango*\n\nSolo se permiten vigencias entre " . ($currentYear - 15) . " y $currentYear . Por favor ingresa un año válido (ej: 2025).");
                    return;
                }

                $this->generateAndSendCertificate($userPhone, 'nit_vigencia', [
                    'nit' => $nit,
                    'vigencia' => $year
                ]);
                break;

            default:
                Log::info("🔀 Estado no reconocido, enviando menú");
                $this->sendMenuInteractive($userPhone);
                break;
        }

        Log::info("=== HANDLE CERTIFICATE FLOW FINALIZADO ===");
    }

    private function generateAndSendCertificate($userPhone, $type, $data)
    {
        Log::info("=== GENERATE AND SEND CERTIFICATE INICIADO ===");
        Log::info("Tipo: {$type}, Datos:", $data);

        try {
            $this->sendMessage($userPhone, "⏳ *Generando certificado...*\n\nPor favor espera unos segundos.");

            $certificados = $this->buscarCertificados($type, $data['nit'], $data['ticket'] ?? null, $data['vigencia'] ?? null);
            Log::info("Certificados encontrados: " . $certificados->count());

            if ($certificados->isEmpty()) {
                Log::warning("❌ No se encontraron certificados para los criterios");
                $this->sendMessage($userPhone, "❌ *No se encontraron certificados*\n\nNo hay certificados con los criterios especificados.");
                $this->clearUserState($userPhone);
                return;
            }

            Log::info("📄 Generando PDF...");
            $pdfPath = $this->generarPdf($certificados, $type);
            Log::info("PDF generado en: {$pdfPath}");

            Log::info("📤 Enviando documento por WhatsApp...");
            $this->sendDocument($userPhone, $pdfPath, $this->generarNombreArchivo($certificados->first(), $type));

            $this->sendMessage($userPhone, "✅ *Certificado generado exitosamente!*\n\nTu certificado FIC ha sido generado y enviado.");
            $this->sendMessage($userPhone, "¿Necesitas algo más? Escribe *MENU* para ver las opciones.");

            $userState = $this->getUserState($userPhone);
            $this->updateUserState($userPhone, [
                'step' => 'main_menu',
                'authenticated' => true,
                'empresa_nit' => $userState['empresa_nit'] ?? null,
                'representante_legal' => $userState['representante_legal'] ?? null
            ]);
        } catch (\Exception $e) {
            Log::error('❌ Error generando certificado WhatsApp: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            $this->sendMessage($userPhone, "❌ *Error del sistema*\n\nPor favor intenta nuevamente o contacta a soporte.");
            $this->clearUserState($userPhone);
        }

        Log::info("=== GENERATE AND SEND CERTIFICATE FINALIZADO ===");
    }

    // -------------------- helpers para interacción --------------------

    private function getLastInteraction($userPhone)
    {
        $key = "wh_last_interaction_{$userPhone}";
        $val = Cache::get($key);
        if ($val) return Carbon::parse($val);
        return null;
    }

    private function setLastInteraction($userPhone, $time)
    {
        $key = "wh_last_interaction_{$userPhone}";
        Cache::put($key, Carbon::parse($time)->toISOString(), now()->addDays(30));
    }

    /**
     * Envía una plantilla (template) usando WhatsApp Cloud API (v24).
     * Retorna true si el envío fue exitoso (status 200/2xx).
     */
    private function sendTemplate($to, $templateName, $languageCode = 'es_CO')
    {
        $phoneNumberId = config('services.whatsapp.phone_number_id') ?? env('WHATSAPP_PHONE_ID');
        $accessToken = config('services.whatsapp.access_token') ?? env('WHATSAPP_TOKEN');

        if (empty($phoneNumberId) || empty($accessToken)) {
            Log::error('❌ Configuración de WhatsApp incompleta para sendTemplate');
            return false;
        }

        $url = "https://graph.facebook.com/v24.0/{$phoneNumberId}/messages";

        $body = [
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'template',
            'template' => [
                'name' => $templateName,
                'language' => ['code' => $languageCode]
            ]
        ];

        try {
            $response = Http::withToken($accessToken)
                ->timeout(15)
                ->post($url, $body);

            Log::info("📡 sendTemplate status: " . $response->status());
            Log::info("📡 sendTemplate body:", $response->json());

            if ($response->successful()) {
                $this->setLastInteraction($to, now());
                return true;
            } else {
                Log::error("❌ sendTemplate failed: " . $response->body());
                return false;
            }
        } catch (\Exception $e) {
            Log::error("💥 Excepción en sendTemplate: " . $e->getMessage());
            return false;
        }
    }

    // Enviar menú interactivo (reply buttons) v24
    private function sendMenuInteractive(string $userPhone)
    {
        Log::info("📋 Enviando MENU interactivo a {$userPhone}");

        $phoneNumberId = config('services.whatsapp.phone_number_id') ?? env('WHATSAPP_PHONE_ID');
        $accessToken = config('services.whatsapp.access_token') ?? env('WHATSAPP_TOKEN');

        if (empty($phoneNumberId) || empty($accessToken)) {
            Log::error('❌ Configuración WhatsApp incompleta (sendMenuInteractive)');
            return;
        }

        $url = "https://graph.facebook.com/v24.0/{$phoneNumberId}/messages";

        $body = [
            'messaging_product' => 'whatsapp',
            'to' => $userPhone,
            'type' => 'interactive',
            'interactive' => [
                'type' => 'button',
                'body' => [
                    'text' => "📌 *MENÚ PRINCIPAL - Chatbot FIC*\n\nSelecciona una opción:"
                ],
                'action' => [
                    'buttons' => [
                        [
                            'type' => 'reply',
                            'reply' => [
                                'id' => 'opt_generate_certificate',
                                'title' => '1) Generar Certificado'
                            ]
                        ],
                        [
                            'type' => 'reply',
                            'reply' => [
                                'id' => 'opt_requirements',
                                'title' => '2) Requisitos'
                            ]
                        ],
                        [
                            'type' => 'reply',
                            'reply' => [
                                'id' => 'opt_support',
                                'title' => '3) Soporte'
                            ]
                        ]
                    ]
                ]
            ]
        ];

        try {
            $resp = Http::withToken($accessToken)
                ->timeout(15)
                ->post($url, $body);

            Log::info("📡 sendMenuInteractive status: " . $resp->status());
            Log::info("📡 sendMenuInteractive body:", $resp->json());

            if ($resp->successful()) {
                $this->setLastInteraction($userPhone, now());
            } else {
                Log::error("❌ Error enviando menu interactivo: " . $resp->body());
                // Fallback textual
                $this->sendMenu($userPhone);
            }
        } catch (\Exception $e) {
            Log::error("💥 Excepción en sendMenuInteractive: " . $e->getMessage());
            // Fallback textual
            $this->sendMenu($userPhone);
        }
    }

    // Enviar mensaje simple (texto)
    private function sendMessage($to, $message)
    {
        Log::info("✉️ ENVIANDO MENSAJE - Para: {$to}");
        Log::info("📝 Mensaje: {$message}");

        $phoneNumberId = config('services.whatsapp.phone_number_id') ?? env('WHATSAPP_PHONE_ID');
        $accessToken = config('services.whatsapp.access_token') ?? env('WHATSAPP_TOKEN');

        if (empty($phoneNumberId) || empty($accessToken)) {
            Log::error('❌ Configuración de WhatsApp incompleta. Revisa services.whatsapp.phone_number_id y access_token');
            return;
        }

        $url = 'https://graph.facebook.com/v24.0/' . $phoneNumberId . '/messages';
        Log::info("🌐 URL: {$url}");
        Log::info("🔑 Token (partial): " . (is_string($accessToken) ? substr($accessToken, 0, 10) . "..." : 'n/a'));

        try {
            $response = Http::withToken($accessToken)
                ->timeout(30)
                ->post($url, [
                    'messaging_product' => 'whatsapp',
                    'to' => $to,
                    'text' => ['body' => $message]
                ]);

            Log::info("📡 Respuesta HTTP Status: " . $response->status());
            Log::info("📡 Respuesta WhatsApp API:", $response->json());

            if ($response->successful()) {
                Log::info("✅ Mensaje enviado exitosamente a {$to}");
                $this->setLastInteraction($to, now());

                // Guardar outbound en BD si existe modelo WhatsappMessage
                $respJson = $response->json();
                $outMsgId = $respJson['messages'][0]['id'] ?? null;
                try {
                    if (class_exists(\App\Models\WhatsappMessage::class)) {
                        \App\Models\WhatsappMessage::create([
                            'message_id' => $outMsgId,
                            'from_number' => $phoneNumberId,
                            'to_phone_number_id' => $to,
                            'direction' => 'outbound',
                            'message' => $message,
                            'payload' => json_encode($respJson)
                        ]);
                    }
                } catch (\Throwable $e) {
                    Log::warning('No se pudo guardar outbound en BD: ' . $e->getMessage());
                }
            } else {
                Log::error("❌ Error enviando mensaje: " . $response->body());
            }
        } catch (\Exception $e) {
            Log::error("💥 Excepción enviando mensaje: " . $e->getMessage());
            Log::error("📋 Stack trace: " . $e->getTraceAsString());
        }
    }

    // Enviar documento (subir media y enviar)
    private function sendDocument($to, $filePath, $fileName)
    {
        Log::info("📎 ENVIANDO DOCUMENTO - Para: {$to}, Archivo: {$fileName}");
        Log::info("📁 Ruta del archivo: {$filePath}");

        $phoneNumberId = config('services.whatsapp.phone_number_id') ?? env('WHATSAPP_PHONE_ID');
        $accessToken = config('services.whatsapp.access_token') ?? env('WHATSAPP_TOKEN');

        if (empty($phoneNumberId) || empty($accessToken)) {
            Log::error('❌ Configuración de WhatsApp incompleta. Revisa services.whatsapp.phone_number_id y access_token');
            return;
        }

        try {
            $mediaResponse = Http::withToken($accessToken)
                ->attach('file', file_get_contents($filePath), $fileName)
                ->post('https://graph.facebook.com/v24.0/' . $phoneNumberId . '/media', [
                    'messaging_product' => 'whatsapp',
                    'type' => 'document/pdf'
                ]);

            Log::info("📡 Respuesta subida de archivo:", $mediaResponse->json());

            if (isset($mediaResponse->json()['id'])) {
                $mediaId = $mediaResponse->json()['id'];
                Log::info("🆔 Media ID obtenido: {$mediaId}");

                $url = 'https://graph.facebook.com/v24.0/' . $phoneNumberId . '/messages';
                $sendResponse = Http::withToken($accessToken)
                    ->post($url, [
                        'messaging_product' => 'whatsapp',
                        'to' => $to,
                        'type' => 'document',
                        'document' => [
                            'id' => $mediaId,
                            'filename' => $fileName
                        ]
                    ]);

                Log::info("📡 Respuesta envío de documento:", $sendResponse->json());
                if ($sendResponse->successful()) {
                    Log::info("✅ Documento enviado exitosamente");
                    $this->setLastInteraction($to, now());

                    $respJson = $sendResponse->json();
                    try {
                        if (class_exists(\App\Models\WhatsappMessage::class)) {
                            \App\Models\WhatsappMessage::create([
                                'message_id' => $respJson['messages'][0]['id'] ?? null,
                                'from_number' => $phoneNumberId,
                                'to_phone_number_id' => $to,
                                'direction' => 'outbound',
                                'message' => '[document] ' . $fileName,
                                'payload' => json_encode($respJson)
                            ]);
                        }
                    } catch (\Throwable $e) {
                        Log::warning('No se pudo guardar outbound (document): ' . $e->getMessage());
                    }
                } else {
                    Log::error("❌ Error al enviar documento: " . $sendResponse->body());
                }
            } else {
                Log::error("❌ No se pudo obtener media ID");
            }
        } catch (\Exception $e) {
            Log::error("💥 Excepción enviando documento: " . $e->getMessage());
            Log::error("📋 Stack trace: " . $e->getTraceAsString());
        }

        if (file_exists($filePath)) {
            unlink($filePath);
            Log::info("🧹 Archivo temporal eliminado: {$filePath}");
        }
    }

    // -------------------- MENÚ y Mensajes predefinidos --------------------

    // Versión textual de fallback del menú (solo como fallback)
    private function sendMenu($userPhone, $compact = false)
    {
        Log::info("📋 Enviando MENU texto a {$userPhone}, compact={$compact}");
        $msg = "📌 *MENÚ PRINCIPAL - Chatbot FIC*\n\n";
        $msg .= "Selecciona una opción escribiendo su nombre o número:\n\n";
        $msg .= "• *1* - Generar Certificado (o escribe *Generar Certificado*)\n";
        $msg .= "• *2* - Requisitos (o escribe *Requisitos*)\n";
        $msg .= "• *3* - Soporte (o escribe *Soporte*)\n";
        $msg .= "• *4* - Registro (o escribe *Registro*)\n\n";
        $msg .= "Ejemplo: Escribe *Generar Certificado* para iniciar.";
        $this->sendMessage($userPhone, $msg);
    }

    private function sendCertificateOptions($userPhone)
    {
        Log::info("📄 Enviando opciones de certificado a {$userPhone}");
        $message = "📄 *GENERAR CERTIFICADO FIC*\n\n";
        $message .= "Por favor indica el *tipo* de certificado escribiendo su nombre o número:\n\n";
        $message .= "• *TICKET* - Certificado específico por número de ticket\n";
        $message .= "• *NIT* - Todos los certificados asociados a tu NIT\n";
        $message .= "• *VIGENCIA* - Certificado filtrado por año de vigencia\n\n";
        $message .= "Ejemplo: responde *NIT* para buscar todos tus certificados.";

        $this->sendMessage($userPhone, $message);
    }

    private function sendRequirements($userPhone)
    {
        Log::info("📋 Enviando requisitos a {$userPhone}");
        $message = "📋 *REQUISITOS PARA CERTIFICADOS FIC*\n\n";
        $message .= "• NIT o Cédula del empresario\n";
        $message .= "• Tipo de certificado (Ticket, NIT o Vigencia)\n";
        $message .= "• Para vigencia: año específico (máx. 15 años atrás)\n\n";
        $message .= "Escribe *MENU* para volver al inicio.";

        $this->sendMessage($userPhone, $message);
    }

    private function sendSupportInfo($userPhone)
    {
        Log::info("📞 Enviando info de soporte a {$userPhone}");
        $message = "📞 *SOPORTE TÉCNICO*\n\n";
        $message .= "Para asistencia técnica contacta:\n\n";
        $message .= "📧 Email: soporte@sena.edu.co\n";
        $message .= "🌐 Web: www.sena.edu.co\n\n";
        $message .= "Escribe *MENU* para volver al inicio.";

        $this->sendMessage($userPhone, $message);
    }

    private function sendRegistrationInfo($userPhone)
    {
        Log::info("📝 Enviando info de registro a {$userPhone}");
        $message = "📝 *REGISTRO DE NUEVO USUARIO*\n\n";
        $message .= "Para registrarte en nuestro sistema, debes ir a la pagina de oficial:\n\n";
        $message .= "🌐 *Web:* www.fic.sena.edu.co/registro\n\n";
        $message .= "Escribe *MENU* para volver al inicio.";

        $this->sendMessage($userPhone, $message);
    }

    // -------------------- BD / PDF helpers (mantenidos) --------------------

    private function buscarCertificados($tipo, $nit, $ticket = null, $vigencia = null)
    {
        Log::info("🔍 Buscando certificados - Tipo: {$tipo}, NIT: {$nit}, Ticket: {$ticket}, Vigencia: {$vigencia}");

        $query = CertificadoFIC::where('constructor_nit', $nit);
        Log::info("Query base construida, count: " . $query->count());

        switch ($tipo) {
            case 'nit_ticket':
                $result = $query->where('ticket', $ticket)->get();
                Log::info("Resultado busqueda por ticket: " . $result->count());
                return $result;
            case 'nit_vigencia':
                $pattern = $vigencia . '-%';
                $result = $query->where('periodo', 'like', $pattern)->get();
                Log::info("Resultado busqueda por vigencia {$pattern}: " . $result->count());
                return $result;
            case 'nit_general':
            default:
                $result = $query->get();
                Log::info("Resultado busqueda general: " . $result->count());
                return $result;
        }
    }

    private function generarPdf($certificados, $tipo)
    {
        Log::info("📊 Generando PDF para {$certificados->count()} certificados, tipo: {$tipo}");

        $constructor = $certificados->first();
        $total = $certificados->sum('valor_pago');

        Log::info("Constructor: {$constructor->constructor_razon_social}, Total: {$total}");

        $datos = [
            'certificados' => $certificados,
            'constructor' => $constructor,
            'total' => $total,
            'fecha_emision' => now(),
            'tipo_busqueda' => $tipo
        ];

        $pdf = Pdf::loadView('certificados.plantilla', $datos)
                  ->setPaper('a4', 'portrait')
                  ->setOptions([
                      'defaultFont' => 'Arial',
                      'isHtml5ParserEnabled' => true,
                      'isRemoteEnabled' => true
                  ]);

        $fileName = $this->generarNombreArchivo($constructor, $tipo);
        $filePath = storage_path('app/temp/' . $fileName);

        Log::info("Guardando PDF en: {$filePath}");

        if (!file_exists(dirname($filePath))) {
            Log::info("Creando directorio: " . dirname($filePath));
            mkdir(dirname($filePath), 0755, true);
        }

        $pdf->save($filePath);
        Log::info("✅ PDF guardado exitosamente");

        return $filePath;
    }

    private function generarNombreArchivo($constructor, $tipo)
    {
        $fecha = now()->format('Y-m-d');
        $nit = $constructor->constructor_nit;
        $fileName = "Certificado_FIC_{$nit}_{$tipo}_{$fecha}.pdf";
        Log::info("Nombre de archivo generado: {$fileName}");
        return $fileName;
    }

    // -------------------- Estado del usuario (cache) --------------------

    private function getUserState($userPhone)
    {
        $state = cache("whatsapp_state_{$userPhone}") ?? [];
        Log::info("📝 Obteniendo estado del usuario {$userPhone}:", $state);
        return $state;
    }

    private function updateUserState($userPhone, $state)
    {
        Log::info("📝 Actualizando estado del usuario {$userPhone}:", $state);
        cache(["whatsapp_state_{$userPhone}" => array_merge($this->getUserState($userPhone), $state)]);
        Log::info("✅ Estado actualizado");
    }

    private function clearUserState($userPhone)
    {
        Log::info("🧹 Limpiando estado del usuario {$userPhone}");
        cache()->forget("whatsapp_state_{$userPhone}");
        Log::info("✅ Estado limpiado");
    }
}
