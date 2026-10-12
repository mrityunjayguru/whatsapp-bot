@extends('layout.master')

@section('content')
<nav class="page-breadcrumb d-flex align-items-center mb-4">
  <a href="{{ route('whatsapp-numbers.index') }}" class="btn btn-outline-secondary me-3"><i data-lucide="arrow-left" class="icon-sm me-2"></i> Back to WhatsApp Numbers</a>
  <ol class="breadcrumb mb-0">
    <li class="breadcrumb-item"><a href="{{ route('whatsapp-numbers.index') }}">WhatsApp Numbers</a></li>
    <li class="breadcrumb-item active" aria-current="page">Register</li>
  </ol>
</nav>

@if ($errors->any())
  <div class="alert alert-danger mb-4">
    <ul class="mb-0">
      @foreach ($errors->all() as $error)
        <li>{{ $error }}</li>
      @endforeach
    </ul>
  </div>
@endif

<div class="row justify-content-center">
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
            <p class="text-muted small mb-0">Follow these steps in Meta Business Manager to collect the values you need for this form. Each company must use its OWN Meta Business Account &amp; WABA.</p>
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
                <span class="fw-bold">Step 1 : Create / Use Meta Business Account (Business Manager)</span>
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
                <span class="fw-bold">Step 2 : Create WhatsApp Business Account (WABA) & verify Business</span>
              </button>
            </h2>
            <div id="step2" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#metaSetupGuide">
              <div class="accordion-body small">
                <ol class="mb-0 ps-3">
                  <li>In Business Settings → left menu under <em>Accounts</em> → open <strong>WhatsApp Accounts</strong>.</li>
                  <li>Click <em>Add → Create a WhatsApp Business Account</em>.</li>
                  <li>Enter:
                    <ul class="mt-1">
                      <li><strong>WABA Name</strong> (usually the company name, e.g. "Acme Corp WABA")</li>
                      <li>Timezone, Website (optional but helps later in business verification)</li>
                    </ul>
                  </li>
                  <li>Complete the Business Verification flow in <em>Business Settings → Security Center → Start Verification</em>. Meta will ask for:
                    <ul class="mt-1">
                      <li>Legal business name & address (must match company documents exactly)</li>
                      <li>Official document (GST / Certificate of Incorporation / Business License / Utility bill)</li>
                      <li>Email / phone domain verification (via email OTP or DNS TXT record)</li>
                    </ul>
                  </li>
                  <li>Once verified, come back to <em>WhatsApp Accounts</em> → select your WABA → on the top-right you will see <strong>WABA ID</strong> (long numeric ID). Copy this value → paste it into the <em>WABA ID</em> field in this form above.</li>
                </ol>
                <div class="alert alert-warning mt-3 mb-0 small">
                  <strong>Important: </strong>Without a fully verified WABA you get very limited message tiers. You can still test with a <em>Test Number</em> (meta gives free one) — but for production, business verification + display name approval is mandatory.
                </div>
              </div>
            </div>
          </div>

          <!-- Step 3 -->
          <div class="accordion-item">
            <h2 class="accordion-header" id="headingThree">
              <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#step3" aria-expanded="false" aria-controls="step3">
                <span class="fw-bold">Step 3 : Add / Register Phone Number in the WABA + Get Phone Number ID</span>
              </button>
            </h2>
            <div id="step3" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#metaSetupGuide">
              <div class="accordion-body small">
                <ol class="mb-0 ps-3">
                  <li>Open your WABA (Business Settings → WhatsApp Accounts → select your WABA).</li>
                  <li>Click the <em>WhatsApp Manager</em> button (top-right) — you land inside the <strong>WhatsApp Manager</strong> dashboard for that WABA.</li>
                  <li>If using Meta's free <em>Test Number</em>: it appears automatically under <em>Phone numbers</em>. Click it → skip to step 6.</li>
                  <li>To add a real number: click <em>Add phone number</em> → enter the client's phone number (with country code) → choose a Display Name (this must match business + get approved by Meta) → choose Category & Description.</li>
                  <li>Complete phone number verification via SMS/call OTP sent to that number.</li>
                  <li>Once the number is listed, click on it. The URL in your browser will look like:<br>
                    <code class="d-block bg-light p-2 mt-1 rounded text-break">.../wa_manager/XXXXXXXXXXXXXXXX?phone_number_id=<strong>1216945994830075</strong>&amp;tab=...</code>
                  </li>
                  <li>Copy the <strong>phone_number_id</strong> from the URL (or use the <em>API Setup</em> page on the left inside WhatsApp Manager — it shows "Phone number ID" explicitly). Paste it into the <em>Phone Number ID</em> field in this form.</li>
                </ol>
              </div>
            </div>
          </div>

          <!-- Step 4 -->
          <div class="accordion-item">
            <h2 class="accordion-header" id="headingFour">
              <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#step4" aria-expanded="false" aria-controls="step4">
                <span class="fw-bold">Step 4 : Create Facebook App + add WhatsApp Product</span>
              </button>
            </h2>
            <div id="step4" class="accordion-collapse collapse" aria-labelledby="headingFour" data-bs-parent="#metaSetupGuide">
              <div class="accordion-body small">
                <ol class="mb-0 ps-3">
                  <li>Go to <a href="https://developers.facebook.com/apps/" target="_blank" rel="noopener">developers.facebook.com/apps/</a> → <em>Create App</em>.</li>
                  <li>Choose <strong>Business</strong> as App type → Next.</li>
                  <li>Link it to the SAME Meta Business Account you used in Step 1 (App Purpose = Business / Client).</li>
                  <li>Give it a name (e.g. "Acme Corp Chatbot App") + App contact email → Create App.</li>
                  <li>On the "Add products to your app" page, find <strong>WhatsApp</strong> → click <em>Set up</em>.</li>
                  <li>Meta will ask: "Use your business account?" → select the Meta Business Account (Step 1) and the WABA (Step 2) → it links them together.</li>
                  <li>App is now connected to WhatsApp. Keep App in <em>Development Mode</em> while testing (only test numbers/registered testers can send/receive). For production, do <em>App Review → Live Mode</em> (requires business verification already done).</li>
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
                  <li>Inside your Facebook App → Products → WhatsApp → <strong>Configuration</strong>.</li>
                  <li>Find the <em>Webhook</em> card → click <em>Edit</em>.</li>
                  <li>Fill in:
                    <ul class="mt-1">
                      <li><strong>Callback URL:</strong>
                        <div class="input-group mt-1">
                          <input type="text" readonly class="form-control form-control-sm" id="cbUrl" value="{{ rtrim(config('app.url'), '/') }}/meta/webhook">
                          <button class="btn btn-sm btn-outline-secondary" type="button" onclick="navigator.clipboard && navigator.clipboard.writeText(document.getElementById('cbUrl').value).then(()=>{this.textContent='Copied!'})">Copy</button>
                        </div>
                      </li>
                      <li><strong>Verify token:</strong> copy the token from the <em>"Webhook Verify Token"</em> field in the <strong>Register Number</strong> form below (use the <em>Copy</em> button next to it) and paste it here. This value is set by the platform admin — if it appears blank, contact the admin.</li>
                    </ul>
                  </li>
                  <li>Click <em>Verify and save</em> — Meta makes a GET call to our Callback URL with that token. If it matches, webhook is linked (green checkmark).</li>
                  <li>On the same Configuration page under <em>Webhook fields</em> → click <em>Manage</em> → subscribe to the field <strong><code>messages</code></strong> (only this field is needed for inbound messages). Click Done.</li>
                </ol>
                <div class="alert alert-success mt-3 mb-0 small">
                  <strong>Note: </strong>Callback URL must be <code>HTTPS</code> with a publicly trusted SSL certificate. Ngrok / Cloudflare tunnels work for local testing. The exact URL for this installation is pre-filled above.
                </div>
              </div>
            </div>
          </div>

          <!-- Step 6 -->
          <div class="accordion-item">
            <h2 class="accordion-header" id="headingSix">
              <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#step6" aria-expanded="false" aria-controls="step6">
                <span class="fw-bold">Step 6 : Create System User + assign WhatsApp Asset + generate Permanent Access Token</span>
              </button>
            </h2>
            <div id="step6" class="accordion-collapse collapse" aria-labelledby="headingSix" data-bs-parent="#metaSetupGuide">
              <div class="accordion-body small">
                <ol class="mb-0 ps-3">
                  <li>Back to Meta Business Settings → <em>Users → System Users</em>.</li>
                  <li>Click <em>Add → Create System User</em> → give it a name (e.g. "Acme Corp WhatsApp Bot") and choose Role = <strong>Admin</strong>.</li>
                  <li>Once created, click <em>Add assets</em> in the "Assigned assets" section:
                    <ul class="mt-1">
                      <li>Asset type = <strong>Apps</strong> → select your Facebook App (Step 4) → enable toggle "Manage App" (full control).</li>
                      <li>Asset type = <strong>WhatsApp Accounts</strong> → select your WABA (Step 2) → enable toggle "Manage WhatsApp Business Account" (or at least "Develop" + partial manage).</li>
                    </ul>
                    Save changes.
                  </li>
                  <li>Now at the top, click <strong>Generate new token</strong>:
                    <ul class="mt-1">
                      <li>Select the same Facebook App.</li>
                      <li>In the scopes list, <strong>select at least these:</strong>
                        <span class="badge bg-light text-dark border me-1 mb-1">whatsapp_business_messaging</span>
                        <span class="badge bg-light text-dark border me-1 mb-1">whatsapp_business_management</span>
                        <span class="badge bg-light text-dark border mb-1">business_management</span>
                      </li>
                      <li>Token Expiration: choose <strong>Never</strong> (permanent token) to avoid rotation issues.</li>
                      <li>Click <em>Generate token</em> → Meta shows the token <strong>ONLY ONCE</strong>. Copy it immediately.</li>
                    </ul>
                  </li>
                  <li>Paste this long token into the <em>Access Token</em> field in this form (it is stored encrypted in DB & never shared).</li>
                </ol>
              </div>
            </div>
          </div>

          <!-- Step 7 -->
          <div class="accordion-item">
            <h2 class="accordion-header" id="headingSeven">
              <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#step7" aria-expanded="false" aria-controls="step7">
                <span class="fw-bold">Step 7 : Graph API Version &amp; Test Send</span>
              </button>
            </h2>
            <div id="step7" class="accordion-collapse collapse" aria-labelledby="headingSeven" data-bs-parent="#metaSetupGuide">
              <div class="accordion-body small">
                <ol class="mb-0 ps-3">
                  <li>In your Facebook App → WhatsApp → <em>API Setup</em> (or <em>Getting Started</em>), Meta shows you the Graph API version it is using, e.g. <code>v23.0</code>.</li>
                  <li>Copy that version string (including the <code>v</code>) and paste it into the <em>Graph API Version</em> field above (e.g. <code>v23.0</code>).</li>
                  <li>Fill all the fields on this Register form → click <em>Register Number</em>. After saving, a free test message can be triggered from the platform's conversation simulator to confirm the credentials work end-to-end.</li>
                </ol>
                <div class="alert alert-dark mt-3 mb-0 small">
                  <strong>Quick credentials checklist before saving:</strong><br>
                  ✅ Company selected (this number belongs to that client)<br>
                  ✅ Phone Number ID (numeric, from API Setup)<br>
                  ✅ WABA ID (numeric, from Business Settings)<br>
                  ✅ Access Token (System User permanent token, never expires)<br>
                  ✅ Graph API Version (e.g. v23.0)
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
        <h6 class="card-title text-muted mb-4 border-bottom pb-2">REGISTER A CLIENT'S WHATSAPP NUMBER</h6>
        <p class="text-muted small">This number must already be set up in Meta's WhatsApp Business Platform (its own WABA, business-verified, with a generated access token) — this form just connects it to this Chatbot and gives it its own isolated FAQ knowledge base and bot persona.</p>

        <form action="{{ route('whatsapp-numbers.store') }}" method="POST">
          @csrf
          <div class="mb-3">
            <label class="form-label">Company <span class="text-danger">*</span></label>
            <select class="form-select @error('company_id') is-invalid @enderror" name="company_id" required>
                <option value="" disabled selected>Select a Company</option>
                @foreach($companies as $company)
                    <option value="{{ $company->id }}" {{ old('company_id') == $company->id ? 'selected' : '' }}>
                        {{ $company->name }} ({{ $company->contact_email }})
                    </option>
                @endforeach
            </select>
            @error('company_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>

          <div class="mb-3">
            <label class="form-label">Label <span class="text-muted small">(your own reference)</span></label>
            <input type="text" class="form-control" name="label" placeholder="e.g. Acme Corp - Support" value="{{ old('label') }}">
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Phone Number ID <span class="text-muted small">(optional)</span></label>
              <input type="text" class="form-control @error('phone_number_id') is-invalid @enderror" name="phone_number_id" placeholder="e.g. 111222333444555" value="{{ old('phone_number_id') }}">
              <small class="text-muted">Meta's own id for this number - from WhatsApp Business Platform &gt; API Setup, and present in every inbound webhook's metadata.</small>
              @error('phone_number_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">WABA ID <span class="text-muted small">(optional)</span></label>
              <input type="text" class="form-control" name="waba_id" value="{{ old('waba_id') }}">
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label">Display Number <span class="text-muted small">(your own reference)</span></label>
            <input type="text" class="form-control" name="display_number" placeholder="+91 98765 43210" value="{{ old('display_number') }}">
          </div>

          <div class="mb-3">
            <label class="form-label">Access Token <span class="text-muted small">(optional)</span></label>
            <textarea class="form-control font-monospace small" name="access_token" rows="3" placeholder="Permanent or system-user access token from Meta">{{ old('access_token') }}</textarea>
            <small class="text-muted">Stored encrypted. Used only to send WhatsApp messages as THIS number - never shared with the Python bot service.</small>
            @error('access_token') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
          </div>

          <div class="mb-3">
            <label class="form-label">Graph API Version</label>
            <input type="text" class="form-control" name="graph_version" value="{{ old('graph_version', 'v23.0') }}">
          </div>

          <div class="mb-3">
            <label class="form-label">Webhook Verify Token <span class="text-danger">*</span></label>
            @php
              $globalVerifyToken = env('META_WEBHOOK_VERIFY_TOKEN') ?? config('services.meta.webhook_verify_token') ?? '';
            @endphp
            <div class="input-group">
              <input type="text" readonly class="form-control font-monospace" id="globalVerifyTokenCreate" value="{{ e($globalVerifyToken) }}" placeholder="Set META_WEBHOOK_VERIFY_TOKEN in .env first">
              <button class="btn btn-outline-secondary" type="button" id="copyGlobalTokenCreateBtn" @if(empty($globalVerifyToken)) disabled @endif>Copy</button>
            </div>
            <small class="text-muted">This exact same token must be pasted in Meta App → WhatsApp → Configuration → Edit webhook. The token is managed by the platform admin via the <code>.env</code> file — simply copy it here using the Copy button above.</small>
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Valid From</label>
              <input type="date" class="form-control @error('valid_from') is-invalid @enderror" name="valid_from" value="{{ old('valid_from', now()->format('Y-m-d')) }}">
              @error('valid_from') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Valid To (Expiry)</label>
              <input type="date" class="form-control @error('expiry_date') is-invalid @enderror" name="expiry_date" value="{{ old('expiry_date', now()->addMonths(2)->format('Y-m-d')) }}">
              @error('expiry_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
          </div>

          <div class="d-flex justify-content-end gap-2 border-top pt-3 mt-3">
            <a href="{{ route('whatsapp-numbers.index') }}" class="btn btn-outline-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">Register Number</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection

@push('custom-scripts')
<script>
    document.addEventListener("DOMContentLoaded", function() {
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

        // --- Expand / Collapse all inner accordion steps ---
        var toggleBtn = document.getElementById('toggleAllStepsBtn');
        var toggleLabel = document.getElementById('toggleAllStepsLabel');
        var collapses = document.querySelectorAll('#metaSetupGuide .accordion-collapse');
        var allExpanded = false;

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

        // Track individual open/close to sync the toggle button label
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

        // --- Verify Token: copy button (global token from .env, read-only) ---
        var globalCreateInput = document.getElementById('globalVerifyTokenCreate');
        var globalCreateCopyBtn = document.getElementById('copyGlobalTokenCreateBtn');
        if (globalCreateCopyBtn && globalCreateInput) {
            globalCreateCopyBtn.addEventListener('click', function() {
                try {
                    globalCreateInput.select();
                    globalCreateInput.setSelectionRange(0, 99999);
                    var oldText = globalCreateCopyBtn.innerText;
                    navigator.clipboard.writeText(globalCreateInput.value).then(function() {
                        globalCreateCopyBtn.innerText = 'Copied!';
                        setTimeout(function() { globalCreateCopyBtn.innerText = oldText; }, 1200);
                    });
                } catch(e) {}
            });
        }
    });
</script>
@endpush
