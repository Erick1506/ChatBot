<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <meta name="csrf-token" content="{{ csrf_token() }}" />
  <title>Certificados · ChatBot</title>

  <!-- Bootstrap (opcional CDN) -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- Vite: carga tus assets (Laravel 9) -->
  @vite([
    'resources/css/app.css',        // estilos globales (se puede poner aquí la maquetación general)
    'resources/css/chatbot.css',    // estilos exclusivos del widget del chatbot
    'resources/js/chatbot.js'       // lógica del widget
  ])

  @stack('head')
</head>
<body>
  {{-- Top bars (header) --}}
  <div class="govco-top">
    <img src="{{ asset('images/govco.png') }}" alt="gov.co" class="govco-logo" />
  </div>

  <div class="topbar"></div>

  <div class="sena-bar">
    <img src="{{ asset('images/logo_sena.png') }}" alt="SENA" class="sena-logo" />
    <div style="flex:1"></div>
    <div class="fecha-text">NOVIEMBRE 2025</div>
  </div>

  {{-- Contenido principal --}}
  <main class="page-wrap">
    @yield('content')
  </main>

  {{-- Se incluye el widget del chatbot (aparece en todas las vistas que extienden este layout) --}}
  {{-- @includeIf('chatbot.widget') --}}


  {{-- axios para llamadas si el chatbot lo necesita --}}
  <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>

  @stack('scripts')
</body>
</html>
