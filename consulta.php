<?php
require 'bootstrap.php';
require_church();
$cid = (int)me()['church_id'];
$personId = filter_input(INPUT_GET, 'persona', FILTER_VALIDATE_INT);
if (!$personId || $personId < 1) {
    header('Location: dashboard.php');
    exit;
}

$stmt = $db->prepare('SELECT id, first_names, last_names, birth_date, father_name, mother_name FROM people WHERE id = ? AND church_id = ? LIMIT 1');
$stmt->execute([$personId, $cid]);
$person = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$person) {
    http_response_code(404);
    require 'layout.php';
    top('Expediente no encontrado');
    ?>
    <section class="card"><h1>Expediente no encontrado</h1><p class="muted">Este expediente no existe o no pertenece a tu parroquia.</p><a class="btn secondary" href="dashboard.php">Volver a Inicio</a></section>
    <?php
    bottom();
    exit;
}

$stmt = $db->prepare('SELECT r.id, r.record_number, r.folio, r.sacrament_date, b.book_number, b.division, b.sacrament
    FROM records r
    INNER JOIN books b ON b.id = r.book_id AND b.church_id = r.church_id
    WHERE r.church_id = ? AND r.person_id = ? AND r.deleted_at IS NULL
    ORDER BY r.sacrament_date ASC, r.id ASC');
$stmt->execute([$cid, $personId]);
$acts = $stmt->fetchAll(PDO::FETCH_ASSOC);

function expediente_fecha($value) {
    if (!$value || $value === '0000-00-00') return 'Sin dato';
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    return $date ? $date->format('d/m/Y') : $value;
}

require 'layout.php';
top('Actas del expediente');
?>
<div class="page-head">
    <div>
        <h1>Actas del expediente #<?=(int)$person['id']?></h1>
        <p class="muted">Registros sacramentales vinculados a esta persona.</p>
    </div>
    <a class="btn secondary" href="dashboard.php">Volver a Inicio</a>
</div>
<section class="card">
    <h2><?=e(trim($person['first_names'].' '.$person['last_names']))?></h2>
    <p><strong>Padre:</strong> <?=e($person['father_name'] ?: 'Sin dato')?></p>
    <p><strong>Madre:</strong> <?=e($person['mother_name'] ?: 'Sin dato')?></p>
    <p><strong>Fecha de nacimiento:</strong> <?=e(expediente_fecha($person['birth_date']))?></p>
</section>
<section class="card">
    <h2>Actas registradas (<?=count($acts)?>)</h2>
    <div class="table-scroll">
        <table>
            <thead><tr><th>Tipo de acta</th><th>Libro / Sublibro</th><th>Fecha</th><th>Número</th><th>Folio</th><th>Acciones</th></tr></thead>
            <tbody>
            <?php foreach ($acts as $act): ?>
                <tr>
                    <td><?=e(ucfirst($act['sacrament']))?></td>
                    <td><?=e(trim($act['book_number'].' '.($act['division'] ?? '')))?></td>
                    <td><?=e(expediente_fecha($act['sacrament_date']))?></td>
                    <td><?=e($act['record_number'] ?: '—')?></td>
                    <td><?=e($act['folio'] ?: '—')?></td>
                    <td><div class="actions">
                        <a class="btn secondary" href="imprimir_acta.php?id=<?=(int)$act['id']?>">Imprimir</a>
                        <a class="btn secondary" href="records.php?edit=<?=(int)$act['id']?>">Editar</a>
                    </div></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$acts): ?><tr><td colspan="6">Esta persona todavía no tiene actas registradas.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php bottom();
