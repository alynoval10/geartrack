@auth
    <form id="geartrack-idle-logout" method="POST" action="{{ route('filament.admin.auth.logout') }}" class="hidden">
        @csrf
    </form>

    <script>
        (() => {
            if (window.gearTrackIdleSessionInitialized) {
                return;
            }

            window.gearTrackIdleSessionInitialized = true;
            const timeoutMs = {{ (int) config('session.idle_timeout', 5) * 60 * 1000 }};
            const activityUrl = @json(route('session.activity'));
            const csrfToken = @json(csrf_token());
            let logoutTimer;
            let lastPingAt = 0;

            const logout = () => document.getElementById('geartrack-idle-logout')?.requestSubmit();

            const ping = () => {
                const now = Date.now();

                // Batasi ping agar aktivitas seperti mengetik tidak membanjiri server.
                if ((now - lastPingAt) < 30000) {
                    return;
                }

                lastPingAt = now;
                fetch(activityUrl, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    credentials: 'same-origin',
                }).then((response) => {
                    if (response.redirected) {
                        window.location.assign(response.url);
                    }
                });
            };

            const registerActivity = () => {
                window.clearTimeout(logoutTimer);
                logoutTimer = window.setTimeout(logout, timeoutMs);
                ping();
            };

            ['pointerdown', 'keydown', 'touchstart', 'scroll'].forEach((eventName) => {
                window.addEventListener(eventName, registerActivity, { passive: true });
            });

            document.addEventListener('livewire:navigated', registerActivity);
            registerActivity();
        })();
    </script>
@endauth
