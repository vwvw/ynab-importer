<?php
/**
 * Account.php
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

declare(strict_types=1);
/**
 * Account.php
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

/**
 * Class Account
 */
class Account
{
    /** @var int */
    public $balance;
    /** @var bool */
    public $deleted;
    /** @var string */
    public $id;
    /** @var string */
    public $name;
    /** @var string */
    public $transferPayeeId;
    /** @var string */
    public $type;

    /**
     * @param array $array
     *
     * @return Account
     */
    public static function fromArray(array $array): self
    {
        $model                  = new self;
        $model->id              = $array['id'];
        $model->name            = $array['name'];
        $model->type            = $array['type'];
        $model->balance         = $array['balance'];
        $model->transferPayeeId = $array['transfer_payee_id'];
        $model->deleted         = $array['deleted'];

        return $model;
    }
}