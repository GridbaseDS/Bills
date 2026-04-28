<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Setting extends Model {
    protected $fillable = ['setting_key', 'setting_value', 'setting_group'];
    public $timestamps = false;
    public static function getAll() {
        return self::pluck('setting_value', 'setting_key')->toArray();
    }
}
