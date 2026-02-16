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
            'gambar' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'kategori' => 'required|string|max:255',
            'status' => ['required', Rule::in(['pending', 'approved', 'rejected'])],
            'jumlah' => 'required|array|min:1',
            'satuan.*' => 'required|string|regex:/^[\d\s\w\.\-]+(gram|kg|ml|l|sendok|siung|buah|lembar|batang|cangkir|sdt|sdm|pcs)?.*$/i',
            'langkah_langkah' => 'nullable|array',
            'langkah_langkah.*' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'bahan_bahan.required' => 'Minimal harus ada 1 bahan',
            'bahan_bahan.*.regex' => 'Format bahan tidak valid. Contoh: "500 gram daging sapi"',
        ];
    }
}