@extends('layout.master')

@php
    $phoneNumberId = $number->phone_number_id;
@endphp

@section('content')
<nav class="page-breadcrumb d-flex align-items-center mb-4">
  <a href="{{ route('whatsapp-numbers.index') }}" class="btn btn-outline-secondary me-3"><i data-lucide="arrow-left" class="icon-sm me-2"></i> Back to WhatsApp Numbers</a>
  <ol class="breadcrumb mb-0">
    <li class="breadcrumb-item"><a href="{{ route('whatsapp-numbers.index') }}">WhatsApp Numbers</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ $number->label ?: $number->display_number ?: $phoneNumberId }}</li>
  </ol>
</nav>

@if (session('success'))
  <div class="alert alert-success mb-4">{{ session('success') }}</div>
@endif
@if (session('error'))
  <div class="alert alert-danger mb-4">{{ session('error') }}</div>
@endif
@if ($errors->any())
  <div class="alert alert-danger mb-4">
    <ul class="mb-0">
      @foreach ($errors->all() as $error)
        <li>{{ $error }}</li>
      @endforeach
    </ul>
  </div>
@endif

<div class="row">
  <!-- Left: number config -->
  <div class="col-md-12">

    <!-- Setup Guide Accordion -->
    <div class="card mb-3 border-primary">
      <div class="card-header bg-transparent border-bottom py-3 px-4 d-flex justify-content-between align-items-center" style="cursor: pointer;" data-bs-toggle="collapse" data-bs-target="#guideCardBody" aria-expanded="false" aria-controls="guideCardBody" id="guideCardHeader">
        <div class="d-flex align-items-start">
          <div class="me-3 text-primary mt-1">
            <i data-lucide="book-open" class="icon-sm"></i>
          </div>
          <div>
            <h6 class="card-title mb-0 text-primary">Meta WhatsApp Business Setup Guide</h6>
            <p class="text-muted small mb-0">Reference guide to collect or update the credentials below. Each company MUST use its own separate Meta Business Account + WABA for its numbers.</p>
          </div>
        </div>
        <button class="btn btn-sm btn-ghost text-muted px-2" type="button" id="guideCardChevronBtn" aria-label="Toggle guide">
          <i data-lucide="chevron-down" class="icon-md"></i>
        </button>
      </div>

      <div id="guideCardBody" class="collapse">
        <div class="card-body pb-0">
          <div class="d-flex justify-content-end mb-3">
            <button class="btn btn-sm btn-outline-primary" type="button" id="toggleAllStepsBtn">
              <i data-lucide="chevrons-down" class="icon-sm me-1"></i> <span id="toggleAllStepsLabel">Expand All Steps</span>
            </button>
          </div>

          <div class="accordion" id="metaSetupGuide">

          <!-- Step 1 -->
          <div class="accordion-item">
            <h2 class="accordion-header" id="headingOne">
              <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#step1" aria-expanded="false" aria-controls="step1">
                <span class="fw-bold">Step 1 : Meta Business Account (Business Manager)</span>
              </button>
            </h2>
            <div id="step1" class="accordion-collapse collapse" aria-labelledby="headingOne" data-bs-parent="#metaSetupGuide">
              <div class="accordion-body small">
                <ol class="mb-0 ps-3">
                  <li>Go to <a href="https://business.facebook.com/" target="_blank" rel="noopener">business.facebook.com</a> and log in with the client company's Facebook admin account.</li>
                  <li><strong>If new:</strong> click <em>"Create Account"</em> → enter company legal name, business email, business details → complete email verification.</li>
                  <li><strong>If exists:</strong> make sure you have <em>Admin</em> access to that Meta Business Account (this is required to create System Users and generate permanent tokens).</li>
                  <li>Open <em>Business Settings</em> (gear icon on the left bottom) → confirm the Business Account ID appears on the <em>Business Info</em> page (write it down for future).</li>
                </ol>
                <div class="alert alert-info mt-3 mb-0 small">
                  <strong>Why: </strong>Everything (WABA, phone number, apps, system users, access tokens) lives INSIDE a single Meta Business Account. You can have multiple WABAs & numbers under the same Business Account for the same company/group.
                </div>
              </div>
            </div>
          </div>

          <!-- Step 2 -->
          <div class="accordion-item">
            <h2 class="accordion-header" id="headingTwo">
              <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#step2" aria-expanded="false" aria-controls="step2">
                <span class="fw-bold">Step 2 : WhatsApp Business Account (WABA) &amp; Business Verification</span>
              </button>
            </h2>
            <div id="step2" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#metaSetupGuide">
              <div class="accordion-body small">
                <ol class="mb-0 ps-3">
                  <li>In Business Settings → left menu under <em>Accounts</em> → open <strong>WhatsApp Accounts</strong>.</li>
                  <li>Click <em>Add → Create a WhatsApp Business Account</em>.</li>
                  <li>Enter WABA Name (company name), Timezone, Website.</li>
                  <li>Complete Business Verification in <em>Business Settings → Security Center → Start Verification</em>. Meta asks for:
                    <ul class="mt-1">
                      <li>Legal business name &amp; address (must match company documents exactly)</li>
                      <li>Official document (GST / Certificate of Incorporation / Business License / Utility bill)</li>
                      <li>Email / phone domain verification (via email OTP or DNS TXT record)</li>
                    </ul>
                  </li>
                  <li>Once verified, in <em>WhatsApp Accounts</em> → select your WABA → copy the <strong>WABA ID</strong> (long numeric, top-right area) and paste it in the <em>WABA ID</em> field below.</li>
                </ol>
                <div class="alert alert-warning mt-3 mb-0 small">
                  <strong>Important: </strong>Without a fully verified WABA you get very limited message tiers. You can still test with a <em>Test Number</em> (Meta gives free one) — but for production, business verification + display name approval is mandatory.
                </div>
              </div>
            </div>
          </div>

          <!-- Step 3 -->
          <div class="accordion-item">
            <h2 class="accordion-header" id="headingThree">
              <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#step3" aria-expanded="false" aria-controls="step3">
                <span class="fw-bold">Step 3 : Add Phone Number &amp; Copy Phone Number ID</span>
              </button>
            </h2>
            <div id="step3" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#metaSetupGuide">
              <div class="accordion-body small">
                <ol class="mb-0 ps-3">
                  <li>Open your WABA (Business Settings → WhatsApp Accounts → select your WABA).</li>
                  <li>Click the <em>WhatsApp Manager</em> button (top-right).</li>
                  <li>Free <em>Test Number</em> users: it appears automatically under <em>Phone numbers</em> → click it → skip to step 6.</li>
                  <li>Real number users: <em>Add phone number</em> → enter number (with country code) → enter Display Name (must match business &amp; get Meta approval) → complete SMS/call OTP verification.</li>
                  <li>Click the number. The URL shows:<br>
                    <code class="d-block bg-light p-2 mt-1 rounded text-break">.../wa_manager/XXXXXXXXXXXXXXXX?phone_number_id=<strong>1216945994830075</strong>&amp;tab=...</code>
                  </li>
                  <li>Copy that <strong>phone_number_id</strong> (or open <em>API Setup</em> in WhatsApp Manager which shows it explicitly) &amp; paste it into the <em>Phone Number ID</em> field below.</li>
                </ol>
              </div>
            </div>
          </div>

          <!-- Step 4 -->
          <div class="accordion-item">
            <h2 class="accordion-header" id="headingFour">
              <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#step4" aria-expanded="false" aria-controls="step4">
                <span class="fw-bold">Step 4 : Create Facebook App &amp; Add WhatsApp Product</span>
              </button>
            </h2>
            <div id="step4" class="accordion-collapse collapse" aria-labelledby="headingFour" data-bs-parent="#metaSetupGuide">
              <div class="accordion-body small">
                <ol class="mb-0 ps-3">
                  <li>Go to <a href="https://developers.facebook.com/apps/" target="_blank" rel="noopener">developers.facebook.com/apps/</a> → <em>Create App</em>.</li>
                  <li>Choose <strong>Business</strong> as App type → Next.</li>
                  <li>Link it to the SAME Meta Business Account (Step 1). App Purpose = Business / Client.</li>
                  <li>Name it (e.g. "Acme Corp Chatbot App") + App contact email → Create App.</li>
                  <li>"Add products to your app" → find <strong>WhatsApp</strong> → <em>Set up</em>.</li>
                  <li>When asked: select your Meta Business Account (Step 1) + WABA (Step 2) → links them together.</li>
                  <li>Keep App in <em>Development Mode</em> while testing (only test numbers/registered testers work). For production: <em>App Review → Live Mode</em> (requires verified business).</li>
                </ol>
              </div>
            </div>
          </div>

          <!-- Step 5 -->
          <div class="accordion-item">
            <h2 class="accordion-header" id="headingFive">
              <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#step5" aria-expanded="false" aria-controls="step5">
                <span class="fw-bold">Step 5 : Configure Webhook (Callback URL &amp; Verify Token)</span>
              </button>
            </h2>
            <div id="step5" class="accordion-collapse collapse" aria-labelledby="headingFive" data-bs-parent="#metaSetupGuide">
              <div class="accordion-body small">
                <ol class="mb-0 ps-3">
                  <li>Inside Facebook App → Products → WhatsApp → <strong>Configuration</strong>.</li>
                  <li><em>Webhook</em> card → click <em>Edit</em>.</li>
                  <li>Fill in:
                    <ul class="mt-1">
                      <li><strong>Callback URL:</strong>
                        <div class="input-group mt-1">
                          <input type="text" readonly class="form-control form-control-sm" id="cbUrl" value="{{ rtrim(config('app.url'), '/') }}/meta/webhook">
                          <button class="btn btn-sm btn-outline-secondary" type="button" onclick="navigator.clipboard && navigator.clipboard.writeText(document.getElementById('cbUrl').value).then(()=>{const t=this.innerText;this.innerText='Copied!';setTimeout(()=>this.innerText=t,1200)})">Copy</button>
                        </div>
                      </li>
                      <li><strong>Verify token:</strong> copy the token from the <em>"Webhook Verify Token"</em> field in the <strong>Number Settings</strong> form below (use the <em>Copy</em> button next to it) and paste it here. This token is managed by the platform admin via the <code>.env</code> file.</li>
                    </ul>
                  </li>
                  <li><em>Verify and save</em> — Meta makes a GET call to us. If the token matches, webhook links successfully (green check).</li>
                  <li>Back on Configuration page → <em>Webhook fields → Manage</em> → subscribe to <strong><code>messages</code></strong> field (only this is needed). Done.</li>
                </ol>
                <div class="alert alert-success mt-3 mb-0 small">
                  <strong>Note: </strong>Callback URL must be <code>HTTPS</code> with a publicly trusted SSL certificate. Ngrok / Cloudflare tunnels work for local testing. The exact URL for THIS installation is pre-filled above (auto-copied from APP_URL).
                </div>
              </div>
            </div>
          </div>

          <!-- Step 6 -->
          <div class="accordion-item">
            <h2 class="accordion-header" id="headingSix">
              <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#step6" aria-expanded="false" aria-controls="step6">
                <span class="fw-bold">Step 6 : System User → Assign Assets → Generate Permanent Access Token</span>
              </button>
            </h2>
            <div id="step6" class="accordion-collapse collapse" aria-labelledby="headingSix" data-bs-parent="#metaSetupGuide">
              <div class="accordion-body small">
                <ol class="mb-0 ps-3">
                  <li>Back to Meta Business Settings → <em>Users → System Users</em>.</li>
                  <li><em>Add → Create System User</em> → name (e.g. "Acme Corp WhatsApp Bot") → Role = <strong>Admin</strong>.</li>
                  <li><em>Add assets</em> in "Assigned assets":
                    <ul class="mt-1">
                      <li>Asset type = <strong>Apps</strong> → your Facebook App (Step 4) → toggle "Manage App".</li>
                      <li>Asset type = <strong>WhatsApp Accounts</strong> → your WABA (Step 2) → toggle "Manage WhatsApp Business Account".</li>
                    </ul>
                    Save.
                  </li>
                  <li>Click <strong>Generate new token</strong>:
                    <ul class="mt-1">
                      <li>Select the same Facebook App.</li>
                      <li>Scopes (MANDATORY):
                        <span class="badge bg-light text-dark border me-1 mb-1">whatsapp_business_messaging</span>
                        <span class="badge bg-light text-dark border me-1 mb-1">whatsapp_business_management</span>
                        <span class="badge bg-light text-dark border mb-1">business_management</span>
                      </li>
                      <li>Token Expiration: choose <strong>Never</strong> (permanent token).</li>
                      <li><em>Generate token</em> → Meta shows it ONCE. Copy it immediately.</li>
                    </ul>
                  </li>
                  <li>Paste the long token into the <em>Access Token</em> field below (stored encrypted, never shared with 3rd parties).</li>
                </ol>
              </div>
            </div>
          </div>

          <!-- Step 7 -->
          <div class="accordion-item">
            <h2 class="accordion-header" id="headingSeven">
              <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#step7" aria-expanded="false" aria-controls="step7">
                <span class="fw-bold">Step 7 : Graph API Version &amp; Save Settings</span>
              </button>
            </h2>
            <div id="step7" class="accordion-collapse collapse" aria-labelledby="headingSeven" data-bs-parent="#metaSetupGuide">
              <div class="accordion-body small">
                <ol class="mb-0 ps-3">
                  <li>Facebook App → WhatsApp → <em>API Setup</em> / <em>Getting Started</em>. Meta shows the current Graph API version string (e.g. <code>v23.0</code>).</li>
                  <li>Copy that version string (including the <code>v</code>) and paste it into the <em>Graph API Version</em> field below (e.g. <code>v23.0</code>).</li>
                  <li>Fill/verify all fields below → click <em>Save Settings</em>. After saving, trigger a test message from the conversation simulator to confirm credentials work.</li>
                </ol>
                <div class="alert alert-dark mt-3 mb-0 small">
                  <strong>Credentials checklist before save:</strong><br>
                  ✅ Phone Number ID (numeric, from API Setup)<br>
                  ✅ WABA ID (numeric, from Business Settings)<br>
                  ✅ Access Token (System User permanent token, Never expires)<br>
                  ✅ Graph API Version (with v prefix, e.g. v23.0)<br>
                  ✅ Webhook Callback URL added + <code>messages</code> field subscribed
                </div>
              </div>
            </div>
          </div>

        </div>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-body">
        <h6 class="card-title text-muted mb-4 border-bottom pb-2">NUMBER SETTINGS</h6>

        <form action="{{ route('whatsapp-numbers.config.update', $phoneNumberId) }}" method="POST">
          @csrf
          @method('PUT')

          @if(!$isCompanyUser)
          <div class="mb-3">
            <label class="form-label">Company</label>
            <select name="company_id" class="form-select">
              <option value="">-- No Company --</option>
              @foreach($companies as $c)
                <option value="{{ $c->id }}" @selected($number->company_id == $c->id)>{{ $c->name }}</option>
              @endforeach
            </select>
            <small class="text-muted">Reassign this number to a different company, or unassign it.</small>
          </div>
          @endif

          <div class="mb-3">
            <label class="form-label">Phone Number ID <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="phone_number_id" value="{{ old('phone_number_id', $phoneNumberId) }}" required>
            <small class="text-muted">Meta's phone number ID. Changing this will update the webhook routing.</small>
          </div>

          <div class="mb-3">
            <label class="form-label">Label <span class="text-muted small">(your own reference)</span></label>
            <input type="text" class="form-control" name="label" value="{{ old('label', $number->label) }}">
          </div>

          <div class="mb-3">
            <label class="form-label">Display Number</label>
            <input type="text" class="form-control" name="display_number" value="{{ old('display_number', $number->display_number) }}">
          </div>

          <div class="mb-3">
            <label class="form-label">WABA ID</label>
            <input type="text" class="form-control" name="waba_id" value="{{ old('waba_id', $number->waba_id) }}">
          </div>

          <div class="mb-3">
            <label class="form-label">Access Token <span class="text-muted small">(leave blank to keep the current one)</span></label>
            <textarea class="form-control font-monospace small" name="access_token" rows="2" placeholder="Only fill this in to replace the saved token"></textarea>
          </div>

          <div class="mb-3">
            <label class="form-label">Graph API Version</label>
            <input type="text" class="form-control" name="graph_version" value="{{ old('graph_version', $number->graph_version) }}">
          </div>

          <div class="mb-3">
            <label class="form-label">Webhook Verify Token <span class="text-danger">*</span></label>
            @php
              $globalVerifyToken = env('META_WEBHOOK_VERIFY_TOKEN') ?? config('services.meta.webhook_verify_token') ?? '';
            @endphp
            <div class="input-group">
              <input type="text" readonly class="form-control font-monospace" id="globalVerifyTokenEdit" value="{{ e($globalVerifyToken) }}" placeholder="Set META_WEBHOOK_VERIFY_TOKEN in .env first">
              <button class="btn btn-outline-secondary" type="button" id="copyGlobalTokenEditBtn" @if(empty($globalVerifyToken)) disabled @endif>Copy</button>
            </div>
            <small class="text-muted">This exact same token must be pasted in Meta App → WhatsApp → Configuration → Edit webhook. The token is managed by the platform admin via the <code>.env</code> file — simply copy it here using the Copy button above.</small>
          </div>

          <div class="row mb-4">
            <div class="col-md-6 mb-3 mb-md-0">
              <label class="form-label">Valid From <span class="text-muted small">(Leave empty for no start date)</span></label>
              <input type="date" class="form-control" name="valid_from" value="{{ old('valid_from', optional($number->valid_from)->format('Y-m-d')) }}">
            </div>
            <div class="col-md-6">
              <label class="form-label">Valid To (Expiry) <span class="text-muted small">(Leave empty for no expiry)</span></label>
              <input type="date" class="form-control" name="expiry_date" value="{{ old('expiry_date', optional($number->expiry_date)->format('Y-m-d')) }}">
            </div>
          </div>

          <div class="d-flex align-items-center justify-content-between bg-light p-3 rounded mb-4">
            <div>
              <p class="mb-0 fw-bold">Number Status</p>
              <small class="text-muted">Turn off to take it offline without deleting it</small>
            </div>
            <div class="form-check form-switch mb-0">
              <input type="checkbox" class="form-check-input" name="is_active" value="1" @checked(old('is_active', $number->is_active)) style="width: 40px; height: 20px;">
            </div>
          </div>

          <div class="d-flex justify-content-end border-top pt-3">
            <button type="submit" class="btn btn-primary">Save Settings</button>
          </div>
        </form>
      </div>
    </div>


    @if(!$isCompanyUser)
    <div class="card mt-3 border-danger">
      <div class="card-body">
        <h6 class="card-title text-danger mb-3 border-bottom pb-2">DANGER ZONE</h6>
        <p class="text-muted small mb-3">Permanently removes this number's config, every FAQ, its bot persona, and its stored access token. The number will stop receiving automated replies immediately. This cannot be undone.</p>
        <form action="{{ route('whatsapp-numbers.destroy', $phoneNumberId) }}" method="POST"
              onsubmit="return confirm('Permanently remove \'{{ $number->label ?: $phoneNumberId }}\' and ALL of its FAQs? This cannot be undone.');">
          @csrf
          @method('DELETE')
          <button type="submit" class="btn btn-outline-danger w-100">Remove This Number Permanently</button>
        </form>
      </div>
    </div>
    @endif
  </div>

