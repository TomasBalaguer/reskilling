@extends('public.layout')

@section('title', 'Campaña no encontrada')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-6 col-md-8">
        <div class="text-center">
            <div class="mb-4">
                <i class="fas fa-search fa-4x text-muted"></i>
            </div>
            
            <h1 class="h2 mb-3">Campaña no encontrada</h1>
            <p class="lead text-muted mb-4">
                No pudimos encontrar una campaña activa con el código 
                <code>{{ $code }}</code>
            </p>
            
            <div class="alert alert-info">
                <h6><i class="fas fa-info-circle"></i> Posibles causas:</h6>
                <ul class="text-start mb-0">
                    <li>El código de campaña es incorrecto</li>
                    <li>La campaña ha expirado</li>
                    <li>La campaña no está activa</li>
                    <li>El enlace puede estar dañado</li>
                </ul>
            </div>
            
            <div class="mt-4">
                <p class="text-muted">
                    Por favor, verifica el código con la persona u organización que te envió el enlace.
                </p>
                
                <a href="/" class="btn btn-primary">
                    <i class="fas fa-home"></i> Ir al inicio
                </a>
            </div>
        </div>
    </div>
</div>
@endsection