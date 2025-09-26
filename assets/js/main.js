// assets/js/main.js

document.addEventListener('DOMContentLoaded', () => {
    // LÓGICA PARA LA PÁGINA DE LOGIN (index.php)
    if (document.body.contains(document.getElementById('login-container'))) {
        const idCardInput = document.getElementById('id_card');
        const errorMessageDiv = document.getElementById('error-message');

        // Función para mostrar errores
        const showError = (message) => {
            errorMessageDiv.textContent = message;
            errorMessageDiv.style.display = 'block';
        };

        // Manejar clic en el botón de consultar
        document.getElementById('btn-check-id').addEventListener('click', async () => {
            console.log("Botón 'Consultar' presionado."); // Prueba
            const id_card = idCardInput.value;
            errorMessageDiv.style.display = 'none';

            if (!id_card) {
                showError('Por favor, ingresa tu número de cédula.');
                return;
            }

            const formData = new FormData();
            formData.append('action', 'check_id');
            formData.append('id_card', id_card);

            try {
                const response = await fetch('api/login.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                console.log("Respuesta de la API:", result); // Prueba

                if (result.success) {
                    // Llenar datos y mostrar el siguiente paso
                    document.getElementById('user-name').textContent = result.data.name;
                    document.getElementById('user-house').textContent = result.data.house;
                    document.getElementById('user-email-hint').textContent = result.data.email_hint;
                    
                    document.getElementById('step-1').classList.add('hidden');
                    document.getElementById('step-2').classList.remove('hidden');
                } else {
                    showError(result.message || 'Error desconocido.');
                }
            } catch (error) {
                console.error("Error en la llamada fetch:", error); // Prueba
                showError('Error de conexión con el servidor. Inténtalo de nuevo.');
            }
        });

        // Manejar clic en el botón de confirmar datos
        document.getElementById('btn-confirm-data').addEventListener('click', async () => {
            // ... (lógica para enviar código) ...
        });
        
        // Manejar clic en el botón de verificar código
        document.getElementById('btn-verify-code').addEventListener('click', async () => {
            // ... (lógica para verificar código y redirigir) ...
        });
        
        document.getElementById('btn-reject-data').addEventListener('click', () => {
            document.getElementById('step-2').classList.add('hidden');
            document.getElementById('whatsapp-contact').classList.remove('hidden');
        });
    }

    // LÓGICA PARA LA PÁGINA DE LA REUNIÓN (meeting.php)
    if (document.body.contains(document.getElementById('meeting-view'))) {
        // ... (código existente para la vista de la reunión) ...
    }
});