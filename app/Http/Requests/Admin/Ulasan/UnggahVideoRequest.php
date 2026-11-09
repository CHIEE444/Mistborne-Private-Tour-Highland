<?php

namespace App\Http\Requests\Admin\Ulasan;

use App\Bantuan\BatasUnggah;
use App\Models\Pengaturan;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UnggahVideoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('ulasan')) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'video' => ['required', 'file', 'mimes:mp4,mov', 'max:'.($this->batasMb() * 1024)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'video.mimes' => 'Format video harus MP4 atau MOV.',
            'video.max' => 'Ukuran video maksimal '.$this->batasMb().' MB.',
            'video.uploaded' => 'Video ditolak server karena melebihi batas unggah PHP'
                .(($server = BatasUnggah::serverMb()) !== null ? " ({$server} MB)" : '')
                .'. Perkecil ukuran video, atau naikkan upload_max_filesize dan post_max_size pada PHP.',
        ];
    }

    private function batasMb(): int
    {
        return Pengaturan::current()->batas_video_mb;
    }
}
