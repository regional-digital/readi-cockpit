<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('app:transcriptionqueue')->everyTwoMinutes();