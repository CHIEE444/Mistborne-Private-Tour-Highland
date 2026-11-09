<?php

namespace App\Http\Controllers\Admin;

use App\Aksi\Ulasan\SetujuiUlasan;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Ulasan\UnggahVideoRequest;
use App\Models\Ulasan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class UlasanController extends Controller
{
    public function index(): View
    {
        $statusTab = request('status', 'menunggu');
        $arsip = $statusTab === 'arsip';

        $query = Ulasan::query()
            ->with('pesanan.perjalanan')
            ->when($arsip, fn ($q) => $q->onlyTrashed())
            ->when(! $arsip && $statusTab === 'menunggu', fn ($q) => $q->where('disetujui', false))
            ->when(! $arsip && $statusTab === 'disetujui', fn ($q) => $q->where('disetujui', true))
            ->when(request('cari'), function ($q, $cari) {
                $q->whereHas('pesanan', function ($q) use ($cari): void {
                    $q->whereLike('nama_lengkap', "%{$cari}%")->orWhereLike('kode', "%{$cari}%");
                });
            })
            ->latest('created_at');

        return view('admin.ulasan.indeks', [
            'daftar' => $query->paginate(10)->withQueryString(),
            'jumlahMenunggu' => Ulasan::query()->where('disetujui', false)->count(),
            'jumlahDisetujui' => Ulasan::query()->where('disetujui', true)->count(),
            'jumlahArsip' => Ulasan::onlyTrashed()->count(),
        ]);
    }

    public function setujui(Ulasan $ulasan, SetujuiUlasan $aksi): RedirectResponse
    {
        $this->authorize('update', $ulasan);

        $aksi->handle($ulasan);

        return back()->with('sukses', 'Ulasan disetujui dan tampil di situs publik.');
    }

    public function unggahVideo(UnggahVideoRequest $request, Ulasan $ulasan): RedirectResponse
    {
        if ($ulasan->video_path) {
            Storage::disk('public')->delete($ulasan->video_path);
        }

        $path = $request->file('video')->store('ulasan/video', 'public');
        $ulasan->update(['video_path' => $path]);

        return back()->with('sukses', 'Video testimoni berhasil diunggah.');
    }

    public function hapusVideo(Ulasan $ulasan): RedirectResponse
    {
        $this->authorize('update', $ulasan);

        if ($ulasan->video_path) {
            Storage::disk('public')->delete($ulasan->video_path);
            $ulasan->update(['video_path' => null]);
        }

        return back()->with('sukses', 'Video testimoni dihapus.');
    }

    public function tolak(Ulasan $ulasan): RedirectResponse
    {
        $this->authorize('delete', $ulasan);

        $ulasan->delete();

        return redirect()->route('admin.ulasan.indeks')->with('sukses', 'Ulasan disembunyikan.');
    }

    public function pulihkan(int $id): RedirectResponse
    {
        $ulasan = Ulasan::onlyTrashed()->findOrFail($id);
        $this->authorize('restore', $ulasan);

        $ulasan->restore();

        return back()->with('sukses', 'Ulasan dipulihkan.');
    }
}
