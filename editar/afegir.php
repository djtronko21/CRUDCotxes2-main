<?php
session_start();
require_once __DIR__ . '/../BD/connexio.php';

if (!isset($_SESSION['usuari_id'])) {
    header("Location: ../LoginRegistre/login.php");
    exit();
}

$idUsuari = $_SESSION['usuari_id'];
$error = null;
$success = null;

// Obtenir rutes
$rutes = $conn->query("SELECT id, origen, desti FROM rutes ORDER BY origen");

// Obtenir cotxes de l'usuari
$cotxes = $conn->prepare("SELECT id, marca, model, matricula FROM cotxes WHERE usuari_id = ? ORDER BY marca");
$cotxes->bind_param("i", $idUsuari);
$cotxes->execute();
$cotxesResult = $cotxes->get_result();

// Procesar formulari
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $rutaId = intval($_POST['ruta_id'] ?? 0);
    $cotxeId = intval($_POST['cotxe_id'] ?? 0);
    $data = $_POST['data'] ?? '';
    $horaInici = $_POST['hora_inici'] ?? '';
    $horaFi = $_POST['hora_fi'] ?? '';
    $places = intval($_POST['places'] ?? 0);
    $preu = floatval($_POST['preu'] ?? 0);
    $comentaris = trim($_POST['comentaris'] ?? '');

    // Validacions
    if ($rutaId <= 0 || $cotxeId <= 0 || empty($data) || empty($horaInici) || $places <= 0 || $preu < 0) {
        $error = "Completa tots els camps obligatoris correctament.";
    } elseif (strtotime($data) < strtotime(date('Y-m-d'))) {
        $error = "La data no pot ser anterior a avui.";
    } elseif ($places > 8) {
        $error = "El cotxe no pot tenir més de 8 places.";
    } elseif (!empty($horaFi) && $horaFi <= $horaInici) {
        $error = "L'hora de fi ha de ser posterior a l'hora d'inici.";
    } else {
        $stmt = $conn->prepare(
            "INSERT INTO horaris (usuari_id, ruta_id, cotxe_id, data, hora_inici, hora_fi, places_totals, preu, comentaris) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param("iiisssids", $idUsuari, $rutaId, $cotxeId, $data, $horaInici, $horaFi, $places, $preu, $comentaris);
        
        if ($stmt->execute()) {
            $success = "Horari afegit correctament!";
            // Redirigir després de 2 segons
            header("Refresh: 2; url=editar.php");
        } else {
            $error = "Error al afegir l'horari: " . $conn->error;
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="ca">
<head>
    <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-3838550041681016"
     crossorigin="anonymous"></script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Afegir Horari — BlaBlaCash</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <style>
        :root { --primary: #4CAF50; --bg: #e0ffff; }
        body { background: var(--bg); font-family: 'Segoe UI', system-ui; }
        .navbar { background: #333; }
        .form-section { background: #fff; padding: 2rem; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
        .form-title { color: #333; font-weight: 700; margin-bottom: 1.5rem; }
        .form-group label { font-weight: 600; color: #555; margin-bottom: 0.5rem; }
        .form-control:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(76, 175, 80, 0.1); }
        .btn-submit { background: var(--primary); color: #fff; font-weight: 600; }
        .btn-submit:hover { background: #45a049; }
        .error { color: #dc3545; background: #fff5f5; border-left: 4px solid #dc3545; padding: 1rem; border-radius: 6px; margin-bottom: 1.5rem; }
        .success { color: #155724; background: #d4edda; border-left: 4px solid #28a745; padding: 1rem; border-radius: 6px; margin-bottom: 1.5rem; }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="../Menu/principal.php"><i class="fas fa-car-side me-2"></i>BlaBlaCash</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse justify-content-end" id="navMenu">
            <ul class="navbar-nav">
                <li class="nav-item"><a class="nav-link" href="../Menu/principal.php"><i class="fas fa-home me-1"></i>Inici</a></li>
                <li class="nav-item"><a class="nav-link" href="../Menu/index.php"><i class="fas fa-search me-1"></i>Viatges</a></li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle text-white" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user-circle me-1"></i><?php echo htmlspecialchars($_SESSION['nom']); ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item active" href="afegir.php"><i class="fas fa-plus me-2"></i>Afegir horari</a></li>
                        <li><a class="dropdown-item" href="editar.php"><i class="fas fa-edit me-2"></i>Editar horaris</a></li>
                        <li><a class="dropdown-item" href="../Perfil/perfil.php"><i class="fas fa-user me-2"></i>Perfil</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="../LoginRegistre/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Tancar sessió</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="container my-4" style="max-width: 600px;">
    <div class="form-section">
        <h2 class="form-title"><i class="fas fa-calendar-plus me-2"></i>Afegir Nou Horari</h2>

        <?php if ($error): ?>
            <div class="error"><i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="success"><i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <form method="post" novalidate>
            <div class="form-group mb-3">
                <label for="ruta_id"><i class="fas fa-route me-1"></i>Ruta</label>
                <select class="form-select" name="ruta_id" id="ruta_id" required>
                    <option value="">Selecciona una ruta</option>
                    <?php while ($ruta = $rutes->fetch_assoc()): ?>
                        <option value="<?php echo $ruta['id']; ?>">
                            <?php echo htmlspecialchars($ruta['origen'] . " → " . $ruta['desti']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="form-group mb-3">
                <label for="cotxe_id"><i class="fas fa-car me-1"></i>Cotxe</label>
                <select class="form-select" name="cotxe_id" id="cotxe_id" required>
                    <option value="">Selecciona un cotxe</option>
                    <?php while ($cotxe = $cotxesResult->fetch_assoc()): ?>
                        <option value="<?php echo $cotxe['id']; ?>">
                            <?php echo htmlspecialchars($cotxe['marca'] . " " . $cotxe['model'] . " - " . $cotxe['matricula']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group mb-3">
                        <label for="data"><i class="far fa-calendar me-1"></i>Data</label>
                        <input type="date" class="form-control" name="data" id="data" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group mb-3">
                        <label for="hora_inici"><i class="far fa-clock me-1"></i>Hora inici</label>
                        <input type="time" class="form-control" name="hora_inici" id="hora_inici" required>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group mb-3">
                        <label for="hora_fi"><i class="far fa-clock me-1"></i>Hora fi (opcional)</label>
                        <input type="time" class="form-control" name="hora_fi" id="hora_fi">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group mb-3">
                        <label for="places"><i class="fas fa-chair me-1"></i>Places disponibles</label>
                        <input type="number" class="form-control" name="places" id="places" min="1" max="8" required>
                    </div>
                </div>
            </div>

            <div class="form-group mb-3">
                <label for="preu"><i class="fas fa-euro-sign me-1"></i>Preu per persona (€)</label>
                <input type="number" step="0.01" min="0" class="form-control" name="preu" id="preu" required>
            </div>

            <div class="form-group mb-3">
                <label for="comentaris"><i class="fas fa-comment me-1"></i>Comentaris (opcional)</label>
                <textarea class="form-control" name="comentaris" id="comentaris" rows="3" placeholder="Afegeix detalls del viatge..."></textarea>
            </div>

            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-submit"><i class="fas fa-save me-2"></i>Afegir Horari</button>
                <a href="editar.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-2"></i>Tornar</a>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>