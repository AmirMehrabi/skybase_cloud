<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class DeploymentWebhookTest extends TestCase
{
    public function test_webhook_authenticates_filters_and_deduplicates_pushes(): void
    {
        $directory = sys_get_temp_dir().'/skybase-webhook-'.bin2hex(random_bytes(8));
        mkdir($directory, 0700);
        $secret = bin2hex(random_bytes(32));
        file_put_contents($directory.'/secret', $secret);
        $socket = stream_socket_server('tcp://127.0.0.1:0', $errorCode, $errorMessage);
        $port = (int) substr(strrchr(stream_socket_get_name($socket, false), ':'), 1);
        fclose($socket);
        $process = new Process(['python3', base_path('deploy/skybase-webhook.py'), '--port', (string) $port, '--secret-file', $directory.'/secret', '--queue', $directory]);
        $process->start();

        try {
            for ($attempt = 0; $attempt < 100; $attempt++) {
                $connection = @stream_socket_client('tcp://127.0.0.1:'.$port, $errorCode, $errorMessage, 0.1);
                if ($connection !== false) {
                    fclose($connection);
                    break;
                }
                usleep(20000);
            }

            $payload = ['repository' => ['full_name' => 'AmirMehrabi/skybase_cloud'], 'ref' => 'refs/heads/master', 'after' => str_repeat('a', 40), 'deleted' => false];
            $send = function (array $payload, string $event = 'push', bool $validSignature = true) use ($secret, $port): int {
                $body = json_encode($payload, JSON_THROW_ON_ERROR);

                return Http::withHeaders([
                    'X-Hub-Signature-256' => $validSignature ? 'sha256='.hash_hmac('sha256', $body, $secret) : 'sha256=invalid',
                    'X-GitHub-Event' => $event,
                    'X-GitHub-Delivery' => 'aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee',
                ])->withBody($body, 'application/json')->post('http://127.0.0.1:'.$port.'/hooks/github/skybase')->status();
            };

            $this->assertSame(403, $send($payload, validSignature: false));
            $this->assertSame(403, $send(array_replace($payload, ['repository' => ['full_name' => 'other/repository']])));
            $this->assertSame(200, $send($payload, 'ping'));
            $this->assertSame(200, $send(array_replace($payload, ['ref' => 'refs/heads/feature'])));
            $this->assertSame(200, $send(array_replace($payload, ['deleted' => true])));
            $this->assertSame(400, $send(array_replace($payload, ['after' => 'shell command'])));
            $this->assertSame([], glob($directory.'/*.json'));
            $this->assertSame(202, $send($payload));
            $this->assertSame(202, $send($payload));
            $this->assertCount(1, glob($directory.'/*.json'));
            $request = json_decode(file_get_contents(glob($directory.'/*.json')[0]), true, flags: JSON_THROW_ON_ERROR);
            $this->assertSame(str_repeat('a', 40), $request['revision']);
        } finally {
            $process->stop();
            foreach (glob($directory.'/*') as $file) {
                unlink($file);
            }
            rmdir($directory);
        }
    }
}
