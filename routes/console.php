<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('teachers:sync-accounts', function () {
    $teachers = \App\Models\Teacher::whereNotNull('nip')->get();
    $count = 0;

    foreach ($teachers as $teacher) {
        $nip = trim($teacher->nip);
        if (empty($nip)) continue;

        $email = $nip . '@guru.smppresensipgri.sch.id';
        $user = $teacher->user_id ? \App\Models\User::find($teacher->user_id) : null;

        if (!$user) {
            $user = \App\Models\User::where('email', $email)->first();
            if (!$user) {
                $user = \App\Models\User::create([
                    'name' => $teacher->name,
                    'email' => $email,
                    'password' => \Illuminate\Support\Facades\Hash::make($nip),
                    'role' => 'guru',
                    'email_verified_at' => now(),
                ]);
            } else {
                $user->update([
                    'name' => $teacher->name,
                    'password' => \Illuminate\Support\Facades\Hash::make($nip),
                    'role' => 'guru',
                ]);
            }
            $teacher->update(['user_id' => $user->id]);
        } else {
            $user->update([
                'name' => $teacher->name,
                'password' => \Illuminate\Support\Facades\Hash::make($nip),
                'role' => 'guru',
            ]);
        }
        $count++;
    }

    $this->info("Berhasil menyinkronkan {$count} akun guru dengan password default NIP.");
})->purpose('Sinkronisasi akun user guru dengan password default ter-hash dari NIP');

