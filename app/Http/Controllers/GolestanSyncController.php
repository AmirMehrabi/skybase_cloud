<?php

namespace App\Http\Controllers;

use App\Http\Requests\GolestanSyncRequest;
use App\Jobs\ProcessGolestanSync;
use App\Models\GolestanSyncRun;
use App\Services\GolestanSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class GolestanSyncController extends Controller
{
    public function preview(GolestanSyncRequest $request, GolestanSyncService $service): JsonResponse
    {
        $input = $request->validated();
        $tenantId = (string) tenant()->id;
        $userId = (int) $request->user()->id;
        $search = $service->recall('search', $input['search_token'], $tenantId, $userId);
        $credentialFields = $input['credential_fields'] ?? GolestanSyncService::DEFAULT_CREDENTIAL_FIELDS;
        $service->require(array_diff($credentialFields, $search['credential_fields'] ?? GolestanSyncService::DEFAULT_CREDENTIAL_FIELDS) === [],
            'Choose PPPoE fields from the Golestan search results.');
        $settings = $service->settings($tenantId, $input + ['credential_fields' => $credentialFields]);
        [$plan, $router, $pool] = $settings;
        $students = collect($input['rows'])->mapWithKeys(function (int $index) use ($search, $service): array {
            $service->require(isset($search['students'][$index]), 'A selected row is missing from this search.');

            return [$index => $search['students'][$index]];
        });
        $numbers = $students->countBy('student_number');
        $identities = $students->filter(fn (array $student) => $student['national_id'] !== '')->countBy('national_id');
        $preview = [];
        $ready = [];
        $available = $pool?->availableAddresses()->count();
        $allocations = [];
        $usernames = [];
        foreach ($students as $index => $student) {
            $choice = isset($input['subscriptions'][$index]) ? (int) $input['subscriptions'][$index] : null;
            $address = $input['addresses'][$index] ?? null;
            try {
                $service->require($numbers[$student['student_number']] === 1 && ($identities[$student['national_id']] ?? 0) <= 1,
                    'The selected results contain a duplicate student number or national ID.');
                $student['fields'] = array_intersect_key($student['fields'] ?? [], array_flip($credentialFields));
                $data = $service->inspect($tenantId, $student, $settings, $choice, $address);
                $preview[$index] = $data['preview'];
                if (isset($data['fingerprint'])) {
                    $ready[$index] = ['student' => $student, 'choice' => $choice, 'address' => $address, 'fingerprint' => $data['fingerprint']];
                    $allocations[$index] = $data['allocate'] && ($address === null || $address !== $data['subscription']?->ip_address);
                    $usernames[mb_strtolower($data['username'])][] = $index;
                }
            } catch (ValidationException $exception) {
                $preview[$index] = ['action' => 'skip', 'message' => collect($exception->errors())->flatten()->first()];
            }
        }
        foreach ($usernames as $indices) {
            if (count($indices) > 1) {
                foreach ($indices as $index) {
                    unset($ready[$index], $allocations[$index]);
                    $preview[$index] = ['action' => 'skip', 'message' => 'More than one selected student maps to this PPPoE username.'];
                }
            }
        }
        $needed = count(array_filter($allocations));
        $capacityError = $available !== null && $needed > $available;
        $token = $ready !== [] && ! $capacityError && ! collect($preview)->contains('action', 'choose')
            ? $service->remember('preview', [
                'tenant_id' => $tenantId, 'user_id' => $userId,
                'settings' => ['plan_id' => $plan->id, 'router_id' => $router->id, 'ip_pool_id' => $pool?->id, 'ip_management' => $input['ip_management'], 'credential_fields' => $credentialFields],
                'rows' => collect($ready)->sortBy(fn (array $row) => $row['address'] === null)->all(),
                'skipped' => collect($preview)->where('action', 'skip')
                    ->map(fn (array $row) => ['status' => 'skipped', 'message' => $row['message']])->all(),
            ]) : null;

        return $this->json([
            'rows' => collect($preview)->map(fn (array $row, int $index) => ['index' => $index, ...$row])->values(),
            'preview_token' => $token, 'ready' => count($ready), 'available' => $available, 'needed' => $needed,
            'settings' => ['plan' => $plan->name, 'router' => $router->name, 'pool' => $pool?->name],
            'error' => $capacityError ? 'The selected pool does not have enough available addresses.' : null,
        ]);
    }

    public function start(GolestanSyncRequest $request, GolestanSyncService $service): JsonResponse
    {
        $tenantId = (string) tenant()->id;
        $userId = (int) $request->user()->id;
        $token = $request->validated('preview_token');
        $existing = GolestanSyncRun::query()->where('tenant_id', $tenantId)->where('preview_token', $token)->first();
        if ($existing) {
            abort_unless((int) $existing->user_id === $userId, 403);

            if ($existing->status === 'pending') {
                ProcessGolestanSync::dispatch($tenantId, $existing->id)->afterCommit();
            }

            return $this->json(['status_url' => route('golestan.sync.status', $existing)]);
        }

        $snapshot = $service->recall('preview', $token, $tenantId, $userId);
        $service->settings($tenantId, $snapshot['settings']);
        $run = GolestanSyncRun::query()->createOrFirst(
            ['tenant_id' => $tenantId, 'preview_token' => $token],
            ['user_id' => $userId, 'total' => count($snapshot['rows']) + count($snapshot['skipped']), 'snapshot' => $snapshot, 'outcomes' => $snapshot['skipped']],
        );
        if ($run->wasRecentlyCreated) {
            ProcessGolestanSync::dispatch($tenantId, $run->id)->afterCommit();
        }

        return $this->json(['status_url' => route('golestan.sync.status', $run)]);
    }

    public function status(Request $request, int $run): JsonResponse
    {
        abort_unless($request->user()?->isAdmin(), 403);
        $sync = GolestanSyncRun::query()->where('tenant_id', tenant()->id)->where('user_id', $request->user()->id)->findOrFail($run);

        return $this->json([
            ...$sync->only(['status', 'total']),
            'outcomes' => collect($sync->outcomes)->sortKeys()->map(function (array $outcome, int $index): array {
                return [
                    'index' => $index, 'status' => $outcome['status'], 'message' => $outcome['message'],
                    'customer_url' => isset($outcome['customer_id']) ? route('customers.show', $outcome['customer_id']) : null,
                    'subscription_url' => isset($outcome['subscription_id']) ? route('subscriptions.show', $outcome['subscription_id']) : null,
                ];
            })->values(),
        ]);
    }

    private function json(array $data): JsonResponse
    {
        return response()->json($data)->header('Cache-Control', 'no-store, private');
    }
}
