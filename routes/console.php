<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('mailadmin:collect')->everyMinute()->withoutOverlapping();
Schedule::command('mailadmin:aggregate')->everyMinute()->withoutOverlapping();
Schedule::command('mailadmin:queue-snapshot')->everyMinute()->withoutOverlapping();
Schedule::command('mailadmin:health-check')->everyFiveMinutes()->withoutOverlapping();
