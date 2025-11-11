<?php
session_start();
include 'connexio.php';
if (!isset($_SESSION['usuari_id'])) {
    header("Location: login.php");
    exit;
}

// Get 4 random trips
$sql = "SELECT h.*, u.nom AS nom_usuari, r.origen, r.desti, c.marca, c.model, c.matricula, c.imatge
        FROM horaris h
        INNER JOIN usuaris u ON h.usuari_id = u.id
        INNER JOIN rutes r ON h.ruta_id = r.id
        LEFT JOIN cotxes c ON h.cotxe_id = c.id
        WHERE h.data >= CURDATE()
        ORDER BY RAND()
        LIMIT 4";
$viatges = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="ca">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BlaBlaCash - Menú Principal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <style>
        :root { --primary: #4CAF50; --bg: #e0ffff; }
        body { background: var(--bg); font-family: 'Segoe UI', system-ui; }
        .navbar { background: #333; }
        .navbar-brand { font-weight: 700; font-size: 1.5rem; }
        .hero {
            background: linear-gradient(135deg, var(--primary), #45a049);
            color: #fff;
            padding: 4rem 0;
            text-align: center;
            margin-bottom: 3rem;
        }
        .hero h1 { font-size: 2.5rem; font-weight: 700; margin-bottom: 1rem; }
        .hero p { font-size: 1.2rem; margin-bottom: 0; }
        .section-title { font-size: 1.8rem; font-weight: 700; margin-bottom: 2rem; position: relative; padding-bottom: 1rem; }
        .section-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 50px;
            height: 3px;
            background: var(--primary);
        }
        .trip-card {
            background: #fff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
        }
        .trip-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.12);
        }
        .trip-image { width: 100%; height: 180px; object-fit: cover; background: #e0e0e0; }
        .trip-body { padding: 1.5rem; }
        .trip-route { font-weight: 700; color: #333; margin-bottom: 0.5rem; }
        .cards-section { background: #fff; padding: 2rem; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
        .cta-section { background: linear-gradient(135deg, var(--primary), #45a049); color: #fff; padding: 3rem; border-radius: 12px; text-align: center; margin: 3rem 0; }
        .cta-section h2 { margin-bottom: 1.5rem; }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="principal.php"><i class="fas fa-car-side me-2"></i>BlaBlaCash</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse justify-content-end" id="navMenu">
            <ul class="navbar-nav">
                <li class="nav-item"><a class="nav-link active" href="principal.php"><i class="fas fa-home me-1"></i>Inici</a></li>
                <li class="nav-item"><a class="nav-link" href="index.php"><i class="fas fa-search me-1"></i>Viatges</a></li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle text-white" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user-circle me-1"></i><?php echo htmlspecialchars($_SESSION['nom']); ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="afegir.php"><i class="fas fa-plus me-2"></i>Afegir horari</a></li>
                        <li><a class="dropdown-item" href="editar.php"><i class="fas fa-edit me-2"></i>Editar horaris</a></li>
                        <li><a class="dropdown-item" href="perfil.php"><i class="fas fa-user me-2"></i>Perfil</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Tancar sessió</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

<section class="hero">
    <div class="container">
        <h1><i class="fas fa-car-side me-2"></i>Benvingut a BlaBlaCash</h1>
        <p>Comparteix el teu viatge i estalvia diners</p>
    </div>
</section>

<div class="container my-5">
    <div class="cards-section">
        <h2 class="section-title">🎲 Viatges Destacats</h2>
        <div class="row g-4">
            <?php 
            if ($viatges && $viatges->num_rows > 0) {
                while ($viatge = $viatges->fetch_assoc()) {
                    $imgSrc = !empty($viatge['imatge']) && file_exists("uploads/" . $viatge['imatge']) 
                        ? "uploads/" . htmlspecialchars($viatge['imatge'])
                        : "https://via.placeholder.com/400x180?text=Cotxe";
            ?>
                <div class="col-md-6 col-lg-3">
                    <div class="trip-card">
                        <img src="<?php echo $imgSrc; ?>" class="trip-image" alt="Cotxe" onerror="this.src='https://via.placeholder.com/400x180'">
                        <div class="trip-body">
                            <div class="trip-route"><?php echo htmlspecialchars($viatge['origen'] . " → " . $viatge['desti']); ?></div>
                            <p style="color: #666; font-size: 0.9rem; margin: 0.5rem 0;">
                                <i class="far fa-calendar me-1"></i><?php echo date('d/m/Y', strtotime($viatge['data'])); ?>
                            </p>
                            <p style="color: #666; font-size: 0.9rem;">
                                <i class="far fa-clock me-1"></i><?php echo substr($viatge['hora_inici'], 0, 5); ?>
                            </p>
                            <?php if (!empty($viatge['marca'])): ?>
                                <p style="color: #666; font-size: 0.85rem;">
                                    <i class="fas fa-car me-1"></i><?php echo htmlspecialchars($viatge['marca'] . " " . $viatge['model']); ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php
                }
            }
            ?>
        </div>
    </div>

    <div class="cta-section">
        <h2>Vols compartir un viatge?</h2>
        <p>Afegeix un nou horari i comença a estalviar diners</p>
        <a href="afegir.php" class="btn btn-light btn-lg"><i class="fas fa-plus me-2"></i>Afegir Horari</a>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>