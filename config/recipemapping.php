<?php
// config/recipe_mappings.php

return [
    'ingredients' => [
        // Contoh format:
        // 'daging sapi' => ['kategori' => 'protein Hewani', 'sub_kategori' => 'daging merah'],
        // 'ayam' => ['kategori' => 'protein Hewani', 'sub_kategori' => 'unggas'],
        // 'tempe' => ['kategori' => 'protein Nabati', 'sub_kategori' => 'kedelai'],
        // ... ISI SESUAI KEBUTUHAN ANDA
    ],
    
    'aliases' => [
        // Contoh:
        // 'daging sapi' => ['sapi', 'beef', 'daging merah', 'dageng sapi'],
        // 'bawang putih' => ['bwg putih', 'b.putih', 'bawang puteh'],
        // ... ISI SESUAI KEBUTUHAN ANDA
    ],
    
    'health_restrictions' => [
        // Format: 'kondisi_kesehatan' => ['bahan' => ['severity' => '...', 'notes' => '...']]
        // Contoh:
        // 'kolesterol tinggi' => [
        //     'daging sapi' => ['severity' => 'batasi', 'notes' => 'Kandungan lemak jenuh tinggi'],
        //     'udang' => ['severity' => 'hindari', 'notes' => 'Kolesterol sangat tinggi'],
        // ],
        // ... ISI SESUAI KEBUTUHAN ANDA
    ],
];