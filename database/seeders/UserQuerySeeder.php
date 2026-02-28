<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserQuerySeeder extends Seeder
{
    public function run(): void
    {
        // [id, query_text, intent, confidence, status, entities, created_at, updated_at]
        $data = [
            [1,  'Resep nasi goreng enak',        'search_recipe',         0.99, 'ok',            '{"device": "desktop", "category": "main", "query_time": "02:41:04"}',                                              '2026-02-07 13:17:04', '2026-02-07 13:31:04'],
            [2,  'Resep ayam bakar',               'search_recipe',         0.83, 'ok',            '{"device": "desktop", "category": "main", "language": "id", "query_time": "02:41:04"}',                            '2026-02-01 11:25:04', '2026-02-01 11:33:04'],
            [3,  'Resep rendang daging',           'search_recipe',         0.90, 'ok',            '{"device": "mobile", "category": "main", "language": "id", "query_time": "02:41:04"}',                             '2026-01-31 13:49:04', '2026-01-31 14:06:04'],
            [4,  'Resep ikan bakar',               'search_recipe',         0.85, 'ok',            '{"device": "desktop", "category": "main", "query_time": "02:41:04"}',                                              '2026-02-20 09:14:04', '2026-02-20 09:20:04'],
            [5,  'Resep sayur asem',               'search_recipe',         0.86, 'ok',            '{"device": "mobile", "category": "vegetable", "query_time": "02:41:04"}',                                          '2026-02-22 04:28:04', '2026-02-22 04:36:04'],
            [6,  'Resep gado-gado',                'search_recipe',         0.81, 'ok',            '{"device": "mobile", "category": "vegetable", "query_time": "02:41:04"}',                                          '2026-02-20 02:09:04', '2026-02-20 02:53:04'],
            [7,  'Resep pepes ikan',               'search_recipe',         0.82, 'ok',            '{"device": "mobile", "category": "fish", "language": "id", "query_time": "02:41:04"}',                             '2026-02-26 00:04:04', '2026-02-26 00:45:04'],
            [8,  'Resep mie goreng',               'search_recipe',         0.75, 'ok',            '{"category": "noodle", "language": "id", "query_time": "02:41:04"}',                                               '2026-02-06 03:10:04', '2026-02-06 03:58:04'],
            [9,  'Resep opor ayam',                'search_recipe',         0.80, 'ok',            '{"category": "main", "language": "id", "query_time": "02:41:04"}',                                                 '2026-02-06 15:36:04', '2026-02-06 16:31:04'],
            [10, 'Resep soto ayam',                'search_recipe',         0.98, 'ok',            '{"category": "soup", "query_time": "02:41:04"}',                                                                   '2026-01-31 15:47:04', '2026-01-31 16:38:04'],
            [11, 'Masakan untuk diabetes',         'health_recommendation', 0.95, 'ok',            '{"language": "id", "query_time": "02:41:04", "health_condition": "diabetes"}',                                     '2026-02-08 03:54:04', '2026-02-08 04:54:04'],
            [12, 'Makanan rendah kalori',          'health_recommendation', 0.98, 'ok',            '{"language": "id", "query_time": "02:41:04", "health_condition": "diet"}',                                         '2026-02-11 05:00:04', '2026-02-11 05:09:04'],
            [13, 'Masakan sehat untuk jantung',    'health_recommendation', 0.94, 'ok',            '{"language": "id", "query_time": "02:41:04", "health_condition": "jantung"}',                                      '2026-02-10 08:35:04', '2026-02-10 09:08:04'],
            [14, 'Makanan untuk ibu hamil',        'health_recommendation', 0.86, 'ok',            '{"device": "desktop", "language": "id", "query_time": "02:41:04", "health_condition": "pregnancy"}',              '2026-02-21 08:05:04', '2026-02-21 08:12:04'],
            [15, 'Masakan tanpa santan',           'dietary_restriction',   0.95, 'ok',            '{"device": "mobile", "language": "id", "query_time": "02:41:04", "dietary_restriction": "no_coconut"}',           '2026-02-16 03:40:04', '2026-02-16 03:49:04'],
            [16, 'Makanan untuk maag',             'health_recommendation', 0.77, 'ok',            '{"language": "id", "query_time": "02:41:04", "health_condition": "maag"}',                                         '2026-02-06 10:37:04', '2026-02-06 10:41:04'],
            [17, 'Masakan rendah garam',           'dietary_restriction',   0.88, 'ok',            '{"language": "id", "query_time": "02:41:04", "dietary_restriction": "low_sodium"}',                               '2026-02-22 15:14:04', '2026-02-22 15:17:04'],
            [18, 'Makanan untuk kolesterol',       'health_recommendation', 0.98, 'ok',            '{"device": "desktop", "query_time": "02:41:04", "health_condition": "kolesterol"}',                               '2026-01-30 23:34:04', '2026-01-31 00:13:04'],
            [19, 'Masakan untuk asam urat',        'health_recommendation', 0.89, 'ok',            '{"device": "desktop", "query_time": "02:41:04", "health_condition": "asam_urat"}',                                '2026-02-04 23:35:04', '2026-02-04 23:48:04'],
            [20, 'Makanan untuk hipertensi',       'health_recommendation', 0.77, 'ok',            '{"device": "desktop", "query_time": "02:41:04", "health_condition": "hipertensi"}',                               '2026-02-14 17:46:04', '2026-02-14 18:13:04'],
            [21, 'Resep apa ya yang enak?',        null,                    0.37, 'fallback',       '{"device": "desktop", "category": "general", "query_time": "02:41:04"}',                                          '2026-02-20 20:44:04', '2026-02-20 21:01:04'],
            [22, 'Bisa rekomendasi masakan?',      null,                    0.54, 'clarification',  '{"device": "mobile", "category": "general", "language": "id", "query_time": "02:41:04"}',                         '2026-02-05 05:37:04', '2026-02-05 05:54:04'],
            [23, 'Cari resep simple',              'search_recipe',         0.85, 'ok',            '{"query_time": "02:41:04"}',                                                                                       '2026-01-29 08:47:04', '2026-01-29 09:46:04'],
            [24, 'Resep masakan padang',           'search_recipe',         0.84, 'ok',            '{"device": "mobile", "region": "padang", "language": "id", "query_time": "02:41:04"}',                            '2026-02-26 19:19:04', '2026-02-26 19:47:04'],
            [25, 'Resep masakan jawa',             'search_recipe',         0.92, 'ok',            '{"device": "desktop", "region": "jawa", "query_time": "02:41:04"}',                                               '2026-02-12 03:36:04', '2026-02-12 03:52:04'],
            [26, 'Makanan cepat saji',             'search_recipe',         0.93, 'ok',            '{"language": "id", "query_time": "02:41:04"}',                                                                    '2026-02-20 14:36:04', '2026-02-20 15:33:04'],
            [27, 'Resep untuk 4 orang',            'search_recipe',         0.94, 'ok',            '{"device": "mobile", "query_time": "02:41:04"}',                                                                  '2026-02-15 07:27:04', '2026-02-15 07:37:04'],
            [28, 'Resep dengan bahan telur',       'ingredient_search',     0.78, 'ok',            '{"language": "id", "ingredient": "telur", "query_time": "02:41:04"}',                                             '2026-02-22 17:40:04', '2026-02-22 18:02:04'],
            [29, 'Resep tanpa msg',                'dietary_restriction',   0.78, 'ok',            '{"language": "id", "query_time": "02:41:04", "dietary_restriction": "no_msg"}',                                   '2026-02-04 08:58:04', '2026-02-04 09:23:04'],
            [30, 'Makanan untuk anak',             'search_recipe',         0.89, 'ok',            '{"device": "desktop", "query_time": "02:41:04"}',                                                                 '2026-02-25 06:51:04', '2026-02-25 07:40:04'],
        ];

        foreach ($data as [$id, $query, $intent, $conf, $status, $entities, $ca, $ua]) {
            DB::table('user_queries')->insert([
                'id'         => $id,
                'user_id'    => 1,
                'query_text' => $query,
                'intent'     => $intent,
                'confidence' => $conf,
                'status'     => $status,
                'entities'   => $entities,
                'created_at' => $ca,
                'updated_at' => $ua,
            ]);
        }
    }
}
