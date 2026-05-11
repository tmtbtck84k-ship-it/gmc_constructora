<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="h4 mb-0"><i class="bi bi-bar-chart-steps"></i> Gantt
        <?php if ($proyecto): ?>
            <small class="text-muted">— <?= htmlspecialchars($proyecto['codigo']) ?> · <?= htmlspecialchars($proyecto['nombre']) ?></small>
        <?php endif; ?>
    </h2>
    <?php if ($proyecto): ?>
        <div class="d-flex gap-2 flex-wrap">
            <div class="btn-group" role="group" aria-label="Zoom">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-vm="Day">Día</button>
                <button type="button" class="btn btn-outline-secondary btn-sm" data-vm="Week">Semana</button>
                <button type="button" class="btn btn-outline-secondary btn-sm" data-vm="Month">Mes</button>
            </div>
            <button type="button" class="btn btn-outline-danger btn-sm" id="btn-toggle-critica">
                <i class="bi bi-fire"></i> Ruta crítica
            </button>
            <?php if (can('obras.gantt.editar')): ?>
                <button type="button" class="btn btn-outline-warning btn-sm" id="btn-recalc-cpm" title="Recalcular CPM">
                    <i class="bi bi-arrow-clockwise"></i>
                </button>
            <?php endif; ?>
            <?php if (can('obras.gantt.exportar')): ?>
                <div class="btn-group">
                    <a class="btn btn-outline-success btn-sm"
                       href="<?= site_url('obras/gantt/pdf/' . $proyecto['id']) ?>" target="_blank">
                        <i class="bi bi-file-earmark-pdf"></i> PDF
                    </a>
                    <button type="button" class="btn btn-outline-success btn-sm" id="btn-export-png">
                        <i class="bi bi-image"></i> PNG
                    </button>
                </div>
                <a class="btn btn-outline-info btn-sm"
                   href="<?= site_url('obras/gantt/reporte/' . $proyecto['id']) ?>">
                    <i class="bi bi-table"></i> Reporte avance
                </a>
            <?php endif; ?>
            <a class="btn btn-outline-secondary btn-sm"
               href="<?= site_url('obras/hitos?proyecto_id=' . $proyecto['id']) ?>">
                <i class="bi bi-flag"></i> Hitos
            </a>
            <a class="btn btn-outline-secondary btn-sm"
               href="<?= site_url('obras/actividades?proyecto_id=' . $proyecto['id']) ?>">
                <i class="bi bi-list-task"></i> Actividades
            </a>
            <?php if (can('obras.gantt.dependencia')): ?>
                <button type="button" class="btn btn-primary btn-sm" id="btn-nueva-dep">
                    <i class="bi bi-link-45deg"></i> Nueva dependencia
                </button>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<form method="get" class="card card-body mb-3">
    <div class="row g-2 align-items-end">
        <div class="col-md-6">
            <label class="form-label">Proyecto</label>
            <select name="proyecto_id" class="form-select" onchange="this.form.submit()">
                <option value="">— seleccione —</option>
                <?php foreach ($proyectos as $p): ?>
                    <option value="<?= (int)$p['id'] ?>"
                        <?= $proyecto && (int)$p['id'] === (int)$proyecto['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($p['codigo']) ?> — <?= htmlspecialchars($p['nombre']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
</form>

<?php if (!$proyecto): ?>
    <div class="alert alert-info">Seleccione un proyecto para ver su Gantt.</div>
<?php else: ?>

<link rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/frappe-gantt@0.6.1/dist/frappe-gantt.css">
<style>
    /* Resaltar barras críticas en rojo */
    .gantt .bar-wrapper.g-bar-critica .bar { fill: #dc3545; }
    .gantt .bar-wrapper.g-bar-critica .bar-progress { fill: #b02a37; }
    /* Días no laborales sombreados (sobreposición pintada por nuestro JS) */
    .gantt .grid-row { fill: #fff; }
    .gantt .grid-row:nth-child(even) { fill: #f8f9fa; }
    .g-no-laboral-overlay { fill: rgba(108,117,125,.15); }
    .g-feriado-overlay    { fill: rgba(220,53,69,.10); }
    .gantt-container { overflow-x: auto; }
    .gantt .bar-label { font-weight: 600; }
</style>

<div class="row g-3">
    <div class="col-lg-9">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <strong>Diagrama de Gantt</strong>
                <span class="text-muted small"><span id="g-count">0</span> actividades · arrastra para ajustar</span>
            </div>
            <div class="card-body p-2">
                <div id="gantt-loading" class="text-center text-muted py-5">
                    <div class="spinner-border" role="status"></div>
                    <div class="mt-2">Cargando datos del Gantt…</div>
                </div>
                <div id="gantt-empty" class="alert alert-warning d-none m-3">
                    Este proyecto no tiene actividades aún. Cree algunas desde
                    <a href="<?= site_url('obras/actividades?proyecto_id=' . $proyecto['id']) ?>">la sección Actividades</a>.
                </div>
                <div class="gantt-container">
                    <svg id="gantt"></svg>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-3">
        <div class="card">
            <div class="card-header"><strong>Detalle</strong></div>
            <div class="card-body" id="g-detalle">
                <p class="text-muted small mb-0">Seleccione una actividad del Gantt para ver detalle, predecesoras y avance.</p>
            </div>
        </div>
        <div class="card mt-3">
            <div class="card-header"><strong>Hitos</strong></div>
            <ul class="list-group list-group-flush" id="g-hitos"></ul>
        </div>
    </div>
</div>

<!-- Modal nueva dependencia -->
<?php if (can('obras.gantt.dependencia')): ?>
<div class="modal fade" id="modal-dep" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form class="modal-content" id="form-dep">
            <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>"
                   value="<?= $this->security->get_csrf_hash() ?>" id="csrf-tok">
            <div class="modal-header">
                <h5 class="modal-title">Nueva dependencia</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Actividad sucesora <span class="text-danger">*</span></label>
                    <select name="actividad_id" id="dep-suc" class="form-select" required></select>
                    <small class="text-muted">La actividad que depende de la otra.</small>
                </div>
                <div class="mb-3">
                    <label class="form-label">Predecesora <span class="text-danger">*</span></label>
                    <select name="predecesor_id" id="dep-pred" class="form-select" required></select>
                </div>
                <div class="row g-3">
                    <div class="col-7">
                        <label class="form-label">Tipo</label>
                        <select name="tipo" class="form-select">
                            <option value="FS">FS · Finish-to-Start (la sucesora arranca al terminar la pred.)</option>
                            <option value="SS">SS · Start-to-Start (arrancan en paralelo)</option>
                            <option value="FF">FF · Finish-to-Finish (terminan a la vez)</option>
                            <option value="SF">SF · Start-to-Finish (raro)</option>
                        </select>
                    </div>
                    <div class="col-5">
                        <label class="form-label">Lag (días)</label>
                        <input type="number" name="lag_dias" class="form-control" value="0" step="1">
                        <small class="text-muted">Negativo = lead time.</small>
                    </div>
                </div>
                <div class="alert alert-danger d-none mt-3" id="dep-err"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-link-45deg"></i> Crear dependencia
                </button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/frappe-gantt@0.6.1/dist/frappe-gantt.umd.js"></script>
<script>
(function () {
    const PROYECTO_ID = <?= (int)$proyecto['id'] ?>;
    const URL_DATA   = "<?= site_url('obras/gantt/data/' . $proyecto['id']) ?>";
    const URL_MOVER  = (id) => "<?= site_url('obras/actividades') ?>/" + id + "/avance"; // alias placeholder
    const URL_MOVER2 = (id) => "<?= site_url('obras/actividades') ?>/" + id + "/mover";
    const URL_AVANCE = (id) => "<?= site_url('obras/actividades') ?>/" + id + "/avance";
    const URL_DEP_CREAR = "<?= site_url('obras/dependencias/crear') ?>";
    const URL_DEP_DEL   = (id) => "<?= site_url('obras/dependencias') ?>/" + id + "/eliminar";
    const PUEDE_EDITAR  = <?= can('obras.gantt.editar') ? 'true' : 'false' ?>;
    const PUEDE_DEPS    = <?= can('obras.gantt.dependencia') ? 'true' : 'false' ?>;
    const CSRF_NAME = "<?= $this->security->get_csrf_token_name() ?>";
    const STORAGE_KEY = "gmc_gantt_view_mode_" + PROYECTO_ID;

    let gantt;
    let datos = null;
    let viewMode = localStorage.getItem(STORAGE_KEY) || "Week";

    let CSRF_HASH = "<?= $this->security->get_csrf_hash() ?>";

    async function fetchJSON(url, opts) {
        const resp = await fetch(url, Object.assign({credentials:'same-origin'}, opts));
        const j = await resp.json().catch(() => ({}));
        // Cada respuesta del backend incluye el token rotado: actualizamos.
        if (j && j.csrf_hash) {
            CSRF_HASH = j.csrf_hash;
            const tk = document.getElementById('csrf-tok');
            if (tk) tk.value = j.csrf_hash;
        }
        if (!resp.ok || j.ok === false) throw new Error(j.error || ("HTTP " + resp.status));
        return j;
    }

    function csrfHash() { return CSRF_HASH; }

    async function cargar() {
        document.getElementById('gantt-loading').classList.remove('d-none');
        try {
            datos = await fetchJSON(URL_DATA);
            document.getElementById('gantt-loading').classList.add('d-none');
            document.getElementById('g-count').textContent = datos.tasks.length;

            renderHitos(datos.hitos);
            poblarSelectorDependencias(datos.tasks);

            if (datos.tasks.length === 0) {
                document.getElementById('gantt-empty').classList.remove('d-none');
                return;
            }
            renderGantt();
        } catch (e) {
            document.getElementById('gantt-loading').innerHTML =
                '<div class="alert alert-danger m-3">Error cargando: ' + e.message + '</div>';
        }
    }

    function renderGantt() {
        const tasks = datos.tasks.map(t => ({
            id:           String(t.id),
            name:         t.name,
            start:        t.start,
            end:          t.end,
            progress:     t.progress,
            dependencies: t.dependencies,
            custom_class: t.custom_class,
            _meta:        t,
        }));
        gantt = new Gantt("#gantt", tasks, {
            view_mode: viewMode,
            language: "es",
            bar_height: 22,
            padding: 16,
            on_click: (task) => mostrarDetalle(task._meta || task),
            on_date_change: PUEDE_EDITAR ? onMover : undefined,
            on_progress_change: PUEDE_EDITAR ? onAvance : undefined,
        });
        // Pintar tasks con colores por hito o crítica
        document.querySelectorAll('.gantt .bar-wrapper').forEach((el) => {
            const id = el.getAttribute('data-id');
            const meta = datos.tasks.find(t => String(t.id) === id);
            if (meta && meta.color) {
                el.querySelector('.bar')?.setAttribute('style', 'fill:' + meta.color);
                el.querySelector('.bar-progress')?.setAttribute('style', 'fill:' + sombrear(meta.color));
            }
        });
        marcarBotonZoomActivo();
    }

    function sombrear(hex) {
        // Devuelve un tono más oscuro del color (multiplicativo)
        if (!hex || hex[0] !== '#') return hex;
        const r = Math.max(0, parseInt(hex.slice(1,3),16) - 50);
        const g = Math.max(0, parseInt(hex.slice(3,5),16) - 50);
        const b = Math.max(0, parseInt(hex.slice(5,7),16) - 50);
        return '#' + [r,g,b].map(v => v.toString(16).padStart(2,'0')).join('');
    }

    function renderHitos(hitos) {
        const ul = document.getElementById('g-hitos');
        if (!hitos.length) {
            ul.innerHTML = '<li class="list-group-item text-muted text-center">Sin hitos.</li>';
            return;
        }
        ul.innerHTML = hitos.map(h => `
            <li class="list-group-item">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge" style="background:${h.color}; min-width: 14px; min-height: 14px;">&nbsp;</span>
                    <div class="flex-grow-1">
                        <code>${escape(h.codigo)}</code>
                        ${escape(h.nombre)}
                        ${h.fecha_objetivo ? `<br><small class="text-muted">obj. ${escape(h.fecha_objetivo)}</small>` : ''}
                    </div>
                    <span class="badge bg-success">${h.porcentaje.toFixed(0)}%</span>
                </div>
            </li>`).join('');
    }

    function mostrarDetalle(t) {
        const det = document.getElementById('g-detalle');
        const deps = (datos.dependencies || []).filter(d => d.actividad_id == t.id);
        const sucDe = (datos.dependencies || []).filter(d => d.predecesor_id == t.id);
        det.innerHTML = `
            <h6 class="mb-2">${escape(t.name)}</h6>
            <div class="row g-2 small">
                <div class="col-6"><strong>Inicio:</strong><br>${escape(t.start)}</div>
                <div class="col-6"><strong>Término:</strong><br>${escape(t.end)}</div>
                <div class="col-6"><strong>Días:</strong> ${t.duration}</div>
                <div class="col-6"><strong>Hito:</strong> ${escape(t.hito_codigo || '—')}</div>
                <div class="col-12"><strong>Responsable:</strong> ${escape(t.responsable || '—')}</div>
                <div class="col-12 mt-2">
                    <label class="form-label small mb-1"><strong>Avance:</strong> <span id="d-pct">${t.progress.toFixed(0)}</span>%</label>
                    <input type="range" min="0" max="100" step="1" class="form-range" id="d-slider"
                        ${PUEDE_EDITAR ? '' : 'disabled'} value="${t.progress}">
                </div>
                <div class="col-12 mt-2">
                    <strong>Predecesoras:</strong>
                    ${deps.length ? '<ul class="mb-0 ps-3">' + deps.map(d =>
                        `<li>${escape(d.tipo)} ← act#${d.predecesor_id} ${d.lag_dias ? '(lag ' + d.lag_dias + ')' : ''}
                            ${PUEDE_DEPS ? `<a href="#" data-del-dep="${d.id}" class="text-danger">×</a>` : ''}
                        </li>`).join('') + '</ul>' : '<span class="text-muted">— sin predecesoras —</span>'}
                </div>
                <div class="col-12 mt-1">
                    <strong>Sucesoras:</strong>
                    ${sucDe.length ? '<ul class="mb-0 ps-3">' + sucDe.map(d =>
                        `<li>${escape(d.tipo)} → act#${d.actividad_id}</li>`).join('') + '</ul>' : '<span class="text-muted">— sin sucesoras —</span>'}
                </div>
            </div>`;
        const slider = document.getElementById('d-slider');
        if (slider) {
            slider.addEventListener('change', async (e) => {
                const v = parseFloat(e.target.value);
                document.getElementById('d-pct').textContent = v.toFixed(0);
                try {
                    await postForm(URL_AVANCE(t.id), { porcentaje: v });
                    await cargar();
                } catch (err) { alert(err.message); }
            });
        }
        det.querySelectorAll('[data-del-dep]').forEach(a => {
            a.addEventListener('click', async (e) => {
                e.preventDefault();
                if (!confirm('¿Eliminar esta dependencia?')) return;
                try {
                    await postForm(URL_DEP_DEL(a.dataset.delDep), {});
                    await cargar();
                } catch (err) { alert(err.message); }
            });
        });
    }

    async function onMover(task, start, end) {
        const inicio = formatDate(start);
        const fin    = formatDate(end);
        const dur    = diasEntre(inicio, fin);
        try {
            await postForm(URL_MOVER2(task.id), {
                fecha_inicio:  inicio,
                duracion_dias: dur,
            });
            await cargar();
        } catch (e) {
            alert('No se pudo mover la actividad: ' + e.message);
            await cargar(); // revertir visual
        }
    }

    async function onAvance(task, progress) {
        try {
            await postForm(URL_AVANCE(task.id), { porcentaje: progress });
        } catch (e) {
            alert('No se pudo actualizar el avance: ' + e.message);
        }
    }

    async function postForm(url, data) {
        const fd = new FormData();
        fd.append(CSRF_NAME, csrfHash());
        Object.entries(data).forEach(([k,v]) => fd.append(k, v));
        return fetchJSON(url, { method:'POST', body: fd });
    }

    function poblarSelectorDependencias(tasks) {
        const optsHTML = tasks.map(t =>
            `<option value="${t.id}">${escape(t.name)}</option>`).join('');
        const suc = document.getElementById('dep-suc');
        const pred = document.getElementById('dep-pred');
        if (suc)  suc.innerHTML  = '<option value="">— seleccione —</option>' + optsHTML;
        if (pred) pred.innerHTML = '<option value="">— seleccione —</option>' + optsHTML;
    }

    // Toggle ruta crítica: alterna visibilidad/realce de barras críticas
    let criticaOn = false;
    const btnCrit = document.getElementById('btn-toggle-critica');
    if (btnCrit) {
        btnCrit.addEventListener('click', () => {
            criticaOn = !criticaOn;
            btnCrit.classList.toggle('active', criticaOn);
            document.querySelectorAll('.gantt .bar-wrapper').forEach((el) => {
                const id = el.getAttribute('data-id');
                const meta = datos.tasks.find(t => String(t.id) === id);
                if (!meta) return;
                if (criticaOn) {
                    if (meta.es_critica === 1) {
                        el.querySelector('.bar')?.setAttribute('style', 'fill:#dc3545');
                        el.querySelector('.bar-progress')?.setAttribute('style', 'fill:#9b2030');
                        el.style.opacity = '1';
                    } else {
                        el.style.opacity = '0.25';
                    }
                } else {
                    el.style.opacity = '1';
                    if (meta.color) {
                        el.querySelector('.bar')?.setAttribute('style', 'fill:' + meta.color);
                        el.querySelector('.bar-progress')?.setAttribute('style', 'fill:' + sombrear(meta.color));
                    }
                }
            });
        });
    }

    // Botón recalcular CPM
    const btnCpm = document.getElementById('btn-recalc-cpm');
    if (btnCpm) {
        btnCpm.addEventListener('click', async () => {
            btnCpm.disabled = true;
            try {
                const r = await postForm("<?= site_url('obras/gantt/recalcular-cpm/' . ($proyecto ? $proyecto['id'] : 0)) ?>", {});
                await cargar();
                alert("CPM recalculado: " + r.criticas + " actividades críticas. Duración total: " + r.duracion_proyecto + " días laborales.");
            } catch (e) { alert("Error: " + e.message); }
            btnCpm.disabled = false;
        });
    }

    // Export PNG (cliente)
    const btnPng = document.getElementById('btn-export-png');
    if (btnPng) {
        btnPng.addEventListener('click', async () => {
            if (!window.html2canvas) {
                await new Promise((res, rej) => {
                    const s = document.createElement('script');
                    s.src = 'https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js';
                    s.onload = res; s.onerror = rej;
                    document.head.appendChild(s);
                });
            }
            const node = document.querySelector('.gantt-container');
            const canvas = await html2canvas(node, { backgroundColor: '#ffffff', scale: 2 });
            const link = document.createElement('a');
            link.download = "gantt-<?= $proyecto ? $proyecto['codigo'] : 'proyecto' ?>-" + (new Date().toISOString().slice(0,10)) + ".png";
            link.href = canvas.toDataURL('image/png');
            link.click();
        });
    }

    // Botones de zoom
    document.querySelectorAll('[data-vm]').forEach(btn => {
        btn.addEventListener('click', () => {
            viewMode = btn.dataset.vm;
            localStorage.setItem(STORAGE_KEY, viewMode);
            if (gantt) gantt.change_view_mode(viewMode);
            marcarBotonZoomActivo();
        });
    });
    function marcarBotonZoomActivo() {
        document.querySelectorAll('[data-vm]').forEach(b => {
            b.classList.toggle('active', b.dataset.vm === viewMode);
        });
    }

    // Modal nueva dependencia
    const btnNuevaDep = document.getElementById('btn-nueva-dep');
    if (btnNuevaDep) {
        btnNuevaDep.addEventListener('click', () => {
            const m = new bootstrap.Modal(document.getElementById('modal-dep'));
            document.getElementById('dep-err').classList.add('d-none');
            document.getElementById('form-dep').reset();
            m.show();
        });
        document.getElementById('form-dep').addEventListener('submit', async (e) => {
            e.preventDefault();
            const fd = new FormData(e.target);
            const data = Object.fromEntries(fd.entries());
            try {
                await postForm(URL_DEP_CREAR, data);
                bootstrap.Modal.getInstance(document.getElementById('modal-dep')).hide();
                await cargar();
            } catch (err) {
                const box = document.getElementById('dep-err');
                box.textContent = err.message;
                box.classList.remove('d-none');
            }
        });
    }

    function escape(s) {
        if (s === null || s === undefined) return '';
        return String(s).replace(/[&<>"']/g, c => ({
            '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'
        }[c]));
    }
    function formatDate(d) {
        if (typeof d === 'string') return d.slice(0,10);
        const yyyy = d.getFullYear();
        const mm = String(d.getMonth()+1).padStart(2,'0');
        const dd = String(d.getDate()).padStart(2,'0');
        return `${yyyy}-${mm}-${dd}`;
    }
    function diasEntre(d1, d2) {
        const a = new Date(d1), b = new Date(d2);
        return Math.max(1, Math.round((b - a) / 86400000) + 1);
    }

    cargar();
})();
</script>
<?php endif; ?>
