<?php
declare(strict_types=1);
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
     */
    public function __construct(array $data)
    {
        $this->id           = $data['id'];
        $this->name         = $data['name'];
        $this->lastModified = Carbon::createFromFormat('Y-m-d\TH:i:sP', $data['last_modified_on']);
        $this->firstMonth   = Carbon::createFromFormat('Y-m-d', $data['first_month']);
        $this->lastMonth    = Carbon::createFromFormat('Y-m-d', $data['last_month']);
        $this->dateFormat   = $data['date_format']['format'];
        $this->currencyCode = $data['currency_format']['iso_code'];
    }

}