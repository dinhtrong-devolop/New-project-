<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PaymentGatewaySettingRequest;
use App\Models\PaymentGatewaySetting;
use App\Services\ActivityLogger;
use App\Services\NaptudongService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DepositSettingController extends Controller
{
    public function edit(NaptudongService $provider): View
    {
        $setting = PaymentGatewaySetting::query()->where('gateway', NaptudongService::GATEWAY)->first();

        return view('admin.deposits.settings', [
            'setting' => $setting,
            'configuration' => $provider->adminConfiguration(),
            'callbackUrl' => route('payment.naptudong.callback'),
        ]);
    }

    public function update(
        PaymentGatewaySettingRequest $request,
        NaptudongService $provider,
        ActivityLogger $logger,
    ): RedirectResponse {
        $data = $request->validated();
        $existing = PaymentGatewaySetting::query()->where('gateway', NaptudongService::GATEWAY)->first();

        $resolvedPartnerId = $data['partner_id'] ?: (string) config('services.naptudong.partner_id');
        $resolvedPartnerKey = $data['partner_key'] ?: $existing?->partner_key ?: (string) config('services.naptudong.partner_key');

        if ($data['is_enabled'] && blank($resolvedPartnerId)) {
            throw ValidationException::withMessages(['partner_id' => 'Partner ID là bắt buộc khi bật nạp thẻ.']);
        }

        if ($data['is_enabled'] && blank($resolvedPartnerKey)) {
            throw ValidationException::withMessages(['partner_key' => 'Partner Key là bắt buộc khi bật nạp thẻ.']);
        }

        $setting = DB::transaction(function () use ($request, $data, $existing): PaymentGatewaySetting {
            $attributes = [
                'is_enabled' => $data['is_enabled'],
                'provider_url' => $data['provider_url'],
                'discount_percent' => $data['discount_percent'],
                'partner_id' => $data['partner_id'],
                'configured_by' => $request->user()->id,
            ];

            if (filled($data['partner_key'])) {
                $attributes['partner_key'] = $data['partner_key'];
            }

            if ($existing) {
                $existing->update($attributes);

                return $existing->fresh();
            }

            return PaymentGatewaySetting::query()->create($attributes + ['gateway' => NaptudongService::GATEWAY]);
        });

        $provider->forgetRuntimeSetting();
        $logger->log($request, 'admin.payment_gateway.updated', $setting, [
            'gateway' => NaptudongService::GATEWAY,
            'is_enabled' => $setting->is_enabled,
            'provider_url' => $setting->provider_url,
            'discount_percent' => $setting->discount_percent,
            'partner_key_updated' => filled($data['partner_key']),
        ]);

        return redirect()->route('admin.deposits.settings.edit')->with('status', 'Đã lưu cấu hình nạp thẻ.');
    }
}
