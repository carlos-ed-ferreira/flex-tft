<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Composition;

class CompositionController extends Controller
{
    public function toggleGlobal(Composition $composition)
    {
        $composition->update([
            'is_global' => ! $composition->is_global,
        ]);

        $status = $composition->is_global ? 'global' : 'privada';

        return back()->with('success', "Composição \"{$composition->name}\" agora é {$status}.");
    }
}
