<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CompositionLevelsSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = now();
        
        $compositionLevels = [
            ['id' => 1, 'composition_id' => 1, 'level' => 3, 'board_state' => json_encode([]), 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'composition_id' => 1, 'level' => 4, 'board_state' => json_encode(['0-4' => ['items' => [], 'championId' => 'TFT16_Illaoi'], '0-5' => ['items' => [], 'championId' => 'TFT16_Shen'], '1-6' => ['items' => [], 'championId' => 'TFT16_XinZhao'], '3-6' => []]), 'created_at' => $now, 'updated_at' => $now],
            ['id' => 3, 'composition_id' => 1, 'level' => 5, 'board_state' => json_encode(['0-4' => ['items' => [], 'championId' => 'TFT16_Illaoi'], '0-5' => ['items' => [], 'championId' => 'TFT16_Shen'], '1-6' => ['items' => [], 'championId' => 'TFT16_Yasuo'], '3-4' => ['items' => [], 'championId' => 'TFT16_Tristana'], '3-6' => []]), 'created_at' => $now, 'updated_at' => $now],
            ['id' => 4, 'composition_id' => 1, 'level' => 6, 'board_state' => json_encode([]), 'created_at' => $now, 'updated_at' => $now],
            ['id' => 5, 'composition_id' => 1, 'level' => 7, 'board_state' => json_encode([]), 'created_at' => $now, 'updated_at' => $now],
            ['id' => 6, 'composition_id' => 1, 'level' => 8, 'board_state' => json_encode(['0-1' => ['items' => [], 'championId' => 'TFT16_Taric'], '0-2' => ['items' => [], 'championId' => 'TFT16_Shen'], '0-3' => ['items' => [], 'championId' => 'TFT16_Swain'], '0-4' => ['items' => [], 'championId' => 'TFT16_Wukong'], '0-6' => ['items' => [], 'championId' => 'TFT16_Sett'], '3-2' => ['items' => [], 'championId' => 'TFT16_Draven'], '3-4' => ['items' => [], 'championId' => 'TFT16_Ahri'], '3-6' => []]), 'created_at' => $now, 'updated_at' => $now],
            ['id' => 7, 'composition_id' => 1, 'level' => 9, 'board_state' => json_encode(['0-0' => ['items' => [], 'championId' => 'TFT16_Taric'], '0-1' => ['items' => [], 'championId' => 'TFT16_Shen'], '0-2' => ['items' => [], 'championId' => 'TFT16_Shyvana'], '0-3' => ['items' => [], 'championId' => 'TFT16_Swain'], '0-4' => ['items' => [], 'championId' => 'TFT16_Wukong'], '0-6' => ['items' => [], 'championId' => 'TFT16_Sett'], '3-2' => ['items' => [], 'championId' => 'TFT16_Kindred'], '3-4' => ['items' => [], 'championId' => 'TFT16_Ahri'], '3-6' => []]), 'created_at' => $now, 'updated_at' => $now],
            ['id' => 8, 'composition_id' => 1, 'level' => 10, 'board_state' => json_encode([]), 'created_at' => $now, 'updated_at' => $now],
            ['id' => 9, 'composition_id' => 2, 'level' => 3, 'board_state' => json_encode(['0-4' => ['items' => [], 'championId' => 'TFT16_Blitzcrank'], '1-3' => ['items' => [], 'championId' => 'TFT16_Briar'], '1-4' => []]), 'created_at' => $now, 'updated_at' => $now],
            ['id' => 10, 'composition_id' => 2, 'level' => 4, 'board_state' => json_encode([]), 'created_at' => $now, 'updated_at' => $now],
            ['id' => 11, 'composition_id' => 2, 'level' => 5, 'board_state' => json_encode([]), 'created_at' => $now, 'updated_at' => $now],
            ['id' => 12, 'composition_id' => 2, 'level' => 6, 'board_state' => json_encode([]), 'created_at' => $now, 'updated_at' => $now],
            ['id' => 13, 'composition_id' => 2, 'level' => 7, 'board_state' => json_encode([]), 'created_at' => $now, 'updated_at' => $now],
            ['id' => 14, 'composition_id' => 2, 'level' => 8, 'board_state' => json_encode(['0-0' => ['items' => [], 'championId' => 'TFT16_Briar'], '0-1' => ['items' => [], 'championId' => 'TFT16_ChoGath'], '0-2' => ['items' => [], 'championId' => 'TFT16_Shyvana'], '0-3' => ['items' => [], 'championId' => 'TFT16_Gangplank'], '0-4' => ['items' => [], 'championId' => 'TFT16_Aatrox'], '0-5' => ['items' => [], 'championId' => 'TFT16_Swain'], '0-6' => ['items' => [], 'championId' => 'TFT16_Ambessa'], '1-6' => []]), 'created_at' => $now, 'updated_at' => $now],
            ['id' => 15, 'composition_id' => 2, 'level' => 9, 'board_state' => json_encode(['0-0' => ['items' => [], 'championId' => 'TFT16_Briar'], '0-1' => ['items' => [], 'championId' => 'TFT16_ChoGath']]), 'created_at' => $now, 'updated_at' => $now],
            ['id' => 16, 'composition_id' => 2, 'level' => 10, 'board_state' => json_encode([]), 'created_at' => $now, 'updated_at' => $now],
            ['id' => 17, 'composition_id' => 3, 'level' => 3, 'board_state' => json_encode([]), 'created_at' => $now, 'updated_at' => $now],
            ['id' => 18, 'composition_id' => 3, 'level' => 4, 'board_state' => json_encode([]), 'created_at' => $now, 'updated_at' => $now],
            ['id' => 19, 'composition_id' => 3, 'level' => 5, 'board_state' => json_encode([]), 'created_at' => $now, 'updated_at' => $now],
            ['id' => 20, 'composition_id' => 3, 'level' => 6, 'board_state' => json_encode([]), 'created_at' => $now, 'updated_at' => $now],
            ['id' => 21, 'composition_id' => 3, 'level' => 7, 'board_state' => json_encode([]), 'created_at' => $now, 'updated_at' => $now],
            ['id' => 22, 'composition_id' => 3, 'level' => 8, 'board_state' => json_encode([]), 'created_at' => $now, 'updated_at' => $now],
            ['id' => 23, 'composition_id' => 3, 'level' => 9, 'board_state' => json_encode([]), 'created_at' => $now, 'updated_at' => $now],
            ['id' => 24, 'composition_id' => 3, 'level' => 10, 'board_state' => json_encode([]), 'created_at' => $now, 'updated_at' => $now],
            ['id' => 25, 'composition_id' => 4, 'level' => 3, 'board_state' => json_encode([]), 'created_at' => $now, 'updated_at' => $now],
            ['id' => 26, 'composition_id' => 4, 'level' => 4, 'board_state' => json_encode([]), 'created_at' => $now, 'updated_at' => $now],
            ['id' => 27, 'composition_id' => 4, 'level' => 5, 'board_state' => json_encode([]), 'created_at' => $now, 'updated_at' => $now],
            ['id' => 28, 'composition_id' => 4, 'level' => 6, 'board_state' => json_encode([]), 'created_at' => $now, 'updated_at' => $now],
            ['id' => 29, 'composition_id' => 4, 'level' => 7, 'board_state' => json_encode([]), 'created_at' => $now, 'updated_at' => $now],
            ['id' => 30, 'composition_id' => 4, 'level' => 8, 'board_state' => json_encode([]), 'created_at' => $now, 'updated_at' => $now],
            ['id' => 31, 'composition_id' => 4, 'level' => 9, 'board_state' => json_encode([]), 'created_at' => $now, 'updated_at' => $now],
            ['id' => 32, 'composition_id' => 4, 'level' => 10, 'board_state' => json_encode([]), 'created_at' => $now, 'updated_at' => $now],
            ['id' => 33, 'composition_id' => 5, 'level' => 3, 'board_state' => json_encode([]), 'created_at' => $now, 'updated_at' => $now],
            ['id' => 34, 'composition_id' => 5, 'level' => 4, 'board_state' => json_encode([]), 'created_at' => $now, 'updated_at' => $now],
            ['id' => 35, 'composition_id' => 5, 'level' => 5, 'board_state' => json_encode([]), 'created_at' => $now, 'updated_at' => $now],
            ['id' => 36, 'composition_id' => 5, 'level' => 6, 'board_state' => json_encode([]), 'created_at' => $now, 'updated_at' => $now],
            ['id' => 37, 'composition_id' => 5, 'level' => 7, 'board_state' => json_encode([]), 'created_at' => $now, 'updated_at' => $now],
            ['id' => 38, 'composition_id' => 5, 'level' => 8, 'board_state' => json_encode([]), 'created_at' => $now, 'updated_at' => $now],
            ['id' => 39, 'composition_id' => 5, 'level' => 9, 'board_state' => json_encode([]), 'created_at' => $now, 'updated_at' => $now],
            ['id' => 40, 'composition_id' => 5, 'level' => 10, 'board_state' => json_encode([]), 'created_at' => $now, 'updated_at' => $now],
            ['id' => 41, 'composition_id' => 6, 'level' => 3, 'board_state' => json_encode([]), 'created_at' => $now, 'updated_at' => $now],
            ['id' => 42, 'composition_id' => 6, 'level' => 4, 'board_state' => json_encode([]), 'created_at' => $now, 'updated_at' => $now],
            ['id' => 43, 'composition_id' => 6, 'level' => 5, 'board_state' => json_encode([]), 'created_at' => $now, 'updated_at' => $now],
            ['id' => 44, 'composition_id' => 6, 'level' => 6, 'board_state' => json_encode([]), 'created_at' => $now, 'updated_at' => $now],
            ['id' => 45, 'composition_id' => 6, 'level' => 7, 'board_state' => json_encode([]), 'created_at' => $now, 'updated_at' => $now],
            ['id' => 46, 'composition_id' => 6, 'level' => 8, 'board_state' => json_encode([]), 'created_at' => $now, 'updated_at' => $now],
            ['id' => 47, 'composition_id' => 6, 'level' => 9, 'board_state' => json_encode([]), 'created_at' => $now, 'updated_at' => $now],
            ['id' => 48, 'composition_id' => 6, 'level' => 10, 'board_state' => json_encode([]), 'created_at' => $now, 'updated_at' => $now],
            ['id' => 49, 'composition_id' => 7, 'level' => 3, 'board_state' => json_encode([]), 'created_at' => $now, 'updated_at' => $now],
            ['id' => 50, 'composition_id' => 7, 'level' => 4, 'board_state' => json_encode([]), 'created_at' => $now, 'updated_at' => $now],
            ['id' => 51, 'composition_id' => 7, 'level' => 5, 'board_state' => json_encode([]), 'created_at' => $now, 'updated_at' => $now],
            ['id' => 52, 'composition_id' => 7, 'level' => 6, 'board_state' => json_encode([]), 'created_at' => $now, 'updated_at' => $now],
            ['id' => 53, 'composition_id' => 7, 'level' => 7, 'board_state' => json_encode([]), 'created_at' => $now, 'updated_at' => $now],
            ['id' => 54, 'composition_id' => 7, 'level' => 8, 'board_state' => json_encode([]), 'created_at' => $now, 'updated_at' => $now],
            ['id' => 55, 'composition_id' => 7, 'level' => 9, 'board_state' => json_encode([]), 'created_at' => $now, 'updated_at' => $now],
            ['id' => 56, 'composition_id' => 7, 'level' => 10, 'board_state' => json_encode([]), 'created_at' => $now, 'updated_at' => $now],
            ['id' => 57, 'composition_id' => 8, 'level' => 3, 'board_state' => json_encode([]), 'created_at' => $now, 'updated_at' => $now],
            ['id' => 58, 'composition_id' => 8, 'level' => 4, 'board_state' => json_encode([]), 'created_at' => $now, 'updated_at' => $now],
            ['id' => 59, 'composition_id' => 8, 'level' => 5, 'board_state' => json_encode([]), 'created_at' => $now, 'updated_at' => $now],
            ['id' => 60, 'composition_id' => 8, 'level' => 6, 'board_state' => json_encode([]), 'created_at' => $now, 'updated_at' => $now],
            ['id' => 61, 'composition_id' => 8, 'level' => 7, 'board_state' => json_encode([]), 'created_at' => $now, 'updated_at' => $now],
            ['id' => 62, 'composition_id' => 8, 'level' => 8, 'board_state' => json_encode([]), 'created_at' => $now, 'updated_at' => $now],
            ['id' => 63, 'composition_id' => 8, 'level' => 9, 'board_state' => json_encode([]), 'created_at' => $now, 'updated_at' => $now],
            ['id' => 64, 'composition_id' => 8, 'level' => 10, 'board_state' => json_encode([]), 'created_at' => $now, 'updated_at' => $now],
            ['id' => 65, 'composition_id' => 9, 'level' => 3, 'board_state' => json_encode([]), 'created_at' => $now, 'updated_at' => $now],
            ['id' => 66, 'composition_id' => 9, 'level' => 4, 'board_state' => json_encode([]), 'created_at' => $now, 'updated_at' => $now],
            ['id' => 67, 'composition_id' => 9, 'level' => 5, 'board_state' => json_encode([]), 'created_at' => $now, 'updated_at' => $now],
            ['id' => 68, 'composition_id' => 9, 'level' => 6, 'board_state' => json_encode([]), 'created_at' => $now, 'updated_at' => $now],
            ['id' => 69, 'composition_id' => 9, 'level' => 7, 'board_state' => json_encode([]), 'created_at' => $now, 'updated_at' => $now],
            ['id' => 70, 'composition_id' => 9, 'level' => 8, 'board_state' => json_encode([]), 'created_at' => $now, 'updated_at' => $now],
            ['id' => 71, 'composition_id' => 9, 'level' => 9, 'board_state' => json_encode([]), 'created_at' => $now, 'updated_at' => $now],
            ['id' => 72, 'composition_id' => 9, 'level' => 10, 'board_state' => json_encode([]), 'created_at' => $now, 'updated_at' => $now],
            ['id' => 73, 'composition_id' => 10, 'level' => 3, 'board_state' => json_encode([]), 'created_at' => $now, 'updated_at' => $now],
            ['id' => 74, 'composition_id' => 10, 'level' => 4, 'board_state' => json_encode([]), 'created_at' => $now, 'updated_at' => $now],
            ['id' => 75, 'composition_id' => 10, 'level' => 5, 'board_state' => json_encode([]), 'created_at' => $now, 'updated_at' => $now],
            ['id' => 76, 'composition_id' => 10, 'level' => 6, 'board_state' => json_encode([]), 'created_at' => $now, 'updated_at' => $now],
            ['id' => 77, 'composition_id' => 10, 'level' => 7, 'board_state' => json_encode([]), 'created_at' => $now, 'updated_at' => $now],
            ['id' => 78, 'composition_id' => 10, 'level' => 8, 'board_state' => json_encode([]), 'created_at' => $now, 'updated_at' => $now],
            ['id' => 79, 'composition_id' => 10, 'level' => 9, 'board_state' => json_encode([]), 'created_at' => $now, 'updated_at' => $now],
            ['id' => 80, 'composition_id' => 10, 'level' => 10, 'board_state' => json_encode([]), 'created_at' => $now, 'updated_at' => $now],
            ['id' => 81, 'composition_id' => 11, 'level' => 3, 'board_state' => json_encode([]), 'created_at' => $now, 'updated_at' => $now],
            ['id' => 82, 'composition_id' => 11, 'level' => 4, 'board_state' => json_encode([]), 'created_at' => $now, 'updated_at' => $now],
            ['id' => 83, 'composition_id' => 11, 'level' => 5, 'board_state' => json_encode([]), 'created_at' => $now, 'updated_at' => $now],
            ['id' => 84, 'composition_id' => 11, 'level' => 6, 'board_state' => json_encode([]), 'created_at' => $now, 'updated_at' => $now],
            ['id' => 85, 'composition_id' => 11, 'level' => 7, 'board_state' => json_encode([]), 'created_at' => $now, 'updated_at' => $now],
            ['id' => 86, 'composition_id' => 11, 'level' => 8, 'board_state' => json_encode([]), 'created_at' => $now, 'updated_at' => $now],
            ['id' => 87, 'composition_id' => 11, 'level' => 9, 'board_state' => json_encode([]), 'created_at' => $now, 'updated_at' => $now],
            ['id' => 88, 'composition_id' => 11, 'level' => 10, 'board_state' => json_encode([]), 'created_at' => $now, 'updated_at' => $now],
            ['id' => 89, 'composition_id' => 12, 'level' => 3, 'board_state' => json_encode([]), 'created_at' => $now, 'updated_at' => $now],
            ['id' => 90, 'composition_id' => 12, 'level' => 4, 'board_state' => json_encode([]), 'created_at' => $now, 'updated_at' => $now],
            ['id' => 91, 'composition_id' => 12, 'level' => 5, 'board_state' => json_encode([]), 'created_at' => $now, 'updated_at' => $now],
            ['id' => 92, 'composition_id' => 12, 'level' => 6, 'board_state' => json_encode([]), 'created_at' => $now, 'updated_at' => $now],
            ['id' => 93, 'composition_id' => 12, 'level' => 7, 'board_state' => json_encode([]), 'created_at' => $now, 'updated_at' => $now],
            ['id' => 94, 'composition_id' => 12, 'level' => 8, 'board_state' => json_encode([]), 'created_at' => $now, 'updated_at' => $now],
            ['id' => 95, 'composition_id' => 12, 'level' => 9, 'board_state' => json_encode([]), 'created_at' => $now, 'updated_at' => $now],
            ['id' => 96, 'composition_id' => 12, 'level' => 10, 'board_state' => json_encode([]), 'created_at' => $now, 'updated_at' => $now],
        ];

        DB::table('composition_levels')->insert($compositionLevels);
    }
}
