<?php
session_start();
require 'config/database.php';

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email=?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if($user && password_verify($password, $user['password'])){
        $_SESSION['user_id'] = $user['id'];
        header("Location: index.php");
        exit;
    } else {
        $error = "Email ou mot de passe incorrect.";
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
    <title>Connexion</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light d-flex flex-column min-vh-100">

<?php include 'includes/header.php'; ?>

<main class="flex-grow-1 d-flex align-items-center py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-5">

                <div class="card shadow-lg border-0 rounded-4">
                    <div class="card-body p-4 p-md-5">

                        <!-- Logo / Title -->
                        <div class="text-center mb-4">
                            <div class="mb-2">
                                <i class="bi bi-people-fill fs-1 text-primary"></i>
                            </div>
                            <h2 class="fw-bold">Connexion</h2>
                            <p class="text-muted mb-0">
                                Accédez à votre espace Alumni
                            </p>
                        </div>

                        <?php if(isset($error)): ?>
                            <div class="alert alert-danger text-center py-2">
                                <i class="bi bi-exclamation-triangle me-1"></i>
                                <?= e($error) ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST" autocomplete="on">

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
                                <label for="email">
                                    <i class="bi bi-envelope me-1"></i> Email
                                </label>
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
                                <label for="password">
                                    <i class="bi bi-lock me-1"></i> Mot de passe
                                </label>
                            </div>

                            <!-- Remember me -->
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="remember">
                                <label class="form-check-label text-muted" for="remember">
                                    Se souvenir de moi
                                </label>
                            </div>

                            <!-- Submit -->
                            <div class="d-grid mb-3">
                                <button type="submit" class="btn btn-primary btn-lg rounded-3">
                                    <i class="bi bi-box-arrow-in-right me-1"></i>
                                    Se connecter
                                </button>
                            </div>

                            <!-- Links -->
                            <div class="text-center">
                                <a href="forgot_password.php" class="small text-decoration-none">
                                    Mot de passe oublié ?
                                </a>
                            </div>

                        </form>

                        <hr class="my-4">

                        <div class="text-center">
                            <span class="text-muted small">
                                Pas encore de compte ?
                            </span>
                            <a href="register.php" class="fw-semibold text-decoration-none ms-1">
                                S'inscrire
                            </a>
                        </div>

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