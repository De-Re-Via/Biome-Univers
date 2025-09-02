<?php
declare(strict_types=1);

session_start();
if (!isset($_SESSION['csrf'])) {
  $_SESSION['csrf'] = bin2hex(random_bytes(16));
}
$csrf = $_SESSION['csrf'];

// --- Auth locale (adapte si besoin) ---
$userId = (int)($_SESSION['user']['id'] ?? 0);  // ton auth met déjà $_SESSION['user']

// --- Token HMAC léger pour l’iFrame ---
$exp    = time() + 1800;                        // 30 min
$SECRET = 'change-me-long-secret';              // DOIT matcher biome.php
$base   = $userId . '|' . $exp;
$sig    = rtrim(strtr(base64_encode(hash_hmac('sha256', $base, $SECRET, true)), '+/', '-_'), '=');

// --- URL publique HTTPS de l’API (tunnel en dev) ---
$API_URL = 'https://laundry-copying-ratio-johns.trycloudflare.com/public/biome.php'; // ex: https://abc123.trycloudflare.com/biome.php

// --- URL du jeu (Netlify) avec paramètres nécessaires ---
$gameUrl = 'https://biomeexploreronline.netlify.app/?' . http_build_query([
  'uid' => $userId,
  'exp' => $exp,
  'sig' => $sig,
  'api' => $API_URL
]);
?>



<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Biome Univers — Maquette</title>
  <!-- CSS principal -->
  <link rel="stylesheet" href="assets/css/style.css" />
