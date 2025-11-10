<?php
require_once "connexio.php";

if (!isset($_SESSION['usuari_id'])) {
    header("Location: login.php");
    exit();
}

$idUsuari = $_SESSION['usuari_id'];

// Obtener datos personales
$sqlUsuari = $conn->prepare("SELECT nom, correu, imatge_perfil FROM usuaris WHERE id = ?");
$sqlUsuari->bind_param("i", $idUsuari);
$sqlUsuari->execute();
$dadesUsuari = $sqlUsuari->get_result()->fetch_assoc();

// Procesar subida de imagen de perfil
if (isset($_FILES['imatge']) && $_FILES['imatge']['error'] === UPLOAD_ERR_OK) {
    $dir = "uploads/";
    if (!is_dir($dir)) mkdir($dir);

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
    $marca = $_POST['marca'];
    $model = $_POST['model'];
    $matricula = $_POST['matricula'];
    $any = $_POST['any'];
    $color = $_POST['color'];
    $imatgeCotxe = null;

    // Comprovar si la matrícula ja existeix
    $checkMatricula = $conn->prepare("SELECT id FROM cotxes WHERE matricula = ? AND usuari_id = ?");
    $checkMatricula->bind_param("si", $matricula, $idUsuari);
    $checkMatricula->execute();
    $result = $checkMatricula->get_result();

    if ($result->num_rows > 0) {
        // La matrícula ja existeix
        $error = "La matrícula '$matricula' ja està registrada. Si us plau, introdueix una matrícula diferent.";
    } else {
        if (!empty($_FILES['imatge_cotxe']['tmp_name'])) {
            $dir = "uploads/";
            if (!is_dir($dir)) mkdir($dir);
            $nomCotxe = "cotxe_" . $idUsuari . "_" . time() . ".jpg";
            move_uploaded_file($_FILES['imatge_cotxe']['tmp_name'], $dir . $nomCotxe);
            $imatgeCotxe = $nomCotxe;
        }

        $stmt = $conn->prepare("INSERT INTO cotxes (usuari_id, marca, model, matricula, any, color, imatge) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isssiss", $idUsuari, $marca, $model, $matricula, $any, $color, $imatgeCotxe);
        $stmt->execute();
        header("Location: perfil.php");
        exit();
    }
}

// Actualitzar imatge de cotxe
if (isset($_POST['accio']) && $_POST['accio'] === 'actualitzar_imatge') {
    $idCotxe = $_POST['id_cotxe'];
    if (!empty($_FILES['nova_imatge']['tmp_name'])) {
        $dir = "uploads/";
        if (!is_dir($dir)) mkdir($dir);
        $nomNou = "cotxe_" . $idUsuari . "_" . time() . ".jpg";
        move_uploaded_file($_FILES['nova_imatge']['tmp_name'], $dir . $nomNou);

        $update = $conn->prepare("UPDATE cotxes SET imatge = ? WHERE id = ? AND usuari_id = ?");
        $update->bind_param("sii", $nomNou, $idCotxe, $idUsuari);
        $update->execute();
    }
    header("Location: perfil.php");
    exit();
}

