<?php

namespace Tests\Unit\Services;

use App\Services\TftPlannerCodeService;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class TftPlannerCodeServiceTest extends TestCase
{
    public function test_it_decodes_tft_planner_code(): void
    {
        $service = new TftPlannerCodeService;

        $this->assertSame([
            'TFT17_MasterYi',
            'TFT17_TahmKench',
            'TFT17_Fiora',
            'TFT17_Gragas',
            'TFT17_Maokai',
            'TFT17_Urgot',
            'TFT17_Belveth',
            'TFT17_Kindred',
        ], $service->decode('0200f03101e02401f02f04f013000000TFTSet17'));
    }

    public function test_it_encodes_tft_planner_code(): void
    {
        $service = new TftPlannerCodeService;

        $this->assertSame('0200f03101e02401f02f04f013000000TFTSet17', $service->encode([
            'TFT17_MasterYi',
            'TFT17_TahmKench',
            'TFT17_Fiora',
            'TFT17_Gragas',
            'TFT17_Maokai',
            'TFT17_Urgot',
            'TFT17_Belveth',
            'TFT17_Kindred',
        ]));
    }

    public function test_it_rejects_unmapped_champions_on_export(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new TftPlannerCodeService)->encode(['TFT17_Unknown']);
    }

    public function test_it_rejects_unsupported_sets_on_import(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new TftPlannerCodeService)->decode('0200f000000000000000000000000000TFTSet18');
    }
}
