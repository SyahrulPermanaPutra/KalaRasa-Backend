<?php
/**
 * DirectSearchRequest
 *
 * Form Request untuk endpoint POST /api/chatbot/search (pencarian nama resep langsung).
 * Dipakai oleh ChatbotController saat intent == 'cari_resep' dengan nama eksplisit,
 * atau saat user menekan tombol "Cari Resep" di frontend tanpa melalui flow NLP penuh.
 *
 * Perbedaan dengan ProcessMessageRequest:
 *   - Tidak butuh 'message' bebas
 *   - Parameter lebih terstruktur (nama, kondisi, waktu)
 *   - Dipetakan langsung ke CbrService::searchByName()
 */

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class DirectSearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Rules
    // ─────────────────────────────────────────────────────────────────────────

    public function rules(): array
    {
        return [
            // Nama resep yang dicari – wajib, maks 200 karakter
            'search_name'       => ['required', 'string', 'min:2', 'max:200'],

            // Session ID – wajib untuk tracking
            'session_id'        => ['required', 'string', 'max:128'],

            // User ID – opsional
            'user_id'           => ['nullable', 'integer', 'min:1'],

            // Jumlah hasil yang diminta – opsional, default 10, maks 30
            'top_k'             => ['nullable', 'integer', 'min:1', 'max:30'],

            // Filter kondisi kesehatan – array string, opsional
            'health_conditions' => ['nullable', 'array'],
            'health_conditions.*' => ['string', 'max:100'],

            // Filter waktu masak (menit) – opsional, harus integer positif
            'time_constraint'   => ['nullable', 'integer', 'min:1', 'max:720'],
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Custom messages
    // ─────────────────────────────────────────────────────────────────────────

    public function messages(): array
    {
        return [
            'search_name.required'   => 'Nama resep yang dicari wajib diisi.',
            'search_name.min'        => 'Nama resep terlalu pendek (minimal 2 karakter).',
            'search_name.max'        => 'Nama resep terlalu panjang (maksimal 200 karakter).',
            'session_id.required'    => 'Session ID wajib disertakan.',
            'top_k.integer'          => 'Jumlah hasil harus berupa angka.',
            'top_k.max'              => 'Maksimal 30 hasil per pencarian.',
            'health_conditions.array' => 'Format kondisi kesehatan tidak valid.',
            'time_constraint.integer' => 'Waktu masak harus berupa angka (menit).',
            'time_constraint.max'     => 'Waktu masak maksimal 720 menit (12 jam).',
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────────

    public function getSearchName(): string
    {
        return trim($this->input('search_name'));
    }

    public function getSessionId(): string
    {
        return $this->input('session_id');
    }

    public function getUserId(): int
    {
        return (int) $this->input('user_id', 0);
    }

    public function getTopK(): int
    {
        return (int) $this->input('top_k', 10);
    }

    public function getHealthConditions(): array
    {
        return $this->input('health_conditions', []);
    }

    public function getTimeConstraint(): ?int
    {
        $val = $this->input('time_constraint');
        return $val !== null ? (int) $val : null;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Error response
    // ─────────────────────────────────────────────────────────────────────────

    protected function failedValidation(Validator $validator): never
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Parameter pencarian tidak valid.',
                'errors'  => $validator->errors(),
            ], 422)
        );
    }
}