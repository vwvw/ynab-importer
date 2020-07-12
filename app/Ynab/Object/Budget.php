<?php

/**
 * Budget.php
 * Copyright (c) 2020 james@firefly-iii.org
 *
 * This file is part of the Firefly III YNAB importer
 * (https://github.com/firefly-iii/ynab-importer).
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

/**
 * Budget.php

 */

declare(strict_types=1);
/**
 * Budget.php

 */

namespace App\Ynab\Object;

use Carbon\Carbon;

/**
 * Class Budget
 */
class Budget
{
    /** @var string */
    public $currencyCode;
    /** @var string */
    public $dateFormat;
    /** @var Carbon */
    public $firstMonth;
    /** @var string */
    public $id;
    /** @var Carbon */
    public $lastModified;
    /** @var Carbon */
    public $lastMonth;
    /** @var string */
    public $name;

    /**
     * Budget constructor.
     *
     * @param array $data
     *
     * @return Budget
     */
    public static function fromArray(array $data): self
    {
        $model               = new self;
        $model->id           = $data['id'];
        $model->name         = $data['name'];
        $model->lastModified = Carbon::createFromFormat('Y-m-d\TH:i:sP', $data['last_modified_on']);
        $model->firstMonth   = Carbon::createFromFormat('Y-m-d', $data['first_month']);
        $model->lastMonth    = Carbon::createFromFormat('Y-m-d', $data['last_month']);
        $model->dateFormat   = $data['date_format']['format'];
        $model->currencyCode = $data['currency_format']['iso_code'];

        return $model;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'id'            => $this->id,
            'name'          => $this->name,
            'currency_code' => $this->currencyCode,
        ];
    }

}
