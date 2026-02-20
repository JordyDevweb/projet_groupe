<?php
session_start();
require 'config/database.php';

if($_SERVER['REQUEST_METHOD'] === 'POST'){

    $nom = trim($_POST['nom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $promotion = trim($_POST['promotion'] ?? '');
    $filiere = trim($_POST['filiere'] ?? '');

    // Vérification champs obligatoires
    if(empty($nom) || empty($email) || empty($password) || empty($confirmPassword)){
        $error = "Tous les champs obligatoires doivent être remplis.";
    }

    // Vérification confirmation mot de passe
    elseif($password !== $confirmPassword){
        $error = "Les mots de passe ne correspondent pas.";
    }

    // ✅ REGEX CORRIGÉE
    elseif(!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/', $password)){
        $error = "Mot de passe non sécurisé : min 8 caractères avec 1 majuscule, 1 minuscule, 1 chiffre et 1 symbole.";
    }

    else{

        // Vérifier si email déjà utilisé
        $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $check->execute([$email]);

        if($check->fetch()){
            $error = "Cet email est déjà utilisé.";
        } else {

            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare("INSERT INTO users (nom,email,password,promotion,filiere) VALUES (?,?,?,?,?)");
            $stmt->execute([$nom, $email, $hashedPassword, $promotion, $filiere]);

            header("Location: login.php");
            exit;
        }
    }
}

function e($v){
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Inscription</title>

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
</head>

<body class="bg-light d-flex flex-column min-vh-100">

<?php include 'includes/header.php'; ?>

<main class="flex-grow-1 d-flex align-items-center py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-10 col-lg-6">

                <div class="card shadow-lg border-0 rounded-4">
                    <div class="card-body p-4 p-md-5">

                        <!-- Title -->
                        <div class="text-center mb-4">
                            <div class="mb-2">
                                <i class="bi bi-person-plus-fill fs-1 text-primary"></i>
                            </div>
                            <h1 class="h3 fw-bold mb-1">Inscription</h1>
                            <p class="text-muted mb-0">Crée ton compte pour rejoindre le réseau Alumni</p>
                        </div>

                        <?php if(isset($error)): ?>
                            <div class="alert alert-danger text-center py-2">
                                <i class="bi bi-exclamation-triangle me-1"></i>
                                <?= e($error) ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST" autocomplete="on" class="mt-3">

                            <!-- Nom -->
                            <div class="form-floating mb-3">
                                <input
                                    type="text"
                                    name="nom"
                                    class="form-control rounded-3"
                                    id="nom"
                                    placeholder="Nom"
                                    required
                                    value="<?= e($_POST['nom'] ?? '') ?>"
                                >
                                <label for="nom"><i class="bi bi-person me-1"></i> Nom complet</label>
                            </div>

                            <!-- Email -->
                            <div class="form-floating mb-3">
                                <input
                                    type="email"
                                    name="email"
                                    class="form-control rounded-3"
                                    id="email"
                                    placeholder="nom@exemple.com"
                                    required
                                    value="<?= e($_POST['email'] ?? '') ?>"
                                >
                                <label for="email"><i class="bi bi-envelope me-1"></i> Email</label>
                            </div>

                            <!-- Password -->
                            <div class="form-floating mb-3">
                                <input
                                    type="password"
                                    name="password"
                                    class="form-control rounded-3"
                                    id="password"
                                    placeholder="Mot de passe"
                                    required
                                >
                                <label for="password"><i class="bi bi-lock me-1"></i> Mot de passe</label>
                            </div>

                            <!-- Confirmation Password -->
                            <div class="form-floating mb-3">
                                <input
                                    type="password"
                                    name="confirm_password"
                                    class="form-control rounded-3"
                                    id="confirm_password"
                                    placeholder="Confirmer mot de passe"
                                    required
                                >
                                <label for="confirm_password">
                                    <i class="bi bi-lock-fill me-1"></i> Confirmer mot de passe
                                </label>
                            </div>

                            <!-- Hint -->
                            <div class="small text-muted mb-3">
                                <i class="bi bi-info-circle me-1"></i>
                                Min. 8 caractères, 1 majuscule, 1 minuscule, 1 chiffre, 1 symbole.
                            </div>

                            <div class="row g-3">
                                <!-- Promotion -->
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input
                                            type="text"
                                            name="promotion"
                                            class="form-control rounded-3"
                                            id="promotion"
                                            placeholder="2026"
                                            value="<?= e($_POST['promotion'] ?? '') ?>"
                                        >
                                        <label for="promotion"><i class="bi bi-mortarboard me-1"></i> Promotion</label>
                                    </div>
                                </div>
                                 <div class="row g-3">
                                <!-- Metier -->
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input
                                            type="text"
                                            name="metier"
                                            class="form-control rounded-3"
                                            id="métier"
                                            placeholder="2026"
                                            value="<?= e($_POST['metier'] ?? '') ?>"
                                        >
                                        <label for="promotion"><i class="bi bi-mortarboard me-1"></i> Metier</label>
                                    </div>
                                </div>

                                <!-- Filière -->
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input
                                            type="text"
                                            name="filiere"
                                            class="form-control rounded-3"
                                            id="filiere"
                                            placeholder="Filière"
                                            value="<?= e($_POST['filiere'] ?? '') ?>"
                                        >
                                        <label for="filiere"><i class="bi bi-diagram-3 me-1"></i> Filiière</label>
                                    </div>
                                </div>
                            </div>

                            <!-- Submit -->
                            <div class="d-grid mt-4">
                                <button type="submit" class="btn btn-primary btn-lg rounded-3">
                                    <i class="bi bi-check2-circle me-1"></i>
                                    S'inscrire
                                </button>
                            </div>

                            <div class="text-center mt-3">
                                <span class="text-muted small">Déjà un compte ?</span>
                                <a href="login.php" class="fw-semibold text-decoration-none ms-1">
                                    Se connecter
                                </a>
                            </div>

                        </form>

                    </div>
                </div>

            </div>
        </div>
    </div>
</main>

<?php include 'includes/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
