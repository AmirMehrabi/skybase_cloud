<?php

namespace App\Http\Controllers;

use App\Http\Requests\Communication\GatewayTestRequest;
use App\Http\Requests\Communication\SmsGatewayRequest;
use App\Models\Setting;
use App\Services\CommunicationDeliveryService;
use App\Services\SmsGatewayService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class SmsGatewayController extends Controller
{
    public function index(SmsGatewayService $service): View
    {
        return view('modules.communications.gateway', ['settings' => $service->settings(tenant()->id)]);
    }

    public function update(SmsGatewayRequest $request, SmsGatewayService $service): RedirectResponse
    {
        $settings = array_replace_recursive($service->settings(tenant()->id), $request->validated());
        foreach (['twilio.token', 'smsportal.secret', 'kavenegar.api_key'] as $field) {
            if (! $request->filled($field)) {
                data_set($settings, $field, data_get($service->settings(tenant()->id), $field));
            }
        }
        $settings['smsportal']['test_mode'] = $request->boolean('smsportal.test_mode');
        $required = match ($settings['provider']) {
            'twilio' => ['twilio.sid', 'twilio.token', 'twilio.sender'], 'smsportal' => ['smsportal.client_id', 'smsportal.secret'], 'kavenegar' => ['kavenegar.api_key'], default => []
        };
        foreach ($required as $field) {
            if (blank(data_get($settings, $field))) {
                throw ValidationException::withMessages([$field => 'This field is required for the active gateway.']);
            }
        }
        Setting::query()->updateOrCreate(['tenant_id' => tenant()->id, 'key' => 'sms.gateway'], ['value' => $settings, 'type' => 'json', 'group' => 'sms']);

        return back()->with('success', 'SMS gateway settings saved.');
    }

    public function test(GatewayTestRequest $request, SmsGatewayService $service): RedirectResponse
    {
        if ($request->input('action') === 'connection') {
            try {
                $service->testConnection(tenant()->id);

                return back()->with('success', 'SMS gateway authentication succeeded. No SMS was sent.');
            } catch (Throwable) {
                return back()->with('error', 'Gateway connection failed. Check saved credentials and API access.');
            }
        }
        app(CommunicationDeliveryService::class)->create(tenant()->id, 'test:'.Str::uuid(), ['channel' => 'sms', 'destination' => $request->input('destination'), 'content' => ['sms_body' => $request->input('message')], 'expires_at' => now()->addMinutes(15)]);

        return back()->with('success', 'Test SMS queued. Track the real result in Delivery activity.');
    }
}
