<?php

namespace Modules\Core\Services\Sms;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Core\Contracts\SmsGateway;

/**
 * Placeholder for a Bangladeshi bulk-SMS provider. It posts to SMS_BULK_URL with the API key and sender id from the
 * environment. Provider APIs differ: read the chosen provider's current documentation and adapt `payload()` before
 * switching SMS_DRIVER to "bulk". Until it is configured it logs and reports failure instead of pretending to send.
 */
class BulkSmsGateway implements SmsGateway
{
    public function send(string $to, string $message): bool
    {
        $config = config('modules.core.sms.bulk');

        if (blank($config['url'] ?? null) || blank($config['api_key'] ?? null)) {
            Log::warning('[sms:bulk] not configured, message not sent to '.$to);

            return false;
        }

        $response = Http::timeout(10)->asForm()->post($config['url'], $this->payload($config, $to, $message));

        if ($response->failed()) {
            Log::warning('[sms:bulk] provider rejected the message: '.$response->status());
        }

        return $response->successful();
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array<string, string>
     */
    protected function payload(array $config, string $to, string $message): array
    {
        return ['api_key' => $config['api_key'], 'senderid' => $config['sender_id'] ?? '', 'number' => SmsManager::normalize($to), 'message' => $message];
    }
}
