<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class LoggingPermissionsTest extends TestCase
{
    #[DataProvider('fileChannels')]
    public function test_logs_remain_group_writable_when_created_with_a_restrictive_umask(string $channel): void
    {
        $path = tempnam(sys_get_temp_dir(), 'skybase-log-');
        unlink($path);
        $originalUmask = umask(0077);
        $logger = Log::build([
            ...config("logging.channels.{$channel}"),
            'path' => $path,
        ]);

        try {
            $logger->warning('Router disconnect failed after saving the subscription.');
            $logger->info('The next process can continue logging.');
            $logger->getLogger()->close();

            $files = glob($path.'*');
            $this->assertCount(1, $files);
            clearstatcache(true, $files[0]);
            $this->assertSame(0664, fileperms($files[0]) & 0777);
            $this->assertStringContainsString('Router disconnect failed', file_get_contents($files[0]));
            $this->assertStringContainsString('The next process can continue logging.', file_get_contents($files[0]));
        } finally {
            umask($originalUmask);
            $logger->getLogger()->close();

            foreach (glob($path.'*') as $file) {
                unlink($file);
            }
        }
    }

    /**
     * @return array<string, array{string}>
     */
    public static function fileChannels(): array
    {
        return [
            'single' => ['single'],
            'daily' => ['daily'],
        ];
    }
}
