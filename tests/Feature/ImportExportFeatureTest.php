<?php

namespace Tests\Feature;

use App\Jobs\ImportExport\ProcessExportJob;
use App\Jobs\ImportExport\ProcessImportJob;
use App\Models\ImportExportRun;
use App\Models\Plan;
use App\Models\Router;
use App\Models\Tenant;
use App\Models\User;
use App\Support\ImportExport\ImportExportSchema;
use App\Support\ImportExport\SpreadsheetImportExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class ImportExportFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_plan_export_request_queues_a_run_with_current_filters(): void
    {
        Queue::fake();
        $tenant = $this->createTenant('alpha-net');
        $user = $this->createUser($tenant);

        $response = $this->actingAs($user)->post(route('plans.export'), [
            'status' => 'active',
            'type' => 'pppoe',
        ]);

        $response->assertRedirect(route('plans.index'));
        $this->assertDatabaseHas('import_export_runs', [
            'tenant_id' => $tenant->id,
            'module' => 'plans',
            'direction' => 'export',
            'status' => 'queued',
        ]);

        $run = ImportExportRun::query()->firstOrFail();
        $this->assertSame(['status' => 'active', 'type' => 'pppoe'], $run->filters);
        Queue::assertPushed(ProcessExportJob::class);
    }

    public function test_plan_export_job_writes_filtered_xlsx_file(): void
    {
        Storage::fake('local');
        $tenant = $this->createTenant('alpha-net');
        $user = $this->createUser($tenant);

        Plan::factory()->create([
            'name' => 'Exported Fiber',
            'internal_name' => 'exported_fiber',
            'status' => 'active',
            'type' => 'pppoe',
        ]);
        Plan::factory()->create([
            'name' => 'Hidden Wireless',
            'internal_name' => 'hidden_wireless',
            'status' => 'inactive',
            'type' => 'wireless',
        ]);

        $run = ImportExportRun::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'module' => ImportExportSchema::MODULE_PLANS,
            'direction' => ImportExportRun::DIRECTION_EXPORT,
            'status' => ImportExportRun::STATUS_QUEUED,
            'filters' => ['status' => 'active'],
            'disk' => 'local',
        ]);

        (new ProcessExportJob($run->id))->handle(app(SpreadsheetImportExportService::class));
        $run->refresh();

        $this->assertSame(ImportExportRun::STATUS_COMPLETED, $run->status);
        $this->assertSame(1, $run->total_rows);
        Storage::disk('local')->assertExists($run->file_path);

        $sheet = IOFactory::load(Storage::disk('local')->path($run->file_path))->getActiveSheet();
        $this->assertSame('internal_name', $sheet->getCell('B1')->getValue());
        $this->assertSame('exported_fiber', $sheet->getCell('B2')->getValue());
        $this->assertNull($sheet->getCell('B3')->getValue());
    }

    public function test_plan_import_upserts_rows_and_reports_validation_failures(): void
    {
        Storage::fake('local');
        $tenant = $this->createTenant('alpha-net');
        $user = $this->createUser($tenant);
        Plan::factory()->create([
            'name' => 'Old Name',
            'internal_name' => 'existing_plan',
            'status' => 'active',
            'type' => 'pppoe',
        ]);

        $run = $this->createImportRun($tenant, $user, ImportExportSchema::MODULE_PLANS);
        $path = ImportExportSchema::basePath($tenant->id, $run->id).'/import-'.$run->id.'.xlsx';
        $this->writeWorkbook($path, ImportExportSchema::headings(ImportExportSchema::MODULE_PLANS), [
            ['Updated Name', 'existing_plan', null, 'active', 'public', 'pppoe', 'Residential', 100, 50, 0, 0, 'Mbps', null, 'GB', 'yes', 49.99, 'USD', 'monthly', 7, 0, null, null, null, 5, 'no', null, null, null, null],
            ['Bad Plan', 'bad_plan', null, 'invalid', 'public', 'pppoe', 'Residential', 100, 50, 0, 0, 'Mbps', null, 'GB', 'yes', 49.99, 'USD', 'monthly', 7, 0, null, null, null, 5, 'no', null, null, null, null],
        ]);
        $run->update(['file_path' => $path]);

        (new ProcessImportJob($run->id))->handle(app(SpreadsheetImportExportService::class));
        $run->refresh();

        $this->assertSame(ImportExportRun::STATUS_COMPLETED, $run->status);
        $this->assertSame(2, $run->processed_rows);
        $this->assertSame(1, $run->updated_count);
        $this->assertSame(1, $run->failed_count);
        $this->assertDatabaseHas('plans', [
            'internal_name' => 'existing_plan',
            'name' => 'Updated Name',
        ]);
        $this->assertDatabaseHas('import_export_run_rows', [
            'import_export_run_id' => $run->id,
            'row_number' => 3,
            'status' => 'failed',
            'identifier' => 'bad_plan',
        ]);
    }

    public function test_subscription_import_creates_customer_and_subscription_from_one_row(): void
    {
        Storage::fake('local');
        $tenant = $this->createTenant('alpha-net');
        $user = $this->createUser($tenant);
        $plan = Plan::factory()->create([
            'internal_name' => 'fiber_100',
            'status' => 'active',
            'price' => 79.99,
            'billing_cycle' => 'monthly',
        ]);
        $router = Router::factory()->online()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Core Router',
        ]);

        $run = $this->createImportRun($tenant, $user, ImportExportSchema::MODULE_SUBSCRIPTIONS);
        $path = ImportExportSchema::basePath($tenant->id, $run->id).'/import-'.$run->id.'.xlsx';
        $this->writeWorkbook($path, ImportExportSchema::headings(ImportExportSchema::MODULE_SUBSCRIPTIONS), [[
            'CUS-IMP-0001',
            'individual',
            'Jane Doe',
            'Jane',
            'Doe',
            null,
            'NAT-001',
            'jane@example.com',
            '555-0100',
            '555-0101',
            null,
            '123 Main',
            null,
            'Tehran',
            'Tehran',
            '12345',
            'Iran',
            'active',
            'prepaid',
            'yes',
            0,
            100,
            'no',
            'SUB-IMP-0001',
            'Jane Home Fiber',
            'pppoe',
            $plan->internal_name,
            $router->name,
            'North POP',
            'pppoe',
            '10.0.0.10',
            null,
            'router',
            'jane.pppoe',
            'secret',
            79.99,
            0,
            'none',
            0,
            79.99,
            'monthly',
            'yes',
            7,
            '2026-06-10',
            'active',
            '2026-06-01 00:00:00',
            null,
            '2026-06-01 00:00:00',
            null,
            null,
            'Imported service',
        ]]);
        $run->update(['file_path' => $path]);

        (new ProcessImportJob($run->id))->handle(app(SpreadsheetImportExportService::class));
        $run->refresh();

        $this->assertSame(ImportExportRun::STATUS_COMPLETED, $run->status);
        $this->assertSame(1, $run->created_count);
        $this->assertSame(0, $run->failed_count);
        $this->assertDatabaseHas('customers', [
            'tenant_id' => $tenant->id,
            'customer_code' => 'CUS-IMP-0001',
            'email' => 'jane@example.com',
        ]);
        $this->assertDatabaseHas('subscriptions', [
            'tenant_id' => $tenant->id,
            'subscription_code' => 'SUB-IMP-0001',
            'plan_id' => $plan->id,
            'router_id' => $router->id,
            'pppoe_username' => 'jane.pppoe',
        ]);
    }

    private function createTenant(string $slug): Tenant
    {
        return Tenant::create([
            'id' => (string) Str::uuid(),
            'name' => Str::headline($slug),
            'slug' => $slug,
            'company_name' => Str::headline($slug),
            'email' => $slug.'@example.com',
            'timezone' => 'UTC',
            'status' => 'active',
        ]);
    }

    private function createUser(Tenant $tenant): User
    {
        return User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'admin',
            'status' => 'active',
        ]);
    }

    private function createImportRun(Tenant $tenant, User $user, string $module): ImportExportRun
    {
        return ImportExportRun::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'module' => $module,
            'direction' => ImportExportRun::DIRECTION_IMPORT,
            'status' => ImportExportRun::STATUS_QUEUED,
            'disk' => 'local',
            'original_filename' => 'import.xlsx',
        ]);
    }

    /**
     * @param  list<string>  $headings
     * @param  list<array<int, mixed>>  $rows
     */
    private function writeWorkbook(string $path, array $headings, array $rows): void
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();

        foreach ($headings as $index => $heading) {
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($index + 1).'1', $heading);
        }

        foreach ($rows as $rowIndex => $row) {
            foreach ($row as $columnIndex => $value) {
                $sheet->setCellValue(Coordinate::stringFromColumnIndex($columnIndex + 1).($rowIndex + 2), $value);
            }
        }

        Storage::disk('local')->makeDirectory(dirname($path));
        (new Xlsx($spreadsheet))->save(Storage::disk('local')->path($path));
        $spreadsheet->disconnectWorksheets();
    }
}
