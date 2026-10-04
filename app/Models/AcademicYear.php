<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AcademicYear extends Model
{
    protected $fillable = ['name', 'semester', 'start_date', 'end_date', 'is_active'];
    protected $casts = ['is_active' => 'boolean', 'start_date' => 'date', 'end_date' => 'date'];

    public function schoolClasses(): HasMany
    {
        return $this->hasMany(SchoolClass::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public static function getActive(): ?self
    {
        return Cache::remember('academic_year_active', 3600, function () {
            return self::where('is_active', true)->first();
        });
    }

    public function makeActive(): void
    {
        // Satu transaksi: hanya boleh ada SATU tahun ajaran aktif, dan tidak
        // pernah berakhir nol (atau dua) yang aktif — bila ada langkah yang
        // gagal, seluruh perubahan dibatalkan dan status lama tetap utuh.
        DB::transaction(function () {
            self::query()->update(['is_active' => false]);

            // refresh() wajib: atribut di memori masih bisa bernilai "true",
            // sehingga update() berikutnya dianggap tidak ada perubahan dan
            // tidak pernah menulis apa pun (berakhir nol yang aktif).
            $this->refresh();
            $this->update(['is_active' => true]);
        });

        // Dihapus SETELAH commit supaya Dashboard, Rekap, dan Presensi segera
        // membaca tahun ajaran aktif yang baru, bukan cache lama (TTL 1 jam).
        self::clearActiveCache();
    }

    public static function clearActiveCache(): void
    {
        Cache::forget('academic_year_active');
    }

    protected static function booted(): void
    {
        static::updated(function ($model) {
            if ($model->is_active) {
                self::clearActiveCache();
            }
        });
    }
}