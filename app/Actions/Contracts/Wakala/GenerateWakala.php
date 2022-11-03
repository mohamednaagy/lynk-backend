<?php

namespace App\Actions\Contracts\Wakala;

use App\Actions\Contracts\HasMedia;

interface GenerateWakala
{
    public function handle(HasMedia $hasMedia);
}