// Eliminar cotxe
if (isset($_GET['delete_cotxe'])) {
    $idCotxe = $_GET['delete_cotxe'];
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
?>

<!DOCTYPE html>
<html lang="ca">
<head>
    <meta charset="UTF-8">
    <title>Perfil d'Usuari</title>
    <link rel="stylesheet" href="css/estilo.css">
    <style>
        body { background-color: #fafafa; }
        .container {
            width: 90%; max-width: 900px; margin: 30px auto;
            background: white; padding: 2rem; border-radius: 15px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .perfil-header { text-align: center; margin-bottom: 2rem; }
        .perfil-header img {
            width: 160px; height: 160px; border-radius: 50%;
            object-fit: cover; border: 4px solid #3498db; margin-bottom: 10px;
        }
        .miniatura {
            width: 120px; border-radius: 10px; cursor: pointer;
            transition: transform 0.2s; box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        }
        .miniatura:hover { transform: scale(1.05); }
        .modal {
            display: none; position: fixed; z-index: 100;
            left: 0; top: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.8); justify-content: center; align-items: center;
        }
        .modal img { max-width: 90%; max-height: 90%; border-radius: 10px; }
    </style>
</head>
<body>
<div class="container">
    <h1 class="center">👤 Perfil de <?php echo htmlspecialchars($dadesUsuari['nom']); ?></h1>

    <div class="center">
        <a href="index.php" class="boto">⬅️ Tornar</a>
        <a href="principal.php" class="boto">🏠 Menú</a>
        <a href="logout.php" class="boto danger">🚪 Sortir</a>
    </div>

    <div class="perfil-header">
        <img src="uploads/<?php echo htmlspecialchars($dadesUsuari['imatge_perfil']); ?>" alt="Perfil">
        <form method="post" enctype="multipart/form-data">
            <input type="file" name="imatge" accept="image/*" required>
            <button type="submit" class="boto success">📷 Actualitzar Foto</button>
        </form>
    </div>

    <div class="perfil-section">
        <h3>📄 Informació Personal</h3>
        <p><strong>Nom:</strong> <?php echo htmlspecialchars($dadesUsuari['nom']); ?></p>
        <p><strong>Correu:</strong> <?php echo htmlspecialchars($dadesUsuari['correu']); ?></p>
    </div>

    <div class="perfil-section">
        <h3>🚗 Els meus cotxes</h3>
        <table>
            <thead>
            <tr>
                <th>Marca</th>
                <th>Model</th>
                <th>Matricula</th>
                <th>Any</th>
                <th>Color</th>
                <th>Imatge</th>
                <th>Accions</th>
            </tr>
            </thead>
            <tbody>
            <?php while ($c = $cotxesResult->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($c['marca']); ?></td>
                    <td><?php echo htmlspecialchars($c['model']); ?></td>
                    <td><?php echo htmlspecialchars($c['matricula']); ?></td>
                    <td><?php echo htmlspecialchars($c['any']); ?></td>
                    <td><?php echo htmlspecialchars($c['color']); ?></td>
                    <td>
                        <?php if (!empty($c['imatge'])): ?>
                            <img src="uploads/<?php echo htmlspecialchars($c['imatge']); ?>" class="miniatura" onclick="mostrarImatge(this)">
                            <form method="post" enctype="multipart/form-data" style="margin-top:5px;">
                                <input type="hidden" name="accio" value="actualitzar_imatge">
                                <input type="hidden" name="id_cotxe" value="<?php echo $c['id']; ?>">
                                <input type="file" name="nova_imatge" accept="image/*" required>
                                <button type="submit" class="boto success">🔄 Canviar</button>
                            </form>
                        <?php else: ?>
                            <em>Sense imatge</em>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="?delete_cotxe=<?php echo $c['id']; ?>" class="boto danger" onclick="return confirm('Eliminar cotxe?')">🗑️ Eliminar</a>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>

        <h4 style="margin-top:1rem;">➕ Afegir Nou Cotxe</h4>
        <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="accio" value="afegir_cotxe">
            <input type="text" name="marca" placeholder="Marca" required>
            <input type="text" name="model" placeholder="Model" required>
            <input type="text" name="matricula" placeholder="Matrícula" required>
            <input type="number" name="any" placeholder="Any" min="1900" max="2025">
            <input type="text" name="color" placeholder="Color">
            <!-- ✅ Afegit: pujar imatge -->
            <input type="file" name="imatge_cotxe" accept="image/*">
            <button type="submit" class="boto success">💾 Afegir Cotxe</button>
        </form>
    </div>
</div>

<script>
function mostrarImatge(img) {
    const modal = document.createElement("div");
    modal.className = "modal";
    modal.innerHTML = `<img src="${img.src}" onclick="this.parentElement.remove()">`;
    document.body.appendChild(modal);
    modal.style.display = "flex";
}
</script>
</body>
</html>