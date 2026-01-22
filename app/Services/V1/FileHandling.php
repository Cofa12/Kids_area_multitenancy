<?php

namespace App\Services\V1;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use function PHPUnit\Framework\directoryExists;

class FileHandling
{
    public function upload(UploadedFile $file,string $directory):string
    {
        if (!File::exists('public/'.$directory)) {
            mkdir('public/'.$directory, 0777, true);
        }

        $imageExtension = $file->extension();
        $imageName = str_replace([' ', '.'], '', microtime()) . '.' . $imageExtension;

        $file->move(public_path($directory), $imageName);

        return $directory . '/' . $imageName;
    }

    public function delete($url):void
    {
        $fullPath = public_path($url);
        if (file_exists($fullPath)) {
            unlink($fullPath);
        }
    }
}
