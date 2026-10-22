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
        const unreadDot = document.getElementById('sidebarConversationsUnreadDot');
        const headerDot = document.getElementById('headerNotificationDot');
        const headerCountBadge = document.getElementById('headerNotificationCountBadge');
        const headerList = document.getElementById('headerNotificationList');
        const headerRefreshBtn = document.getElementById('headerNotificationRefreshBtn');

        function showUnreadDot() {
            if (unreadDot) {
                unreadDot.style.display = 'inline-block';
            }
            if (headerDot) {
                headerDot.style.display = 'inline-block';
            }
        }

        function hideUnreadDot() {
            if (unreadDot) {
                unreadDot.style.display = 'none';
            }
            if (headerDot) {
                headerDot.style.display = 'none';
            }
        }

        function escapeHtml(str) {
            if (!str) return '';
            return String(str)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function loadNotificationsFeed() {
            fetch('{{ route("notifications.feed") }}', {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(res => res.json())
            .then(data => {
                if (!data) return;

                // Update Red Dots (Header & Sidebar)
                if (data.has_unread) {
                    showUnreadDot();
                    if (headerCountBadge) {
                        headerCountBadge.textContent = data.unread_count > 99 ? '99+' : data.unread_count;
                        headerCountBadge.style.display = 'inline-block';
                    }
                } else {
                    hideUnreadDot();
                    if (headerCountBadge) {
                        headerCountBadge.style.display = 'none';
                    }
                }

                // Render Dropdown List
                if (headerList) {
                    if (!data.items || data.items.length === 0) {
                        headerList.innerHTML = `
                            <div class="py-4 text-center text-secondary fs-12px">
                                <i data-lucide="bell-off" class="icon-md mb-1 opacity-50"></i>
                                <p class="mb-0">No recent notifications</p>
                            </div>
                        `;
                    } else {
                        let html = '';
                        data.items.forEach(item => {
                            const initial = escapeHtml(item.sender.charAt(0).toUpperCase());
                            const unreadClass = item.is_unread ? 'bg-light fw-semibold' : '';
                            const unreadDot = item.is_unread ? '<span class="d-inline-block rounded-circle bg-danger me-1" style="width: 6px; height: 6px;"></span>' : '';
                            const channelBadge = item.channel === 'whatsapp'
                                ? '<span class="badge bg-success py-0 px-1 fs-10px">WA</span>'
                                : '<span class="badge bg-info py-0 px-1 fs-10px">WIDGET</span>';

                            html += `
                                <a href="${item.url}" class="dropdown-item d-flex align-items-center py-2 px-2 border-bottom border-light ${unreadClass}">
                                    <div class="w-32px h-32px rounded-circle d-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary me-2 fw-bold fs-12px" style="min-width: 32px; width: 32px; height: 32px;">
                                        ${initial}
                                    </div>
                                    <div class="flex-grow-1 overflow-hidden me-2">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <div class="d-flex align-items-center overflow-hidden">
                                                ${unreadDot}
                                                <p class="fs-12px text-truncate mb-0 fw-medium">${escapeHtml(item.sender)}</p>
                                            </div>
                                            <span class="fs-10px text-muted ms-1">${escapeHtml(item.time_formatted)}</span>
                                        </div>
                                        <div class="d-flex align-items-center justify-content-between mt-1">
                                            <p class="fs-11px text-secondary text-truncate mb-0">${escapeHtml(item.preview)}</p>
                                            ${channelBadge}
                                        </div>
                                    </div>
                                </a>
                            `;
                        });
                        headerList.innerHTML = html;
                    }

                    if (typeof lucide !== 'undefined' && lucide.createIcons) {
                        lucide.createIcons();
                    }
                }
            })
            .catch(err => {
                console.warn('[Notifications] Failed to load feed:', err);
                if (headerList) {
                    headerList.innerHTML = `
                        <div class="py-3 text-center text-secondary fs-12px">
                            Unable to load activity
                        </div>
                    `;
                }
            });
        }

        // Attach refresh button event
        if (headerRefreshBtn) {
            headerRefreshBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                if (headerList) {
                    headerList.innerHTML = `
                        <div class="p-3 text-center text-secondary fs-12px">
                            <div class="spinner-border spinner-border-sm text-primary me-1" role="status"></div>
                            Refreshing...
                        </div>
                    `;
                }
                loadNotificationsFeed();
            });
        }

        // Initial Load on page ready
        loadNotificationsFeed();

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

                // Show the red dot on the Conversations menu and Header Notifications icon
                showUnreadDot();

                // Refresh dropdown list live
                loadNotificationsFeed();

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
