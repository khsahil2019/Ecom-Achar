<?php
/**
 * About Us - Our Heritage Story
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Our Heritage Story - Traditional Indian Pickling | Achar Heritage';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="py-5 text-white" style="background: linear-gradient(135deg, var(--brand-primary-dark), var(--brand-primary));">
    <div class="container text-center py-4">
        <span class="badge bg-warning text-dark px-3 py-1 rounded-pill fw-bold text-uppercase mb-2">Preserving Heirloom Traditions</span>
        <h1 class="font-heading display-5 text-white mb-3">Grandmother’s Rooftop Sunshine & Earthen Jars</h1>
        <p class="fs-5 text-light opacity-90 mx-auto" style="max-width: 650px;">
            The story of Achar Heritage was born out of an unwavering passion for authentic, unadulterated Indian home dining.
        </p>
    </div>
</div>

<div class="container py-5">
    
    <div class="row align-items-center g-5 mb-5">
        <div class="col-lg-6">
            <span class="text-uppercase fw-bold text-muted small">Estd. 1968 Tradition</span>
            <h2 class="font-heading display-6 mb-3">Reclaiming What Modern Factories Took Away</h2>
            <p class="text-secondary" style="line-height: 1.8;">
                In an era dominated by mass-produced factory pickles laden with artificial synthetic vinegar, chemical stabilizers, and low-grade refined palm oil, the soul of authentic Indian pickle was gradually lost.
            </p>
            <p class="text-secondary" style="line-height: 1.8;">
                At <strong>Achar Heritage</strong>, we refuse shortcuts. We wake up before sunrise to source firm, handpicked Ramkela and Rajapuri mangoes, plump winter lemons, mountain ginger, and fiery Banarasi chillies. Every spice is dry-roasted on cast-iron tawas and stone-crushed to preserve natural essential oils.
            </p>
            <div class="p-3 bg-light rounded-4 border-start border-4 border-warning">
                <em>"Pickling isn't merely food processing — it is patience, seasonal rhythm, and grandmother's blessing captured inside glass."</em>
            </div>
        </div>
        <div class="col-lg-6 text-center">
            <img src="<?= BASE_URL ?>/uploads/products/prod-combo.jpg" alt="Artisanal Achar jars" class="img-fluid rounded-4 shadow-lg" style="max-height: 420px; object-fit: cover;">
        </div>
    </div>

    <!-- The 3 Pillars -->
    <div class="row g-4 my-4">
        <div class="col-md-4">
            <div class="card border-0 rounded-4 shadow-sm p-4 bg-white h-100">
                <div class="fs-2 text-warning mb-2"><i class="bi bi-droplet-half"></i></div>
                <h4 class="font-heading fs-5">Pure Kachi Ghani Mustard Oil</h4>
                <p class="text-muted small m-0">We exclusively use first-press, cold-extracted mustard oil. Rich in natural pungency, it acts as nature’s greatest antibacterial shield.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 rounded-4 shadow-sm p-4 bg-white h-100">
                <div class="fs-2 text-warning mb-2"><i class="bi bi-sun"></i></div>
                <h4 class="font-heading fs-5">21-Day Sun Incubation</h4>
                <p class="text-muted small m-0">No boiler steam cooking. Our ceramic martabans spend three weeks sun-basking on traditional terracotta terraces.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 rounded-4 shadow-sm p-4 bg-white h-100">
                <div class="fs-2 text-warning mb-2"><i class="bi bi-shield-check"></i></div>
                <h4 class="font-heading fs-5">Glass Jars Only</h4>
                <p class="text-muted small m-0">Pickles react with plastic containers. We pack every single gram into sterilized food-grade glass jars with double-leak seals.</p>
            </div>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
