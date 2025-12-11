/**
 * PrestaShop 9 Importer Module - Admin JavaScript
 * Version: 2.0.0
 */

(function() {
    'use strict';

    // Esperar a que el DOM esté listo
    document.addEventListener('DOMContentLoaded', function() {
        initPostImportFixes();
        initDiagnostic();
        initImportForm();
    });

    /**
     * Inicializa los botones de Post-Import Fixes
     */
    function initPostImportFixes() {
        const fixButtons = document.querySelectorAll('.psimporter-execute-fix');
        
        fixButtons.forEach(function(button) {
            button.addEventListener('click', function(e) {
                const fixName = this.getAttribute('data-fix');
                
                if (!confirm(window.psimporter_translations ? 
                    window.psimporter_translations.confirm_execute : 
                    '¿Está seguro de ejecutar este fix?')) {
                    e.preventDefault();
                    return false;
                }

                // Mostrar spinner en el botón
                const originalText = this.innerHTML;
                this.innerHTML = '<span class="psimporter-spinner"></span> ' + 
                    (window.psimporter_translations ? 
                        window.psimporter_translations.executing : 
                        'Ejecutando...');
                this.disabled = true;

                // Simular ejecución (en producción esto sería una llamada AJAX)
                setTimeout(function() {
                    // Esta parte se reemplazaría con AJAX real
                    console.log('Executing fix: ' + fixName);
                }, 500);
            });
        });
    }

    /**
     * Inicializa el sistema de diagnóstico
     */
    function initDiagnostic() {
        const diagnosticButton = document.querySelector('.psimporter-run-diagnostic');
        
        if (diagnosticButton) {
            diagnosticButton.addEventListener('click', function(e) {
                e.preventDefault();
                
                const resultsDiv = document.querySelector('.psimporter-diagnostic-results');
                if (resultsDiv) {
                    resultsDiv.innerHTML = '<div class="psimporter-alert psimporter-alert-info">' +
                        '<i class="icon-refresh icon-spin"></i> ' +
                        (window.psimporter_translations ? 
                            window.psimporter_translations.running_diagnostic : 
                            'Ejecutando diagnóstico...') +
                        '</div>';
                }

                // Simular diagnóstico (en producción sería AJAX)
                setTimeout(function() {
                    if (resultsDiv) {
                        resultsDiv.innerHTML = generateDiagnosticResults();
                    }
                }, 2000);
            });
        }
    }

    /**
     * Genera resultados de diagnóstico de ejemplo
     */
    function generateDiagnosticResults() {
        const checks = [
            { status: 'success', message: 'Estructura de categorías: OK' },
            { status: 'success', message: 'Grupos de categorías: OK' },
            { status: 'warning', message: 'Se encontraron 5 productos sin categoría' },
            { status: 'success', message: 'Campos GTIN actualizados correctamente' }
        ];

        let html = '<div class="psimporter-diagnostic">';
        checks.forEach(function(check) {
            const icon = check.status === 'success' ? 'check' : 
                        check.status === 'warning' ? 'exclamation-triangle' : 'times';
            html += '<div class="psimporter-diagnostic-item ' + check.status + '">' +
                   '<i class="icon-' + icon + '"></i> ' + check.message +
                   '</div>';
        });
        html += '</div>';

        return html;
    }

    /**
     * Inicializa el formulario de importación
     */
    function initImportForm() {
        const importForm = document.querySelector('.psimporter-import-form');
        
        if (importForm) {
            importForm.addEventListener('submit', function(e) {
                const fileInput = this.querySelector('input[type="file"]');
                
                if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
                    e.preventDefault();
                    alert(window.psimporter_translations ? 
                        window.psimporter_translations.select_file : 
                        'Por favor seleccione un archivo SQL');
                    return false;
                }

                // Validar tamaño del archivo (máximo 100MB)
                const maxSize = 100 * 1024 * 1024; // 100MB
                if (fileInput.files[0].size > maxSize) {
                    e.preventDefault();
                    alert(window.psimporter_translations ? 
                        window.psimporter_translations.file_too_large : 
                        'El archivo es demasiado grande (máximo 100MB)');
                    return false;
                }

                // Mostrar barra de progreso
                showProgressBar();
            });
        }
    }

    /**
     * Muestra una barra de progreso durante la importación
     */
    function showProgressBar() {
        const progressHtml = '<div class="psimporter-progress">' +
            '<div class="psimporter-progress-bar" style="width: 0%">0%</div>' +
            '</div>';
        
        const container = document.querySelector('.psimporter-import-zone');
        if (container) {
            const progressDiv = document.createElement('div');
            progressDiv.innerHTML = progressHtml;
            container.appendChild(progressDiv);

            // Simular progreso (en producción esto vendría del servidor)
            simulateProgress();
        }
    }

    /**
     * Simula el progreso de la importación
     */
    function simulateProgress() {
        let progress = 0;
        const progressBar = document.querySelector('.psimporter-progress-bar');
        
        if (!progressBar) return;

        const interval = setInterval(function() {
            progress += Math.random() * 15;
            if (progress > 100) progress = 100;

            progressBar.style.width = progress + '%';
            progressBar.textContent = Math.round(progress) + '%';

            if (progress >= 100) {
                clearInterval(interval);
            }
        }, 500);
    }

    /**
     * Muestra notificación temporal
     */
    function showNotification(message, type) {
        type = type || 'info';
        const notification = document.createElement('div');
        notification.className = 'psimporter-alert psimporter-alert-' + type;
        notification.innerHTML = message;
        notification.style.position = 'fixed';
        notification.style.top = '20px';
        notification.style.right = '20px';
        notification.style.zIndex = '9999';
        notification.style.minWidth = '300px';
        notification.style.boxShadow = '0 4px 8px rgba(0,0,0,0.2)';

        document.body.appendChild(notification);

        setTimeout(function() {
            notification.style.transition = 'opacity 0.5s';
            notification.style.opacity = '0';
            setTimeout(function() {
                document.body.removeChild(notification);
            }, 500);
        }, 3000);
    }

    /**
     * Manejo de errores AJAX
     */
    function handleAjaxError(xhr, status, error) {
        console.error('AJAX Error:', status, error);
        showNotification(
            window.psimporter_translations ? 
                window.psimporter_translations.ajax_error : 
                'Error de conexión. Por favor, inténtelo de nuevo.',
            'danger'
        );
    }

    // Exportar funciones para uso global si es necesario
    window.PSImporter = {
        showNotification: showNotification,
        showProgressBar: showProgressBar
    };

})();