</head>
<body>

  <main>
    <!-- =========================================================
         ONGLET UNIVERS
         ========================================================= -->
    <section id="tab-univers" class="tab-panel active" aria-labelledby="tab-univers-btn">

      <!-- HERO vidéo "sticky" (reste collée en haut ; le contenu glisse par-dessus) -->
      <div class="hero sticky">
        <video id="vid-univers" class="hero-video" playsinline muted autoplay loop preload="metadata"
               poster="../public/assets/media/univers-poster.jpg">
          <source src="../public/assets/media/univers-hero.mp4" type="video/mp4" />
        </video>
        <div class="hero-overlay"></div>
        <h1 class="hero-title neon">BIOMES UNIVERS</h1>
      </div>

      <!-- Barre d’onglets placée SOUS la vidéo (défile avec le contenu) -->
      <div class="tabs-wrap">
        <div class="tabs-bar">
          <!-- Slot utilisateur (rempli via JS) -->
          <div class="user-slot" aria-live="polite"></div>

          <nav class="tabs">
            <button class="tab neon active" data-tab="univers"  id="tab-univers-btn">Univers</button>
            <button class="tab" data-tab="explorer" id="tab-explorer-btn">Explorer</button>
          </nav>
        </div>
        <div class="tabs-underline neon-line"></div>
      </div>




      <!-- Contenu de l’onglet Univers -->
      <div class="section-content">
        <section class="block">
          <h2 class="h-like">Texte introductif</h2>
          <p class="lead">Brève présentation du projet, objectifs pédagogiques, contexte…</p>
        </section>

        <section class="block"> 
          <h2 class="h-like">LE BIOME UNIVERS</h2>

          <div class="teaser-box" style="position:relative; border:none; height:auto; padding:0">
            <video
              id="teaserVideo"
              preload="metadata"
              playsinline
              muted
              loop
              poster="assets/media/biomeUnivers-poster.png"
              style="width:100%; height:auto; border-radius:12px; display:block">
              <source src="assets/media/biomeUnivers.mp4" type="video/mp4" />
              Votre navigateur ne supporte pas la vidéo HTML5.
              <a href="assets/media/biomeUnivers.mp4">Télécharger la vidéo</a>.
            </video>

            <!-- Bouton Activer le son -->
            <button id="unmuteBtn" aria-label="Activer le son"
              style="position:absolute; inset:auto 1rem 1rem auto; 
                    padding:.6rem .9rem; border:0; border-radius:.75rem;
                    background:#111; color:#fff; font-weight:600; cursor:pointer; 
                    box-shadow:0 6px 20px rgba(0,0,0,.25);">
              🔊 Activer le son
            </button>
          </div>
        </section>

        <script>
          document.addEventListener("DOMContentLoaded", () => {
            const video = document.getElementById("teaserVideo");
            const btn   = document.getElementById("unmuteBtn");

            // 1) autoplay muet 3 secondes après l’accès
            setTimeout(() => {
              video.play().catch(err => console.warn("Autoplay bloqué :", err));
            }, 3000);

            // 2) au clic/touch → activer le son
            const enableSound = () => {
              video.muted = false;      // activer le son
              video.volume = 1;         // volume max (ajuste si besoin)
              video.controls = true;    // montrer les contrôles (optionnel)
              btn.remove();             // retirer le bouton
              // relancer play() au cas où le navigateur le demande après unmute
              video.play().catch(() => {});
            };

            btn.addEventListener("click", enableSound);
            // Permet aussi un clic direct sur la vidéo pour activer le son :
            video.addEventListener("click", () => {
              if (video.muted) enableSound();
            });
          });
        </script>



        <section class="block">
          <h2 class="h-like">Stats Biome Monde (6 axes)</h2>
          <!-- Jauges simples (placeholders de maquette) -->
          <ul class="gauges" id="gauges-world">
            <li class="gauge"><span>Énergie</span><div class="bar"><i style="width:60%"></i></div><em>60</em></li>
            <li class="gauge"><span>Biodiversité</span><div class="bar"><i style="width:55%"></i></div><em>55</em></li>
            <li class="gauge"><span>Production</span><div class="bar"><i style="width:65%"></i></div><em>65</em></li>
            <li class="gauge"><span>Cohésion</span><div class="bar"><i style="width:50%"></i></div><em>50</em></li>
            <li class="gauge"><span>Résilience</span><div class="bar"><i style="width:52%"></i></div><em>52</em></li>
            <li class="gauge"><span>Technologie</span><div class="bar"><i style="width:58%"></i></div><em>58</em></li>
          </ul>
        </section>

        <section class="block">
          <h2 class="h-like">Principe du projet & du jeu</h2>
          <p>Texte explicatif plus long…</p>
        </section>

        <!-- Footer : liens ouvrant des modaux -->
        <footer class="site-footer">
          <button data-open="about" class="linklike">À propos</button>
          <button data-open="legal" class="linklike">CGU • Mentions légales</button>
          <a href="https://instagram.com" target="_blank" rel="noreferrer">Instagram</a>
        </footer>
      </div>
    </section>

    <!-- =========================================================
         ONGLET EXPLORER
         ========================================================= -->
    <section id="tab-explorer" class="tab-panel" aria-labelledby="tab-explorer-btn">

      <!-- HERO vidéo "sticky" -->
      <div class="hero sticky">
        <video id="vid-explorer" class="hero-video" playsinline muted autoplay loop preload="metadata"
               poster="../public/assets/media/explorer-poster.jpg">
          <source src="../public/assets/media/explorer-hero.mp4" type="video/mp4" />
        </video>
        <div class="hero-overlay"></div>
        <h1 class="hero-title neon">BIOMES EXPLORER</h1>
      </div>

      <!-- Barre d’onglets (sous la vidéo) -->
      <div class="tabs-wrap">
        <nav class="tabs">
          <button class="tab" data-tab="univers">Univers</button>
          <button class="tab neon active" data-tab="explorer">Explorer</button>
        </nav>
        <div class="tabs-underline neon-line"></div>
      </div>

      <!-- Contenu de l’onglet Explorer -->
      <div class="section-content">
        <section class="block">
          <h2 class="h-like">Texte introductif</h2>
          <p class="lead">Entrée en matière côté joueur, indication de l’espace de jeu…</p>
        </section>

        <section class="block">
          <h2 class="h-like">Fenêtre du jeu</h2>

          <!-- (Optionnel) pré-connexion au domaine du jeu pour accélérer le 1er chargement -->
          <link rel="preconnect" href="https://biomeexploreronline.netlify.app" crossorigin>

          <div id="game-root" class="game-box" style="position:relative; min-height: 640px;">
            <iframe id="explorer-frame"
              src="<?= htmlspecialchars($gameUrl, ENT_QUOTES) ?>"
              title="Biome Explorer" loading="eager"
              style="border:0;width:100%;height:100%;display:block;border-radius:12px;background:#0b0f14;"
              allow="fullscreen; gamepad; clipboard-read; clipboard-write">
            </iframe>
            <noscript>
              <p>Le jeu nécessite JavaScript. Ouvrir directement :
                <a href="https://biomeexploreronline.netlify.app/" target="_blank" rel="noreferrer">
                  biomeexploreronline.netlify.app
                </a>
              </p>
            </noscript>
          </div>
        </section>


        <section class="block">
          <h2 class="h-like">Stats du joueur (6 axes)</h2>
          <!-- Jauges simples (placeholders de maquette) -->
          <ul class="gauges" id="gauges-player">
            <li class="gauge"><span>Énergie</span><div class="bar"><i style="width:50%"></i></div><em>50</em></li>
            <li class="gauge"><span>Biodiversité</span><div class="bar"><i style="width:50%"></i></div><em>50</em></li>
            <li class="gauge"><span>Production</span><div class="bar"><i style="width:50%"></i></div><em>50</em></li>
            <li class="gauge"><span>Cohésion</span><div class="bar"><i style="width:50%"></i></div><em>50</em></li>
            <li class="gauge"><span>Résilience</span><div class="bar"><i style="width:50%"></i></div><em>50</em></li>
            <li class="gauge"><span>Technologie</span><div class="bar"><i style="width:50%"></i></div><em>50</em></li>
          </ul>
        </section>

        <section class="block">
          <h2 class="h-like">Stats Biome Monde (rappel)</h2>
          <div class="world-mini">[ Rappel synthétique / bouton “actualiser” plus tard ]</div>
        </section>

        <!-- Footer : liens ouvrant des modaux -->
        <footer class="site-footer">
          <button data-open="about" class="linklike">À propos</button>
          <button data-open="legal" class="linklike">CGU • Mentions légales</button>
          <a href="https://instagram.com" target="_blank" rel="noreferrer">Instagram</a>
        </footer>
      </div>
    </section>
  </main>

  <!-- ====================== Modaux ====================== -->

  <!-- Auth (connexion / inscription) -->
  <dialog id="authModal" class="auth-modal">
    <form method="dialog" class="auth-card">
      <h3 class="neon">Connexion / Inscription</h3>
      <label>Nom (inscription)
        <input type="text" name="name" />
      </label>
      <label>Email
        <input type="email" name="email" required />
      </label>
      <label>Mot de passe
        <input type="password" name="password" required />
      </label>
      <div class="row">
        <button value="login" class="btn primary">Se connecter</button>
        <button value="register" class="btn ghost">Créer un compte</button>
        <button type="button" class="btn ghost" id="auth-cancel-btn">Annuler</button>
      </div>
      <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES) ?>">
    </form>
  </dialog>

  <!-- À propos -->
  <dialog id="aboutModal" class="legal-modal">
    <article class="legal-card">
      <header><h3 class="neon">À propos de Biome Univers</h3></header>
      <section>
        <p><strong>Biome Univers</strong> est un projet pédagogique mêlant
        sensibilisation environnementale et jeu web. Deux espaces :</p>
        <ul>
          <li><strong>Univers</strong> : présentation, teaser, indicateurs globaux (« Biome Monde »).</li>
          <li><strong>Explorer</strong> : le jeu <em>Biome Explorer</em> (Phaser) avec 6 axes suivis (Énergie, Biodiversité, Production, Cohésion, Résilience, Technologie).</li>
        </ul>
        <p>Technos prévues : PHP 8, HTML/CSS/JS, Phaser, MySQL/MariaDB, MongoDB (features sociales à venir).</p>
        <p><small>Statut : maquette fonctionnelle. Contenus/design amenés à évoluer.</small></p>
      </section>
      <section class="legal-aside">
        <p><strong>Contact</strong> : <a href="mailto:contact@biome-univers.local">contact@biome-univers.local</a></p>
      </section>
      <footer class="row end">
        <button class="btn ghost" data-close="about">Fermer</button>
      </footer>
    </article>
  </dialog>

  <!-- Mentions légales & CGU -->
  <dialog id="legalModal" class="legal-modal">
    <article class="legal-card">
      <header><h3 class="neon">Mentions légales & CGU</h3></header>

      <section>
        <h4>Éditeur</h4>
        <p>Projet pédagogique « Biome Univers » (non commercial). Responsable de publication : <em>(à compléter)</em> — 
           Email : <a href="mailto:contact@biome-univers.local">contact@biome-univers.local</a></p>
      </section>

      <section>
        <h4>Hébergement</h4>
        <p>Développement local : XAMPP (Apache/PHP/MySQL). En production : à compléter.</p>
      </section>

      <section>
        <h4>Propriété intellectuelle</h4>
        <p>Textes, maquettes, médias et codes sont protégés. Toute reproduction nécessite autorisation. 
           Les médias tiers appartiennent à leurs ayants droit.</p>
      </section>

      <section>
        <h4>Données personnelles</h4>
        <p>Pas de collecte sans consentement. Les données de compte/joueur ne servent qu’au fonctionnement du jeu, sans prospection.</p>
        <p>Demande d’accès/suppression : <a href="mailto:contact@biome-univers.local">contact@biome-univers.local</a></p>
      </section>

      <section>
        <h4>Cookies</h4>
        <p>Pas de cookies de tracking. Des cookies techniques de session pourront être utilisés pour la connexion.</p>
      </section>

      <section>
        <h4>Conditions d’utilisation</h4>
        <ul>
          <li>Accès libre à « Univers ». « Explorer » pourra nécessiter un compte.</li>
          <li>Interdit de nuire au service, à la sécurité, ou aux droits des tiers.</li>
          <li>Service fourni « en l’état » dans un cadre d’apprentissage ; pas de garantie de disponibilité.</li>
        </ul>
      </section>

      <section class="legal-aside">
        <p><small>Dernière mise à jour : <?= date('d/m/Y'); ?></small></p>
      </section>

      <footer class="row end">
        <button class="btn ghost" data-close="legal">Fermer</button>
      </footer>
    </article>
  </dialog>

  <!-- Expose CSRF pour la suite (auth/API) -->
  <script>window.__APP__ = { csrf: "<?= htmlspecialchars($csrf, ENT_QUOTES) ?>" };</script>

  <!-- JS global (gère switch d’onglets, vidéos, modaux, jauges démo) -->
  <script src="assets/js/app.js"></script>
</body>
</html>
