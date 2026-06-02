<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ImportMembersRequest;
use App\Models\Cooperative;
use App\Services\MemberImportService;
use App\Services\Settings\SettingsService;
use App\Support\AccessControl;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MemberImportController extends Controller
{
    public function __construct(
        private readonly SettingsService $settings,
        private readonly MemberImportService $imports,
    ) {
    }

    public function index(Request $request): Response
    {
        $importSession = session()->get('import_preview');

        return Inertia::render('Admin/Pages/Members/Import', [
            'importPreview' => $importSession,
            'canImport' => $request->user()?->can(AccessControl::PERMISSION_CREATE_MEMBERS) ?? false,
        ]);
    }

    public function downloadTemplate(): StreamedResponse
    {
        $headers = MemberImportService::templateHeaders();
        $example = MemberImportService::templateExampleRow();

        return response()->streamDownload(function () use ($headers, $example): void {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, $headers);
            fputcsv($handle, $example);

            fclose($handle);
        }, 'template_import_ahli.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function preview(ImportMembersRequest $request): RedirectResponse
    {
        $file = $request->validated('file');
        $result = $this->imports->parseFile($file);

        if (isset($result['error'])) {
            return back()->withErrors(['file' => $result['error']]);
        }

        $preview = $this->imports->preview($result['rows']);

        session()->flash('import_preview', [
            'rows' => $result['rows'],
            'totalRows' => $preview['totalRows'],
            'previewRows' => $preview['previewRows'],
            'validRows' => $preview['validRows'],
            'invalidRows' => $preview['invalidRows'],
            'errors' => $preview['errors'],
        ]);

        return back();
    }

    public function import(Request $request): RedirectResponse
    {
        $importSession = session()->get('import_preview');

        if (! $importSession || empty($importSession['rows'])) {
            return redirect()->route('admin.members.import')->with('status', 'Sesi import telah tamat. Sila muat naik fail sekali lagi.');
        }

        $cooperative = $this->activeCooperative();

        if (! $cooperative) {
            return redirect()->route('admin.members.import')->withErrors(['file' => 'Koperasi tidak ditemui.']);
        }

        $summary = $this->imports->import(
            $importSession['rows'],
            $cooperative->id,
            $request->user(),
        );

        session()->forget('import_preview');

        return redirect()
            ->route('admin.members.index')
            ->with('status', "Import selesai: {$summary['imported']} diimport, {$summary['duplicates']} pendua dilangkau, {$summary['errors']} ralat.");
    }

    private function activeCooperative(): ?Cooperative
    {
        return $this->settings->activeCooperative();
    }
}