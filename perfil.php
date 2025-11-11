<?php
session_start();
require_once "connexio.php";

if (!isset($_SESSION['usuari_id'])) {
    header("Location: login.php");
    exit();
}

$idUsuari = $_SESSION['usuari_id'];
$error = null;
$success = null;

// Obtener datos personales
$sqlUsuari = $conn->prepare("SELECT nom, correu, imatge_perfil FROM usuaris WHERE id = ?");
$sqlUsuari->bind_param("i", $idUsuari);
$sqlUsuari->execute();
$dadesUsuari = $sqlUsuari->get_result()->fetch_assoc();

// Verificar que existeixen dades
if (!$dadesUsuari) {
    die("Error: Usuari no trobat.");
}

// Procesar subida de imagen de perfil
if (isset($_FILES['imatge']) && $_FILES['imatge']['error'] === UPLOAD_ERR_OK) {
    $dir = "uploads/";
    if (!is_dir($dir)) mkdir($dir, 0755, true);

    $nomFitxer = "perfil_" . $idUsuari . "_" . time() . ".jpg";
    move_uploaded_file($_FILES['imatge']['tmp_name'], $dir . $nomFitxer);

    $update = $conn->prepare("UPDATE usuaris SET imatge_perfil = ? WHERE id = ?");
    $update->bind_param("si", $nomFitxer, $idUsuari);
    $update->execute();

    header("Location: perfil.php");
    exit();
}

// Afegir cotxe
if (isset($_POST['accio']) && $_POST['accio'] === 'afegir_cotxe') {
    $marca = trim($_POST['marca'] ?? '');
    $model = trim($_POST['model'] ?? '');
    $matricula = trim($_POST['matricula'] ?? '');
    $any = !empty($_POST['any']) ? intval($_POST['any']) : null;
    $color = trim($_POST['color'] ?? '');
    $imatgeCotxe = null;

    if (empty($marca) || empty($model) || empty($matricula)) {
        $error = "Completa tots els camps obligatoris.";
    } else {
        // Comprovar si la matrícula ja existeix
        $checkMatricula = $conn->prepare("SELECT id FROM cotxes WHERE matricula = ? AND usuari_id = ?");
        $checkMatricula->bind_param("si", $matricula, $idUsuari);
        $checkMatricula->execute();
        $result = $checkMatricula->get_result();

        if ($result->num_rows > 0) {
            $error = "La matrícula '" . htmlspecialchars($matricula) . "' ja està registrada.";
        } else {
            if (!empty($_FILES['imatge_cotxe']['tmp_name'])) {
                $dir = "uploads/";
                if (!is_dir($dir)) mkdir($dir, 0755, true);
                $nomCotxe = "cotxe_" . $idUsuari . "_" . time() . ".jpg";
                move_uploaded_file($_FILES['imatge_cotxe']['tmp_name'], $dir . $nomCotxe);
                $imatgeCotxe = $nomCotxe;
            }

            $stmt = $conn->prepare("INSERT INTO cotxes (usuari_id, marca, model, matricula, any, color, imatge) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isssiss", $idUsuari, $marca, $model, $matricula, $any, $color, $imatgeCotxe);
            $stmt->execute();
            $success = "Cotxe afegit correctament!";
            header("Refresh: 1; url=perfil.php");
        }
    }
}

// Actualitzar imatge de cotxe
if (isset($_POST['accio']) && $_POST['accio'] === 'actualitzar_imatge') {
    $idCotxe = intval($_POST['id_cotxe'] ?? 0);
    if ($idCotxe > 0 && !empty($_FILES['nova_imatge']['tmp_name'])) {
        $dir = "uploads/";
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        $nomNou = "cotxe_" . $idUsuari . "_" . time() . ".jpg";
        move_uploaded_file($_FILES['nova_imatge']['tmp_name'], $dir . $nomNou);

        $update = $conn->prepare("UPDATE cotxes SET imatge = ? WHERE id = ? AND usuari_id = ?");
        $update->bind_param("sii", $nomNou, $idCotxe, $idUsuari);
        $update->execute();
        header("Location: perfil.php");
        exit();
    }
}

// Eliminar cotxe
if (isset($_GET['delete_cotxe'])) {
    $idCotxe = intval($_GET['delete_cotxe']);
    $delete = $conn->prepare("DELETE FROM cotxes WHERE id = ? AND usuari_id = ?");
    $delete->bind_param("ii", $idCotxe, $idUsuari);
    $delete->execute();
    header("Location: perfil.php");
    exit();
}

