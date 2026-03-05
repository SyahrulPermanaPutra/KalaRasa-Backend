<?php
/**
 * ProcessMessageRequest
 *
 * Form Request untuk endpoint POST /api/chatbot/message.
 * Memisahkan validasi dari ChatbotController agar controller tetap tipis.
 *
 * Aturan validasi diselaraskan dengan parameter yang dikonsumsi oleh:
 *   - NlpService::analyze()
 *   - CbrService::match()
 *   - SessionService::cacheRecipes()
 */

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class ProcessMessageRequest extends FormRequest
{
    /**
     * Siapa yang boleh membuat request ini?
     * Sesuaikan dengan middleware auth yang dipakai di routes/api.php.
     */
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
            // Pesan yang dikirim pengguna – wajib ada, maks 1000 karakter
            'message'    => ['required', 'string', 'min:1', 'max:1000'],

            // Session ID chatbot – wajib ada, format UUID atau string pendek
            'session_id' => ['required', 'string', 'max:128'],

            // User ID – opsional (guest tidak punya), harus integer positif jika ada
            'user_id'    => ['nullable', 'integer', 'min:1'],

            // Bahasa pesan – opsional, default 'id' (Indonesia)
            'locale'     => ['nullable', 'string', 'in:id,en'],
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Custom messages
    // ─────────────────────────────────────────────────────────────────────────

    public function messages(): array
    {
        return [
            'message.required'    => 'Pesan tidak boleh kosong.',
            'message.max'         => 'Pesan terlalu panjang (maksimal 1000 karakter).',
            'session_id.required' => 'Session ID wajib disertakan.',
            'session_id.max'      => 'Session ID tidak valid (terlalu panjang).',
            'user_id.integer'     => 'User ID harus berupa angka.',
            'user_id.min'         => 'User ID tidak valid.',
            'locale.in'           => 'Bahasa tidak didukung. Gunakan "id" atau "en".',
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers – akses terstruktur dari controller
    // ─────────────────────────────────────────────────────────────────────────

    public function getMessage(): string
    {
        return trim($this->input('message'));
    }

    public function getSessionId(): string
    {
        return $this->input('session_id');
    }

    public function getUserId(): int
    {
        // Fallback ke 0 jika guest (tidak login)
        return (int) $this->input('user_id', 0);
    }

    public function getLocale(): string
    {
        return $this->input('locale', 'id');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Error response – kembalikan JSON agar konsisten dengan API
    // ─────────────────────────────────────────────────────────────────────────

    protected function failedValidation(Validator $validator): never
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Permintaan tidak valid.',
                'errors'  => $validator->errors(),
            ], 422)
        );
    }
}