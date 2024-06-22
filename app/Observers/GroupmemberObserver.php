<?php

namespace App\Observers;

use App\Models\Groupmember;
use App\KeycloakHelper;
use App\MailmanHelper;
use Illuminate\Support\Facades\Mail;
use App\Mail\UserWaitingForJoin;
use App\Mail\JoinApproved;
use App\Mail\JoinDeclined;

class GroupmemberObserver
{
}
