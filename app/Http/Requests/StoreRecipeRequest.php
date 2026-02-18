<?php
// app/Http/Requests/StoreRecipeRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRecipeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'nama' => 'required|string|max:255',
            'waktu_masak' => 'required|integer|min:1|max:1440',
            'region' => 'nullable|string|max:100',
            'deskripsi' => 'required|string',
            'kategori' => 'required|string|max:255',
            'gambar' => 'required|image|mimes:jpeg,png,jpg,gif|max:1024',
            'bahan_bahan' => 'required|array|min:1',
            'bahan_bahan.*.nama' => 'required|string|max:100',
            'bahan_bahan.*.jumlah' => 'required|numeric|min:0',
            'bahan_bahan.*.satuan' => 'required|string|max:50',
            'langkah_langkah' => 'nullable|array',
            'langkah_langkah.*' => 'required|string',
        ];
    }

     public function messages()
    {
        return [
            'gambar.required' => 'Gambar resep wajib diunggah.',
            'gambar.image' => 'File harus berupa gambar.',
            'gambar.max' => 'Ukuran gambar tidak boleh lebih dari 1MB.',
            'bahan_bahan.required' => 'Minimal satu bahan harus ditambahkan.',
            'bahan_bahan.*.nama.required' => 'Nama bahan wajib diisi.',
            'bahan_bahan.*.jumlah.required' => 'Jumlah bahan wajib diisi.',
            'bahan_bahan.*.satuan.required' => 'Satuan bahan wajib diisi.',
        ];
    }
}