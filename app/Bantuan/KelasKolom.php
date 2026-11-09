<?php

namespace App\Bantuan;

use Illuminate\View\ComponentAttributeBag;

/**
 * Field formulir admin dibungkus x-admin.formulir.kolom: kelas grid (col-span-*, col-start-*) harus menempel
 * di pembungkus itu supaya field benar-benar melebar di grid, sedangkan kelas lain tetap di input.
 */
class KelasKolom
{
    /**
     * @return array{0: string, 1: ComponentAttributeBag}
     */
    public static function pisah(ComponentAttributeBag $atribut): array
    {
        $kelas = collect(preg_split('/\s+/', trim((string) $atribut->get('class'))))->filter();
        [$kelasGrid, $kelasInput] = $kelas->partition(fn (string $nama): bool => preg_match('/(^|:)col-(span|start|end)-/', $nama) === 1);

        $atributInput = $atribut->except('class');
        if ($kelasInput->isNotEmpty()) {
            $atributInput = $atributInput->merge(['class' => $kelasInput->implode(' ')]);
        }

        return [$kelasGrid->implode(' '), $atributInput];
    }
}
