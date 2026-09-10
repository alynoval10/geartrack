<div class="gt-hero">

    <div class="gt-hero-content">

        <div class="gt-eyebrow">
            <span class="gt-eyebrow-dot"></span>
            SISTEM INVENTARIS TKJ
        </div>

        <h1 class="gt-hero-title">
            Kelola inventaris
            <span>lebih terorganisir.</span>
        </h1>

        <p class="gt-hero-description">
            Kelola, identifikasi, dan lacak perangkat TKJ menggunakan
            sistem inventaris berbasis QR Code.
        </p>

        <div class="gt-hero-actions">

            <a
                href="{{ \App\Filament\Resources\Assets\AssetResource::getUrl('create') }}"
                class="gt-button gt-button-primary"
            >
                <x-heroicon-o-plus style="width:18px;height:18px;" />
                Tambah Aset
            </a>

            <button
                type="button"
                class="gt-button gt-button-secondary"
                disabled
            >
                <x-heroicon-o-qr-code style="width:18px;height:18px;" />
                Scan QR

                <span class="gt-coming-soon">
                    Segera
                </span>
            </button>

        </div>

    </div>


    <div class="gt-hero-visual">

        <div class="gt-visual-glow"></div>

        <div class="gt-qr-card">

            <div class="gt-qr-icon">
                <x-heroicon-o-qr-code style="width:48px;height:48px;" />
            </div>

            <div class="gt-device-info">

                <span>GT-RT-0001</span>

                <strong>
                    Perangkat Teridentifikasi
                </strong>

                <div class="gt-status">
                    <span></span>
                    Inventaris terverifikasi
                </div>

            </div>

        </div>


        <div class="gt-floating-card">

            <x-heroicon-o-check-circle
                style="width:20px;height:20px;"
            />

            <div>
                <strong>QR Ready</strong>
                <span>Identifikasi cepat</span>
            </div>

        </div>

    </div>

</div>