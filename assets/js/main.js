document.addEventListener('DOMContentLoaded', () => {
    // --- Comprobación para ejecutar solo en la página de login ---
    const loginContainer = document.getElementById('login-container');
    if (!loginContainer) return;

    // --- Elementos del DOM ---
    const idCardInput = document.getElementById('id_card');
    const errorMessageDiv = document.getElementById('error-message');
    const steps = {
        1: document.getElementById('step-1'),
        2: document.getElementById('step-2'),
        3: document.getElementById('step-3'),
    };
    const whatsappContact = document.getElementById('whatsapp-contact');

    // --- Elementos del Formulario ---
    const formCheckId = document.getElementById('form-check-id');
    const formVerifyCode = document.getElementById('form-verify-code');
    
    // --- Botones ---
    const btnCheckId = document.getElementById('btn-check-id');
    const btnConfirmData = document.getElementById('btn-confirm-data');
    const btnRejectData = document.getElementById('btn-reject-data');
    const btnVerifyCode = document.getElementById('btn-verify-code');

    // --- Funciones de Utilidad ---
    const showStep = (stepNumber) => {
        Object.values(steps).forEach(step => step.classList.add('hidden'));
        if (steps[stepNumber]) {
            steps[stepNumber].classList.remove('hidden');
        }
        whatsappContact.classList.add('hidden');
        errorMessageDiv.style.display = 'none';
    };

    const showError = (message) => {
        errorMessageDiv.textContent = message;
        errorMessageDiv.style.display = 'block';
    };

    const setLoadingState = (button, isLoading) => {
        if (isLoading) {
            button.disabled = true;
            button.innerHTML = '<span class="spinner"></span>Cargando...';
        } else {
            button.disabled = false;
            button.innerHTML = button.dataset.originalText;
        }
    };
    
    // Guardar texto original de los botones
    [btnCheckId, btnConfirmData, btnVerifyCode].forEach(btn => {
        if(btn) btn.dataset.originalText = btn.innerHTML;
    });

    // --- Lógica de Eventos ---

    // 1. Consultar Cédula
    if (formCheckId) {
        formCheckId.addEventListener('submit', async (event) => {
            event.preventDefault(); // Prevenir el envío tradicional del formulario
            
            const id_card = idCardInput.value.trim();
            if (!id_card) {
                showError('Por favor, ingresa tu número de cédula.');
                return;
            }

            setLoadingState(btnCheckId, true);
            
            const formData = new FormData();
            formData.append('action', 'check_id');
            formData.append('id_card', id_card);

            try {
                const response = await fetch('/demos/asambleas/api/login.php', { method: 'POST', body: formData });
                const result = await response.json();

                if (result.success) {
                    document.getElementById('user-name').textContent = result.data.name;
                    document.getElementById('user-house').textContent = result.data.house;
                    document.getElementById('user-email-hint').textContent = result.data.email_hint;
                    showStep(2);
                } else {
                    showError(result.message || 'Error desconocido.');
                }
            } catch (error) {
                showError('Error de conexión con el servidor. Inténtalo de nuevo.');
            } finally {
                setLoadingState(btnCheckId, false);
            }
        });
    }

    // 2. Confirmar Datos y Enviar Código
    if (btnConfirmData) {
        btnConfirmData.addEventListener('click', async () => {
            const id_card = idCardInput.value.trim();
            setLoadingState(btnConfirmData, true);

            const formData = new FormData();
            formData.append('action', 'send_code');
            formData.append('id_card', id_card);

            try {
                const response = await fetch('/demos/asambleas/api/login.php', { method: 'POST', body: formData });
                const result = await response.json();

                if (result.success) {
                    showStep(3);
                } else {
                    showError(result.message || 'No se pudo enviar el código.');
                }
            } catch (error) {
                showError('Error de conexión. No se pudo enviar el código.');
            } finally {
                setLoadingState(btnConfirmData, false);
            }
        });
    }
    
    // 3. Verificar Código de Acceso
    if (formVerifyCode) {
        formVerifyCode.addEventListener('submit', async (event) => {
            event.preventDefault();
            const id_card = idCardInput.value.trim();
            const code = document.getElementById('login_code').value.trim();

            if (!code) {
                showError('Por favor, ingresa el código de verificación.');
                return;
            }

            setLoadingState(btnVerifyCode, true);
            
            const formData = new FormData();
            formData.append('action', 'verify_code');
            formData.append('id_card', id_card);
            formData.append('code', code);

            try {
                const response = await fetch('/demos/asambleas/api/login.php', { method: 'POST', body: formData });
                const result = await response.json();

                if (result.success && result.redirect) {
                    window.location.href = result.redirect;
                } else {
                    showError(result.message || 'El código es incorrecto o ha expirado.');
                }
            } catch (error) {
                showError('Error de conexión al verificar el código.');
            } finally {
                setLoadingState(btnVerifyCode, false);
            }
        });
    }
    
    // 4. Rechazar Datos
    if (btnRejectData) {
        btnRejectData.addEventListener('click', async () => {
            showStep(1); // Vuelve al paso 1
            showError("Por favor, contacta al administrador para corregir tus datos.");

            try {
                // Obtener el número de WhatsApp desde la API
                const response = await fetch('/demos/asambleas/api/get_setting.php?key=admin_whatsapp');
                const result = await response.json();

                if (result.success && result.value) {
                    const whatsappLink = whatsappContact.querySelector('a');
                    // Limpiar el número de caracteres no numéricos, excepto el '+' inicial
                    const cleanNumber = result.value.replace(/[^0-9+]/g, '');
                    whatsappLink.href = `https://wa.me/${cleanNumber}`;
                    whatsappContact.classList.remove('hidden');
                } else {
                    // Si no hay número, el botón de WhatsApp permanece oculto.
                    console.warn('No se pudo obtener el número de WhatsApp del administrador.');
                }
            } catch (error) {
                console.error('Error al obtener la configuración de WhatsApp:', error);
            }
        });
    }
});

// --- Añadir CSS para el Spinner ---
const style = document.createElement('style');
style.innerHTML = `
.spinner {
    display: inline-block;
    width: 1em;
    height: 1em;
    border: 2px solid rgba(255, 255, 255, 0.3);
    border-radius: 50%;
    border-top-color: #fff;
    animation: spin 1s ease-in-out infinite;
    margin-right: 8px;
    vertical-align: middle;
}
@keyframes spin {
    to { transform: rotate(360deg); }
}
`;
document.head.appendChild(style);