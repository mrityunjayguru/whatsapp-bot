<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MetaSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'app_id',
        'app_secret',
        'page_id',
        'page_access_token',
        'webhook_verify_token',
        'graph_api_version',
        'default_created_by',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function defaultUser()
    {
        return $this->belongsTo(User::class, 'default_created_by');
    }

    public static function forCompany(?int $companyId): ?self
    {
        if (!$companyId) {
            return null;
        }
        return self::where('company_id', $companyId)->first();
    }

    public function credential(string $key)
    {
        $settingValue = $this->getAttribute($key);

        if ($settingValue !== null && $settingValue !== '') {
            return $settingValue;
        }

        $envMap = [
            'app_id'            => config('services.meta.app_id'),
            'app_secret'        => config('services.meta.app_secret'),
            'page_id'           => config('services.meta.page_id'),
            'page_access_token' => config('services.meta.page_access_token'),
            'webhook_verify_token' => config('services.meta.webhook_verify'),
            'graph_api_version' => config('services.meta.graph_api_version'),
            'default_created_by' => config('services.meta.default_user_id'),
        ];

        return $envMap[$key] ?? null;
    }
}
