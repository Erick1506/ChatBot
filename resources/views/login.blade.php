@extends('layouts.app')

@section('content')
  <div class="card-cert1">
    <div class="cert-title">CERTIFICADOS DE<br>APORTES Y FIC</div>
    <div class="cert-sub"></div>

    <form id="mainForm" onsubmit="return false;">
      <div class="mb-2 text-start">
        <label class="form-label">Nombre de Usuario:</label>
        <div class="input-group">
          <span class="input-group-text">@</span>
          <input class="form-control" placeholder="Usuario" />
        </div>
      </div>

      <div class="mb-2 text-start">
        <label class="form-label">Contraseña:</label>
        <div class="input-group">
          <input type="password" class="form-control" placeholder="Contraseña" />
          <button class="btn btn-outline-secondary" type="button" onclick="alert('Mostrar/ocultar no implementado')">👁️</button>
        </div>
      </div>

      <div class="mb-2">
        <div class="login-captcha">123ABC</div>
      </div>

      <div class="mb-3">
        <input class="form-control" placeholder="Ingrese el código" />
      </div>

      <div class="mb-2">
        <button class="btn btn-success w-100">Ingresar</button>
      </div>

      <div style="font-size:13px; color:#777;">
        <a href="#">Registro</a> · <a href="#">Recuperar Clave</a> · <a href="/">Ir a chatbot</a>
      </div>
      <div style="margin-top:10px; font-size:13px;">
        <span>🔎 CONSULTAR CÓDIGO CERTIFICADO</span>
      </div>
    </form>
  </div>
@endsection
