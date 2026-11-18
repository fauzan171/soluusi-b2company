<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    // add a UUID to the model
    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            // hanya jika ada kolom uuid
            $model->uuid = Str::orderedUuid();
        });
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'username',
        'email',
        'password',
        'firstname',
        'lastname',
        'gender',
        'role_id',
        'is_active',
        'must_change_password',
        'must_confirm_profile',
        'password_changed_at',
        'phone_number',
        'date_of_birth',
        'bank',
        'account_number',
        'regency_id'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'must_change_password' => 'boolean',
        'must_confirm_profile' => 'boolean',
        'password_changed_at' => 'datetime',
    ];

    /**
     * Get the own role of the user.
     */
    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Get the all consultant of the user.
     */
    public function consultants()
    {
        return $this->hasMany(Consultant::class);
    }

    /** B2B: klinik yang user ini admin/staff-nya. */
    public function klinikAdmins()
    {
        return $this->hasMany(\App\Models\Klinik\KlinikAdmin::class);
    }

    /** B2B: klinik pertama user (Partner Admin/Staff context). */
    public function klinik()
    {
        return $this->hasOneThrough(
            \App\Models\Klinik\Klinik::class,
            \App\Models\Klinik\KlinikAdmin::class,
            'user_id',   // FK on klinik_admins
            'id',        // FK on kliniks
            'id',        // local key users
            'klinik_id'  // local key klinik_admins
        );
    }

    /** CAP: perusahaan yang user ini HR admin/employee-nya. */
    public function perusahaanAdmins()
    {
        return $this->hasMany(\App\Models\Perusahaan\PerusahaanAdmin::class);
    }

    public function perusahaan()
    {
        return $this->hasOneThrough(
            \App\Models\Perusahaan\Perusahaan::class,
            \App\Models\Perusahaan\PerusahaanAdmin::class,
            'user_id',       // FK on perusahaan_admins
            'id',            // PK perusahaans
            'id',            // PK users
            'perusahaan_id'  // FK perusahaan_admins
        );
    }

    /** CAP: record karyawan (untuk employee login — quota/riwayat milik sendiri). */
    public function perusahaanEmployee()
    {
        return $this->hasOne(\App\Models\Perusahaan\PerusahaanEmployee::class);
    }

    /**
     * Get the primary consultant of the user.
     */
    public function consultant()
    {
        return $this->hasOne(Consultant::class);
    }

    /**
     * Get the all consultation session of the user.
     */
    public function consultationSessions()
    {
        return $this->hasMany(ConsultationSession::class)->with(['voucher', 'consultantPackage']);
    }

    /**
     * Get the all consultant rating of the user.
     */
    public function consultantRatings()
    {
        return $this->hasMany(ConsultantRating::class);
    }

    public function socialAccounts()
    {
        return $this->hasMany(SocialAccount::class);
    }

    public function regency()
    {
        return $this->belongsTo(Regency::class);
    }

    /**
     * Get full name attribute
     */
    public function getFullNameAttribute(): string
    {
        return trim(($this->firstname ?? '') . ' ' . ($this->lastname ?? ''));
    }
}

