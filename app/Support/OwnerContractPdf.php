<?php

namespace App\Support;

use App\Models\OwnerUnitContract;
use Mpdf\Mpdf;

class OwnerContractPdf
{
    public function make(OwnerUnitContract $contract): string
    {
        $contract->loadMissing(['owner', 'unit.building']);

        $tempDir = storage_path('app/mpdf');
        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0775, true);
        }

        $pdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'Letter',
            'default_font' => 'dejavusans',
            'tempDir' => $tempDir,
            'autoScriptToLang' => true,
            'autoLangToFont' => true,
            'margin_left' => 54,
            'margin_right' => 54,
            'margin_top' => 20,
            'margin_bottom' => 58,
        ]);

        $footerLogo = $this->imageData(public_path('brand/pattern-logo.jpeg'));
        $pdf->SetTitle($contract->contract_no.' Owner Contract');
        $pdf->WriteHTML(view('pdfs.owner-contract', [
            'contract' => $contract,
            'logo' => $footerLogo,
            'clauses' => config('owner-contract-template.clauses', []),
            'tags' => config('owner-contract-template.tags', []),
            'managementServices' => config('owner-contract-template.management_services', []),
            'startupServices' => config('owner-contract-template.startup_services', []),
        ])->render());

        return $pdf->Output('', 'S');
    }

    private function imageData(string $path): ?string
    {
        if (! is_file($path)) {
            return null;
        }

        return 'data:image/jpeg;base64,'.base64_encode(file_get_contents($path));
    }
}
