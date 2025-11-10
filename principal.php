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
        INNER JOIN cotxes c ON h.cotxe_id = c.id
        ORDER BY RAND()
        LIMIT 4";
$viatges = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="ca">
<head>
    <meta charset="UTF-8">
    <title>BlaBlaCash - Menú Principal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4CAF50;
            --hover-color: #45a049;
            --cards-bg: #e0ffff;
        }
        
        body {
            background-color: #e0ffff;
        }

        .navbar {
            background-color: #333 !important;
            padding: 1rem 0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .navbar .container {
            padding-left: 0;
            padding-right: 0;
            margin-left: 30px;
            margin-right: 30px;
            max-width: 100%;
        }

        .navbar-brand {
            font-size: 1.8rem;
            font-weight: bold;
            color: white !important;
            margin-right: auto;
            padding-left: 0;
        }

        .navbar-nav {
            margin-left: auto;
            padding-right: 0;
        }

        .nav-link {
            color: rgba(255,255,255,0.8) !important;
            transition: all 0.3s ease;
            font-size: 1.1rem;
        }

        .nav-link:hover {
            color: white !important;
            transform: translateY(-2px);
        }

        .dropdown-menu {
            border: none;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            right: 0;
            left: auto;
            min-width: 200px;
        }

        .hero-section {
            background: linear-gradient(135deg, #4CAF50, #45a049);
            color: white;
            padding: 4rem 0;
            margin-bottom: 3rem;
        }

        .cards-section {
            background-color: var(--cards-bg);
            padding: 2rem;
            border-radius: 12px;
            margin: 0 30px;
        }

        .card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border: none;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            background: white;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 12px rgba(0,0,0,0.15);
        }

        .card-img-top {
            height: 200px;
            object-fit: cover;
        }

        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-primary:hover {
            background-color: var(--hover-color);
            border-color: var(--hover-color);
        }

        .section-title {
            position: relative;
            padding-bottom: 15px;
            margin-bottom: 30px;
            font-size: 2rem;
            color: #333;
        }

        .section-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 50px;
            height: 3px;
            background-color: var(--primary-color);
        }

        .dropdown-item {
            padding: 0.5rem 1.5rem;
            transition: all 0.2s;
        }

        .dropdown-item:hover {
            background-color: #f8f9fa;
            transform: translateX(5px);
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container">
        <a class="navbar-brand" href="principal.php">
            <i class="fas fa-car-side me-2"></i>BlaBlaCash
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
            <ul class="navbar-nav">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user-circle me-1"></i><?php echo htmlspecialchars($_SESSION['nom']); ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="index.php"><i class="fas fa-calendar me-2"></i>Veure horaris</a></li>
                        <li><a class="dropdown-item" href="afegir.php"><i class="fas fa-plus me-2"></i>Afegir horari</a></li>
                        <li><a class="dropdown-item" href="editar.php"><i class="fas fa-edit me-2"></i>Editar horaris</a></li>
                        <li><a class="dropdown-item" href="perfil.php"><i class="fas fa-user me-2"></i>Perfil</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Tancar sessió</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

<section class="hero-section text-center">
    <div class="container">
        <h1 class="display-4"><i class="fas fa-car-side me-2"></i>Benvingut a BlaBlaCash</h1>
        <p class="lead">Comparteix el teu viatge i estalvia diners</p>
    </div>
</section>

<div class="container-fluid mb-5">
    <div class="cards-section">
        <h2 class="section-title">🎲 Viatges Aleatoris</h2>
        <div class="row g-4">
            <?php while ($viatge = $viatges->fetch_assoc()): ?>
                <div class="col-md-6 col-lg-3">
                    <div class="card h-100">
                        <?php
                        $imgSrc = '';
                        if (!empty($viatge['imatge'])) {
                            if (@getimagesizefromstring($viatge['imatge']) !== false) {
                                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                                $mime = finfo_buffer($finfo, $viatge['imatge']);
                                finfo_close($finfo);
                                $imgSrc = 'data:' . $mime . ';base64,' . base64_encode($viatge['imatge']);
                            } else {
                                $candidate = 'uploads/' . $viatge['imatge'];
                                if (file_exists($candidate)) {
                                    $imgSrc = $candidate;
                                }
                            }
                        }
                        if (empty($imgSrc)) {
                            $imgSrc = 'https://via.placeholder.com/300x200?text=No+Image';
                        }
                        ?>
                        <img src="<?php echo htmlspecialchars($imgSrc); ?>" class="card-img-top" alt="Cotxe">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($viatge['marca'] . ' ' . $viatge['model']); ?></h5>
                            <p class="card-text">
                                <small class="text-muted"><i class="fas fa-map-marker-alt me-1"></i><?php echo htmlspecialchars($viatge['origen'] . ' → ' . $viatge['desti']); ?></small><br>
                                <small class="text-muted"><i class="fas fa-calendar me-1"></i><?php echo htmlspecialchars($viatge['data']); ?></small><br>
                                <small class="text-muted"><i class="fas fa-user me-1"></i><?php echo htmlspecialchars($viatge['nom_usuari']); ?></small>
                            </p>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>