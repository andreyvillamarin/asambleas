        </main> <!-- Cierra .content-body -->
        <footer class="main-footer">
            <p>&copy; <?php echo date('Y'); ?> Sistema de Asambleas. Todos los derechos reservados.</p>
        </footer>
    </div> <!-- Cierra .main-content -->
</div> <!-- Cierra .admin-wrapper -->

<script>
document.addEventListener('DOMContentLoaded', () => {
    const meetingControls = document.querySelector('.meeting-controls');
    if (!meetingControls) {
        return; // Salir si los controles no existen
    }

    const meetingId = meetingControls.dataset.meetingId;

    // --- Lógica para Dashboard en Tiempo Real ---
    if (meetingId) {
        const updateAdminDashboard = async () => {
            try {
                // Usamos una ruta absoluta para máxima fiabilidad
                const response = await fetch(`/api/real_time_data.php?t=${new Date().getTime()}`);
                if (!response.ok) return;
                const data = await response.json();
                if (data.error) return;

                document.getElementById('connected-users-count').textContent = data.users.connected_count;
                document.getElementById('quorum-percentage').textContent = `${data.quorum.percentage}%`;
                document.getElementById('current-coefficient').textContent = data.quorum.current_coefficient;
            } catch (error) {
                // Silencioso para no molestar al admin
            }
        };
        setInterval(updateAdminDashboard, 5000);
        updateAdminDashboard();
    }

    // --- Lógica para Botones de Control ---
    const btnDisconnectAll = document.getElementById('btn-disconnect-all');
    const btnCloseMeeting = document.getElementById('btn-close-meeting');

    if (btnDisconnectAll) {
        btnDisconnectAll.addEventListener('click', async () => {
            if (!meetingId) {
                alert('No hay una reunión activa para desconectar usuarios.');
                return;
            }
            if (confirm('¿Estás seguro de que quieres desconectar a todos los usuarios?')) {
                try {
                    const formData = new FormData();
                    formData.append('action', 'disconnect_all');
                    formData.append('meeting_id', meetingId);
                    // Usamos una ruta absoluta para máxima fiabilidad
                    const response = await fetch(`/api/admin_actions.php`, { method: 'POST', body: formData });

                    if (!response.ok) {
                        throw new Error(`Error HTTP ${response.status}: ${response.statusText}`);
                    }
                    const result = await response.json();
                    alert(result.message);
                    if (result.success) window.location.reload();
                } catch (error) {
                    console.error('Error al desconectar usuarios:', error);
                    alert('Se produjo un error al comunicar con el servidor: ' + error.message);
                }
            }
        });
    }

    if (btnCloseMeeting) {
        btnCloseMeeting.addEventListener('click', async () => {
            if (!meetingId) {
                alert('No hay una reunión activa para cerrar.');
                return;
            }
            if (confirm('¿Estás seguro de que quieres cerrar esta reunión?')) {
                try {
                    const formData = new FormData();
                    formData.append('action', 'close_meeting');
                    formData.append('meeting_id', meetingId);
                    const response = await fetch(`/api/admin_actions.php`, { method: 'POST', body: formData });

                    if (!response.ok) {
                        throw new Error(`Error HTTP ${response.status}: ${response.statusText}`);
                    }
                    const result = await response.json();
                    alert(result.message);
                    if (result.success) window.location.reload();
                } catch (error) {
                    console.error('Error al cerrar la reunión:', error);
                    alert('Se produjo un error al comunicar con el servidor: ' + error.message);
                }
            }
        });
    }
});
</script>
</body>
</html>