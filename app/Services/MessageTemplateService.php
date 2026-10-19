<?php

namespace App\Services;

use App\Models\Customer;
use Illuminate\Validation\ValidationException;

class MessageTemplateService
{
    public const VARIABLES = ['customer_name', 'first_name', 'last_name', 'email', 'phone', 'username', 'title', 'body', 'action_url', 'invoice_id', 'invoice_amount', 'due_date', 'ticket_id', 'subscription_id', 'router_name', 'incident_id', 'stage', 'timestamp', 'reset_code'];

    public function validate(array $content): void
    {
        foreach (['subject', 'email_body', 'sms_body'] as $field) {
            preg_match_all('/{{\s*([^{}]+)\s*}}/', $content[$field] ?? '', $matches);
            foreach ($matches[1] as $variable) {
                if (! in_array(trim($variable), self::VARIABLES, true)) {
                    throw ValidationException::withMessages([$field => 'Unknown variable: '.trim($variable)]);
                }
            }
        }
        foreach ($content['lookup_tokens'] ?? [] as $variable) {
            if (! in_array($variable, self::VARIABLES, true)) {
                throw ValidationException::withMessages(['lookup_tokens' => 'Select a supported variable for each Lookup token.']);
            }
        }
    }

    public function render(array $content, array $data): array
    {
        foreach (['subject', 'email_body', 'sms_body'] as $field) {
            $content[$field] = preg_replace_callback('/{{\s*([^{}]+?)\s*}}/', fn (array $match): string => (string) ($data[trim($match[1])] ?? ''), $content[$field] ?? '');
        }
        if (($content['sms_mode'] ?? 'plain') === 'lookup') {
            foreach ($content['lookup_tokens'] ?? [] as $token => $variable) {
                $value = (string) ($data[$variable] ?? '');
                $max = match ($token) {
                    'token10' => 4, 'token20' => 8, default => 0
                };
                if ($value === '' || mb_strlen($value) > 100 || preg_match('/[\r\n\x00]/', $value) || substr_count($value, ' ') > $max) {
                    throw ValidationException::withMessages(['lookup_tokens' => "Value for {$token} cannot be used in this Lookup template; credentials are never modified."]);
                }
                $content['lookup_tokens'][$token] = $value;
            }
        }

        return $content;
    }

    public function customerData(Customer $customer): array
    {
        return ['customer_name' => $customer->full_name, 'first_name' => $customer->first_name, 'last_name' => $customer->last_name,
            'email' => $customer->email, 'phone' => $customer->mobile ?: $customer->phone, 'username' => $customer->subscriptions->first()?->pppoe_username];
    }

    public function smsSegments(string $body): array
    {
        $gsm = '@£$¥èéùìòÇ'."\n".'Øø'."\r".'ÅåΔ_ΦΓΛΩΠΨΣΘΞÆæßÉ !"#¤%&\'()*+,-./0123456789:;<=>?¡ABCDEFGHIJKLMNOPQRSTUVWXYZÄÖÑÜ§¿abcdefghijklmnopqrstuvwxyzäöñüà';
        $extended = "^{}\\[~]|€\f";
        $length = 0;
        foreach (mb_str_split($body) as $character) {
            if (str_contains($gsm, $character)) {
                $length++;
            } elseif (str_contains($extended, $character)) {
                $length += 2;
            } else {
                $units = intdiv(strlen(mb_convert_encoding($body, 'UTF-16BE', 'UTF-8')), 2);

                return ['encoding' => 'Unicode', 'length' => $units, 'segments' => $units <= 70 ? (int) ($units > 0) : (int) ceil($units / 67)];
            }
        }

        return ['encoding' => 'GSM-7', 'length' => $length, 'segments' => $length <= 160 ? (int) ($length > 0) : (int) ceil($length / 153)];
    }
}
