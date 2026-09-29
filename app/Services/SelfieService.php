<?php

namespace App\Services;

use App\Models\Avanto;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class SelfieService
{
    public function store(Avanto $avanto, UploadedFile $file): string
    {
        $this->deleteExisting($avanto);

        $path = $file->store("avanto-selfies/{$avanto->user_id}", 'public');

        $avanto->update(['selfie_path' => $path]);

        return $path;
    }

    public function delete(Avanto $avanto): void
    {
        $this->deleteExisting($avanto);
        $avanto->update(['selfie_path' => null]);
    }

    public function url(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        return Storage::disk('public')->url($path);
    }

    private function deleteExisting(Avanto $avanto): void
    {
        if ($avanto->selfie_path) {
            Storage::disk('public')->delete($avanto->selfie_path);
        }
    }
}
