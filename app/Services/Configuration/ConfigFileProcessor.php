<?php

declare(strict_types=1);

namespace App\Services\Configuration;

use App\Services\Storage\StorageService;
use Illuminate\Contracts\Filesystem\FileNotFoundException;

/**
 * Class ConfigFileProcessor.
 */
class ConfigFileProcessor
{
    /**
     * Input (the content of) a configuration file and this little script will convert it to a compatible array.
     *
     * @param string $fileName
     *
     * @throws FileNotFoundException
     * @return Configuration
     */
    public static function convertConfigFile(string $fileName): Configuration
    {
        app('log')->debug('Now in ConfigFileProcessor::convertConfigFile');
        $content = StorageService::getContent($fileName);
        $json    = json_decode($content, true, 512, JSON_THROW_ON_ERROR);

        return Configuration::fromFile($json);
    }
}
