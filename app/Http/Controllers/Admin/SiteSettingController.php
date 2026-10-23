<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SiteSettingRequest;
use App\Models\SiteSetting;
use App\Services\ActivityLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class SiteSettingController extends Controller
{
    /** @var array<string, string> */
    private array $assetFields = [
        'logo' => 'logo_path',
        'dark_logo' => 'dark_logo_path',
        'footer_logo' => 'footer_logo_path',
        'favicon' => 'favicon_path',
        'banner' => 'banner_path',
        'cover' => 'cover_path',
    ];

    public function edit(): View
    {
        $setting = SiteSetting::query()->firstOrCreate(
            ['key' => 'general'],
            SiteSetting::defaults(),
        );

        return view('admin.settings.general', compact('setting'));
    }

    public function update(SiteSettingRequest $request, ActivityLogger $logger): RedirectResponse
    {
        $setting = SiteSetting::query()->firstOrCreate(
            ['key' => 'general'],
            SiteSetting::defaults(),
        );
        $data = $request->safe()->except(array_merge(
            array_keys($this->assetFields),
            array_map(fn (string $field): string => 'remove_'.$field, array_keys($this->assetFields)),
        ));
        $newFiles = [];
        $oldFiles = [];
        $data['footer_columns'] = collect($data['footer_columns'])->map(fn (array $column): array => [
            'title' => trim((string) $column['title']),
            'links' => collect($column['links'] ?? [])->filter(fn (array $link): bool => filled($link['label'] ?? null) && filled($link['url'] ?? null))
                ->map(fn (array $link): array => [
                    'label' => trim((string) $link['label']),
                    'url' => trim((string) $link['url']),
                ])->values()->all(),
        ])->values()->all();

        try {
            foreach ($this->assetFields as $input => $column) {
                $oldPath = $setting->getAttribute($column);

                if ($request->hasFile($input)) {
                    $newPath = $request->file($input)->store('site-settings/'.$input, 'public');
                    $data[$column] = $newPath;
                    $newFiles[] = $newPath;

                    if ($oldPath) {
                        $oldFiles[] = $oldPath;
                    }
                } elseif ($request->boolean('remove_'.$input)) {
                    $data[$column] = null;

                    if ($oldPath) {
                        $oldFiles[] = $oldPath;
                    }
                }
            }

            DB::transaction(function () use ($request, $setting, $data): void {
                $setting->update($data + ['updated_by' => $request->user()->id]);
            });
        } catch (Throwable $exception) {
            Storage::disk('public')->delete($newFiles);
            throw $exception;
        }

        Storage::disk('public')->delete(array_values(array_unique($oldFiles)));
        SiteSetting::forgetCache();
        $logger->log($request, 'admin.site_settings.updated', $setting, [
            'site_name' => $setting->site_name,
            'assets_updated' => array_keys(array_filter($this->assetFields, fn (string $column, string $input): bool => array_key_exists($column, $data), ARRAY_FILTER_USE_BOTH)),
        ]);

        return back()->with('status', 'Đã lưu cài đặt chung của website.');
    }
}
