<?php

namespace App\Actions\Contracts\Lenders;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

interface GetUser
{
    /**
     * Update user.
     *
     * @param  int  $userId
     * @return Model|Collection|Builder|array|null $user
     */
    public function handle(int $userId): Model|Collection|Builder|array|null;
}
