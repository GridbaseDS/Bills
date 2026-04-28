<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Setting;

class SettingController extends Controller
{
    public function index()
    {
        return response()->json(Setting::getAll());
    }

    public function updateMultiple(Request $request)
    {
        foreach ($request->all() as $key => $value) {
            Setting::where('setting_key', $key)->update(['setting_value' => $value]);
        }
        return response()->json(['success' => true]);
    }
}
