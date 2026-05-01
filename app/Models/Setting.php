<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Setting extends Model {
    protected $fillable = ['setting_key', 'setting_value', 'setting_group'];
    public $timestamps = false;
    
    public static function getAll() {
        return self::pluck('setting_value', 'setting_key')->toArray();
    }
    
    public static function get(string $key, $default = null) {
        $setting = self::where('setting_key', $key)->first();
        return $setting ? $setting->setting_value : $default;
    }
    
    public static function set(string $key, string $value, string $group = 'general'): void {
        self::updateOrCreate(
            ['setting_key' => $key],
            ['setting_value' => $value, 'setting_group' => $group]
        );
    }
    
    public static function updateBulk(array $settings): void {
        foreach ($settings as $key => $value) {
            self::set($key, $value);
        }
    }
    
    public static function getGroup(string $group): array {
        return self::where('setting_group', $group)
            ->pluck('setting_value', 'setting_key')
            ->toArray();
    }
}
