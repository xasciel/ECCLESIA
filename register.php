
<?php
require 'bootstrap.php';

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    if (strlen($_POST['password'] ?? '') < 12) {
        $msg = 'La contraseña debe tener al menos 12 caracteres.';
    } else {
        try {
            $db->beginTransaction();

            $s = $db->prepare(
                'INSERT INTO churches(name,email,address)
                 VALUES(?,?,?)'
            );

            $s->execute([
                trim($_POST['church']),
                trim($_POST['email']),
                trim($_POST['address'])
            ]);

            $id = $db->lastInsertId();

            $s = $db->prepare(
                'INSERT INTO users
                (church_id,name,email,password_hash,role)
                VALUES(?,?,?,?,"church_admin")'
            );

            $s->execute([
                $id,
                trim($_POST['name']),
                trim($_POST['email']),
                password_hash(
                    $_POST['password'],
                    PASSWORD_DEFAULT
                )
            ]);

            $db->commit();

            $msg = 'Solicitud enviada. Espera la aprobación del proveedor.';

        } catch (Throwable $ex) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            $msg = 'No fue posible registrar la solicitud. Verifica que el correo no esté registrado.';
        }
    }
}
?>

<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport"
          content="width=device-width,initial-scale=1">

    <title>Registrar iglesia | Ecclesia</title>

    <link rel="stylesheet" href="assets/style.css">

    <style>
        .acciones-registro {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 12px;
            margin-top: 20px;
        }

        .acciones-registro button {
            margin: 0;
        }

        .btn-regresar {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 12px 20px;
            border: 1px solid #682b36;
            border-radius: 10px;
            color: #682b36;
            background: transparent;
            font-size: 15px;
            font-weight: 500;
            text-decoration: none;
            transition: 0.2s ease;
        }

        .btn-regresar:hover {
            background: #682b36;
            color: #fff;
            text-decoration: none;
        }

        @media (max-width: 480px) {
            .acciones-registro {
                flex-direction: column;
                align-items: stretch;
            }

            .acciones-registro button,
            .btn-regresar {
                width: 100%;
                text-align: center;
            }
        }
    </style>
</head>

<body>

<header>
    <div class="brand">✝ ECCLESIA</div>
    <a href="index.php">Iniciar sesión</a>
</header>

<main>
    <section class="card formbox">

        <h1>Solicitar acceso</h1>

        <?php if ($msg): ?>
            <p class="notice">
                <?= e($msg) ?>
            </p>
        <?php endif; ?>

        <form method="post">

            <input
                type="hidden"
                name="csrf"
                value="<?= csrf() ?>"
            >

            <label>Nombre de la iglesia</label>
            <input
                name="church"
                required
                maxlength="180"
            >

            <label>Dirección</label>
            <input
                name="address"
                maxlength="255"
            >

            <label>Nombre del responsable</label>
            <input
                name="name"
                required
            >

            <label>Correo</label>
            <input
                type="email"
                name="email"
                required
            >

            <label>Contraseña (mínimo 12 caracteres)</label>
            <input
                type="password"
                name="password"
                required
                minlength="12"
            >

            <div class="acciones-registro">

                <button type="submit">
                    Enviar solicitud
                </button>

                <a href="index.php" class="btn-regresar">
                    ← Regresar
                </a>

            </div>

        </form>

    </section>
</main>

</body>
</html>
