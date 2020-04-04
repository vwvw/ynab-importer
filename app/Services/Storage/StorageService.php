<?php

declare(strict_types=1);

namespace App\Services\Storage;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Class StorageService.
 */
class StorageService
{
    /**
     * @param string $name
     *
     * @throws FileNotFoundException
     * @return string
     */
    public static function getContent(string $name): string
    {
        $disk = Storage::disk('uploads');
        if ($disk->exists($name)) {
            return $disk->get($name);
        }
        throw new RuntimeException(sprintf('No such file %s', $name));
    }

    /**
     * @param string $content
     *
     * @return string
     */
    public static function storeContent(string $content): string
    {
        $fileName = Str::random(20);
        $disk     = Storage::disk('uploads');
        $disk->put($fileName, $content);

        return $fileName;
    }
}
