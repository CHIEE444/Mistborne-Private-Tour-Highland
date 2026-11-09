<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Layanan\NotifikasiAdminLayanan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NotifikasiController extends Controller
{
    /** Dipanggil berkala oleh lonceng di header supaya angka notifikasi baru muncul tanpa muat ulang. */
    public function ringkasan(Request $request, NotifikasiAdminLayanan $notifikasi): JsonResponse
    {
        return response()->json($notifikasi->ringkasan($request->user()));
    }

    public function baca(Request $request, NotifikasiAdminLayanan $notifikasi, string $notification): RedirectResponse
    {
        $item = $request->user()->notifications()->whereKey($notification)->firstOrFail();
        $item->markAsRead();

        return redirect()->to($notifikasi->tautan($item));
    }

    public function bacaSemua(Request $request, NotifikasiAdminLayanan $notifikasi): JsonResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return response()->json($notifikasi->ringkasan($request->user()));
    }
}
