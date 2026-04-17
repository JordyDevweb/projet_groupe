<?php
require __DIR__ . '/config/database.php';

function e($v){ return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); }

// Statistiques globales
$totalAlumni = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE directory_visible=1 AND directory_approved=1")->fetchColumn();
$totalTestimonials = (int)$pdo->query("SELECT COUNT(*) FROM testimonials WHERE approved=1")->fetchColumn();
$totalRecommendations = (int)$pdo->query("SELECT COUNT(*) FROM recommendations")->fetchColumn();

// Exemple de données pour un graphique mensuel (à adapter dynamiquement si besoin)
$monthlySignups = $pdo->query("
    SELECT MONTH(created_at) as month, COUNT(*) as total
    FROM users
    WHERE directory_visible=1
    GROUP BY MONTH(created_at)
    ORDER BY MONTH(created_at)
")->fetchAll(PDO::FETCH_ASSOC);

$chartLabels = [];
$chartData = [];
foreach($monthlySignups as $m){
    $chartLabels[] = $m['month'];
    $chartData[] = $m['total'];
}
?>

<?php include 'includes/header.php'; ?>

<div class="container py-5">
    <h2 class="fw-bold mb-4">Statistiques de la communauté Alumni</h2>

    <!-- Cartes chiffrées -->
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card text-center shadow-sm p-4">
                <h3><?= e($totalAlumni) ?></h3>
                <p>Alumni inscrits</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-center shadow-sm p-4">
                <h3><?= e($totalTestimonials) ?></h3>
                <p>Témoignages publiés</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-center shadow-sm p-4">
                <h3><?= e($totalRecommendations) ?></h3>
                <p>Recommandations</p>
            </div>
        </div>
    </div>

    <!-- Graphique inscriptions mensuelles -->
    <div class="card shadow-sm p-4">
        <h4 class="mb-4">Nouveaux inscrits par mois</h4>
        <canvas id="signupChart" height="150"></canvas>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<!-- JS pour les graphiques -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const ctx = document.getElementById('signupChart').getContext('2d');
const signupChart = new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?= json_encode($chartLabels) ?>,
        datasets: [{
            label: 'Nouveaux inscrits',
            data: <?= json_encode($chartData) ?>,
            backgroundColor: 'rgba(54, 162, 235, 0.6)',
            borderColor: 'rgba(54, 162, 235, 1)',
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: { beginAtZero: true }
        }
    }
});
</script>