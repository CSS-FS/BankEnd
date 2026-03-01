<?php

namespace App\Models;

use App\Traits\HasMedia;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Models\FarmManager;
use App\Models\FarmStaff;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Lab404\Impersonate\Models\Impersonate;
use Laravel\Cashier\Billable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail
{
    use Billable;
    use HasApiTokens;
    use HasFactory;
    use HasMedia;
    use HasRoles;
    use Impersonate;
    use Notifiable;

    protected $dates = ['trial_ends_at'];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'is_active',
        'password_reset_required',
        'pricing_id',
        'trial_ends_at',
        'subscription_status',
        'stripe_customer_id',
        'stripe_subscription_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'password_reset_required' => 'boolean',
        ];
    }

    public function canImpersonate(): bool
    {
        return auth()->check()
            && auth()->user()->hasRole('admin');
    }

    public function farms(): HasMany
    {
        return $this->hasMany(Farm::class, 'owner_id');
    }

    public function managedFarms(): BelongsToMany
    {
        return $this->belongsToMany(Farm::class, 'farm_managers', 'manager_id', 'farm_id')
            ->using(FarmManager::class)
            ->withPivot('link_date');
    }

    public function staffFarms(): BelongsToMany
    {
        return $this->belongsToMany(Farm::class, 'farm_staff', 'worker_id', 'farm_id')
            ->using(FarmStaff::class)
            ->withPivot('link_date');
    }

    public function getFarmsCountAttribute()
    {
        $count = 0;

        if (! $this->relationLoaded('farms')) {
            $count += $this->farms()->count();
        } else {
            $count += $this->farms->count();
        }

        if (! $this->relationLoaded('managedFarms')) {
            $count += $this->managedFarms()->count();
        } else {
            $count += $this->managedFarms->count();
        }

        if (! $this->relationLoaded('staffFarms')) {
            $count += $this->staffFarms()->count();
        } else {
            $count += $this->staffFarms->count();
        }

        return $count;
    }

    public function getShedsCountAttribute()
    {
        if (! $this->relationLoaded('farms')) {
            return 0;
        }

        return $this->farms->sum('sheds_count');
    }

    public function getBirdsCountAttribute()
    {
        if (! $this->relationLoaded('farms')) {
            return 0;
        }

        return $this->farms->sum(function ($farm) {
            return $farm->sheds->sum(function ($shed) {
                return $shed->latestFlock->chicken_count;
            });
        });
    }

    public function settings(): HasOne
    {
        return $this->hasOne(UserSetting::class);
    }

    public function pricing()
    {
        return $this->belongsTo(Pricing::class);
    }

    public function paymentLogs()
    {
        return $this->hasMany(PaymentLog::class);
    }

    // Helper: Days left in trial
    public function trialDaysLeft()
    {
        return $this->trial_ends_at ? now()->diffInDays($this->trial_ends_at, false) : 0;
    }

    public function isOnTrial()
    {
        return $this->trial_ends_at && now()->lt($this->trial_ends_at);
    }

    public function activeSubscription()
    {
        return $this->subscriptions()->active()->first();
    }

    /**
     * Get the shortcuts for the user
     */
    public function shortcuts(): BelongsToMany
    {
        return $this->belongsToMany(Shortcut::class, 'shortcut_user')
            ->withTimestamps();
    }

    /**
     * Get admin shortcuts for the user (if user is admin)
     */
    public function adminShortcuts()
    {
        return $this->shortcuts()
            ->where('group', 'admin');
    }

    /**
     * Get user shortcuts for the user
     */
    public function userShortcuts()
    {
        return $this->shortcuts()
            ->where('group', 'user');
    }

    /**
     * Attach shortcuts to user based on their role
     */
    public function attachDefaultShortcuts()
    {
        $group = $this->is_admin ? 'admin' : 'user';

        $defaultShortcuts = Shortcut::where('group', $group)
            ->where('default', true)
            ->pluck('id')
            ->toArray();

        $this->shortcuts()
            ->syncWithoutDetaching($defaultShortcuts);
    }

    public function deviceTokens()
    {
        return $this->hasMany(DeviceToken::class);
    }
}
