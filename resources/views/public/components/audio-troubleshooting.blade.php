<div class="card border-info">
    <div class="card-header bg-info text-white">
        <h6 class="mb-0">
            <i class="fas fa-tools"></i> Solución de Problemas de Audio
        </h6>
    </div>
    <div class="card-body">
        <div class="accordion" id="troubleshootingAccordion">
            <!-- Permisos -->
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#permissions">
                        <i class="fas fa-shield-alt me-2"></i>
                        Permisos de Micrófono
                    </button>
                </h2>
                <div id="permissions" class="accordion-collapse collapse" data-bs-parent="#troubleshootingAccordion">
                    <div class="accordion-body">
                        <h6>Chrome/Edge:</h6>
                        <ol>
                            <li>Busca el icono de micrófono <i class="fas fa-microphone text-danger"></i> en la barra de direcciones</li>
                            <li>Haz clic en él y selecciona "Permitir siempre"</li>
                            <li>Recarga la página</li>
                        </ol>
                        
                        <h6>Firefox:</h6>
                        <ol>
                            <li>Busca el icono de micrófono en la barra de direcciones</li>
                            <li>Haz clic en "Permitir" cuando aparezca la notificación</li>
                            <li>Si no aparece, ve a Configuración > Privacidad > Permisos > Micrófono</li>
                        </ol>
                        
                        <h6>Safari:</h6>
                        <ol>
                            <li>Ve a Safari > Preferencias > Sitios web > Micrófono</li>
                            <li>Permite el acceso para este sitio</li>
                        </ol>
                    </div>
                </div>
            </div>
            
            <!-- Hardware -->
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#hardware">
                        <i class="fas fa-microphone me-2"></i>
                        Problemas de Hardware
                    </button>
                </h2>
                <div id="hardware" class="accordion-collapse collapse" data-bs-parent="#troubleshootingAccordion">
                    <div class="accordion-body">
                        <ul>
                            <li><strong>Verifica la conexión:</strong> Asegúrate de que tu micrófono esté conectado correctamente</li>
                            <li><strong>Prueba en otras apps:</strong> Verifica que el micrófono funcione en otras aplicaciones</li>
                            <li><strong>Micrófono por defecto:</strong> Ve a la configuración de audio del sistema y selecciona el micrófono correcto</li>
                            <li><strong>Nivel de volumen:</strong> Ajusta el nivel de grabación en la configuración de audio</li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <!-- Navegador -->
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#browser">
                        <i class="fas fa-browser me-2"></i>
                        Problemas del Navegador
                    </button>
                </h2>
                <div id="browser" class="accordion-collapse collapse" data-bs-parent="#troubleshootingAccordion">
                    <div class="accordion-body">
                        <ul>
                            <li><strong>Navegadores compatibles:</strong> Chrome, Firefox, Safari, Edge (versiones recientes)</li>
                            <li><strong>HTTPS requerido:</strong> La grabación de audio requiere conexión segura</li>
                            <li><strong>Actualiza tu navegador:</strong> Asegúrate de tener la versión más reciente</li>
                            <li><strong>Desactiva extensiones:</strong> Algunas extensiones pueden bloquear el micrófono</li>
                            <li><strong>Modo incógnito:</strong> Prueba en una ventana de incógnito</li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <!-- Sistema Operativo -->
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#system">
                        <i class="fas fa-desktop me-2"></i>
                        Permisos del Sistema
                    </button>
                </h2>
                <div id="system" class="accordion-collapse collapse" data-bs-parent="#troubleshootingAccordion">
                    <div class="accordion-body">
                        <h6>Windows 10/11:</h6>
                        <ol>
                            <li>Ve a Configuración > Privacidad > Micrófono</li>
                            <li>Activa "Permitir que las aplicaciones accedan al micrófono"</li>
                            <li>Activa "Permitir que las aplicaciones de escritorio accedan al micrófono"</li>
                        </ol>
                        
                        <h6>macOS:</h6>
                        <ol>
                            <li>Ve a Preferencias del Sistema > Seguridad y Privacidad > Micrófono</li>
                            <li>Marca la casilla junto a tu navegador</li>
                        </ol>
                        
                        <h6>Linux:</h6>
                        <ol>
                            <li>Verifica que ALSA/PulseAudio estén configurados correctamente</li>
                            <li>Ejecuta <code>arecord -l</code> para ver dispositivos disponibles</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="mt-3 text-center">
            <small class="text-muted">
                Si sigues teniendo problemas, contacta al administrador del sistema
            </small>
        </div>
    </div>
</div>