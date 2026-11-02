
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
| CONSULTAR LIBRO O SUBLIBRO
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

/*
|--------------------------------------------------------------------------
| IDENTIFICAR SI ES SUBLIBRO
|--------------------------------------------------------------------------
*/

$parentId = (int) ($book['parent_book_id'] ?? 0);
$isSubbook = $parentId > 0;

$parentBook = null;

if ($isSubbook) {
    $s = $db->prepare("
        SELECT
            id,
            book_number,
            sacrament,
            year_start,
            year_end
        FROM books
        WHERE id = ?
          AND church_id = ?
          AND parent_book_id IS NULL
        LIMIT 1
    ");

    $s->execute([$parentId, $cid]);

    $parentBook = $s->fetch(PDO::FETCH_ASSOC);
}

/*
|--------------------------------------------------------------------------
| CONSULTAR SUBLIBROS
|--------------------------------------------------------------------------
*/

$s = $db->prepare("
    SELECT
        b.id,
        b.division,
        b.book_number,
        (
            SELECT COUNT(*)
            FROM records r
            WHERE r.book_id = b.id
              AND r.church_id = b.church_id
              AND r.deleted_at IS NULL
        ) AS total_actas
    FROM books b
    WHERE b.parent_book_id = ?
      AND b.church_id = ?
    ORDER BY b.division ASC, b.id ASC
");

$s->execute([$id, $cid]);

$childBooks = $s->fetchAll(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| CONSULTAR ACTAS
|--------------------------------------------------------------------------
| Libro principal: muestra sus actas y las de sus sublibros.
| Sublibro: muestra únicamente sus propias actas.
|--------------------------------------------------------------------------
*/

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

      AND (
          r.book_id = ?

          OR r.book_id IN (
              SELECT sb.id
              FROM books sb
              WHERE sb.parent_book_id = ?
                AND sb.church_id = ?
          )
      )

    ORDER BY
        r.sacrament_date DESC,
        r.id DESC
");

$s->execute([
    $cid,
    $id,
    $id,
    $cid
]);

$records = $s->fetchAll(PDO::FETCH_ASSOC);

$totalRecords = count($records);

/*
|--------------------------------------------------------------------------
| MOSTRAR PÁGINA
|--------------------------------------------------------------------------
*/

require 'layout.php';

top($isSubbook ? 'Detalle del sublibro' : 'Detalle del libro');
?>

<div class="page-head">

    <div>
        <h1>
            <?= $isSubbook
                ? 'Información del sublibro'
                : 'Información del libro'
            ?>
        </h1>

        <p class="muted">
            Consulta la información y las actas registradas.
        </p>
    </div>

    <div class="actions">

        <?php if ($isSubbook && $parentBook): ?>

            <a
                class="btn secondary"
                href="ver_libro.php?id=<?= (int) $parentBook['id'] ?>"
            >
                ← Volver al libro principal
            </a>

        <?php endif; ?>

        <a
            class="btn secondary"
            href="agregar.php?edit=<?= $id ?>"
        >
            Editar <?= $isSubbook ? 'sublibro' : 'libro' ?>
        </a>

        <a
            class="btn secondary"
            href="books.php"
        >
            Volver a libros
        </a>

    </div>

</div>

<?php if ($isSubbook && $parentBook): ?>

    <section class="card">

        <p class="muted small">
            Libro principal al que pertenece
        </p>

        <h2>
            <?= e($parentBook['book_number']) ?>
        </h2>

        <p class="muted">
            Periodo:
            <?= e($parentBook['year_start']) ?>
            –
            <?= e($parentBook['year_end']) ?>
        </p>

        <a
            class="btn secondary"
            href="ver_libro.php?id=<?= (int) $parentBook['id'] ?>"
        >
            Ver libro principal
        </a>

    </section>

<?php endif; ?>

<!-- INFORMACIÓN GENERAL -->

<section class="card">

    <h2>
        <?= e($book['book_number']) ?>

        <?php if (!empty($book['division'])): ?>
            · Sublibro <?= e($book['division']) ?>
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
                    <?= $isSubbook
                        ? 'Sublibro'
                        : 'Libro principal'
                    ?>
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

<!-- SUBLIBROS DEL LIBRO PRINCIPAL -->

<?php if (!$isSubbook): ?>

    <section class="card">

        <div class="page-head">

            <div>
                <h2>
                    Sublibros registrados
                    (<?= count($childBooks) ?>)
                </h2>

                <p class="muted">
                    Divisiones pertenecientes a este libro.
                </p>
            </div>

            <a
                class="btn secondary"
                href="agregar.php?tipo=sublibro&parent=<?= $id ?>"
            >
                + Agregar sublibro
            </a>

        </div>

        <?php if ($childBooks): ?>

            <div class="table-scroll">

                <table>

                    <thead>
                        <tr>
                            <th>Sublibro</th>
                            <th>Actas</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php foreach ($childBooks as $sub): ?>

                            <tr>

                                <td>
                                    <strong>
                                        <?= e($sub['division']) ?>
                                    </strong>
                                </td>

                                <td>
                                    <?= (int) $sub['total_actas'] ?>
                                </td>

                                <td>

                                    <div class="actions">

                                        <a
                                            class="btn secondary"
                                            href="ver_libro.php?id=<?= (int) $sub['id'] ?>"
                                        >
                                            Ver sublibro
                                        </a>

                                        <a
                                            class="btn secondary"
                                            href="agregar.php?tipo=sublibro&edit=<?= (int) $sub['id'] ?>"
                                        >
                                            Editar
                                        </a>

                                    </div>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        <?php else: ?>

            <p class="muted">
                Este libro no tiene sublibros registrados.
            </p>

        <?php endif; ?>

    </section>

<?php endif; ?>

<!-- ACTAS REGISTRADAS -->

<section class="card">

    <h2>
        Actas de este
        <?= $isSubbook ? 'sublibro' : 'libro' ?>
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
                    <th>Sublibro</th>
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
                            <?= e(
                                $r['division']
                                ?: 'Sin división'
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
                        <td colspan="6">
                            Todavía no hay actas registradas.
                        </td>
                    </tr>

                <?php endif; ?>

            </tbody>

        </table>

    </div>

</section>

<?php bottom(); ?>
