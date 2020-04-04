<?php
declare(strict_types=1);
/**
 * Transaction.php
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
 * Class Transaction
 */
class Transaction
{
    /** @var string */
    public $accountId;
    /** @var string */
    public $accountName;
    /** @var int */
    public $amount;
    /** @var string */
    public $categoryName;
    /** @var string */
    public $date;
    /** @var string */
    public $memo;
    /** @var string */
    public $payeeId;
    /** @var string */
    public $payeeName;
    /** @var string */
    public $transactionId;
    /** @var string */
    public $transferAccountId;
    /** @var string */
    public $transferTransactionId;

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'transaction_id'          => $this->transactionId,
            'date'                    => $this->date,
            'account_id'              => $this->accountId,
            'account_name'            => $this->accountName,
            'payee_id'                => $this->payeeId,
            'payee_name'              => $this->payeeName,
            'category_name'           => $this->categoryName,
            'transfer_account_id'     => $this->transferAccountId,
            'transfer_transaction_id' => $this->transferTransactionId,
            'amount'                  => $this->amount,
            'memo'                    => $this->memo,
        ];
    }

}