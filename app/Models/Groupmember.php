<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Group;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use App\Observers\GroupmemberObserver;

#[ObservedBy([GroupmemberObserver::class])]
class Groupmember extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        "email",
        "waitingforjoin",
        "tobeinkeycloak",
        "tobeinmailinglist"
    ];

    public function group(): BelongsTo
    {
        return $this->BelongsTo(Group::class);
    }
}
