<?php

namespace Tests\Unit\Bantuan;

use App\Bantuan\NomorWhatsApp;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class NomorWhatsAppTest extends TestCase
{
    /** @return array<string, array{0: string, 1: string}> */
    public static function nomorValid(): array
    {
        return [
            'lokal biasa' => ['081234567890', '081234567890'],
            'awalan 62' => ['6281234567890', '081234567890'],
            'awalan +62' => ['+6281234567890', '081234567890'],
            'spasi dan strip' => ['+62 812-3456-7890', '081234567890'],
            'titik dan kurung' => ['(0812) 3456.7890', '081234567890'],
            'spasi di tepi' => ['  081234567890  ', '081234567890'],
        ];
    }

    #[DataProvider('nomorValid')]
    public function test_merapikan_nomor_ke_format_lokal(string $masukan, string $diharapkan): void
    {
        $this->assertSame($diharapkan, NomorWhatsApp::lokal($masukan));
    }

    public function test_nomor_dengan_huruf_atau_simbol_lain_tidak_diubah_agar_validasi_menolaknya(): void
    {
        $this->assertSame('08123abc4567', NomorWhatsApp::lokal('08123abc4567'));
        $this->assertSame('0812#3456', NomorWhatsApp::lokal('0812#3456'));
    }

    public function test_kosong_atau_null_menjadi_string_kosong(): void
    {
        $this->assertSame('', NomorWhatsApp::lokal(null));
        $this->assertSame('', NomorWhatsApp::lokal('   '));
    }
}
