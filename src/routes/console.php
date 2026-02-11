<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('analytics:aggregate-daily')->hourly();
