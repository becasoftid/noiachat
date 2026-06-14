<?php

namespace App\Modules\Contacts\Application\Services;

use App\Modules\Audit\Domain\Enums\AuditActionType;
use App\Modules\Contacts\Application\DTOs\UpsertContactDTO;
use App\Modules\Contacts\Domain\Enums\ContactStatus;
use App\Modules\Contacts\Domain\Repositories\ContactRepositoryInterface;
use App\Modules\Shared\Application\Services\AuditLogger;
use App\Modules\Shared\Domain\Exceptions\BusinessRuleException;
use App\Modules\Shared\Domain\ValueObjects\PhoneNumber;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use ZipArchive;

class ContactImportService
{
    private const HEADER_MAP = [
        'first_name' => 'first_name',
        'firstname' => 'first_name',
        'name' => 'first_name',
        'nombre' => 'first_name',
        'nombres' => 'first_name',
        'last_name' => 'last_name',
        'lastname' => 'last_name',
        'apellido' => 'last_name',
        'apellidos' => 'last_name',
        'email' => 'email',
        'correo' => 'email',
        'correo_electronico' => 'email',
        'primary_phone' => 'primary_phone',
        'phone' => 'primary_phone',
        'telefono' => 'primary_phone',
        'celular' => 'primary_phone',
        'status' => 'status',
        'estado' => 'status',
    ];

    public function __construct(
        private readonly ContactService $contacts,
        private readonly ContactRepositoryInterface $contactRepository,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function import(UploadedFile $file, int $userId, ?Request $request = null): array
    {
        $rows = $this->readRows($file);
        $result = [
            'created' => 0,
            'skipped' => 0,
            'errors' => [],
        ];
        $seenPhones = [];

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;
            $data = $this->normalizeRow($row);

            if ($this->isEmptyRow($data)) {
                continue;
            }

            $validator = Validator::make($data, [
                'first_name' => ['required', 'string', 'max:120'],
                'last_name' => ['nullable', 'string', 'max:120'],
                'email' => ['nullable', 'email', 'max:255'],
                'primary_phone' => ['required', 'string', 'max:30'],
                'status' => ['nullable', 'in:active,blocked,no_contact,invalid'],
            ]);

            if ($validator->fails()) {
                $result['skipped']++;
                $result['errors'][] = [
                    'row' => $rowNumber,
                    'message' => implode(' ', $validator->errors()->all()),
                ];
                continue;
            }

            try {
                $phone = PhoneNumber::from($data['primary_phone'])->value();

                if ($duplicateRow = $this->findDuplicateRow($phone, $seenPhones)) {
                    $result['skipped']++;
                    $result['errors'][] = [
                        'row' => $rowNumber,
                        'message' => "Telefono duplicado dentro del archivo. Ya aparece en la fila {$duplicateRow}.",
                    ];
                    continue;
                }

                $seenPhones[$phone] = $rowNumber;

                if ($this->contactRepository->findByPrimaryPhone($phone)) {
                    $result['skipped']++;
                    $result['errors'][] = [
                        'row' => $rowNumber,
                        'message' => 'Telefono duplicado: ya existe un contacto con este numero.',
                    ];
                    continue;
                }

                $this->contacts->create(new UpsertContactDTO(
                    $data['first_name'],
                    $data['last_name'] ?: null,
                    $data['email'] ?: null,
                    $phone,
                    $data['status'] ?: ContactStatus::ACTIVE->value,
                ), $userId, $request);

                $result['created']++;
            } catch (BusinessRuleException $exception) {
                $result['skipped']++;
                $result['errors'][] = [
                    'row' => $rowNumber,
                    'message' => $exception->getMessage(),
                ];
            } catch (\Throwable $exception) {
                $result['skipped']++;
                $result['errors'][] = [
                    'row' => $rowNumber,
                    'message' => $exception->getMessage(),
                ];
            }
        }

        $this->auditLogger->log(
            $userId,
            AuditActionType::IMPORT->value,
            'contacts',
            null,
            null,
            null,
            [
                'file_name' => $file->getClientOriginalName(),
                'created' => $result['created'],
                'skipped' => $result['skipped'],
                'error_count' => count($result['errors']),
            ],
            $request,
        );

        return $result;
    }

