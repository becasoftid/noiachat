<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Users\Infrastructure\Persistence\Models\Role;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;
use ZipArchive;

class ContactImportTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        $this->admin = User::query()->where('email', 'admin@noiachat.local')->firstOrFail();
    }

    public function test_operator_can_import_contacts_from_csv_with_row_errors(): void
    {
        $operator = User::factory()->create();
        $operator->roles()->attach(Role::query()->where('name', 'operator')->firstOrFail()->id);

        $csv = implode("\n", [
            'nombre,apellido,correo,telefono,estado',
            'Ana,Lopez,ana@example.com,573001110001,active',
            ',Sin Nombre,error@example.com,573001110002,active',
            'Duplicado,,dup@example.com,573001112233,active',
        ]);

        $this->actingAs($operator)->post(route('contacts.import.store'), [
            'file' => UploadedFile::fake()->createWithContent('contacts.csv', $csv),
        ])->assertRedirect()
            ->assertSessionHas('import_result', fn (array $result) => $result['created'] === 1 && $result['skipped'] === 2);

        $this->assertDatabaseHas('contacts', [
            'full_name' => 'Ana Lopez',
            'email' => 'ana@example.com',
            'primary_phone' => '573001110001',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $operator->id,
            'action' => 'import',
            'module' => 'contacts',
        ]);
    }

    public function test_admin_can_import_contacts_from_xlsx(): void
    {
        $file = new UploadedFile(
            $this->makeXlsx([
                ['first_name', 'last_name', 'email', 'primary_phone', 'status'],
                ['Beatriz', 'Rios', 'beatriz@example.com', '573001110003', 'active'],
            ]),
            'contacts.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true,
        );

        $this->actingAs($this->admin)->post(route('contacts.import.store'), [
            'file' => $file,
        ])->assertRedirect()
            ->assertSessionHas('import_result', fn (array $result) => $result['created'] === 1 && $result['skipped'] === 0);

        $this->assertDatabaseHas('contacts', [
            'full_name' => 'Beatriz Rios',
            'email' => 'beatriz@example.com',
            'primary_phone' => '573001110003',
        ]);
    }

    public function test_auditor_cannot_import_contacts(): void
    {
        $auditor = User::factory()->create();
        $auditor->roles()->attach(Role::query()->where('name', 'auditor')->firstOrFail()->id);

        $this->actingAs($auditor)->post(route('contacts.import.store'), [
            'file' => UploadedFile::fake()->createWithContent('contacts.csv', "first_name,primary_phone\nAna,573001110004"),
        ])->assertForbidden();
    }

    public function test_import_errors_can_be_downloaded_as_csv(): void
    {
        $csv = implode("\n", [
            'nombre,telefono',
            ',573001110005',
        ]);

        $this->actingAs($this->admin)->post(route('contacts.import.store'), [
            'file' => UploadedFile::fake()->createWithContent('contacts.csv', $csv),
        ])->assertRedirect()
            ->assertSessionHas('import_result');

        $this->actingAs($this->admin)->get(route('contacts.import.create'))
            ->assertOk()
            ->assertSee('Descargar errores CSV');

        $response = $this->actingAs($this->admin)->get(route('contacts.import.errors'));

        $response->assertOk();
        $response->assertHeader('content-disposition', 'attachment; filename=errores-importacion-contactos.csv');
        $content = $response->streamedContent();

        $this->assertStringContainsString('row,message', $content);
        $this->assertStringContainsString('2', $content);
    }

    public function test_import_detects_duplicate_phones_inside_file(): void
    {
        $csv = implode("\n", [
            'nombre,telefono',
            'Laura,573001110006',
            'Laura Local,3001110006',
        ]);

        $this->actingAs($this->admin)->post(route('contacts.import.store'), [
            'file' => UploadedFile::fake()->createWithContent('contacts.csv', $csv),
        ])->assertRedirect()
            ->assertSessionHas('import_result', function (array $result): bool {
                return $result['created'] === 1
                    && $result['skipped'] === 1
                    && str_contains($result['errors'][0]['message'], 'Telefono duplicado dentro del archivo');
            });
    }

    public function test_import_detects_duplicate_phone_against_existing_contacts(): void
    {
        $csv = implode("\n", [
            'nombre,telefono',
            'Ana Duplicada,3001112233',
        ]);

        $this->actingAs($this->admin)->post(route('contacts.import.store'), [
            'file' => UploadedFile::fake()->createWithContent('contacts.csv', $csv),
        ])->assertRedirect()
            ->assertSessionHas('import_result', function (array $result): bool {
                return $result['created'] === 0
                    && $result['skipped'] === 1
                    && str_contains($result['errors'][0]['message'], 'Telefono duplicado');
            });
    }

    private function makeXlsx(array $rows): string
    {
        $path = tempnam(sys_get_temp_dir(), 'contacts-xlsx-');
        $zip = new ZipArchive();
        $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        $strings = [];
        $sheetRows = [];

        foreach ($rows as $rowIndex => $row) {
            $cells = [];
            foreach (array_values($row) as $columnIndex => $value) {
                $strings[] = (string) $value;
                $cell = $this->columnName($columnIndex + 1).($rowIndex + 1);
                $cells[] = '<c r="'.$cell.'" t="s"><v>'.(count($strings) - 1).'</v></c>';
            }
            $sheetRows[] = '<row r="'.($rowIndex + 1).'">'.implode('', $cells).'</row>';
        }

        $zip->addFromString('xl/sharedStrings.xml', '<?xml version="1.0" encoding="UTF-8"?><sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'.collect($strings)->map(fn ($value) => '<si><t>'.htmlspecialchars($value, ENT_XML1).'</t></si>')->implode('').'</sst>');
        $zip->addFromString('xl/worksheets/sheet1.xml', '<?xml version="1.0" encoding="UTF-8"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>'.implode('', $sheetRows).'</sheetData></worksheet>');
        $zip->close();

        return $path;
    }

    private function columnName(int $index): string
    {
        $name = '';
        while ($index > 0) {
            $index--;
            $name = chr(65 + ($index % 26)).$name;
            $index = intdiv($index, 26);
        }

        return $name;
    }
}
