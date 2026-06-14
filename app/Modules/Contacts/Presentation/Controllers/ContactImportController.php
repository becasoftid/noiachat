<?php

namespace App\Modules\Contacts\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Contacts\Application\Services\ContactImportService;
use App\Modules\Contacts\Presentation\Requests\ImportContactsRequest;
use Illuminate\Http\Request;

class ContactImportController extends Controller
{
    public function create()
    {
        return view('noia.contacts.import');
    }

    public function store(ImportContactsRequest $request, ContactImportService $importer)
    {
        $result = $importer->import($request->file('file'), $request->user()->id, $request);
        session(['import_result' => $result]);

        return back()
            ->with('status', "Importacion finalizada. Creados: {$result['created']}. Omitidos: {$result['skipped']}.");
    }

    public function downloadErrors(Request $request)
    {
        abort_unless($request->user()?->can('contacts.manage'), 403);

        $result = session('import_result', []);
        $errors = $result['errors'] ?? [];

        if ($errors === []) {
            return back()->with('status', 'No hay errores de importacion para descargar.');
        }

        return response()->streamDownload(function () use ($errors): void {
            $output = fopen('php://output', 'w');
            fputcsv($output, ['row', 'message']);

            foreach ($errors as $error) {
                fputcsv($output, [$error['row'] ?? '', $error['message'] ?? '']);
            }

            fclose($output);
        }, 'errores-importacion-contactos.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
