<?php

namespace App\Services\Traits;

use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

trait WithAutocommitDisabledTrait
{
    public function withAutocommitDisabled(callable $callback): bool
    {
        $connection = DB::connection();

        try {
            $connection->statement('SET AUTOCOMMIT = 0');
            $connection->statement('SET TRANSACTION ISOLATION LEVEL READ COMMITTED');

            $result = $callback($connection);

            $connection->statement('COMMIT');
            $connection->statement('SET AUTOCOMMIT = 1');

            return $result;

        } catch (Exception $e) {
            $connection->statement('ROLLBACK');
            $connection->statement('SET AUTOCOMMIT = 1');

            Log::error('Error in autocommit disabled operation', ['error' => $e->getMessage()]);

            return false;

        } finally {
            $connection->disconnect();
        }
    }
}
