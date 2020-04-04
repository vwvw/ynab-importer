<?php
/**
 * Configuration.php
 * Copyright (c) 2020 james@firefly-iii.org.
 *
 * This file is part of the Firefly III bunq importer
 * (https://github.com/firefly-iii/bunq-importer).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace App\Services\Configuration;

use RuntimeException;

/**
 * Class Configuration.
 */
class Configuration
{
    /** @var int */
    public const VERSION = 1;
    /** @var int */
    private $version;


    /**
     * Configuration constructor.
     */
    private function __construct()
    {
        $this->version = self::VERSION;
    }

    /**
     * @param array $array
     *
     * @return static
     */
    public static function fromArray(array $array): self
    {
        $version         = $array['version'] ?? 1;
        $object          = new self;
        $object->version = $version;

        return $object;
    }

    /**
     * @param array $data
     *
     * @return $this
     */
    public static function fromFile(array $data): self
    {
        app('log')->debug('Now in Configuration::fromFile', $data);
        $version = $data['version'] ?? 1;
        if (1 === $version) {
            return self::fromDefaultFile($data);
        }
        throw new RuntimeException(sprintf('Configuration file version "%s" cannot be parsed.', $version));
    }

    /**
     * @param array $array
     *
     * @return $this
     */
    public static function fromRequest(array $array): self
    {
        $object          = new self;
        $object->version = self::VERSION;

        return $object;
    }

    /**
     * @param array $data
     *
     * @return static
     */
    private static function fromDefaultFile(array $data): self
    {
        $object          = new self;
        $object->version = $data['version'] ?? self::VERSION;

        return $object;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'version'           => $this->version,
        ];
    }
}
