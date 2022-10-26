<?php

namespace App\Actions\Lenders;

use App\Actions\Contracts\Lenders\GetUser;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class GetUserAction implements GetUser
{
    /**
     * @param  int  $userId
     * @return array|Builder|Builder[]|Collection|Model|null
     */
    public function handle(int $userId): Model|Collection|Builder|array|null
    {
        return User::query()->find($userId);
    }
}
