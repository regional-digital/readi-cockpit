<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Models\Group;

class Groupmember extends Model
{
    use HasFactory;
    use SoftDeletes;

    public function group(): HasOne
    {
        return $this->hasOne(Group::class);
    }
}
