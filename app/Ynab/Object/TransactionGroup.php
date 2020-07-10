<?php

/**
 * TransactionGroup.php
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
 * TransactionGroup.php

 */

declare(strict_types=1);
/**
 * TransactionGroup.php

 */

namespace App\Ynab\Object;

/**
 * Class TransactionGroup
 */
class TransactionGroup
{
    /** @var string */
    public $memo;
    /** @var string */
    public $transactionId;
    /** @var array */
    public $transactions;

    private function __construct()
    {
        $this->transactions = [];
    }

    /**
     * @param array $array
     *
     * @return static
     */
    public static function fromArray(array $array): self
    {
        $model = new self;

        if (0 === count($array['subtransactions'])) {
            // just one unsplit transaction:
            $transaction                        = new Transaction;
            $transaction->transactionId         = $array['id'];
            $transaction->date                  = $array['date'];
            $transaction->amount                = $array['amount'];
            $transaction->memo                  = $array['memo'];
            $transaction->accountId             = $array['account_id'];
            $transaction->accountName           = $array['account_name'];
            $transaction->payeeId               = $array['payee_id'];
            $transaction->payeeName             = $array['payee_name'];
            $transaction->categoryName          = $array['category_name'];
            $transaction->transferAccountId     = $array['transfer_account_id'];
            $transaction->transferTransactionId = $array['transfer_transaction_id'];

            $model->transactions[] = $transaction;

            return $model;
        }
        // multiple transactions:
        $model->transactionId = $array['id'];
        $model->memo          = $array['memo'];

        /** @var array $sub */
        foreach ($array['subtransactions'] as $sub) {
            $transaction                        = new Transaction;
            $transaction->date                  = $array['date'];
            $transaction->memo                  = $sub['memo'];
            $transaction->accountId             = $array['account_id'];
            $transaction->accountName           = $array['account_name'];
            $transaction->payeeId               = $sub['payee_id'];
            $transaction->payeeName             = $sub['payee_name'];
            $transaction->categoryName          = $sub['category_name'];
            $transaction->transferAccountId     = $sub['transfer_account_id'];
            $transaction->transferTransactionId = $sub['transfer_transaction_id'];
            $transaction->amount                = $sub['amount'];
            $transaction->transactionId         = $sub['transaction_id'];
            $model->transactions[]              = $transaction;
        }

        return $model;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        $return = [
            'transaction_id' => $this->transactionId,
            'memo'           => $this->memo,
            'transactions'   => [],
        ];
        /** @var Transaction $transaction */
        foreach ($this->transactions as $transaction) {
            $return['transactions'][] = $transaction->toArray();
        }

        return $return;
    }
}
