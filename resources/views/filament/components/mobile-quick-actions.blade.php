@auth
    @php
        $mobileActions = [
            [
                'label' => 'Scan',
                'url' => route('qr.scan'),
                'icon' => 'heroicon-o-qr-code',
                'active' => request()->routeIs('qr.scan'),
            ],
            [
                'label' => 'Pinjam',
                'url' => \App\Filament\Resources\Loans\LoanResource::getUrl('create'),
                'icon' => 'heroicon-o-arrow-up-on-square-stack',
                'active' => request()->routeIs('filament.admin.resources.loans.create'),
            ],
            [
                'label' => 'Kerusakan',
                'url' => \App\Filament\Resources\MaintenanceReports\MaintenanceReportResource::getUrl('create'),
                'icon' => 'heroicon-o-wrench-screwdriver',
                'active' => request()->routeIs('filament.admin.resources.maintenance-reports.create'),
            ],
            [
                'label' => 'Opname',
                'url' => \App\Filament\Resources\StockTakes\StockTakeResource::getUrl('index'),
                'icon' => 'heroicon-o-clipboard-document-check',
                'active' => request()->routeIs('filament.admin.resources.stock-takes.*'),
            ],
        ];
    @endphp

    <nav
        aria-label="Navigasi cepat seluler"
        class="fixed inset-x-0 bottom-0 z-40 border-t border-gray-200 bg-white/95 px-2 pt-2 shadow-[0_-8px_24px_rgb(15_23_42_/_0.08)] backdrop-blur md:hidden dark:border-white/10 dark:bg-gray-950/95"
        style="padding-bottom: max(.5rem, env(safe-area-inset-bottom));"
    >
        <div class="mx-auto grid max-w-lg grid-cols-4 gap-1">
            @foreach ($mobileActions as $action)
                <a
                    href="{{ $action['url'] }}"
                    @class([
                        'flex min-h-14 flex-col items-center justify-center gap-1 rounded-xl px-1 text-[11px] font-semibold transition',
                        'bg-primary-50 text-primary-700 dark:bg-primary-500/10 dark:text-primary-300' => $action['active'],
                        'text-gray-500 hover:bg-gray-100 hover:text-gray-950 dark:text-gray-400 dark:hover:bg-white/5 dark:hover:text-white' => ! $action['active'],
                    ])
                    @if ($action['active']) aria-current="page" @endif
                >
                    <x-dynamic-component :component="$action['icon']" class="h-5 w-5" />
                    <span>{{ $action['label'] }}</span>
                </a>
            @endforeach
        </div>
    </nav>
@endauth
