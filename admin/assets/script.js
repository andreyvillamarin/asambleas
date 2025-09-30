document.addEventListener('DOMContentLoaded', () => {
    const meetingControls = document.querySelector('.meeting-controls');

    // Si no existe el contenedor principal del dashboard, no hacer nada.
    if (!meetingControls) {
        return;
    }

    const meetingId = meetingControls.dataset.meetingId;

    // --- Lógica para el Dashboard en Tiempo Real ---
    // Solo se activa si hay una reunión activa (meetingId no está vacío)
    if (meetingId) {
        const updateAdminDashboard = async () => {
            try {
                const response = await fetch(`../api/real_time_data.php?t=${new Date().getTime()}`);
                const data = await response.json();

                if (data.error) {
                    console.error('Error al actualizar el dashboard del admin:', data.error);
                    // Podríamos querer limpiar el dashboard o mostrar un mensaje
                    return;
                }

                // Actualizar tarjetas de estadísticas
                document.getElementById('connected-users-count').textContent = data.users.connected_count;
                document.getElementById('quorum-percentage').textContent = `${data.quorum.percentage}%`;
                document.getElementById('current-coefficient').textContent = data.quorum.current_coefficient;

                // Actualizar tabla de asistentes
                const tableBody = document.querySelector('#connected-users-table tbody');
                tableBody.innerHTML = ''; // Limpiar tabla

                if (data.users.list.length > 0) {
                    data.users.list.forEach(user => {
                        const powerGiverInfo = user.power_giver_house 
                            ? `Prop. ${user.power_giver_house} (Coef: ${user.power_giver_coefficient})` 
                            : 'N/A';

                        const userCoeff = parseFloat(String(user.coefficient).replace(',', '.'));
                        const giverCoeff = user.power_giver_coefficient 
                            ? parseFloat(String(user.power_giver_coefficient).replace(',', '.')) 
                            : 0;
                        const totalCoeff = (userCoeff + giverCoeff).toFixed(5);

                        const row = `<tr>
                            <td>${user.owner_name}</td>
                            <td>${user.house_number}</td>
                            <td>${user.coefficient}</td>
                            <td>${powerGiverInfo}</td>
                            <td><strong>${totalCoeff}</strong></td>
                        </tr>`;
                        tableBody.innerHTML += row;
                    });
                } else {
                    const row = `<tr><td colspan="5" style="text-align:center;">No hay asistentes conectados.</td></tr>`;
                    tableBody.innerHTML = row;
                }
            } catch (error) {
                console.error('Error fatal al actualizar el dashboard del admin:', error);
            }
        };

        setInterval(updateAdminDashboard, 5000);
        updateAdminDashboard();
    }

    // --- Lógica para los botones de control ---
    const btnDisconnectAll = document.getElementById('btn-disconnect-all');
    const btnCloseMeeting = document.getElementById('btn-close-meeting');

    if (btnDisconnectAll) {
        btnDisconnectAll.addEventListener('click', async () => {
            if (!meetingId) {
                alert('No hay una reunión activa para desconectar usuarios.');
                return;
            }
            if (confirm('¿Estás seguro de que quieres desconectar a todos los usuarios? Esta acción no se puede deshacer.')) {
                const formData = new FormData();
                formData.append('action', 'disconnect_all');
                formData.append('meeting_id', meetingId);
                const response = await fetch('../api/admin_actions.php', { method: 'POST', body: formData });
                const result = await response.json();
                alert(result.message);
                if (result.success) window.location.reload();
            }
        });
    }

    if (btnCloseMeeting) {
        btnCloseMeeting.addEventListener('click', async () => {
            if (confirm('¿Estás seguro de que quieres cerrar esta reunión?')) {
                const formData = new FormData();
                formData.append('action', 'close_meeting');
                formData.append('meeting_id', meetingId);
                const response = await fetch('../api/admin_actions.php', { method: 'POST', body: formData });
                const result = await response.json();
                alert(result.message);
                if (result.success) window.location.reload();
            }
        });
    }
});