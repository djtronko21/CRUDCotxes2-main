<?php
session_start();
require_once "connexio.php";

if (!isset($_SESSION['usuari_id'])) {
    header("Location: login.php");
    exit();
}

$idUsuari = $_SESSION['usuari_id'];

// Rutes disponibles
$rutes = $conn->query("SELECT id, origen, desti FROM rutes ORDER BY origen");

// Cotxes de l'usuari
$cotxes = $conn->prepare("SELECT id, marca, model, matricula, imatge FROM cotxes WHERE usuari_id = ?");
$cotxes->bind_param("i", $idUsuari);
$cotxes->execute();
$cotxesResult = $cotxes->get_result();

$error = null;

// Si s'envia el formulari
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $ruta_id = isset($_POST['ruta_id']) ? intval($_POST['ruta_id']) : 0;
    $cotxe_raw = $_POST['cotxe_id'] ?? '';
    $cotxe_id = ($cotxe_raw === '' ? null : intval($cotxe_raw));
    $data = trim($_POST['data'] ?? '');
    $hora_inici = trim($_POST['hora_inici'] ?? '');
    $hora_fi = trim($_POST['hora_fi'] ?? '');
    $comentaris = trim($_POST['comentaris'] ?? '');

    // Validacions
    if ($ruta_id <= 0) {
        $error = "Selecciona una ruta vàlida.";
    } elseif (!\DateTime::createFromFormat('Y-m-d', $data)) {
        $error = "Data invàlida.";
    } elseif (!\DateTime::createFromFormat('H:i', $hora_inici)) {
        $error = "Hora d'inici invàlida.";
    } elseif ($hora_fi !== '' && !\DateTime::createFromFormat('H:i', $hora_fi)) {
        $error = "Hora fi invàlida.";
    }

    if ($error === null) {
        if ($cotxe_id === null) {
            $sql = "INSERT INTO horaris (usuari_id, ruta_id, data, hora_inici, hora_fi, comentaris)
                    VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iissss", $idUsuari, $ruta_id, $data, $hora_inici, $hora_fi, $comentaris);
        } else {
            $sql = "INSERT INTO horaris (usuari_id, ruta_id, cotxe_id, data, hora_inici, hora_fi, comentaris)
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iiissss", $idUsuari, $ruta_id, $cotxe_id, $data, $hora_inici, $hora_fi, $comentaris);
        }

        if ($stmt->execute()) {
            header("Location: editar.php");
            exit();
        } else {
            $error = "Error al afegir l'horari.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ca">
<head>
    <meta charset="UTF-8">
    <title>Afegir Horari</title>
    <link rel="stylesheet" href="css/estilo.css">
    <style>
        body {
            background-color: #e0ffff;
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
        }

        .main-header {
            background: #333;
            padding: 1rem;
            display: flex;
            justify-content: flex-end;
            align-items: center;
            position: relative;
        }

        .logo {
            margin-right: auto;
        }

        .logo a {
            color: white;
            text-decoration: none;
            font-size: 1.5rem;
            font-weight: bold;
            transition: color 0.3s;
        }

        .logo a:hover {
            color: #4CAF50;
        }

        .menu-toggle {
            display: block;
            color: white;
            cursor: pointer;
            padding: 10px;
            font-size: 1.1rem;
            background: #4CAF50;
            border-radius: 4px;
            transition: background-color 0.3s;
            margin-left: 15px;
        }

        .menu-toggle:hover {
            background: #45a049;
        }

        .main-nav {
            position: relative;
        }

        .main-nav ul {
            display: none;
            position: absolute;
            top: 100%;
            right: 0;
            background: #333;
            min-width: 200px;
            border-radius: 4px;
            padding: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            z-index: 1000;
            margin: 0;
            list-style: none;
        }

        .main-nav.active ul {
            display: block;
        }

        .main-nav ul li {
            display: block;
            margin: 8px 0;
        }

        .main-nav ul li a {
            display: block;
            padding: 8px 15px;
            color: white;
            text-decoration: none;
            transition: all 0.3s;
            border-radius: 4px;
        }

        .main-nav ul li a:hover {
            background: #4CAF50;
            transform: translateX(5px);
        }

        .container {
            max-width: 600px;
            margin: 40px auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            padding: 25px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
        }

        input, select, textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }

        .cotxe-img-preview {
            display: block;
            margin: 20px auto;
            max-width: 300px;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.2);
        }

        .error {
            color: red;
            text-align: center;
            margin-bottom: 15px;
        }

        .btn {
            background: #4CAF50;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            width: 100%;
            font-size: 16px;
        }

        .btn:hover {
            background: #45a049;
        }
    </style>
