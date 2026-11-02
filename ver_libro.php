
<?php
require 'bootstrap.php';
require_church();

$cid = (int) me()['church_id'];

$id = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

if (!$id || $id <= 0) {
    http_response_code(400);
    exit('Identificador de libro inválido.');
}

/*
|--------------------------------------------------------------------------
| CONSULTAR LIBRO
|--------------------------------------------------------------------------
*/

$s = $db->prepare("
    SELECT
        b.*,
        rt.name AS type_name
    FROM books b
    LEFT JOIN record_types rt
        ON rt.church_id = b.church_id
        AND rt.slug = b.sacrament
    WHERE b.id = ?
      AND b.church_id = ?
    LIMIT 1
");

$s->execute([$id, $cid]);

$book = $s->fetch(PDO::FETCH_ASSOC);

if (!$book) {
    http_response_code(404);
    exit('No se encontró el libro en tu parroquia.');
}

$s = $db->prepare("
    SELECT
        r.id,
        r.record_number,
        r.folio,
        r.sacrament_date,
        p.first_names,
        p.last_names,
        b.book_number,
        b.division
    FROM records r

    INNER JOIN people p
        ON p.id = r.person_id
       AND p.church_id = r.church_id

    INNER JOIN books b
        ON b.id = r.book_id
       AND b.church_id = r.church_id

    WHERE r.church_id = ?
      AND r.deleted_at IS NULL

      AND r.book_id = ?

    ORDER BY
        r.sacrament_date DESC,
        r.id DESC
");

$s->execute([$cid, $id]);

$records = $s->fetchAll(PDO::FETCH_ASSOC);

$totalRecords = count($records);

/*
|--------------------------------------------------------------------------
| MOSTRAR PÁGINA
|--------------------------------------------------------------------------
*/

require 'layout.php';

top('Detalle del libro');
?>

<div class="page-head">

    <div>
        <h1>
            Información del libro
        </h1>

        <p class="muted">
            Consulta la información y las actas registradas.
        </p>
    </div>

    <div class="actions">

        <a
            class="btn secondary"
            href="agregar.php?edit=<?= $id ?>"
        >
            Editar libro
        </a>

        <a
            class="btn secondary"
            href="books.php"
        >
            Volver a libros
        </a>

    </div>

</div>

<!-- INFORMACIÓN GENERAL -->

<section class="card">

    <h2>
        <?= e($book['book_number']) ?>

        <?php if (!empty($book['division'])): ?>
            · <?= e($book['division']) ?>
        <?php endif; ?>
    </h2>

    <div class="field-grid">

        <div>
            <p class="muted small">
                Tipo de acta
            </p>

            <p>
                <strong>
                    <?= e(
                        $book['type_name']
                        ?: ucfirst($book['sacrament'])
                    ) ?>
                </strong>
            </p>
        </div>

        <div>
            <p class="muted small">
                Periodo
            </p>

            <p>
                <strong>
                    <?= e($book['year_start']) ?>
                    –
                    <?= e($book['year_end']) ?>
                </strong>
            </p>
        </div>

        <div>
            <p class="muted small">
                Actas registradas
            </p>

            <p>
                <strong>
                    <?= $totalRecords ?>
                </strong>
            </p>
        </div>

        <div>
            <p class="muted small">
                Clasificación
            </p>

            <p>
                <strong>
                    Libro
                </strong>
            </p>
        </div>

    </div>

    <h3>Observaciones</h3>

    <p>
        <?= nl2br(
            e(
                $book['notes']
                ?: 'Sin observaciones registradas.'
            )
        ) ?>
    </p>

</section>

<!-- ACTAS REGISTRADAS -->

<section class="card">

    <h2>
        Actas de este
        libro
        (<?= $totalRecords ?>)
    </h2>

    <div class="table-scroll">

        <table>

            <thead>
                <tr>
                    <th>Número</th>
                    <th>Folio</th>
                    <th>Persona</th>
                    <th>Fecha</th>
                    <th>Acciones</th>
                </tr>
            </thead>

            <tbody>

                <?php foreach ($records as $r): ?>

                    <tr>

                        <td>
                            <?= e($r['record_number']) ?>
                        </td>

                        <td>
                            <?= e($r['folio']) ?>
                        </td>

                        <td>
                            <strong>
                                <?= e(
                                    $r['first_names'] . ' ' .
                                    $r['last_names']
                                ) ?>
                            </strong>
                        </td>

                        <td>
                            <?= e(
                                $r['sacrament_date']
                                ?: 'Sin fecha'
                            ) ?>
                        </td>



                        <td>

                            <div class="actions">

                                <a
                                    class="btn secondary"
                                    href="imprimir_acta.php?id=<?= (int) $r['id'] ?>"
                                >
                                    Imprimir
                                </a>

                                <a
                                    class="btn secondary"
                                    href="records.php?edit=<?= (int) $r['id'] ?>"
                                >
                                    Ver / editar
                                </a>

                            </div>

                        </td>

                    </tr>

                <?php endforeach; ?>

                <?php if (!$records): ?>

                    <tr>
                        <td colspan="5">
                            Todavía no hay actas registradas.
                        </td>
                    </tr>

                <?php endif; ?>

            </tbody>

        </table>

    </div>

</section>

<?php bottom(); ?>
