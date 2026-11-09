<?php

namespace Tests\Unit\Bantuan;

use App\Bantuan\BatasUnggah;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class BatasUnggahTest extends TestCase
{
    /** @return array<string, array{0: string, 1: string, 2: ?int}> */
    public static function batasServer(): array
    {
        return [
            'upload lebih kecil' => ['2M', '8M', 2],
            'post lebih kecil' => ['128M', '20M', 20],
            'satuan gigabyte' => ['1G', '2G', 1024],
            'dibulatkan ke bawah' => ['1536K', '8M', 1],
            'upload tanpa batas' => ['0', '8M', 8],
            'keduanya tanpa batas' => ['0', '0', null],
        ];
    }

    #[DataProvider('batasServer')]
    public function test_batas_server_adalah_nilai_terkecil_yang_bukan_nol(string $upload, string $post, ?int $diharapkan): void
    {
        $this->assertSame($diharapkan, BatasUnggah::serverMb($upload, $post));
    }

    public function test_batas_efektif_adalah_yang_terkecil_antara_pengaturan_dan_server(): void
    {
        $this->assertSame(2, BatasUnggah::efektifMb(100, '2M', '8M'));
        $this->assertSame(100, BatasUnggah::efektifMb(100, '128M', '130M'));
        $this->assertSame(50, BatasUnggah::efektifMb(50, '0', '0'));
    }

    public function test_tanpa_argumen_membaca_pengaturan_php_yang_aktif(): void
    {
        $this->assertSame(
            BatasUnggah::serverMb(ini_get('upload_max_filesize'), ini_get('post_max_size')),
            BatasUnggah::serverMb(),
        );
    }
}
