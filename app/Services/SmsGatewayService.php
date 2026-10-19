<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class SmsGatewayService
{
    public function settings(string $tenantId): array
    {
        return array_replace_recursive([
            'provider' => 'none', 'rate_per_minute' => 30,
            'twilio' => ['sid' => '', 'token' => '', 'sender' => ''],
            'smsportal' => ['client_id' => '', 'secret' => '', 'test_mode' => false],
            'kavenegar' => ['api_key' => '', 'sender' => '', 'credentials_template' => '', 'reset_template' => ''],
        ], Setting::get('sms.gateway', [], $tenantId) ?? []);
    }

    public function send(string $tenantId, string $destination, array $content, ?string $provider = null): array
    {
        $settings = $this->settings($tenantId);
        $provider ??= $settings['provider'];
        $http = Http::acceptJson()->connectTimeout(5)->timeout(20);
        if (($content['sms_mode'] ?? 'plain') === 'lookup' && $provider !== 'kavenegar') {
            throw new RuntimeException('This message requires a Kavenegar Lookup gateway.', 422);
        }
        if ($provider === 'twilio') {
            $config = $settings['twilio'];
            $response = $http->withBasicAuth($config['sid'], $config['token'])->asForm()
                ->post('https://api.twilio.com/2010-04-01/Accounts/'.rawurlencode($config['sid']).'/Messages.json', [
                    'To' => $destination, 'From' => $config['sender'], 'Body' => $content['sms_body'],
                ]);
            $this->check($response);
            $id = $response->json('sid');
        } elseif ($provider === 'smsportal') {
            $config = $settings['smsportal'];
            $response = $http->withBasicAuth($config['client_id'], $config['secret'])
                ->post('https://rest.smsportal.com/v3/BulkMessages', [
                    'sendOptions' => ['testMode' => (bool) $config['test_mode']],
                    'messages' => [['destination' => $destination, 'content' => $content['sms_body']]],
                ]);
            $this->check($response);
            $body = array_change_key_case($response->json() ?? [], CASE_LOWER);
            $id = $body['eventid'] ?? null;
            if (isset($body['messages']) && (int) $body['messages'] < 1) {
                throw new RuntimeException('SMSPortal did not accept the message. Check recipient opt-out and error reports.', 422);
            }
            if (! $id && ! $config['test_mode']) {
                throw new RuntimeException('SMSPortal did not return an event identifier; acceptance is uncertain.');
            }

            return ['provider' => $provider, 'id' => $id !== null ? (string) $id : null, 'status' => $config['test_mode'] ? 'simulated' : 'accepted'];
        } elseif ($provider === 'kavenegar') {
            $config = $settings['kavenegar'];
            $lookup = ($content['sms_mode'] ?? 'plain') === 'lookup';
            $data = $lookup
                ? array_merge(['receptor' => $destination, 'template' => $content['lookup_template']], $content['lookup_tokens'] ?? [])
                : ['receptor' => $destination, 'sender' => $config['sender'], 'message' => $content['sms_body']];
            $response = $http->asForm()->post('https://api.kavenegar.com/v1/'.rawurlencode($config['api_key']).'/'.($lookup ? 'verify/lookup' : 'sms/send').'.json', $data);
            $this->check($response);
            if ((int) $response->json('return.status') !== 200) {
                throw new RuntimeException('Kavenegar rejected the message (code '.(int) $response->json('return.status').').', 422);
            }
            $id = $response->json('entries.0.messageid');
        } else {
            throw new RuntimeException('Configure an active SMS gateway in Settings.', 422);
        }
        if (! $id) {
            throw new RuntimeException('Gateway response did not include a message identifier; acceptance is uncertain.');
        }

        return ['provider' => $provider, 'id' => (string) $id, 'status' => 'accepted'];
    }

    public function testConnection(string $tenantId): void
    {
        $settings = $this->settings($tenantId);
        $http = Http::acceptJson()->connectTimeout(5)->timeout(10);
        $response = match ($settings['provider']) {
            'twilio' => $http->withBasicAuth($settings['twilio']['sid'], $settings['twilio']['token'])
                ->get('https://api.twilio.com/2010-04-01/Accounts/'.rawurlencode($settings['twilio']['sid']).'.json'),
            'smsportal' => $http->withBasicAuth($settings['smsportal']['client_id'], $settings['smsportal']['secret'])->get('https://rest.smsportal.com/v3/Balance'),
            'kavenegar' => $http->get('https://api.kavenegar.com/v1/'.rawurlencode($settings['kavenegar']['api_key']).'/account/info.json'),
            default => throw new RuntimeException('Select and save an SMS gateway first.', 422),
        };
        $this->check($response);
        if ($settings['provider'] === 'kavenegar' && (int) $response->json('return.status') !== 200) {
            throw new RuntimeException('Kavenegar authentication failed.', 422);
        }
    }

    public function receipt(string $tenantId, string $provider, string $id): ?string
    {
        $config = $this->settings($tenantId)[$provider] ?? [];
        $http = Http::acceptJson()->connectTimeout(5)->timeout(10);
        if ($provider === 'twilio') {
            $response = $http->withBasicAuth($config['sid'], $config['token'])->get('https://api.twilio.com/2010-04-01/Accounts/'.rawurlencode($config['sid']).'/Messages/'.rawurlencode($id).'.json');
            $this->check($response);

            return match ($response->json('status')) {
                'delivered', 'read' => 'delivered', 'failed', 'undelivered', 'canceled' => 'failed', default => null
            };
        }
        if ($provider === 'kavenegar') {
            $response = $http->get('https://api.kavenegar.com/v1/'.rawurlencode($config['api_key']).'/sms/status.json', ['messageid' => $id]);
            $this->check($response);

            return match ((int) $response->json('entries.0.status')) {
                10 => 'delivered', 5, 6, 11, 13, 14, 100 => 'failed', default => null
            };
        }

        return null;
    }

    private function check(Response $response): void
    {
        if (! $response->successful()) {
            throw new RuntimeException('SMS gateway returned HTTP '.$response->status().'.', $response->status());
        }
    }
}
