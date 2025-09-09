@extends('public.layout')

@section('title', 'Invitación inválida')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-6 col-md-8">
        <div class="text-center">
            <div class="mb-4">
                <i class="fas fa-exclamation-triangle fa-4x text-danger"></i>
            </div>
            
            <h1 class="h2 mb-3">Invitación Inválida</h1>
            <p class="lead text-muted mb-4">
                La invitación que intentas usar no es válida o ha expirado.
            </p>
            
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">¿Qué puede haber ocurrido?</h5>
                    <ul class="text-start">
                        <li>El enlace de invitación puede haber expirado</li>
                        <li>La invitación ya fue utilizada anteriormente</li>
                        <li>El enlace puede estar incompleto o dañado</li>
                        <li>La campaña puede haber sido pausada o eliminada</li>
                    </ul>
                </div>
            </div>
            
            <div class="mt-4">
                <p class="text-muted">
                    Si crees que esto es un error, contacta con la organización 
                    que te envió la invitación para obtener un nuevo enlace.
                </p>
                
                <a href="/" class="btn btn-primary">
                    <i class="fas fa-home"></i> Ir al inicio
                </a>
            </div>
        </div>
    </div>
</div>
@endsection