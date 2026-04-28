<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Quote;
use App\Models\Setting;

class QuoteController extends Controller
{
    public function index()
    {
        $quotes = Quote::with('client')->orderBy('created_at', 'desc')->get();
        $quotes->transform(function ($q) {
            $q->company_name = $q->client->company_name ?? $q->client->contact_name;
            return $q;
        });
        return response()->json(['success' => true, 'data' => $quotes]);
    }
}
