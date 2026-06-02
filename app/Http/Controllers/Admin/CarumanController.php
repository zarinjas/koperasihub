<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateCarumanRequest;
use App\Models\Member;
use App\Models\MemberContribution;
use App\Services\CarumanService;
use App\Support\AccessControl;
use Illuminate\Http\Request;
use Inertia\Response;

class CarumanController extends Controller
{
    public function __construct(
        private readonly CarumanService $carumanService,
    ) {
    }

    public function index(Request $request): Response
    {
        $this->authorize(AccessControl::PERMISSION_VIEW_CARUMAN);

        $cooperativeId = $request->user()->cooperative_id;

        $year = (int) $request->input('year', (int) now()->format('Y'));
        $search = $request->input('search', '');

        $contributions = MemberContribution::query()
            ->forCooperative($cooperativeId)
            ->year($year)
            ->search($search)
            ->with('member')
            ->paginate(20)
            ->withQueryString();

        $members = Member::query()
            ->forCooperative($cooperativeId)
            ->when($search !== '', fn ($q) => $q->where('member_no', 'like', "%{$search}%")
                ->orWhere('full_name', 'like', "%{$search}%"))
            ->whereNotIn('id', $contributions->pluck('member_id'))
            ->orderBy('member_no')
            ->limit(10)
            ->get()
            ->map(fn (Member $member) => [
                'id' => $member->id,
                'member_no' => $member->member_no,
                'full_name' => $member->full_name,
                'identity_no' => $member->identity_no,
            ]);

        $years = range(max(2023, (int) now()->format('Y') - 3), (int) now()->format('Y') + 1);

        return inertia('Admin/Pages/Caruman/Index', [
            'contributions' => $contributions->through(fn (MemberContribution $c) => $this->serializeContribution($c)),
            'members' => $members,
            'years' => array_values($years),
            'selectedYear' => $year,
            'search' => $search,
        ]);
    }

    public function update(UpdateCarumanRequest $request, MemberContribution $contribution): \Illuminate\Http\RedirectResponse
    {
        $this->authorize(AccessControl::PERMISSION_EDIT_CARUMAN);

        $this->carumanService->updateOrCreate(
            cooperativeId: $contribution->cooperative_id,
            memberId: $contribution->member_id,
            year: $contribution->year,
            values: $request->validated(),
            actor: $request->user(),
        );

        return redirect()->back()->with('status', 'Caruman berjaya dikemas kini.');
    }

    public function storeOrUpdate(UpdateCarumanRequest $request): \Illuminate\Http\RedirectResponse
    {
        $this->authorize(AccessControl::PERMISSION_EDIT_CARUMAN);

        $cooperativeId = $request->user()->cooperative_id;
        $memberId = $request->integer('member_id');
        $year = $request->integer('year', (int) now()->format('Y'));

        $this->carumanService->updateOrCreate(
            cooperativeId: $cooperativeId,
            memberId: $memberId,
            year: $year,
            values: $request->validated(),
            actor: $request->user(),
        );

        return redirect()->back()->with('status', 'Caruman berjaya disimpan.');
    }

    private function serializeContribution(MemberContribution $contribution): array
    {
        return [
            'id' => $contribution->id,
            'member_id' => $contribution->member_id,
            'member_no' => $contribution->member?->member_no,
            'member_name' => $contribution->member?->full_name,
            'year' => $contribution->year,
            'caruman_semasa' => (float) $contribution->caruman_semasa,
            'caruman_keseluruhan' => (float) $contribution->caruman_keseluruhan,
            'dividen' => (float) $contribution->dividen,
            'notes' => $contribution->notes,
        ];
    }
}