<?php

namespace Modules\Diary\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Diary\Enums\PhraseField;
use Modules\Diary\Models\QuickPhrase;

/**
 * The shared quick phrases every teacher sees (teachers add their own from the panel).
 */
class PhraseController
{
    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('diary.manage'), 403);

        $phrases = QuickPhrase::query()->whereNull('teacher_id')->orderBy('field')->orderBy('sort_order')->orderBy('id')->get();

        return response()->json(['data' => $phrases->map(fn (QuickPhrase $phrase): array => $this->row($phrase))->values()]);
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('diary.manage'), 403);

        return response()->json(['data' => $this->row(QuickPhrase::create([...$this->validated($request), 'teacher_id' => null]))], 201);
    }

    public function update(Request $request, QuickPhrase $phrase): JsonResponse
    {
        abort_unless($request->user()->can('diary.manage') && $phrase->teacher_id === null, 403);

        $phrase->update($this->validated($request));

        return response()->json(['data' => $this->row($phrase)]);
    }

    public function destroy(Request $request, QuickPhrase $phrase): JsonResponse
    {
        abort_unless($request->user()->can('diary.manage') && $phrase->teacher_id === null, 403);

        $phrase->delete();

        return response()->json(null, 204);
    }

    /**
     * @return array<string, mixed>
     */
    protected function validated(Request $request): array
    {
        $data = $request->validate(['field' => ['required', 'in:'.implode(',', array_column(PhraseField::cases(), 'value'))], 'text_bn' => ['required', 'string', 'max:255'], 'sort_order' => ['nullable', 'integer', 'min:0'], 'is_active' => ['sometimes', 'boolean']]);

        return [...$data, 'sort_order' => $data['sort_order'] ?? 0, 'is_active' => $data['is_active'] ?? true];
    }

    /**
     * @return array<string, mixed>
     */
    protected function row(QuickPhrase $phrase): array
    {
        return ['id' => $phrase->id, 'field' => $phrase->field->value, 'text_bn' => $phrase->text_bn, 'sort_order' => $phrase->sort_order, 'is_active' => $phrase->is_active];
    }
}
