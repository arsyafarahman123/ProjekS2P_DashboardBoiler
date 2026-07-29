<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RlaDocument extends Model
{
    protected $fillable = ['unit', 'tanggal', 'nama_file', 'path'];

    protected $casts = [
        'tanggal' => 'date',
    ];

    /**
     * Check apakah file ini adalah gambar (png/jpg/jpeg).
     */
    public function isImage(): bool
    {
        $ext = strtolower(pathinfo($this->nama_file, PATHINFO_EXTENSION));
        return in_array($ext, ['png', 'jpg', 'jpeg'], true);
    }

    /**
     * URL untuk menampilkan file (inline view).
     * Menggunakan route permanen rla.file — tidak bergantung symlink storage.
     */
    public function fileUrl(): string
    {
        return route('input-data.rla.file', $this);
    }

    /**
     * URL download langsung (bypass controller finfo).
     */
    public function downloadUrl(): string
    {
        return route('input-data.rla.download', $this);
    }
}