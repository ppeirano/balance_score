<?php
require_once __DIR__ . '/../../models/Reunion.php';
require_once __DIR__ . '/../../models/Iniciativa.php';
require_once __DIR__ . '/../../models/Responsable.php';

$reunion = null;
if ($id) {
    $reunion = Reunion::getById($pdo, $id);
}
$iniciativas = Iniciativa::getAll($pdo);
$responsables = Responsable::getAll($pdo);

require_once __DIR__ . '/../layout/header.php';
$esEditar = ($reunion !== null);
?>

<h4 class="mb-4">
    <i class="bi bi-people me-2"></i><?= $esEditar ? 'Editar' : 'Nueva' ?> Reunión
</h4>

<div class="card">
    <div class="card-body">
        <form method="POST" action="<?= BASE_URL ?>index.php?page=reuniones&action=guardar">
            <?php if ($esEditar): ?>
                <input type="hidden" name="id" value="<?= $reunion['id'] ?>">
            <?php endif; ?>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Título *</label>
                    <input type="text" class="form-control" name="titulo" value="<?= sanitize($reunion['titulo'] ?? '') ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Fecha *</label>
                    <input type="datetime-local" class="form-control" name="fecha" value="<?= $reunion ? date('Y-m-d\TH:i', strtotime($reunion['fecha'])) : date('Y-m-d\TH:i') ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">IE Vinculada</label>
                    <select class="form-select" name="iniciativa_id">
                        <option value="">General</option>
                        <?php foreach ($iniciativas as $ie): ?>
                            <option value="<?= $ie['id'] ?>" <?= ($reunion && $reunion['iniciativa_id'] == $ie['id']) ? 'selected' : '' ?>>
                                <?= sanitize($ie['codigo'] . ' - ' . $ie['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Participantes</label>
                <input type="text" class="form-control" name="participantes" value="<?= sanitize($reunion['participantes'] ?? '') ?>" placeholder="Nombres separados por coma">
            </div>

            <!-- Generador de Agenda con IA -->
            <div class="card bg-light mb-3">
                <div class="card-body py-2">
                    <div class="row align-items-end">
                        <div class="col-md-4">
                            <label class="form-label small text-muted mb-1"><i class="bi bi-stars me-1"></i>Generar Agenda con IA</label>
                            <select class="form-select form-select-sm" id="agendaResponsable">
                                <option value="">Seleccionar responsable...</option>
                                <?php foreach ($responsables as $resp): ?>
                                    <option value="<?= sanitize($resp['nombre']) ?>"><?= sanitize($resp['nombre']) ?> — <?= sanitize($resp['cargo'] ?? '') ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button type="button" class="btn btn-sm btn-outline-primary" id="btnGenerarAgenda" onclick="generarAgenda()">
                                <i class="bi bi-stars me-1"></i>Generar Agenda
                            </button>
                            <span id="agendaSpinner" class="ms-2 d-none">
                                <span class="spinner-border spinner-border-sm text-primary" role="status"></span>
                                <small class="text-muted">Analizando...</small>
                            </span>
                        </div>
                        <div class="col-md-5">
                            <small class="text-muted">Analiza planes, actividades, compromisos y riesgos del responsable para armar la agenda.</small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Minuta</label>
                <textarea class="form-control" name="minuta" id="minutaTextarea" rows="8" placeholder="Contenido de la minuta: notas, decisiones, temas tratados..."><?= sanitize($reunion['minuta'] ?? '') ?></textarea>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Guardar</button>
                <a href="<?= BASE_URL ?>index.php?page=reuniones" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<script>
function generarAgenda() {
    const resp = document.getElementById('agendaResponsable').value;
    if (!resp) { alert('Seleccione un responsable primero.'); return; }

    const btn = document.getElementById('btnGenerarAgenda');
    const spinner = document.getElementById('agendaSpinner');
    const textarea = document.getElementById('minutaTextarea');

    btn.disabled = true;
    spinner.classList.remove('d-none');

    fetch('<?= BASE_URL ?>index.php?page=reuniones&action=generar_agenda', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'responsable=' + encodeURIComponent(resp)
    })
    .then(r => r.json())
    .then(data => {
        btn.disabled = false;
        spinner.classList.add('d-none');
        if (data.error) {
            alert(data.error);
            return;
        }
        if (textarea.value.trim()) {
            textarea.value += '\n\n--- AGENDA GENERADA POR IA ---\n\n' + data.agenda;
        } else {
            textarea.value = data.agenda;
        }
        textarea.style.height = 'auto';
        textarea.style.height = textarea.scrollHeight + 'px';
    })
    .catch(err => {
        btn.disabled = false;
        spinner.classList.add('d-none');
        alert('Error de conexión. Intente nuevamente.');
    });
}
</script>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
