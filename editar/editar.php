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

// Eliminar horari
if (isset($_GET['delete']) && isset($_GET['confirm']) && $_GET['confirm'] === 'yes') {
    $horariId = intval($_GET['delete']);
    $delete = $conn->prepare("DELETE FROM horaris WHERE id = ? AND usuari_id = ?");
    $delete->bind_param("ii", $horariId, $idUsuari);
    
    if ($delete->execute()) {
        $success = "Horari eliminat correctament!";
        header("Refresh: 1; url=editar.php");
    } else {
        $error = "Error al eliminar l'horari.";
    }
}

// Obtenir horaris de l'usuari
$horaris = $conn->prepare(
    "SELECT 
        h.id,
        h.data,
        h.hora_inici,
        h.hora_fi,
        h.places_totals,
        h.preu,
        h.comentaris,
        r.origen,
        r.desti,
        c.marca,
        c.model,
        COALESCE((SELECT COUNT(*) FROM places_reservades WHERE horari_id = h.id), 0) AS places_ocupades
    FROM horaris h
    INNER JOIN rutes r ON h.ruta_id = r.id
    LEFT JOIN cotxes c ON h.cotxe_id = c.id
    WHERE h.usuari_id = ?
    ORDER BY h.data DESC, h.hora_inici ASC"
);
$horaris->bind_param("i", $idUsuari);
$horaris->execute();
$horarisResult = $horaris->get_result();
?>
<!DOCTYPE html>
<html lang="ca">
<head>
    <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-3838550041681016"
     crossorigin="anonymous"></script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Els meus Horaris — BlaBlaCash</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <style>
        :root { --primary: #4CAF50; --bg: #e0ffff; }
        body { background: var(--bg); font-family: 'Segoe UI', system-ui; }
        .navbar { background: #333; }
        .header-card { background: linear-gradient(135deg, #4CAF50, #45a049); color: #fff; padding: 2rem; border-radius: 12px; margin-bottom: 2rem; }
        .horari-card {
            background: #fff;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            margin-bottom: 1rem;
            transition: all 0.3s ease;
            border-left: 4px solid var(--primary);
        }
        .horari-card:hover { box-shadow: 0 8px 20px rgba(0,0,0,0.12); }
        .horari-route { font-size: 1.2rem; font-weight: 700; color: #333; margin-bottom: 1rem; }
        .horari-info { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin: 1rem 0; }
        .info-item { display: flex; align-items: center; gap: 0.5rem; color: #666; font-size: 0.95rem; }
        .horari-actions { display: flex; gap: 0.5rem; margin-top: 1rem; }
        .btn-edit { background: #0dcaf0; border: none; }
        .btn-delete { background: #dc3545; border: none; }
        .empty-state { text-align: center; padding: 3rem; }
        .success { color: #155724; background: #d4edda; border-left: 4px solid #28a745; padding: 1rem; border-radius: 6px; margin-bottom: 1.5rem; }
        .error { color: #dc3545; background: #fff5f5; border-left: 4px solid #dc3545; padding: 1rem; border-radius: 6px; margin-bottom: 1.5rem; }
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
                        <li><a class="dropdown-item" href="afegir.php"><i class="fas fa-plus me-2"></i>Afegir horari</a></li>
                        <li><a class="dropdown-item active" href="editar.php"><i class="fas fa-edit me-2"></i>Editar horaris</a></li>
                        <li><a class="dropdown-item" href="../Perfil/perfil.php"><i class="fas fa-user me-2"></i>Perfil</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="../LoginRegistre/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Tancar sessió</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="container my-4">
    <div class="header-card">
        <h1 class="mb-1"><i class="fas fa-calendar-check me-2"></i>Els meus Horaris</h1>
        <p class="mb-0">Gestiona els teus horaris de viatges</p>
    </div>

    <?php if ($error): ?>
        <div class="error"><i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="success"><i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <?php if ($horarisResult->num_rows > 0): ?>
        <div class="row">
            <?php while ($h = $horarisResult->fetch_assoc()):
                $placesDisponibles = $h['places_totals'] - $h['places_ocupades'];
                $esPassat = strtotime($h['data']) < strtotime(date('Y-m-d'));
            ?>
                <div class="col-md-6">
                    <div class="horari-card <?php echo $esPassat ? 'opacity-50' : ''; ?>">
                        <div class="horari-route">
                            <i class="fas fa-arrow-right" style="color: var(--primary);"></i>
                            <?php echo htmlspecialchars($h['origen'] . " → " . $h['desti']); ?>
                        </div>

                        <div class="horari-info">
                            <div class="info-item">
                                <i class="far fa-calendar"></i>
                                <span><?php echo date('d/m/Y', strtotime($h['data'])); ?></span>
                            </div>
                            <div class="info-item">
                                <i class="far fa-clock"></i>
                                <span><?php echo substr($h['hora_inici'], 0, 5); ?></span>
                            </div>
                            <div class="info-item">
                                <i class="fas fa-euro-sign"></i>
                                <span><?php echo number_format($h['preu'], 2); ?> €</span>
                            </div>
                            <div class="info-item">
                                <i class="fas fa-chair"></i>
                                <span><?php echo $placesDisponibles . "/" . $h['places_totals']; ?> places</span>
                            </div>
                        </div>

                        <?php if (!empty($h['marca'])): ?>
                            <p style="color: #666; margin: 0.5rem 0;"><i class="fas fa-car me-1"></i><?php echo htmlspecialchars($h['marca'] . " " . $h['model']); ?></p>
                        <?php endif; ?>

                        <?php if (!empty($h['comentaris'])): ?>
                            <p style="color: #888; font-size: 0.9rem; margin: 0.5rem 0;"><i class="fas fa-comment me-1"></i><?php echo htmlspecialchars($h['comentaris']); ?></p>
                        <?php endif; ?>

                        <div class="horari-actions">
                            <a href="modificar.php?id=<?php echo $h['id']; ?>" class="btn btn-edit btn-sm text-white" <?php echo $esPassat ? 'disabled' : ''; ?>>
                                <i class="fas fa-edit me-1"></i>Editar
                            </a>
                            <a href="editar.php?delete=<?php echo $h['id']; ?>&confirm=yes" class="btn btn-delete btn-sm text-white" onclick="return confirm('Segur que vols eliminar aquest horari?')">
                                <i class="fas fa-trash me-1"></i>Eliminar
                            </a>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-inbox" style="font-size: 3rem; color: #999; margin-bottom: 1rem;"></i>
            <h3>No tens horaris publicats</h3>
            <p class="text-muted">Comença a compartir viatges ara!</p>
            <a href="afegir.php" class="btn btn-primary"><i class="fas fa-plus me-2"></i>Crear primer horari</a>
        </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>