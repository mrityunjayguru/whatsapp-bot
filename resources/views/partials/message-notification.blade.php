{{-- Notification Sound & Toast for Incoming Customer Messages --}}
@auth
@php
    $currentUser = auth()->user();
    $currentEmployee = \App\Models\Employee::where('email', $currentUser->email)->first();
    $isAdmin = is_null($currentUser->company_id) || $currentUser->id === 1 || ($currentEmployee && $currentEmployee->role === 'ADMIN') || !$currentEmployee;
    $employeeId = $currentEmployee ? $currentEmployee->id : null;
    $companyId = $currentUser->company_id;
@endphp

<audio id="globalMessageAudio" preload="auto" src="{{ asset('sounds/message.mp3') }}"></audio>

<script>
(function() {
    function initMessageNotification() {
        if (typeof window.Echo === 'undefined') {
            setTimeout(initMessageNotification, 500);
            return;
        }

        const audio = document.getElementById('globalMessageAudio');
        let audioUnlocked = false;

        function unlockAudio() {
            if (!audioUnlocked && audio) {
                audio.play().then(() => {
                    audio.pause();
                    audio.currentTime = 0;
                    audioUnlocked = true;
                }).catch(() => {});
            }
        }

        ['click', 'touchstart', 'keydown'].forEach(evt => {
            document.addEventListener(evt, unlockAudio, { once: true });
        });

        function playNotificationSound() {
            if (!audio) return;
            audio.currentTime = 0;
            const playPromise = audio.play();
            if (playPromise !== undefined) {
                playPromise.catch(function(err) {
                    console.warn('[Audio] Autoplay blocked or failed:', err);
                });
            }
        }

        const isAdmin = {{ $isAdmin ? 'true' : 'false' }};
        const companyId = {{ json_encode($companyId) }};
        const currentEmployeeId = {{ json_encode($employeeId) }};

        window.Echo.private('messages')
            .listen('.message.received', function(e) {
                console.log('[Notification] Incoming message received:', e);

                // Filter by company/tenant if not super admin
                if (companyId !== null && e.tenant_id && e.tenant_id != companyId) {
                    return;
                }

                // If regular employee, check if conversation is assigned to them or unassigned
                if (!isAdmin && currentEmployeeId !== null) {
                    if (e.assigned_tenant_user_id && e.assigned_tenant_user_id != currentEmployeeId) {
                        return; // assigned to another employee
                    }
                }

                // Play sound
                playNotificationSound();
            });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initMessageNotification);
    } else {
        initMessageNotification();
    }
})();
</script>
@endauth
