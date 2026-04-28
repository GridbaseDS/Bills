<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\RecurringInvoice;

class RecurringController extends Controller
{
    public function index()
    {
        $recs = RecurringInvoice::with('client')->orderBy('created_at', 'desc')->get();
        $recs->transform(function ($r) {
            $r->company_name = $r->client->company_name ?? $r->client->contact_name;
            return $r;
        });
        return response()->json(['success' => true, 'data' => $recs]);
    }
}
