<?php
require_once "connexio.php";

if (!isset($_SESSION['usuari_id'])) {
    header("Location: login.php");
    exit();
}

$idUsuari = $_SESSION['usuari_id'];

$sql = "SELECT 
    h.id, h.usuari_id, h.data, h.hora_inici, h.hora_fi, h.comentaris,
    u.nom AS nom_usuari,
    r.origen, r.desti,
    c.marca, c.model, c.matricula, c.imatge
FROM horaris h
INNER JOIN usuaris u ON h.usuari_id = u.id
INNER JOIN rutes r ON h.ruta_id = r.id
INNER JOIN cotxes c ON h.cotxe_id = c.id
ORDER BY h.data DESC, h.hora_inici ASC";

$resultat = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="ca">
<head>
    <meta charset="UTF-8">
    <title>Tots els Viatges</title>
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
            justify-content: flex-end; /* Aligns items to the right */
            align-items: center;
            position: relative;
        }

        .logo {
            margin-right: auto; /* Pushes everything else to the right */
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
            margin-left: 15px; /* Space between logo and menu */
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

        .grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            padding: 20px;
            max-width: 1400px;
            margin: 0 auto;
        }

        .card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            overflow: hidden;
            transition: transform 0.2s;
        }

        .card:hover {
            transform: translateY(-5px);
        }

        .card img {
            width: 100%;
            height: 150px;
            object-fit: cover;
        }

        .card-content {
            padding: 15px;
        }

        .card-content h3 {
            margin-top: 0;
            color: #333;
        }

        .card-content p {
            margin: 8px 0;
            color: #666;
        }

        .card-content b {
            color: #333;
        }

        .my-trip {
            border: 2px solid #4CAF50;
        }

        h1 {
            text-align: center;
            color: #333;
            margin: 20px 0;
            font-size: 2rem;
        }

        @media (max-width: 1200px) {
            .grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        @media (max-width: 900px) {
            .grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 600px) {
            .grid {
                grid-template-columns: 1fr;
            }

            .main-nav ul {
                width: 200px;
                right: 0;
            }
        }
    </style>
</head>
<body>

<header class="main-header">
    <div class="logo">
        <a href="index.php">BlaBlaCash</a>
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

<h1>🚗 Tots els Viatges Disponibles</h1>

<div class="grid">
<?php
while ($fila = $resultat->fetch_assoc()) {
    $imgSrc = '';
    
    if (!empty($fila['imatge'])) {
        if (@getimagesizefromstring($fila['imatge']) !== false) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_buffer($finfo, $fila['imatge']);
            finfo_close($finfo);
            $imgSrc = 'data:' . $mime . ';base64,' . base64_encode($fila['imatge']);
        } else {
            $candidate = 'uploads/' . $fila['imatge'];
            if (file_exists($candidate)) {
                $imgSrc = $candidate;
            }
        }
    }
    
    if (empty($imgSrc)) {
        if (file_exists('img/default-car.jpg')) {
            $imgSrc = 'img/default-car.jpg';
        } else {
            $imgSrc = 'https://via.placeholder.com/300x200?text=No+Image';
        }
    }

    $esMeu = ($fila['usuari_id'] == $idUsuari);

    echo "<div class='card" . ($esMeu ? " my-trip" : "") . "'>";
    echo "<img src='" . htmlspecialchars($imgSrc) . "' alt='Cotxe' onerror=\"this.src='https://via.placeholder.com/300x200?text=Error'\">";
    echo "<div class='card-content'>";
    echo "<h3>" . htmlspecialchars($fila['marca'] . " " . $fila['model']) . "</h3>";
    echo "<p><b>Conductor:</b> " . htmlspecialchars($fila['nom_usuari']) . "</p>";
    echo "<p><b>Matrícula:</b> " . htmlspecialchars($fila['matricula']) . "</p>";
    echo "<p><b>Data:</b> " . htmlspecialchars($fila['data']) . "</p>";
    echo "<p><b>Hora:</b> " . htmlspecialchars($fila['hora_inici']);
    if (!empty($fila['hora_fi'])) {
        echo " - " . htmlspecialchars($fila['hora_fi']);
    }
    echo "</p>";
    echo "<p><b>Trajecte:</b> " . htmlspecialchars($fila['origen']) . " → " . htmlspecialchars($fila['desti']) . "</p>";
    if (!empty($fila['comentaris'])) {
        echo "<p><b>Comentaris:</b> " . htmlspecialchars($fila['comentaris']) . "</p>";
    }
    echo "</div></div>";
}
?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Close menu when clicking outside
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
</script>

</body>
</html>