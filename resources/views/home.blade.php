@extends('layouts.app')

@section('content')
  <div class="card-cert">
    <div class="cert-title">CERTIFICADOS DE<br>APORTES Y FIC</div>
    <div class="cert-sub"></div>

    <form id="mainForm" onsubmit="return false;">
      @includeIf('chatbot.widget')
      <br>
      <div style="font-size:13px; color:#777;">
        <a href="/login">Iniciar sesion</a>
      </div>
    </form>
  </div>
@endsection