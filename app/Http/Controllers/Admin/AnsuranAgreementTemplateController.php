<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\InteractsWithActiveCooperative;
use App\Http\Controllers\Controller;
use App\Http\Requests\Ansuran\StoreAgreementTemplateRequest;
use App\Models\AnsuranAgreementTemplate;
use Inertia\Inertia;

class AnsuranAgreementTemplateController extends Controller
{
    use InteractsWithActiveCooperative;

    public function index()
    {
        $cooperativeId = $this->activeCooperative()?->id;

        return Inertia::render('Admin/Pages/Ansuran/Templates/Index', [
            'templates' => AnsuranAgreementTemplate::forCooperative($cooperativeId)
                ->ordered()
                ->get()
                ->map(fn ($t) => [
                    'id' => $t->id,
                    'name' => $t->name,
                    'description' => $t->description,
                    'is_active' => $t->is_active,
                ]),
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Pages/Ansuran/Templates/Form', [
            'placeholders' => $this->getPlaceholders(),
        ]);
    }

    public function store(StoreAgreementTemplateRequest $request)
    {
        $cooperativeId = $this->activeCooperative()?->id;

        $data = $request->validated();
        $data['cooperative_id'] = $cooperativeId;
        $data['created_by'] = auth()->id();

        AnsuranAgreementTemplate::create($data);

        return redirect()->route('admin.ansuran.templates.index')
            ->with('success', 'Template perjanjian berjaya ditambah.');
    }

    public function edit(AnsuranAgreementTemplate $template)
    {
        return Inertia::render('Admin/Pages/Ansuran/Templates/Form', [
            'template' => [
                'id' => $template->id,
                'name' => $template->name,
                'content' => $template->content,
                'description' => $template->description,
                'is_active' => $template->is_active,
            ],
            'placeholders' => $this->getPlaceholders(),
        ]);
    }

    public function update(StoreAgreementTemplateRequest $request, AnsuranAgreementTemplate $template)
    {
        $data = $request->validated();
        $data['updated_by'] = auth()->id();

        $template->update($data);

        return redirect()->route('admin.ansuran.templates.index')
            ->with('success', 'Template perjanjian berjaya dikemaskini.');
    }

    public function destroy(AnsuranAgreementTemplate $template)
    {
        $template->delete();

        return redirect()->route('admin.ansuran.templates.index')
            ->with('success', 'Template perjanjian berjaya dipadam.');
    }

    private function getPlaceholders(): array
    {
        return [
            ['key' => '{{nama_ahli}}', 'label' => 'Nama Ahli'],
            ['key' => '{{no_ahli}}', 'label' => 'No Ahli'],
            ['key' => '{{no_kad_pengenalan}}', 'label' => 'No Kad Pengenalan'],
            ['key' => '{{nama_produk}}', 'label' => 'Nama Produk'],
            ['key' => '{{varian}}', 'label' => 'Varian'],
            ['key' => '{{harga_penuh}}', 'label' => 'Harga Penuh'],
            ['key' => '{{bayaran_pendahuluan}}', 'label' => 'Bayaran Pendahuluan'],
            ['key' => '{{jumlah_pembiayaan}}', 'label' => 'Jumlah Pembiayaan'],
            ['key' => '{{kadar_keuntungan}}', 'label' => 'Kadar Keuntungan'],
            ['key' => '{{tempoh_ansuran}}', 'label' => 'Tempoh Ansuran'],
            ['key' => '{{bayaran_bulanan}}', 'label' => 'Bayaran Bulanan'],
            ['key' => '{{jumlah_perlu_dibayar}}', 'label' => 'Jumlah Perlu Dibayar'],
            ['key' => '{{tarikh_kontrak}}', 'label' => 'Tarikh Kontrak'],
            ['key' => '{{kaedah_penghantaran}}', 'label' => 'Kaedah Penghantaran'],
            ['key' => '{{alamat_penghantaran}}', 'label' => 'Alamat Penghantaran'],
        ];
    }
}