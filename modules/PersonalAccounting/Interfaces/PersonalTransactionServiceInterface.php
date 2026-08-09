<?php

namespace Modules\PersonalAccounting\Interfaces;

use Modules\PersonalAccounting\Models\PersonalTransaction;

interface PersonalTransactionServiceInterface
{
    public function createTransaction(array $data): PersonalTransaction;

    public function updateTransaction(int $id, array $data): PersonalTransaction;

    public function deleteTransaction(int $id): void;
}
