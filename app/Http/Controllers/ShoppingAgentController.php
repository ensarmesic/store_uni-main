<?php

namespace App\Http\Controllers;

use App\Services\{EuSize, ShoppingAgent};
use Illuminate\Http\Request;

class ShoppingAgentController
{
    public function index() { return view('agent.index'); }

    public function analyze(Request $request, ShoppingAgent $agent)
    {
        $request->validate(['message' => ['required','string','max:2000'], 'size' => ['nullable','string','max:20'], 'budget' => ['nullable','numeric','min:1','max:10000']]);
        if ($request->filled('size') && EuSize::number($request->input('size')) === null) return back()->withErrors(['size' => 'Unesi važeći EU broj.'])->withInput();
        $analysis = $agent->analyze($request);
        return view('agent.index', ['analysis' => $analysis, 'message' => $request->input('message')]);
    }
}
