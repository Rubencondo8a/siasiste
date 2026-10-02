(function() {
    'use strict';

    var config = window.CHAT_CONFIG;
    if (!config) return;

    var messagesEl = document.getElementById('chatMessages');
    var form = document.getElementById('chatForm');
    var input = document.getElementById('chatInput');
    var sendBtn = document.getElementById('chatSend');
    var estadoEl = document.getElementById('chatEstado');
    var inputWrapper = document.getElementById('chatInputWrapper');
    var typingEl = document.getElementById('typingIndicator');
    var lastMessageId = 0;
    var pollingTimer = null;
    var isClosed = config.isClosed;
    var lastPollTime = new Date().toISOString().slice(0, 19).replace('T', ' ');
    var typingTimer = null;
    var isTyping = false;
    var typingEndpoint = config.baseUrl + (config.userRol === 'docente' ? 'docente' : 'estudiante') + '/indicarEscritura';
    var likeEndpoint = config.baseUrl + (config.userRol === 'docente' ? 'docente' : 'estudiante') + '/darLikeMensaje';
    var aprobarEndpoint = config.baseUrl + 'docente/aprobarMensaje';
    var likesState = {};
    var esDocente = config.userRol === 'docente';
    var aprobState = config.aprobaciones || {};

    function playNotifSound() {
        try {
            var ctx = new (window.AudioContext || window.webkitAudioContext)();
            var notes = [523, 659, 784, 1047];
            for (var i = 0; i < notes.length; i++) {
                var osc = ctx.createOscillator();
                var gain = ctx.createGain();
                osc.connect(gain);
                gain.connect(ctx.destination);
                osc.type = 'sine';
                var t = ctx.currentTime + i * 0.08;
                osc.frequency.setValueAtTime(notes[i], t);
                gain.gain.setValueAtTime(0.2, t);
                gain.gain.exponentialRampToValueAtTime(0.01, t + 0.2);
                osc.start(t);
                osc.stop(t + 0.2);
            }
        } catch(e) {}
    }

    function escapeHtml(text) {
        var d = document.createElement('div');
        d.appendChild(document.createTextNode(text));
        return d.innerHTML;
    }

    function formatTime(dateStr) {
        var d = new Date(dateStr);
        var day = String(d.getDate()).padStart(2, '0');
        var month = String(d.getMonth() + 1).padStart(2, '0');
        var hours = String(d.getHours()).padStart(2, '0');
        var mins = String(d.getMinutes()).padStart(2, '0');
        return day + '/' + month + ' ' + hours + ':' + mins;
    }

    function profilePhoto(foto) {
        if (foto) return config.baseUrl + 'uploads/profile/' + foto;
        return config.baseUrl + 'assets/img/user-default.svg';
    }

    function sendTypingStatus(action) {
        var fd = new FormData();
        fd.append('discusion_id', config.discusionId);
        fd.append('action', action);
        fd.append('_csrf_token', document.querySelector('input[name="_csrf_token"]')?.value || '');
        fetch(typingEndpoint, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(function(r) { return r.json(); })
        .catch(function() {});
    }

    function onTyping() {
        if (!isTyping) {
            isTyping = true;
            sendTypingStatus('typing');
        }
        clearTimeout(typingTimer);
        typingTimer = setTimeout(function() {
            isTyping = false;
            sendTypingStatus('stop');
        }, 2000);
    }

    function stopTyping() {
        clearTimeout(typingTimer);
        if (isTyping) {
            isTyping = false;
            sendTypingStatus('stop');
        }
    }

    function showTyping(names) {
        if (!typingEl) return;
        if (!names || names.length === 0) {
            typingEl.style.display = 'none';
            typingEl.innerHTML = '';
            return;
        }
        var text = '';
        if (names.length === 1) {
            text = escapeHtml(names[0]) + ' est\u00e1 escribiendo';
        } else if (names.length === 2) {
            text = escapeHtml(names[0]) + ' y ' + escapeHtml(names[1]) + ' est\u00e1n escribiendo';
        } else {
            text = names.length + ' personas est\u00e1n escribiendo';
        }
        typingEl.innerHTML = '<div class="typing-dots"><span></span><span></span><span></span></div> ' + text + '...';
        typingEl.style.display = 'block';
    }

    function renderMessage(msg) {
        var isOwn = parseInt(msg.usuario_id) === config.userId;
        var isDocente = msg.autor_rol === 'docente';
        var cls = isOwn ? 'msg-own' : 'msg-other';
        if (isDocente && !isOwn) cls += ' msg-docente';

        var editado = msg.editado ? ' <span class="msg-edited"><i class="fas fa-pen" style="font-size:.6rem;"></i> editado</span>' : '';
        var fotoSrc = profilePhoto(msg.autor_foto);
        var likeHtml = renderLikeHtml(msg.id, isOwn);

        return '<div class="msg ' + cls + '" data-msg-id="' + msg.id + '">' +
            '<div class="msg-avatar"><img src="' + fotoSrc + '" alt=""></div>' +
            '<div class="msg-content">' +
                '<div class="msg-author">' + escapeHtml(msg.autor_nombre) +
                    (isDocente ? ' <span class="badge badge-warning badge-sm">Docente</span>' : '') +
                '</div>' +
                '<div class="msg-bubble">' + escapeHtml(msg.mensaje) + '</div>' +
                likeHtml +
                '<div class="msg-time">' + formatTime(msg.created_at) + editado + '</div>' +
            '</div>' +
        '</div>';
    }

    function renderLikeHtml(msgId, isOwn) {
        var st = likesState[msgId];
        var total = st ? st.total_likes : 0;
        var yo = st && st.yo_like;
        var aprobado = !!aprobState[msgId];
        var checkHtml = esDocente
            ? '<button type="button" class="msg-aprob-btn' + (aprobado ? ' activo' : '') + '" data-msg-id="' + msgId + '"' +
                (isClosed ? ' disabled' : '') +
                ' title="' + (aprobado ? 'Quitar el check: los estudiantes no podr\u00e1n dar like a este comentario' : 'Poner check: los estudiantes podr\u00e1n dar like a este comentario') + '">' +
                '<i class="fas fa-check"></i></button>'
            : '';

        if (isOwn) {
            if (!total && !checkHtml) return '';
            return '<div class="msg-like-row">' +
                (total ? '<span class="msg-like-count"><i class="fas fa-heart"></i> ' + total + '</span>' : '') +
                checkHtml +
            '</div>';
        }

        var bloqueado = !esDocente && !aprobado;
        var titulo = isClosed
            ? 'Debate cerrado'
            : (bloqueado ? 'El docente a\u00fan no habilita los likes en este comentario' : 'Dar like');
        var etiqueta = total ? '<span class="like-num">' + total + '</span>' : (bloqueado ? '' : '<span class="like-label">Apoyo</span>');

        return '<div class="msg-like-row">' + checkHtml +
            '<button type="button" class="msg-like-btn' + (yo ? ' liked' : '') + '" data-msg-id="' + msgId + '"' +
            (isClosed || bloqueado ? ' disabled' : '') + ' title="' + titulo + '">' +
                '<i class="far fa-heart' + (yo ? ' fas' : '') + '"></i>' + etiqueta +
            '</button>' +
        '</div>';
    }

    function refreshLikeDom(msgId) {
        var msgEl = messagesEl.querySelector('.msg[data-msg-id="' + msgId + '"]');
        if (!msgEl) return;
        var isOwn = msgEl.classList.contains('msg-own');
        var row = msgEl.querySelector('.msg-like-row');
        if (!row) {
            var timeEl = msgEl.querySelector('.msg-time');
            if (timeEl) {
                var tmp0 = document.createElement('div');
                tmp0.innerHTML = renderLikeHtml(parseInt(msgId), isOwn);
                if (tmp0.firstChild) timeEl.insertAdjacentHTML('beforebegin', tmp0.firstChild);
            }
            return;
        }
        var tmp = document.createElement('div');
        tmp.innerHTML = renderLikeHtml(parseInt(msgId), isOwn);
        if (tmp.firstChild) {
            row.replaceWith(tmp.firstChild);
        } else {
            row.remove();
        }
    }

    function refreshAllLikes() {
        messagesEl.querySelectorAll('.msg[data-msg-id]').forEach(function(el) {
            refreshLikeDom(parseInt(el.getAttribute('data-msg-id')));
        });
    }

    function applyLikesMap(map) {
        for (var k in map) {
            likesState[k] = map[k];
            refreshLikeDom(k);
        }
    }

    function applyAprobaciones(map) {
        var nuevo = map || {};
        var tocados = {};
        var k;
        for (k in aprobState) tocados[k] = true;
        for (k in nuevo) tocados[k] = true;

        var cambiados = [];
        for (k in tocados) {
            if (!!aprobState[k] !== !!nuevo[k]) {
                cambiados.push(k);
            }
        }
        aprobState = nuevo;
        for (var i = 0; i < cambiados.length; i++) {
            refreshLikeDom(cambiados[i]);
        }
    }

    function appendAnuncio(nombre) {
        var div = document.createElement('div');
        div.className = 'chat-sistema';
        div.innerHTML = '\uD83C\uDFC6 <strong>' + escapeHtml(nombre) + '</strong> obtuvo un punto por buen aporte al tema';
        messagesEl.appendChild(div);
        scrollToBottom();
        setTimeout(function() { div.classList.add('chat-sistema-fade'); }, 9000);
    }

    function renderMessages(messages) {
        var html = '';
        for (var i = 0; i < messages.length; i++) {
            html += renderMessage(messages[i]);
            if (messages[i].id > lastMessageId) lastMessageId = messages[i].id;
        }
        if (html) {
            messagesEl.innerHTML = html;
            scrollToBottom();
        } else {
            messagesEl.innerHTML = '<div class="chat-empty"><i class="fas fa-comments fa-2x mb-2" style="opacity:.2"></i><p class="text-muted">No hay mensajes a\u00fan. S\u00e9 el primero en escribir.</p></div>';
        }
    }

    function appendMessages(messages) {
        if (messages.length === 0) return;
        var wasAtBottom = isScrolledToBottom();
        var html = '';
        for (var i = 0; i < messages.length; i++) {
            if (messagesEl.querySelector('.msg[data-msg-id="' + messages[i].id + '"]')) continue;
            html += renderMessage(messages[i]);
            if (messages[i].id > lastMessageId) lastMessageId = messages[i].id;
        }
        if (!html) return;
        var empty = messagesEl.querySelector('.chat-empty');
        if (empty) empty.remove();
        messagesEl.insertAdjacentHTML('beforeend', html);
        if (wasAtBottom) scrollToBottom();
    }

    function scrollToBottom() {
        messagesEl.scrollTop = messagesEl.scrollHeight;
    }

    function isScrolledToBottom() {
        return messagesEl.scrollHeight - messagesEl.scrollTop - messagesEl.clientHeight < 80;
    }

    var pollingInFlight = false;

    function poll() {
        if (pollingInFlight) return;
        pollingInFlight = true;
        var now = new Date().toISOString().slice(0, 19).replace('T', ' ');
        fetch(config.baseUrl + (config.userRol === 'docente' ? 'docente' : 'estudiante') + '/obtenerMensajes?discusion_id=' + config.discusionId + '&after_id=' + lastMessageId + '&since=' + encodeURIComponent(lastPollTime))
        .then(function(r) {
            if (r.status === 404) {
                var err = new Error('deleted');
                err.deleted = true;
                throw err;
            }
            return r.json();
        })
        .then(function(d) {
            if (d.success && d.mensajes && d.mensajes.length > 0) {
                appendMessages(d.mensajes);
                for (var i = 0; i < d.mensajes.length; i++) {
                    if (parseInt(d.mensajes[i].usuario_id) !== config.userId) {
                        playNotifSound();
                        break;
                    }
                }
            }
            if (d.likes) {
                applyLikesMap(d.likes);
            }
            if (d.aprobaciones) {
                applyAprobaciones(d.aprobaciones);
            }
            if (d.anuncios && d.anuncios.length > 0) {
                for (var i = 0; i < d.anuncios.length; i++) {
                    appendAnuncio(d.anuncios[i].nombre);
                }
            }
            if (d.typing && d.typing.length > 0) {
                var names = [];
                for (var i = 0; i < d.typing.length; i++) {
                    names.push(d.typing[i].nombre);
                }
                showTyping(names);
            } else {
                showTyping([]);
            }
            if (d.editados && d.editados.length > 0) {
                for (var i = 0; i < d.editados.length; i++) {
                    var edited = d.editados[i];
                    var msgEl = messagesEl.querySelector('.msg[data-msg-id="' + edited.id + '"]');
                    if (msgEl) {
                        var bubble = msgEl.querySelector('.msg-bubble');
                        if (bubble) bubble.innerHTML = escapeHtml(edited.mensaje);
                        var timeEl = msgEl.querySelector('.msg-time');
                        if (timeEl && !timeEl.querySelector('.msg-edited')) {
                            timeEl.insertAdjacentHTML('beforeend', ' <span class="msg-edited"><i class="fas fa-pen" style="font-size:.6rem;"></i> editado</span>');
                        }
                    }
                }
            }
            if (d.estado === 'cerrada' && !isClosed) {
                isClosed = true;
                if (inputWrapper) inputWrapper.style.display = 'none';
                if (estadoEl) { estadoEl.textContent = '\uD83D\uDD34 Cerrado'; estadoEl.className = 'chat-estado cerrada'; }
                refreshAllLikes();
            } else if (d.estado === 'abierta' && isClosed) {
                isClosed = false;
                if (inputWrapper) inputWrapper.style.display = '';
                if (estadoEl) { estadoEl.textContent = '\uD83D\uDFE2 Abierto'; estadoEl.className = 'chat-estado abierta'; }
                refreshAllLikes();
            }
        })
        .catch(function(err) {
            pollingInFlight = false;
            if (err && err.deleted) {
                clearInterval(pollingTimer);
                Swal.fire({
                    icon: 'info',
                    title: 'Debate eliminado',
                    text: 'El docente elimin\u00f3 este debate.',
                    timer: 2500,
                    showConfirmButton: false
                }).then(function() {
                    window.location.href = config.baseUrl + (config.userRol === 'docente' ? 'docente' : 'estudiante') + '/discusiones?semana_id=' + (config.semanaId || '');
                });
            }
            return;
        })
        .finally(function() { pollingInFlight = false; });
        lastPollTime = now;
    }

    function sendMessage(fd) {
        sendBtn.disabled = true;
        input.disabled = true;
        stopTyping();

        var endpoint = config.userRol === 'docente' ? 'docente/enviarMensaje' : 'estudiante/enviarMensaje';
        fetch(config.baseUrl + endpoint, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(function(r) { return r.json(); })
        .then(function(d) {
            if (d.success) {
                input.value = '';
                input.style.height = 'auto';
                poll();
            } else {
                Swal.fire({ icon: 'error', title: 'Error', text: d.message });
            }
        })
        .catch(function() {
            Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo enviar el mensaje.' });
        })
        .finally(function() {
            sendBtn.disabled = false;
            input.disabled = false;
            input.focus();
        });
    }

    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            var msg = input.value.trim();
            if (!msg) return;
            var fd = new FormData(form);
            fd.set('mensaje', msg);
            sendMessage(fd);
        });
    }

    if (input) {
        input.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = Math.min(this.scrollHeight, 120) + 'px';
            onTyping();
        });
        input.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                form.requestSubmit();
            }
        });
    }

    var btnCerrar = document.getElementById('btnCerrarReabrir');
    if (btnCerrar) {
        btnCerrar.addEventListener('click', function() {
            if (isClosed) {
                // Reabrir requiere un nuevo rango de vigencia
                Swal.fire({
                    title: 'Reabrir debate',
                    html:
                        '<div class="text-left">' +
                        '<div class="text-muted mb-2" style="font-size:.85rem">Indique el nuevo rango de vigencia para reabrir el debate.</div>' +
                        '<div class="form-group"><label class="mb-1">Vigente desde</label>' +
                        '<input type="datetime-local" id="swalFechaInicio" class="form-control" value="' + (config.fechaInicioSugerida || '') + '"></div>' +
                        '<div class="form-group mb-0"><label class="mb-1">Vigente hasta</label>' +
                        '<input type="datetime-local" id="swalFechaFin" class="form-control" value="' + (config.fechaFinSugerida || '') + '"></div>' +
                        '</div>',
                    showCancelButton: true,
                    confirmButtonText: 'Reabrir',
                    cancelButtonText: 'Cancelar',
                    focusConfirm: false,
                    preConfirm: function() {
                        var fi = document.getElementById('swalFechaInicio').value;
                        var ff = document.getElementById('swalFechaFin').value;
                        if (!fi || !ff) {
                            Swal.showValidationMessage('Indique ambas fechas.');
                            return false;
                        }
                        if (new Date(ff) <= new Date(fi)) {
                            Swal.showValidationMessage('La fecha de fin debe ser posterior a la de inicio.');
                            return false;
                        }
                        return { inicio: fi, fin: ff };
                    }
                }).then(function(r) {
                    if (!r.isConfirmed) return;
                    var fd2 = new FormData();
                    fd2.append('discusion_id', config.discusionId);
                    fd2.append('fecha_inicio', r.value.inicio);
                    fd2.append('fecha_fin', r.value.fin);
                    fd2.append('_csrf_token', document.querySelector('input[name="_csrf_token"]')?.value || '');
                    fetch(config.baseUrl + 'docente/reabrirDiscusion', { method: 'POST', body: fd2, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                    .then(function(res) { return res.json(); })
                    .then(function(d) {
                        if (!d.success) {
                            Swal.fire({ icon: 'error', title: 'Error', text: d.message });
                            return;
                        }
                        notificarYRecargar(d.job_id, 'Debate reabierto', 'Notificando reapertura a los estudiantes...');
                    })
                    .catch(function() { Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo reabrir el debate.' }); });
                });
                return;
            }

            Swal.fire({
                title: 'Cerrar debate',
                html: 'El debate quedara <strong>finalizado</strong> y ya no aceptara participaciones.<br><small class="text-muted">Los estudiantes seran notificados por correo del cierre.</small>',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Si, cerrar debate',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#dc3545'
            }).then(function(rc) {
                if (!rc.isConfirmed) return;
                cerrarDebateAhora();
            });

            function cerrarDebateAhora() {
                var fd = new FormData();
                fd.append('discusion_id', config.discusionId);
                fd.append('_csrf_token', document.querySelector('input[name="_csrf_token"]')?.value || '');

                fetch(config.baseUrl + 'docente/cerrarDiscusion', { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function(r) { return r.json(); })
                .then(function(d) {
                    if (d.success) {
                        notificarYRecargar(d.job_id, 'Debate cerrado', 'Notificando cierre a los estudiantes...');
                    } else if (d.message && d.message.indexOf('Reabrir') !== -1) {
                        // Ya estaba cerrado: pedir nuevo rango
                        btnCerrar.click();
                    } else {
                        Swal.fire({ icon: 'error', title: 'Error', text: d.message });
                    }
                })
                .catch(function() { Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo cambiar el estado.' }); });
            }
        });

        /**
         * Muestra un modal bloqueante con el avance del aviso por correo.
         * El estado ya cambio en la BD: aqui solo se consume el trabajo de
         * notificacion, un destinatario por peticion, y se recarga al final.
         */
        function notificarYRecargar(jobId, tituloOk, textoInicio) {
            if (!jobId) {
                Swal.fire({ icon: 'success', title: tituloOk, timer: 1100, showConfirmButton: false });
                setTimeout(function() { location.reload(); }, 1000);
                return;
            }

            Swal.fire({
                title: tituloOk,
                html:
                    '<div class="text-left">' +
                    '<p class="mb-2" id="swlMsg">' + textoInicio + '</p>' +
                    '<div class="progress" style="height:8px;"><div class="progress-bar progress-bar-striped progress-bar-animated" id="swlBar" style="width:0%"></div></div>' +
                    '<div class="d-flex justify-content-between mt-1" style="font-size:.8rem">' +
                    '<span id="swlContador">0 / 0</span><span id="swlEstado" class="text-muted"></span></div>' +
                    '<div class="mt-2" style="font-size:.8rem" id="swlUltimos"></div>' +
                    '</div>',
                showConfirmButton: false,
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: function() { procesarJob(jobId); }
            });

            function setBar(d, ultimo) {
                var bar = document.getElementById('swlBar');
                if (bar) bar.style.width = (d.total > 0 ? Math.round((d.procesados / d.total) * 100) : 100) + '%';
                var c = document.getElementById('swlContador');
                if (c) c.textContent = d.procesados + ' / ' + d.total;
                var e = document.getElementById('swlEstado');
                if (e) e.textContent = d.enviados + ' enviados' + (d.fallidos ? ', ' + d.fallidos + ' no enviados' : '');
                var u = document.getElementById('swlUltimos');
                if (u) {
                    var html = '';
                    if (ultimo) {
                        html += (ultimo.estado === 'enviado'
                            ? '<i class="fas fa-check-circle text-success"></i>'
                            : '<i class="fas fa-exclamation-circle text-warning"></i>')
                            + ' Avisando a <strong>' + escapar(ultimo.nombre) + '</strong>';
                    }
                    var lista = d.ultimos || [];
                    if (lista.length > 1) {
                        html += '<div class="mt-1" style="max-height:66px;overflow:auto">';
                        for (var i = lista.length - 2; i >= 0; i--) {
                            html += '<div class="text-muted">' + (lista[i].estado === 'enviado'
                                ? '<i class="fas fa-check text-success"></i>'
                                : '<i class="fas fa-times text-warning"></i>')
                                + ' ' + escapar(lista[i].nombre) + '</div>';
                        }
                        html += '</div>';
                    }
                    u.innerHTML = html;
                }
            }

            function procesarJob(id) {
                var fd = new FormData();
                fd.append('job_id', id);
                fd.append('_csrf_token', document.querySelector('input[name="_csrf_token"]')?.value || '');
                fetch(config.baseUrl + 'docente/procesarNotificacionDebate', { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function(r) { return r.json(); })
                .then(function(d) {
                    if (!d.success) {
                        var m0 = document.getElementById('swlMsg');
                        if (m0) m0.textContent = d.message || 'No se pudo continuar el aviso.';
                        return;
                    }
                    setBar(d, d.ultimos && d.ultimos.length ? d.ultimos[0] : null);
                    if (d.completado) {
                        var msg = document.getElementById('swlMsg');
                        if (msg) {
                            msg.innerHTML = d.fallidos > 0
                                ? 'Listo: <strong>' + d.enviados + '</strong> enviados, <strong>' + d.fallidos + '</strong> no pudieron salir.'
                                : 'Listo: se notifico a <strong>' + d.enviados + '</strong> estudiantes.';
                        }
                        setTimeout(function() { Swal.close(); location.reload(); }, 1800);
                        return;
                    }
                    setTimeout(function() { procesarJob(id); }, 50);
                })
                .catch(function() {
                    var msg = document.getElementById('swlMsg');
                    if (msg) msg.innerHTML = '<span class="text-danger">Se perdio la conexion durante el aviso. Recargando...</span>';
                    setTimeout(function() { location.reload(); }, 1800);
                });
            }
        }

        function escapar(s) {
            var d = document.createElement('div');
            d.textContent = s == null ? '' : s;
            return d.innerHTML;
        }

        function actualizarEstado(cerrado, mensaje, iconoNuevo) {
            isClosed = cerrado;
            if (inputWrapper) inputWrapper.style.display = isClosed ? 'none' : '';
            if (estadoEl) {
                estadoEl.textContent = isClosed ? '\uD83D\uDD34 Cerrado' : '\uD83D\uDFE2 Abierto';
                estadoEl.className = 'chat-estado ' + (isClosed ? 'cerrada' : 'abierta');
            }
            btnCerrar.innerHTML = isClosed
                ? '<i class="fas fa-lock-open"></i><span class="d-none d-md-inline"> Reabrir</span>'
                : '<i class="fas fa-lock"></i><span class="d-none d-md-inline"> Cerrar</span>';
            btnCerrar.title = isClosed ? 'Reabrir debate con nueva fecha' : 'Cerrar debate';
            Swal.fire({ icon: 'success', title: mensaje, timer: 1400, showConfirmButton: false });
        }
    }

    // Transparencia: tooltip propio con los usuarios que dieron like
    var likesTooltipCache = {};
    var lastMouse = { x: -1, y: -1 };
    document.addEventListener('mousemove', function(e) { lastMouse.x = e.clientX; lastMouse.y = e.clientY; });
    var likesTipEl = null;
    var likesTipMsgId = null;

    function urlLikes() {
        return config.baseUrl + (config.userRol === 'docente' ? 'docente/likesDeMensaje' : 'estudiante/likesDeMensaje');
    }

    function cargarLikers(msgId, cb) {
        if (likesTooltipCache[msgId]) { cb(likesTooltipCache[msgId]); return; }
        fetch(urlLikes() + '?mensaje_id=' + msgId, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(function(r) { return r.json(); })
        .then(function(d) {
            if (!d.success) { cb(null); return; }
            likesTooltipCache[msgId] = d.likes;
            cb(d.likes);
        })
        .catch(function() { cb(null); });
    }
    function likesTipHide() {
        if (likesTipEl) likesTipEl.style.display = 'none';
        likesTipMsgId = null;
    }

    function likesTipShow(el, msgId, nombres) {
        if (!likesTipEl) {
            likesTipEl = document.createElement('div');
            likesTipEl.className = 'likes-tooltip';
            document.body.appendChild(likesTipEl);
        }
        likesTipEl.innerHTML = nombres;
        likesTipEl.style.display = 'block';
        likesTipMsgId = msgId;

        var r = el.getBoundingClientRect();
        if (r.width === 0 || r.height === 0) { likesTipHide(); return; } // el mensaje se re-renderizo
        var dentro = lastMouse.x >= r.left && lastMouse.x <= r.right && lastMouse.y >= r.top && lastMouse.y <= r.bottom;
        if (!dentro) { likesTipHide(); return; } // el cursor ya no esta encima
        var tw = likesTipEl.offsetWidth, th = likesTipEl.offsetHeight;
        var top = r.top - th - 8;
        if (top < 8) top = r.bottom + 8;
        var left = r.left + (r.width / 2) - (tw / 2);
        if (left < 8) left = 8;
        if (left + tw > window.innerWidth - 8) left = window.innerWidth - 8 - tw;
        likesTipEl.style.top = (top + window.scrollY * 0) + 'px';
        likesTipEl.style.left = left + 'px';
    }

    function mostrarTooltipLike(el, msgId) {
        var st = likesState[msgId];
        if (!st || !st.total_likes) return;
        if (likesTipMsgId === msgId && likesTipEl && likesTipEl.style.display === 'block') return;

        cargarLikers(msgId, function(likes) {
            if (!likes || !likes.length) return;
            var nombres = likes.map(function(u) {
                return '<div class="likes-tooltip-item">' + u.nombres + ' ' + u.apellidos + '</div>';
            }).join('');
            likesTipShow(el, msgId, nombres);
        });
    }

    messagesEl.addEventListener('mouseover', function(e) {
        var btn = e.target.closest('.msg-like-btn');
        if (btn) { mostrarTooltipLike(btn, parseInt(btn.getAttribute('data-msg-id'))); return; }
        var cnt = e.target.closest('.msg-like-count');
        if (cnt) {
            var msgEl = cnt.closest('.msg');
            if (msgEl) {
                var id = msgEl.getAttribute('data-msg-id');
                if (id) mostrarTooltipLike(cnt, parseInt(id));
            }
        }
    });
    messagesEl.addEventListener('mouseout', function(e) {
        var t = e.target.closest('.msg-like-btn, .msg-like-count');
        if (t) likesTipHide();
    });
    messagesEl.addEventListener('scroll', likesTipHide);
    renderMessages(config.initialMessages);
    pollingTimer = setInterval(poll, 3000);

    messagesEl.addEventListener('click', function(e) {
        var btn = e.target.closest('.msg-like-btn');
        if (!btn || btn.disabled) return;
        var msgId = parseInt(btn.getAttribute('data-msg-id'));
        if (!msgId) return;

        btn.disabled = true;
        var fd = new FormData();
        fd.append('mensaje_id', msgId);
        fd.append('_csrf_token', document.querySelector('input[name="_csrf_token"]')?.value || '');

        fetch(likeEndpoint, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(function(r) { return r.json(); })
        .then(function(d) {
            if (d.success) {
                likesState[msgId] = { total_likes: d.total_likes, yo_like: d.liked ? 1 : 0 };
                refreshLikeDom(msgId);
                if (d.otorgado && d.autor_nombre) {
                    appendAnuncio(d.autor_nombre);
                    playNotifSound();
                }
            } else {
                Swal.fire({ icon: 'error', title: 'Error', text: d.message, timer: 2000, showConfirmButton: false });
            }
        })
        .catch(function() {})
        .finally(function() { btn.disabled = false; });
    });

    messagesEl.addEventListener('click', function(e) {
        var btn = e.target.closest('.msg-aprob-btn');
        if (!btn || btn.disabled) return;
        var msgId = parseInt(btn.getAttribute('data-msg-id'));
        if (!msgId) return;

        btn.disabled = true;
        var fd = new FormData();
        fd.append('mensaje_id', msgId);
        fd.append('_csrf_token', document.querySelector('input[name="_csrf_token"]')?.value || '');

        fetch(aprobarEndpoint, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(function(r) { return r.json(); })
        .then(function(d) {
            if (d.success) {
                if (d.aprobado) {
                    aprobState[msgId] = { aprobado: 1, docente: d.docente, fecha: d.fecha };
                } else {
                    delete aprobState[msgId];
                }
                refreshLikeDom(msgId);
                Swal.fire({
                    icon: d.aprobado ? 'success' : 'info',
                    text: d.mensaje,
                    toast: true,
                    position: 'bottom-end',
                    showConfirmButton: false,
                    timer: 2200,
                    timerProgressBar: true
                });
            } else {
                Swal.fire({ icon: 'error', title: 'Error', text: d.message, timer: 2200, showConfirmButton: false });
                refreshLikeDom(msgId);
            }
        })
        .catch(function() { btn.disabled = false; });
    });

    messagesEl.addEventListener('dblclick', function(e) {
        var bubble = e.target.closest('.msg-bubble');
        if (!bubble) return;
        var msgEl = bubble.closest('.msg');
        if (!msgEl || !msgEl.classList.contains('msg-own')) return;

        var msgId = parseInt(msgEl.getAttribute('data-msg-id'));
        if (!msgId) return;

        var currentText = bubble.textContent.replace(' (editado)', '').trim();

        Swal.fire({
            title: 'Editar mensaje',
            input: 'textarea',
            inputValue: currentText,
            showCancelButton: true,
            confirmButtonText: 'Guardar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#6c5ce7',
            inputValidator: function(v) { if (!v.trim()) return 'El mensaje no puede estar vac\u00edo.'; }
        }).then(function(result) {
            if (result.isConfirmed) {
                var fd = new FormData();
                fd.append('mensaje_id', msgId);
                fd.append('mensaje', result.value.trim());
                fd.append('_csrf_token', document.querySelector('input[name="_csrf_token"]')?.value || '');
                var endpoint = config.userRol === 'docente' ? 'docente/editarMensaje' : 'estudiante/editarMensaje';
                fetch(config.baseUrl + endpoint, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function(r) { return r.json(); })
                .then(function(d) {
                    if (d.success) {
                        bubble.textContent = result.value.trim();
                        var timeEl = msgEl.querySelector('.msg-time');
                        if (timeEl && !timeEl.querySelector('.msg-edited')) {
                            timeEl.insertAdjacentHTML('beforeend', ' <span class="msg-edited"><i class="fas fa-pen" style="font-size:.6rem;"></i> editado</span>');
                        }
                    } else {
                        Swal.fire({ icon: 'error', title: 'Error', text: d.message });
                    }
                })
                .catch(function() { Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo editar.' }); });
            }
        });
    });
})();