// Obtenir cotxes
$cotxes = $conn->prepare("SELECT * FROM cotxes WHERE usuari_id = ?");
$cotxes->bind_param("i", $idUsuari);
$cotxes->execute();
$cotxesResult = $cotxes->get_result();

// Helper function
function imgSrcOrPlaceholder($file) {
    if (!empty($file) && file_exists("uploads/" . $file)) {
        return "uploads/" . htmlspecialchars($file);
    }
    if (file_exists("img/default-car.jpg")) {
        return "img/default-car.jpg";
    }
    return "https://via.placeholder.com/400x250?text=No+Image";
}
?>
<!DOCTYPE html>
<html lang="ca">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil — BlaBlaCash</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <style>
        :root { --primary:#4CAF50; --muted:#6c757d; --bg:#e0ffff; }
        body { background: var(--bg); font-family: 'Segoe UI', system-ui, Arial; }
        .navbar { background:#333; padding:0.75rem 1rem; box-shadow:0 2px 6px rgba(0,0,0,.12); }
        .navbar-brand { color:#fff; font-weight:700; font-size:1.5rem; }
        .profile-card { background:#fff; border-radius:12px; padding:1.5rem; box-shadow:0 6px 18px rgba(0,0,0,.06); }
        .avatar { width:140px; height:140px; object-fit:cover; border-radius:50%; border:6px solid #fff; box-shadow:0 6px 18px rgba(0,0,0,.08); }
        .car-card { border-radius:10px; overflow:hidden; background:#fff; box-shadow:0 6px 18px rgba(0,0,0,.06); transition: transform 0.3s ease; }
        .car-card:hover { transform: translateY(-4px); }
        .car-card img { width:100%; height:160px; object-fit:cover; }
        .btn-primary { background:var(--primary); border:none; }
        .btn-primary:hover { background:#3e9b3e; }
        .small-muted { color:var(--muted); font-size:.95rem; }
        .modal-img { max-width:90%; max-height:90vh; border-radius:10px; }
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
                <li class="nav-item"><a class="nav-link" href="principal.php"><i class="fas fa-home me-1"></i>Inici</a></li>
                <li class="nav-item"><a class="nav-link" href="index.php"><i class="fas fa-search me-1"></i>Viatges</a></li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle text-white" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user-circle me-1"></i><?php echo htmlspecialchars($_SESSION['nom']); ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="afegir.php"><i class="fas fa-plus me-2"></i>Afegir horari</a></li>
                        <li><a class="dropdown-item" href="editar.php"><i class="fas fa-edit me-2"></i>Editar horaris</a></li>
                        <li><a class="dropdown-item active" href="perfil.php"><i class="fas fa-user me-2"></i>Perfil</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Tancar sessió</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="container my-5">
    <div class="row g-4">
        <div class="col-lg-4">
            <div class="profile-card text-center">
                <?php $perfilImg = imgSrcOrPlaceholder($dadesUsuari['imatge_perfil'] ?? null); ?>
                <img src="<?php echo $perfilImg; ?>" alt="Perfil" class="avatar mb-3" onerror="this.src='https://via.placeholder.com/140'">
                <h3 class="mb-1"><?php echo htmlspecialchars($dadesUsuari['nom'] ?? 'Usuari'); ?></h3>
                <p class="small-muted mb-3"><?php echo htmlspecialchars($dadesUsuari['correu'] ?? ''); ?></p>

                <form method="post" enctype="multipart/form-data" class="mb-3">
                    <div class="mb-2">
                        <input class="form-control" type="file" name="imatge" accept="image/*" required>
                    </div>
                    <button class="btn btn-primary w-100" type="submit"><i class="fas fa-camera me-2"></i>Actualitzar foto</button>
                </form>

                <a href="principal.php" class="btn btn-outline-secondary w-100 mb-2"><i class="fas fa-home me-2"></i>Anar al menú</a>
                <a href="logout.php" class="btn btn-danger w-100"><i class="fas fa-sign-out-alt me-2"></i>Tancar sessió</a>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="profile-card">
                <h4 class="mb-3"><i class="fas fa-car me-2"></i>Els meus cotxes</h4>

                <?php if ($error): ?>
                    <div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?></div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success"><i class="fas fa-check-circle me-2"></i><?php echo $success; ?></div>
                <?php endif; ?>

                <div class="row row-cols-1 row-cols-md-2 g-3 mb-4">
                    <?php 
                    if ($cotxesResult->num_rows > 0):
                        while ($c = $cotxesResult->fetch_assoc()):
                            $imgCar = imgSrcOrPlaceholder($c['imatge'] ?? null);
                    ?>
                        <div class="col">
                            <div class="car-card">
                                <img src="<?php echo $imgCar; ?>" alt="Cotxe" onerror="this.src='https://via.placeholder.com/400x250'">
                                <div class="p-3">
                                    <h5 class="mb-1"><?php echo htmlspecialchars($c['marca'] . ' ' . $c['model']); ?></h5>
                                    <p class="small-muted mb-2"><i class="fas fa-id-card me-1"></i>Matrícula: <?php echo htmlspecialchars($c['matricula']); ?></p>
                                    <p class="small-muted mb-2">
                                        <i class="far fa-calendar me-1"></i>Any: <?php echo htmlspecialchars($c['any'] ?? 'N/A'); ?> 
                                        <i class="fas fa-palette ms-2 me-1"></i>Color: <?php echo htmlspecialchars($c['color'] ?? 'N/A'); ?>
                                    </p>

                                    <div class="d-flex gap-2">
                                        <button class="btn btn-outline-secondary btn-sm" onclick="mostrarImatge('<?php echo addslashes($imgCar); ?>')">
                                            <i class="fas fa-search-plus me-1"></i>Veure
                                        </button>

                                        <form method="post" enctype="multipart/form-data" style="display:inline-block;">
                                            <input type="hidden" name="accio" value="actualitzar_imatge">
                                            <input type="hidden" name="id_cotxe" value="<?php echo intval($c['id']); ?>">
                                            <label class="btn btn-outline-primary btn-sm mb-0">
                                                <i class="fas fa-sync me-1"></i>Canviar 
                                                <input type="file" name="nova_imatge" accept="image/*" onchange="this.form.submit()" style="display:none" required>
                                            </label>
                                        </form>

                                        <a href="?delete_cotxe=<?php echo intval($c['id']); ?>" class="btn btn-danger btn-sm ms-auto" onclick="return confirm('Segur que vols eliminar aquest cotxe?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php 
                        endwhile;
                    else:
                    ?>
                        <div class="col-12">
                            <div class="text-center p-4 small-muted">
                                <i class="fas fa-inbox" style="font-size: 3rem; opacity: 0.3;"></i>
                                <p class="mt-2">No tens cotxes registrats.</p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <hr>

                <h5 class="mb-3"><i class="fas fa-plus-circle me-2"></i>Afegir Nou Cotxe</h5>
                <form method="post" enctype="multipart/form-data" class="row g-2">
                    <input type="hidden" name="accio" value="afegir_cotxe">
                    <div class="col-md-6">
                        <label class="form-label">Marca</label>
                        <input class="form-control" type="text" name="marca" placeholder="Toyota" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Model</label>
                        <input class="form-control" type="text" name="model" placeholder="Corolla" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Matrícula</label>
                        <input class="form-control" type="text" name="matricula" placeholder="1234ABC" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Any</label>
                        <input class="form-control" type="number" name="any" placeholder="2023" min="1900" max="<?php echo date('Y'); ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Color</label>
                        <input class="form-control" type="text" name="color" placeholder="Blanc">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Imatge (opcional)</label>
                        <input class="form-control" type="file" name="imatge_cotxe" accept="image/*">
                    </div>
                    <div class="col-12">
                        <button class="btn btn-primary w-100" type="submit"><i class="fas fa-save me-2"></i>Afegir cotxe</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Image modal -->
<div id="imgModal" class="modal" tabindex="-1" style="display:none; background:rgba(0,0,0,.9); position:fixed; inset:0; justify-content:center; align-items:center; z-index:2000; cursor:pointer;">
    <img id="imgModalImg" class="modal-img" src="" alt="Imatge">
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
function mostrarImatge(src) {
    const modal = document.getElementById('imgModal');
    const img = document.getElementById('imgModalImg');
    img.src = src;
    modal.style.display = 'flex';
    modal.onclick = function(){ modal.style.display = 'none'; img.src=''; };
}
</script>
</body>
</html>