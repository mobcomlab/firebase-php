<?php

namespace Firebase\Integration\Laravel\Auth;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Auth\Authenticatable as AuthenticatableTrait;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;

class Authenticatable extends Model implements AuthenticatableContract, AuthorizableContract
{
    use AuthenticatableTrait, Authorizable {
        getAuthPassword as legacyPassword;
    }

    public $incrementing = false;
    protected $table = "user";

    public function getAuthPassword()
    {
        return null;
    }

}