</div>
@endsection

@push('custom-scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.8.3/tinymce.min.js" referrerpolicy="origin"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        tinymce.init({
            selector: '#answer, #greeting_message, #fallback_message',
            menubar: false,
            plugins: 'link lists',
            toolbar: 'undo redo | bold italic | alignleft aligncenter alignright | bullist numlist | link',
            promotion: false,
            branding: false,
            setup: function(editor) {
                editor.on('submit', function() {
                    editor.save();
                });
            }
        });

        // --- Outer guide card collapse toggle + chevron rotation ---
        var guideCardBody = document.getElementById('guideCardBody');
        var guideChevron = document.querySelector('#guideCardChevronBtn [data-lucide="chevron-down"], #guideCardChevronBtn svg');
        var guideHeader = document.getElementById('guideCardHeader');
        function rotateChevron() {
            var isOpen = guideCardBody.classList.contains('show');
            if (guideChevron) {
                guideChevron.style.transition = 'transform 0.25s ease';
                guideChevron.style.transform = isOpen ? 'rotate(180deg)' : 'rotate(0deg)';
            }
            if (guideHeader) guideHeader.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        }
        rotateChevron();
        guideCardBody.addEventListener('shown.bs.collapse', rotateChevron);
        guideCardBody.addEventListener('hidden.bs.collapse', rotateChevron);

        // Sync TinyMCE content to textarea before form submit and validate for FAQ
        var addBtn = document.getElementById('addFaqBtn');
        if (addBtn) {
            addBtn.closest('form').addEventListener('submit', function(e) {
                tinymce.triggerSave();
                var val = document.getElementById('answer').value.trim();
                if (!val || val === '<p></p>' || val === '<p><br></p>') {
                    e.preventDefault();
                    alert('Please enter an answer.');
                    tinymce.get('answer').focus();
                }
            });
        }

        // Sync TinyMCE content to textarea before form submit and validate for Messages
        var msgForm = document.getElementById('messagesForm');
        if (msgForm) {
            msgForm.addEventListener('submit', function(e) {
                tinymce.triggerSave();
                var greeting = document.getElementById('greeting_message').value.trim();
                if (!greeting || greeting === '<p></p>' || greeting === '<p><br></p>') {
                    e.preventDefault();
                    alert('Please enter a greeting message.');
                    tinymce.get('greeting_message').focus();
                    return;
                }
                var fallback = document.getElementById('fallback_message').value.trim();
                if (!fallback || fallback === '<p></p>' || fallback === '<p><br></p>') {
                    e.preventDefault();
                    alert('Please enter a fallback message.');
                    tinymce.get('fallback_message').focus();
                    return;
                }
            });
        }

        // Expand / Collapse all accordion steps
        var toggleBtn = document.getElementById('toggleAllStepsBtn');
        var toggleLabel = document.getElementById('toggleAllStepsLabel');
        var collapses = document.querySelectorAll('#metaSetupGuide .accordion-collapse');
        var allExpanded = false;

        if (toggleBtn) {
            toggleBtn.addEventListener('click', function() {
                allExpanded = !allExpanded;
                collapses.forEach(function(el) {
                    var bsCollapse = bootstrap.Collapse.getOrCreateInstance(el, { toggle: false });
                    if (allExpanded) {
                        bsCollapse.show();
                    } else {
                        bsCollapse.hide();
                    }
                });
                toggleLabel.textContent = allExpanded ? 'Collapse All Steps' : 'Expand All Steps';
            });

            collapses.forEach(function(el) {
                el.addEventListener('hidden.bs.collapse', syncToggleLabel);
                el.addEventListener('shown.bs.collapse', syncToggleLabel);
            });

            function syncToggleLabel() {
                var anyOpen = Array.from(collapses).some(function(el) {
                    return el.classList.contains('show');
                });
                var allOpen = Array.from(collapses).every(function(el) {
                    return el.classList.contains('show');
                });
                allExpanded = allOpen;
                if (!anyOpen) {
                    allExpanded = false;
                    toggleLabel.textContent = 'Expand All Steps';
                } else if (allOpen) {
                    toggleLabel.textContent = 'Collapse All Steps';
                }
            }
        }

        // --- Verify Token: copy button (global token from .env, read-only) ---
        var globalEditInput = document.getElementById('globalVerifyTokenEdit');
        var globalEditCopyBtn = document.getElementById('copyGlobalTokenEditBtn');
        if (globalEditCopyBtn && globalEditInput) {
            globalEditCopyBtn.addEventListener('click', function() {
                try {
                    globalEditInput.select();
                    globalEditInput.setSelectionRange(0, 99999);
                    var oldText = globalEditCopyBtn.innerText;
                    navigator.clipboard.writeText(globalEditInput.value).then(function() {
                        globalEditCopyBtn.innerText = 'Copied!';
                        setTimeout(function() { globalEditCopyBtn.innerText = oldText; }, 1200);
                    });
                } catch(e) {}
            });
        }
    });
</script>
@endpush