    private function readRows(UploadedFile $file): array
    {
        $extension = strtolower($file->getClientOriginalExtension());

        return match ($extension) {
            'csv', 'txt' => $this->readCsv($file->getRealPath()),
            'xlsx' => $this->readXlsx($file->getRealPath()),
            default => throw new \InvalidArgumentException('Formato no soportado. Usa CSV o XLSX.'),
        };
    }

    private function readCsv(string $path): array
    {
        $handle = fopen($path, 'r');

        if (! $handle) {
            throw new \RuntimeException('No se pudo leer el archivo.');
        }

        $header = null;
        $rows = [];

        while (($values = fgetcsv($handle)) !== false) {
            if ($header === null) {
                $header = $this->normalizeHeaders($values);
                continue;
            }

            $rows[] = $this->combineRow($header, $values);
        }

        fclose($handle);

        return $rows;
    }

    private function readXlsx(string $path): array
    {
        $zip = new ZipArchive();

        if ($zip->open($path) !== true) {
            throw new \RuntimeException('No se pudo leer el archivo XLSX.');
        }

        $sharedStrings = $this->readSharedStrings($zip);
        $sheet = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();

        if ($sheet === false) {
            throw new \RuntimeException('El archivo XLSX no contiene una hoja importable.');
        }

        $xml = simplexml_load_string($sheet);
        $matrix = [];

        foreach ($xml->sheetData->row ?? [] as $row) {
            $values = [];
            foreach ($row->c as $cell) {
                $reference = (string) $cell['r'];
                $column = $this->columnIndex(preg_replace('/\d+/', '', $reference) ?: 'A');
                $values[$column] = $this->cellValue($cell, $sharedStrings);
            }
            if ($values !== []) {
                ksort($values);
                $matrix[] = array_values($values);
            }
        }

        if ($matrix === []) {
            return [];
        }

        $header = $this->normalizeHeaders(array_shift($matrix));

        return array_map(fn (array $values) => $this->combineRow($header, $values), $matrix);
    }

    private function readSharedStrings(ZipArchive $zip): array
    {
        $content = $zip->getFromName('xl/sharedStrings.xml');

        if ($content === false) {
            return [];
        }

        $xml = simplexml_load_string($content);
        $strings = [];

        foreach ($xml->si ?? [] as $item) {
            $strings[] = trim((string) $item->t);
        }

        return $strings;
    }

    private function cellValue(\SimpleXMLElement $cell, array $sharedStrings): string
    {
        $value = (string) ($cell->v ?? '');

        if ((string) $cell['t'] === 's') {
            return $sharedStrings[(int) $value] ?? '';
        }

        return trim($value);
    }

    private function columnIndex(string $letters): int
    {
        $index = 0;
        foreach (str_split($letters) as $letter) {
            $index = ($index * 26) + (ord(strtoupper($letter)) - 64);
        }

        return $index - 1;
    }

    private function normalizeHeaders(array $headers): array
    {
        return array_map(function ($header): string {
            $key = strtolower(trim((string) $header));
            $key = str_replace([' ', '-', '.'], '_', $key);

            return self::HEADER_MAP[$key] ?? $key;
        }, $headers);
    }

    private function combineRow(array $header, array $values): array
    {
        $row = [];

        foreach ($header as $index => $key) {
            if ($key === '') {
                continue;
            }

            $row[$key] = trim((string) ($values[$index] ?? ''));
        }

        return $row;
    }

    private function normalizeRow(array $row): array
    {
        return [
            'first_name' => $row['first_name'] ?? '',
            'last_name' => $row['last_name'] ?? '',
            'email' => $row['email'] ?? '',
            'primary_phone' => $row['primary_phone'] ?? '',
            'status' => $row['status'] ?? ContactStatus::ACTIVE->value,
        ];
    }

    private function isEmptyRow(array $row): bool
    {
        return collect($row)->filter(fn ($value) => $value !== '')->isEmpty();
    }

    private function findDuplicateRow(string $phone, array $seenPhones): ?int
    {
        foreach ($seenPhones as $seenPhone => $rowNumber) {
            if ($seenPhone === $phone || $this->phonesMatch($seenPhone, $phone)) {
                return $rowNumber;
            }
        }

        return null;
    }

    private function phonesMatch(string $storedPhone, string $incomingPhone): bool
    {
        return strlen($storedPhone) >= 7
            && strlen($incomingPhone) >= 7
            && (str_ends_with($storedPhone, $incomingPhone) || str_ends_with($incomingPhone, $storedPhone));
    }
}
