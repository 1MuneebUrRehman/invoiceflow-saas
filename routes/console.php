<?php

use App\Console\Commands\MarkOverdueInvoices;
use App\Console\Commands\SendInvoiceReminders;
use Illuminate\Support\Facades\Schedule;

// Run at midnight so overdue status is correct before reminders fire.
Schedule::command(MarkOverdueInvoices::class)->dailyAt('00:05');
Schedule::command(SendInvoiceReminders::class)->dailyAt('09:00');
