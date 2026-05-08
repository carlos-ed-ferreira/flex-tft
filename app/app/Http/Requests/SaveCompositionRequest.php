<?php

namespace App\Http\Requests;

use App\Models\Composition;
use App\Support\TftLevels;
use Illuminate\Foundation\Http\FormRequest;
use stdClass;

class SaveCompositionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $composition = $this->route('composition');

        if (! $composition instanceof Composition) {
            return true;
        }

        $user = $this->user();

        return $user && ((int) $user->id === (int) $composition->user_id || ($user->role ?? null) === 'a');
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'notes' => 'nullable|string',
            'levels' => 'required|array',
            'levels.*.level' => 'required|integer|in:' . TftLevels::validationValues(),
            'levels.*.board_state' => 'present',
            'dispositions' => 'nullable|array',
            'dispositions.*.type' => 'required|in:champion,trait,item',
            'dispositions.*.champion_ids' => 'nullable|array',
            'dispositions.*.champion_ids.*' => 'string',
            'dispositions.*.star_level' => 'nullable|integer|min:1|max:2',
            'dispositions.*.trait_id' => 'nullable|string',
            'dispositions.*.trait_count' => 'nullable|integer|min:1',
            'dispositions.*.item_ids' => 'nullable|array',
            'dispositions.*.item_ids.*' => 'string',
            'dispositions.*.priority' => 'nullable|integer',
        ];
    }

    public function compositionData(): array
    {
        $validated = $this->validated();

        $validated['levels'] = array_map(function (array $levelData) {
            if (is_array($levelData['board_state']) && empty($levelData['board_state'])) {
                $levelData['board_state'] = new stdClass;
            }

            return $levelData;
        }, $validated['levels']);

        return $validated;
    }
}