</head>
<body>

<header class="main-header">
    <div class="logo">
        <a href="principal.php">BlaBlaCash</a>
    </div>
    <div class="menu-toggle" onclick="toggleMenu()">
        ☰ Menú
    </div>
    <nav class="main-nav" id="mainNav">
        <ul>
            <li><a href="afegir.php">➕ Afegir Horari</a></li>
            <li><a href="editar.php">📝 Editar Viatges</a></li>
            <li><a href="perfil.php">👤 Perfil</a></li>
            <li><a href="principal.php">🏠 Menú Principal</a></li>
            <li><a href="logout.php">🚪 Tancar Sessió</a></li>
        </ul>
    </nav>
</header>

<div class="container">
    <h1 style="text-align: center; margin-bottom: 30px;">➕ Afegir Nou Horari</h1>

    <?php if ($error): ?>
        <div class="error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="post">
        <div class="form-group">
            <label for="ruta_id">Ruta:</label>
            <select name="ruta_id" id="ruta_id" required>
                <option value="">-- Selecciona una ruta --</option>
                <?php while ($r = $rutes->fetch_assoc()): ?>
                    <option value="<?php echo $r['id']; ?>">
                        <?php echo htmlspecialchars($r['origen'] . " → " . $r['desti']); ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="cotxe_id">Cotxe (opcional):</label>
            <select name="cotxe_id" id="cotxe_id">
                <option value="">-- Cap cotxe --</option>
                <?php while ($c = $cotxesResult->fetch_assoc()): ?>
                    <option value="<?php echo $c['id']; ?>" 
                            data-img="<?php echo !empty($c['imatge']) ? 'uploads/' . htmlspecialchars($c['imatge']) : ''; ?>">
                        <?php echo htmlspecialchars($c['marca'] . " " . $c['model'] . " (" . $c['matricula'] . ")"); ?>
                    </option>
                <?php endwhile; ?>
            </select>
            <img id="preview-img" class="cotxe-img-preview" style="display:none;" alt="Imatge del cotxe">
        </div>

        <div class="form-group">
            <label for="data">Data:</label>
            <input type="date" name="data" id="data" required>
        </div>

        <div class="form-group">
            <label for="hora_inici">Hora Inici:</label>
            <input type="time" name="hora_inici" id="hora_inici" required>
        </div>

        <div class="form-group">
            <label for="hora_fi">Hora Fi:</label>
            <input type="time" name="hora_fi" id="hora_fi">
        </div>

        <div class="form-group">
            <label for="comentaris">Comentaris:</label>
            <textarea name="comentaris" id="comentaris" rows="3"></textarea>
        </div>

        <button type="submit" class="btn">💾 Guardar</button>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.addEventListener('click', function(event) {
        const nav = document.getElementById('mainNav');
        const menuToggle = document.querySelector('.menu-toggle');
        
        if (!nav.contains(event.target) && !menuToggle.contains(event.target)) {
            nav.classList.remove('active');
        }
    });
});

function toggleMenu() {
    const nav = document.getElementById('mainNav');
    nav.classList.toggle('active');
}

const select = document.getElementById('cotxe_id');
const img = document.getElementById('preview-img');

function updatePreview() {
    const opt = select.selectedOptions[0];
    if (!opt) {
        img.style.display = 'none';
        return;
    }
    const imgData = opt.getAttribute('data-img');
    if (imgData) {
        img.src = imgData;
        img.style.display = 'block';
    } else {
        img.style.display = 'none';
    }
}

select.addEventListener('change', updatePreview);
updatePreview();
</script>

</body>
</html>