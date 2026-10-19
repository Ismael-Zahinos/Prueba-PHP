document.addEventListener('DOMContentLoaded', () => {
    // Referencias a elementos comunes
    const alertBox = document.getElementById('alert-box');
    const loadingSpinner = document.getElementById('loading');

    /**
     * Muestra notificaciones temporales de éxito o error
     */
    function showAlert(message, type = 'success', duration = 4000) {
        if (!alertBox) return;
        alertBox.textContent = message;
        alertBox.className = type; // Aplica clase .success o .error
        alertBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });

        if (duration > 0) {
            setTimeout(() => {
                alertBox.className = '';
                alertBox.textContent = '';
            }, duration);
        }
    }

    /**
     * Muestra u oculta el spinner de carga
     */
    function setLoading(isLoading) {
        if (!loadingSpinner) return;
        if (isLoading) {
            loadingSpinner.classList.add('active');
        } else {
            loadingSpinner.classList.remove('active');
        }
    }

    /**
     * Formateador de moneda en euros (€)
     */
    function formatMoney(amount) {
        return new Intl.NumberFormat('es-ES', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }).format(amount) + ' €';
    }

    /**
     * Formateador de fechas (YYYY-MM-DD a DD/MM/YYYY)
     */
    function formatDate(dateStr) {
        if (!dateStr) return '';
        const parts = dateStr.split('-');
        if (parts.length === 3) {
            return `${parts[2]}/${parts[1]}/${parts[0]}`;
        }
        return dateStr;
    }

    // ==========================================
    // 1. GESTIÓN DEL LISTADO Y FILTROS
    // ==========================================
    const filtroForm = document.getElementById('filtro-form');
    const tbodyReservas = document.getElementById('tbody-reservas');
    const btnLimpiar = document.getElementById('btn-limpiar-filtros');

    async function cargarReservas() {
        if (!tbodyReservas) return;

        setLoading(true);

        const params = new URLSearchParams();
        const estado = document.getElementById('filtro-estado')?.value;
        const nombre = document.getElementById('filtro-nombre')?.value.trim();
        const desde = document.getElementById('filtro-desde')?.value;
        const hasta = document.getElementById('filtro-hasta')?.value;

        if (estado) params.append('estado', estado);
        if (nombre) params.append('nombre', nombre);
        if (desde) params.append('fecha_desde', desde);
        if (hasta) params.append('fecha_hasta', hasta);

        try {
            const response = await fetch(`${BASE_URL}/api/reservas?${params.toString()}`);
            const result = await response.json();

            if (!response.ok) {
                throw new Error(result.message || 'Error al obtener las reservas.');
            }

            renderTablaReservas(result.data);
        } catch (error) {
            showAlert(error.message, 'error');
        } finally {
            setLoading(false);
        }
    }

    function renderTablaReservas(reservas) {
        if (!tbodyReservas) return;
        tbodyReservas.innerHTML = '';

        if (!reservas || reservas.length === 0) {
            tbodyReservas.innerHTML = `
                <tr id="sin-reservas">
                    <td colspan="8" style="text-align: center; color: var(--text-muted); padding: 2rem;">
                        No se encontraron reservas con los criterios seleccionados.
                    </td>
                </tr>
            `;
            return;
        }

        reservas.forEach(reserva => {
            const tr = document.createElement('tr');
            tr.id = `fila-reserva-${reserva.id}`;

            const botonCancelar = reserva.estado !== 'cancelada'
                ? `<button class="btn btn-danger btn-cancelar-reserva" data-id="${reserva.id}" style="font-size: 0.8rem; padding: 0.3rem 0.6rem;">Cancelar</button>`
                : '';

            tr.innerHTML = `
                <td>#${reserva.id}</td>
                <td>
                    <strong>${escapeHTML(reserva.nombre_huesped)}</strong><br>
                    <small style="color: var(--text-muted);">${escapeHTML(reserva.email_huesped)}</small>
                </td>
                <td>${escapeHTML(reserva.nombre_alojamiento)}</td>
                <td>${formatDate(reserva.fecha_entrada)}</td>
                <td>${formatDate(reserva.fecha_salida)}</td>
                <td>${formatMoney(reserva.importe)}</td>
                <td><span class="badge badge-${reserva.estado}">${reserva.estado}</span></td>
                <td style="white-space: nowrap;">
                    <a href="${BASE_URL}/reservas/${reserva.id}" class="btn btn-secondary" style="font-size: 0.8rem; padding: 0.3rem 0.6rem;">Ver Detalle</a>
                    ${botonCancelar}
                </td>
            `;

            tbodyReservas.appendChild(tr);
        });
    }

    if (filtroForm) {
        filtroForm.addEventListener('submit', (e) => {
            e.preventDefault();
            cargarReservas();
        });
    }

    if (btnLimpiar) {
        btnLimpiar.addEventListener('click', () => {
            filtroForm.reset();
            cargarReservas();
        });
    }

    // ==========================================
    // 2. CREACIÓN DE NUEVA RESERVA
    // ==========================================
    const modalCrear = document.getElementById('modal-crear');
    const btnToggleModal = document.getElementById('btn-toggle-modal');
    const btnCancelarModal = document.getElementById('btn-cancelar-modal');
    const formCrearReserva = document.getElementById('form-crear-reserva');
    const btnSubmitCrear = document.getElementById('btn-submit-crear');

    if (btnToggleModal && modalCrear) {
        btnToggleModal.addEventListener('click', () => {
            modalCrear.style.display = modalCrear.style.display === 'none' ? 'block' : 'none';
            if (modalCrear.style.display === 'block') {
                modalCrear.scrollIntoView({ behavior: 'smooth' });
            }
        });
    }

    if (btnCancelarModal && modalCrear) {
        btnCancelarModal.addEventListener('click', () => {
            modalCrear.style.display = 'none';
            formCrearReserva.reset();
        });
    }

    if (formCrearReserva) {
        formCrearReserva.addEventListener('submit', async (e) => {
            e.preventDefault();

            const entrada = document.getElementById('nuevo-entrada').value;
            const salida = document.getElementById('nuevo-salida').value;
            const importe = parseFloat(document.getElementById('nuevo-importe').value);

            // Validación en cliente
            if (new Date(salida) <= new Date(entrada)) {
                showAlert('La fecha de salida debe ser posterior a la fecha de entrada.', 'error');
                return;
            }

            if (isNaN(importe) || importe <= 0) {
                showAlert('El importe debe ser mayor que 0.', 'error');
                return;
            }

            const payload = {
                nombre_huesped: document.getElementById('nuevo-nombre').value.trim(),
                email_huesped: document.getElementById('nuevo-email').value.trim(),
                nombre_alojamiento: document.getElementById('nuevo-alojamiento').value.trim(),
                fecha_entrada: entrada,
                fecha_salida: salida,
                importe: importe,
                notas: document.getElementById('nuevo-notas').value.trim()
            };

            btnSubmitCrear.disabled = true;
            btnSubmitCrear.textContent = 'Guardando...';

            try {
                const response = await fetch(`${BASE_URL}/api/reservas`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });

                const result = await response.json();

                if (!response.ok) {
                    throw new Error(result.message || 'Error al registrar la reserva.');
                }

                showAlert(`¡Reserva #${result.data.id} creada con éxito!`, 'success');
                formCrearReserva.reset();
                modalCrear.style.display = 'none';

                // Recargar listado dinámicamente
                cargarReservas();
            } catch (error) {
                showAlert(error.message, 'error');
            } finally {
                btnSubmitCrear.disabled = false;
                btnSubmitCrear.textContent = 'Guardar Reserva';
            }
        });
    }

    // ==========================================
    // 3. CANCELACIÓN / CAMBIO DE ESTADO
    // ==========================================
    // Desde el listado (delegación de eventos)
    if (tbodyReservas) {
        tbodyReservas.addEventListener('click', async (e) => {
            if (e.target && e.target.classList.contains('btn-cancelar-reserva')) {
                const id = e.target.getAttribute('data-id');
                if (!confirm(`¿Estás seguro de que deseas cancelar la reserva #${id}?`)) {
                    return;
                }

                await cambiarEstadoReserva(id, 'cancelada', () => {
                    cargarReservas();
                });
            }
        });
    }

    // Desde la vista de detalle
    const accionesEstado = document.getElementById('acciones-estado');
    if (accionesEstado) {
        accionesEstado.addEventListener('click', async (e) => {
            if (e.target && e.target.classList.contains('btn-accion-estado')) {
                const id = e.target.getAttribute('data-id');
                const nuevoEstado = e.target.getAttribute('data-estado');

                const accionTexto = nuevoEstado === 'confirmada' ? 'confirmar' : 'cancelar';
                if (!confirm(`¿Deseas ${accionTexto} la reserva #${id}?`)) {
                    return;
                }

                await cambiarEstadoReserva(id, nuevoEstado, () => {
                    window.location.reload(); // Recarga para actualizar timeline en la vista de detalle
                });
            }
        });
    }

    async function cambiarEstadoReserva(id, nuevoEstado, onSuccess) {
        try {
            const response = await fetch(`${BASE_URL}/api/reservas/${id}/estado`, {
                method: 'PATCH',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ estado: nuevoEstado })
            });

            const result = await response.json();

            if (!response.ok) {
                throw new Error(result.message || 'No se pudo cambiar el estado de la reserva.');
            }

            showAlert(`Reserva #${id} actualizada a '${nuevoEstado}'.`, 'success');
            if (onSuccess) onSuccess();
        } catch (error) {
            showAlert(error.message, 'error');
        }
    }

    function escapeHTML(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }
});