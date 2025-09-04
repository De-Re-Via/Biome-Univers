<?php
// Production config – InfinityFree
return [
    // MySQL
    'DB_HOST' => 'sql111.infinityfree.com',
    'DB_NAME' => 'if0_39846649_biomeunivers',
    'DB_USER' => 'if0_39846649',
    'DB_PASS' => 'TANK16jube',

    // HMAC shared secret (64 hex) – garde-le privé
    'SECRET' => '8a3f7e2c1d0b4a58c9e2f4076edb7c4a3e8d12f9b0c6a41f2d8e7c5a9b3d1f06',


    // URL de l’API (fichier PHP côté site)
    'API_URL' => 'https://biomeunivers.infinityfree.me/biome.php',

    // CORS (origines autorisées pour le jeu)
    'CORS_ALLOW_ORIGINS' => [
        'https://biomeexploreronline.netlify.app',
        'https://biomeunivers.infinityfree.me',
    ],
];
