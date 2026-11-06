<?php

echo 'App TZ: ' . config('app.timezone') . "\n";
echo 'Now: ' . now() . "\n";
echo 'Today: ' . \Carbon\Carbon::today() . "\n";
echo "\n--- Presence Logs ---\n";

$logs = \App\Models\PresenceLog::orderBy('created_at')->get();
foreach ($logs as $l) {
    $emp = $l->employee;
    echo "{$emp->name} | {$l->status} | {$l->created_at}\n";
}
