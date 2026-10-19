<?php

namespace Modules\Core\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Modules\Core\Http\Requests\UpdateSettingsRequest;
use Modules\Core\Services\Settings;

class SettingsController
{
    /** Settings that hold an uploaded file path on the public disk. */
    private const array FILES = ['logo', 'principal_signature'];

    public function show(Settings $settings): JsonResponse
    {
        return response()->json(['data' => $this->payload($settings)]);
    }

    public function update(UpdateSettingsRequest $request, Settings $settings): JsonResponse
    {
        $settings->setMany($request->validated());

        return response()->json(['data' => $this->payload($settings)]);
    }

    public function upload(Request $request, Settings $settings, string $key): JsonResponse
    {
        abort_unless(in_array($key, self::FILES, true), 404);

        $request->validate(['file' => ['required', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048']]);

        if ($old = $settings->get($key)) {
            Storage::disk('public')->delete($old);
        }

        $settings->set($key, $request->file('file')->store('settings', 'public'));

        return response()->json(['data' => $this->payload($settings)]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function payload(Settings $settings): array
    {
        $data = [];

        foreach (array_keys(UpdateSettingsRequest::fieldRules()) as $key) {
            if (! str_contains($key, '.')) {
                $data[$key] = $settings->get($key);
            }
        }

        foreach (self::FILES as $key) {
            $path = $settings->get($key);
            $data[$key.'_url'] = $path ? Storage::disk('public')->url($path) : null;
        }

        return $data;
    }
}
