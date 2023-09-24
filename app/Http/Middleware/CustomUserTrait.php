<?php

namespace App\Traits;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Model;

trait CustomUserTrait
{
    use Authenticatable;
    use Model;
}