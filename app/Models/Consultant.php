<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Carbon\Carbon;

class Consultant extends Model
{
    use HasFactory, SoftDeletes;

    // ponytail: uuid diisi saat creating — semua path (registrasi/admin/seed) tembus.
    // Tidak pakai trait HasUuids karena defaultnya menimpa PK (id auto-increment int).
    protected static function booted()
    {
        static::creating(function ($consultant) {
            if (empty($consultant->uuid)) {
                $consultant->uuid = (string) Str::uuid();
            }
        });
    }

    const ACCEPT = 'accept';
    const REJECT = 'reject';
    const PENDING = 'pending';
    const INTERVIEW = 'inteview';
    const PAYMENT = 'payment';

    protected $fillable = [
        'about',
        'profit_sharing',
        'user_id',
        'status',
        'uuid',
        'reject_reason',
        'decision_at',
        'interview_at',
        'interview_link',
        'b2c_consultant_id',
        'is_active',
        'photo_url',
        'headline',
        'profile',
    ];

    protected $dates = [
        'deleted_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'b2c_consultant_id' => 'integer',
        'profile' => 'array',
    ];

    public function getBlogPackageLabelAttribute(): string
    {
        if (!$this->blog_package || $this->blog_package === 'none') {
            return 'Belum Berlangganan';
        }
        return self::blogPackageConfig($this->blog_package)['name'] ?? ucfirst($this->blog_package);
    }

    public function getCanUseBlogAiAttribute(): bool
    {
        return self::blogPackageConfig($this->blog_package)['ai'] ?? false;
    }

    public function blogPostsQuotaUsed(): int
    {
        return (int) $this->blogPosts()->count();
    }

    public function blogQuotaExceeded(): bool
    {
        if (!$this->blog_package || $this->blog_package === 'none') {
            return true;
        }
        return $this->blogPostsQuotaUsed() >= (int) $this->blog_limit;
    }

    /**
     * Get the user that owns the Consultant
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all of the socialMedia for the Consultant
     */
    public function socialMedia()
    {
        return $this->belongsToMany(SocialMedia::class, ConsultantSocialMedia::class)->withPivot('url');
    }

    /**
     * Get the own of the category
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /** Exception ketersediaan (mission M3 / K7) — global semua perusahaan. */
    public function availabilityExceptions()
    {
        return $this->hasMany(\App\Models\ConsultantAvailabilityException::class);
    }

    /** Assignment pool tenant konsultan (mission M3). */
    public function pools()
    {
        return $this->hasMany(\App\Models\ConsultantPool::class);
    }

    /**
     * get All category of the consultant
     */
    public function categories()
    {
        return $this->belongsToMany(Category::class, ConsultantCategory::class, 'consultant_id', 'category_id');
    }

    /**
     * get the all company of the consultant
     */
    // public function companies()
    // {
    //     return $this->hasMany(Company::class);
    // }

    /**
     * get the all company of the consultant
     */
    public function consultantCompanies()
    {
        return $this->belongsToMany(Company::class, ConsultantCompany::class, 'consultant_id', 'company_id')
        ->withPivot('start_at', 'end_at', 'position', 'description')
        ->orderByRaw('end_at IS NULL DESC, end_at DESC');;
    }

    public function getExperienceAttribute()
    {
        $periods = $this->consultantCompanies->map(function ($company) {
            $start = Carbon::parse($company->pivot?->start_at);
            $end   = $company->pivot?->end_at 
                     ? Carbon::parse($company->pivot->end_at) 
                     : Carbon::now();
            return ['start' => $start, 'end' => $end];
        })->sortBy('start')->values();

        $merged = [];
        foreach ($periods as $period) {
            if (empty($merged)) {
                $merged[] = $period;
                continue;
            }

            $lastIndex = count($merged) - 1;
            $last = $merged[$lastIndex];

            if ($period['start'] <= $last['end']) {
                // overlap → extend end kalau lebih panjang
                if ($period['end'] > $last['end']) {
                    $merged[$lastIndex]['end'] = $period['end'];
                }
            } else {
                // tidak overlap → buat interval baru
                $merged[] = $period;
            }
        }

        // hitung total hari dari interval unik
        $totalDays = collect($merged)->sum(function ($range) {
            return $range['end']->diffInDays($range['start']);
        });

        return round($totalDays / 365, 0); // tahun, 1 angka desimal
    }

    /**
     * get the all schedule of the consultant
     */
    public function schedules()
    {
        return $this->hasMany(Schedule::class);
    }

    /**
     * get the All of packages for the Consultant
     */
    public function packages()
    {
        return $this->hasMany(ConsultantPackage::class);
    }

    /**
     * get the all certificate of the consultant
     */
    public function certificates()
    {
        return $this->hasMany(Certificate::class)->orderBy('year', 'desc');
    }

    /**
     * get all education of the consultant
     */
    public function educations()
    {
        return $this->hasMany(ConsultantEducation::class)
        ->orderByRaw('end_date IS NULL DESC, end_date DESC');
        // return $this->belongsToMany(Education::class, ConsultantEducation::class);
    }

    /**
     * get all of the educations of the consultant
     */
    public function consultantEducations()
    {
        return $this->belongsToMany(Education::class, ConsultantEducation::class, 'consultant_id', 'education_id')
        ->withPivot('departement_id', 'start_date', 'end_date', 'degree', 'description')
        ->orderByRaw('end_date IS NULL DESC, end_date DESC');

    }

    /**
     * get session of the consultant
     */
    public function sessions()
    {
        return $this->hasMany(ConsultationSession::class);
    }

    /**
     * get all category skills of the consultant
     */
    public function categorySkills()
    {
        return $this->hasMany(CategorySkill::class);
    }

    /**
     * get all category skills of the consultant
     */
    public function skills()
    {
        return $this->belongsToMany(Skill::class, CategorySkill::class, 'consultant_id', 'skill_id');
    }

    /**
     * get all of the rating for the consultant
     */
    public function ratings()
    {
        return $this->hasMany(ConsultantRating::class);
    }

    public function manualReviews()
    {
        // ponytail: B2B skip manual_reviews (Dev Prep §4.1 optional). Stub kosong
        // supaya caller B2C-compatible tidak fatal; ganti relation kalau tabel di-port.
        return $this->hasMany(ConsultationSession::class)->whereRaw('1 = 0');
    }

    // SEO CMS relationships (blogPosts/publishedBlogPosts/comments/pendingBlogPosts)
    // dihapus — Blog* models di-skip di B2B (Dev Prep §4.2).
    // scopeWithTotalSessions juga dihapus (refer manual_reviews table).

    /**
     * Get all subcategories for this consultant
     */
    public function subcategories()
    {
        return $this->belongsToMany(Subcategory::class, 'consultant_subcategories', 'consultant_id', 'subcategory_id');
    }

    /**
     * Get subcategories within a specific category
     */
    public function subcategoriesInCategory($categoryId)
    {
        return $this->subcategories()->where('category_id', $categoryId);
    }
}
