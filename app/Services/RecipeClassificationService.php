<?php
// app/Services/RecipeClassificationService.php

namespace App\Services;

use App\Models\Ingredient;
use App\Models\HealthCondition;
use App\Models\HealthConditionRestriction;
use App\Models\RecipeSuitability;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class RecipeClassificationService
{
    // 🔑 PENTING: Mapping ini akan Anda isi sendiri nanti
    protected array $ingredientMapping = [];
    protected array $ingredientAliases = [];
    protected array $healthRestrictions = [];

    /**
     * Normalisasi string untuk case-insensitive & konsistensi
     */
    protected function normalize(string $text): string
    {
        return Str::lower(Str::trim(preg_replace('/\s+/', ' ', $text)));
    }

    /**
     * Parse input bahan menjadi komponen terstruktur
     * Contoh: "500 gram daging sapi" → ['jumlah' => '500', 'satuan' => 'gram', 'nama' => 'daging sapi']
     */
    public function parseIngredient(string $input): array
    {
        $normalized = $this->normalize($input);
        
        // Regex untuk ekstrak jumlah, satuan, nama (case-insensitive)
        $pattern = '/^(\d+(?:\.\d+)?)\s*(gram|kg|ml|l|sendok|makan|sdt|sdm|siung|buah|lembar|batang|cangkir|pcs)?\s*(.+)$/i';
        
        if (preg_match($pattern, $normalized, $matches)) {
            // Normalisasi satuan ke bentuk standar
            $satuanMap = [
                'sendok makan' => 'sdm', 'sendok' => 'sdm', 'makan' => 'sdm',
                'sendok teh' => 'sdt', 'teh' => 'sdt',
                'kilogram' => 'kg', 'gram' => 'gram',
                'liter' => 'l', 'mililiter' => 'ml',
                'piece' => 'pcs', 'buah' => 'pcs'
            ];
            
            $satuan = $matches[2] ? $this->normalize($matches[2]) : null;
            $satuan = $satuanMap[$satuan] ?? $satuan;
            
            return [
                'jumlah' => $matches[1],
                'satuan' => $satuan,
                'nama' => $this->normalize($matches[3]),
            ];
        }

        // Fallback jika tidak match pola
        return [
            'jumlah' => null,
            'satuan' => null,
            'nama' => $normalized,
        ];
    }

    /**
     * Cari atau buat ingredient dengan case-insensitive matching + alias support
     */
    public function findOrCreateIngredient(string $normalizedNama): Ingredient
    {
        // 1. Cari di database (case-insensitive)
        $ingredient = Ingredient::whereRaw('LOWER(nama) = ?', [$normalizedNama])->first();
        
        if ($ingredient) {
            return $ingredient;
        }

        // 2. Cek alias mapping
        foreach ($this->ingredientAliases as $canonical => $aliases) {
            if (in_array($normalizedNama, array_map(fn($a) => $this->normalize($a), $aliases))) {
                // Cari canonical name di database
                $ingredient = Ingredient::whereRaw('LOWER(nama) = ?', [$canonical])->first();
                if ($ingredient) return $ingredient;
                
                // Jika belum ada, gunakan canonical untuk create
                $normalizedNama = $canonical;
                break;
            }
        }

        // 3. Tentukan kategori dari mapping
        $kategori = 'bumbu'; // Default fallback
        $subKategori = null;
        
        if (isset($this->ingredientMapping[$normalizedNama])) {
            $kategori = $this->ingredientMapping[$normalizedNama]['kategori'] ?? 'bumbu';
            $subKategori = $this->ingredientMapping[$normalizedNama]['sub_kategori'] ?? null;
        } else {
            // Fallback logic untuk kategori berdasarkan keyword
            $kategoriKeywords = [
                'protein Hewani' => ['daging', 'ayam', 'ikan', 'udang', 'telur', 'sapi', 'kambing', 'beef', 'chicken'],
                'protein Nabati' => ['tempe', 'tahu', 'kacang', 'kedelai', 'jamur'],
                'sayuran' => ['bayam', 'wortel', 'kangkung', 'bawang', 'tomat', 'selada', 'timun'],
                'buah-buahan' => ['pisang', 'apel', 'jeruk', 'mangga', 'alpukat'],
                'karbohidrat' => ['nasi', 'mie', 'kentang', 'ubi', 'roti', 'gandum'],
                'bumbu' => ['garam', 'gula', 'merica', 'ketumbar', 'jahe', 'bawang', 'cabe'],
                'lemak' => ['minyak', 'mentega', 'margarin', 'santan'],
            ];
            
            foreach ($kategoriKeywords as $kat => $keywords) {
                foreach ($keywords as $kw) {
                    if (str_contains($normalizedNama, $kw)) {
                        $kategori = $kat;
                        break 2;
                    }
                }
            }
        }

        // 4. Buat ingredient baru dengan format nama yang rapi
        return Ingredient::create([
            'nama' => ucwords(str_replace('_', ' ', $normalizedNama)),
            'kategori' => $kategori,
            'sub_kategori' => $subKategori,
        ]);
    }

    /**
     * Tentukan apakah bahan adalah bahan utama
     */
    public function isMainIngredient(string $normalizedNama): bool
    {
        $mainKeywords = [
            'daging', 'ayam', 'ikan', 'udang', 'tempe', 'tahu', 'nasi', 'mie', 
            'kentang', 'telur', 'sapi', 'kambing', 'beef', 'chicken'
        ];
        
        foreach ($mainKeywords as $keyword) {
            if (str_contains($normalizedNama, $keyword)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Generate recipe_suitability berdasarkan ingredients
     */
    public function generateRecipeSuitability(int $recipeId, array $ingredientIds): void
    {
        DB::transaction(function () use ($recipeId, $ingredientIds) {
            // Hapus data lama untuk resep ini
            RecipeSuitability::where('recipe_id', $recipeId)->delete();
            
            // Ambil semua kondisi kesehatan
            $healthConditions = HealthCondition::all();
            
            foreach ($healthConditions as $condition) {
                $isSuitable = true;
                $notes = [];
                
                // Cek setiap ingredient terhadap restriction
                foreach ($ingredientIds as $ingredientId) {
                    $restriction = HealthConditionRestriction::where([
                        'health_condition_id' => $condition->id,
                        'ingredient_id' => $ingredientId,
                    ])->first();
                    
                    if ($restriction) {
                        if ($restriction->severity === 'hindari') {
                            $isSuitable = false;
                            $ingredient = Ingredient::find($ingredientId);
                            $notes[] = "Mengandung {$ingredient->nama}: {$restriction->notes}";
                        } elseif ($restriction->severity === 'batasi') {
                            $ingredient = Ingredient::find($ingredientId);
                            $notes[] = "Perlu dibatasi: {$ingredient->nama} - {$restriction->notes}";
                        }
                    }
                }
                
                // Simpan hasil
                RecipeSuitability::create([
                    'recipe_id' => $recipeId,
                    'health_condition_id' => $condition->id,
                    'is_suitable' => $isSuitable,
                    'notes' => $notes ? implode('; ', $notes) : null,
                ]);
            }
        });
    }

    /**
     * Setter untuk mapping (bisa diisi dari config atau database nanti)
     */
    public function setIngredientMapping(array $mapping): self
    {
        $this->ingredientMapping = $mapping;
        return $this;
    }

    public function setIngredientAliases(array $aliases): self
    {
        $this->ingredientAliases = $aliases;
        return $this;
    }

    public function setHealthRestrictions(array $restrictions): self
    {
        $this->healthRestrictions = $restrictions;
        return $this;
    }
}