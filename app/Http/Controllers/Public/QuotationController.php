<?php

namespace App\Http\Controllers\Public;

use App\Domain\Lead\Models\Quotation;
use App\Http\Controllers\Controller;

class QuotationController extends Controller
{
    public function show(Quotation $quotation)
    {
        $quotation->load(['items', 'lead', 'package']);

        return view('public.quotation', [
            'quotation' => $quotation,
            'expired' => $quotation->isExpired(),
        ]);
    }
}
