import os

fpath = r'c:\xampp\htdocs\chatbot\resources\views\contacts\show.blade.php'

if os.path.exists(fpath):
    with open(fpath, 'r', encoding='utf-8') as f:
        content = f.read()

    # 1. formatted_id patch (only table row left since timeline is being removed, but let's replace globally)
    content = content.replace("CONV-{{ str_pad($conv->id, 5, '0', STR_PAD_LEFT) }}", "{{ $conv->formatted_id }}")

    # 2. Extract SECTION 6 properly
    sec6_str = """    <!-- SECTION 6: LOCATION -->
    <div class="card w-100 grid-margin">
      <div class="card-body">
        <h6 class="card-title text-muted mb-1">SECTION 6: LOCATION</h6>
        <p class="text-muted tx-13 mb-4">Contact location details</p>
        
        <div class="row g-3">
          <div class="col-md-6">
            <div class="text-muted small mb-1">Country</div>
            <div class="fw-semibold">{{ $contact->country ?? '—' }}</div>
          </div>
          <div class="col-md-6">
            <div class="text-muted small mb-1">State</div>
            <div class="fw-semibold">{{ $contact->state ?? '—' }}</div>
          </div>
          <div class="col-md-6">
            <div class="text-muted small mb-1">City</div>
            <div class="fw-semibold">{{ $contact->city ?? '—' }}</div>
          </div>
          <div class="col-md-6">
            <div class="text-muted small mb-1">Pincode</div>
            <div class="fw-semibold">{{ $contact->pincode ?? '—' }}</div>
          </div>
        </div>
      </div>
    </div>"""

    content = content.replace(sec6_str, '')

    # 3. Replace right column (Timeline) with new Conversations Table + Section 6
    timeline_str = """  <!-- SECTION 3: ACTIVITY TIMELINE -->
  <div class="col-md-6 col-lg-5 grid-margin stretch-card">
    <div class="card w-100">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-4">
          <h6 class="card-title text-muted mb-0">SECTION 3: ACTIVITY TIMELINE</h6>
          <button class="btn btn-sm btn-outline-light border text-dark fw-bold d-flex align-items-center">
            <i data-lucide="calendar" class="icon-sm me-2 text-muted"></i> {{ now()->format('M j, Y') }}
          </button>
        </div>

        <div class="border rounded p-4 mb-2">
          <div style="border-left: 1px solid #e9ecef; padding-left: 24px; position: relative; margin-left: 5px;">
            
            @forelse($contact->conversations->take(5) as $conv)
              @php
                  $dotColor = 'bg-secondary';
                  if ($conv->status === 'OPEN') $dotColor = 'bg-info';
                  elseif ($conv->status === 'RESOLVED') $dotColor = 'bg-success';
              @endphp
              <div class="mb-4 w-100" style="position: relative;">
                <span class="{{ $dotColor }} rounded-circle" style="width: 12px; height: 12px; position: absolute; left: -31px; top: 4px; border: 2px solid white; box-shadow: 0 0 0 1px #e9ecef;"></span>
                <div class="d-flex justify-content-between align-items-start w-100">
                  <h6 class="mb-1 fw-bold text-dark" style="font-size: 14.5px;">Conversation {{ ucfirst(strtolower($conv->status)) }}</h6>
                  <span class="text-muted text-nowrap ms-2" style="font-size: 11px;">{{ $conv->created_at->format('h:i A') }}</span>
                </div>
                <p class="text-muted mb-1 text-wrap" style="font-size: 13px;">{{ $conv->formatted_id }} — {{ $conv->title ?? 'No Title' }}</p>
                <p class="text-muted mb-0" style="font-size: 12px;">by <span class="text-secondary">{{ $conv->assignedUser->name ?? 'System' }}</span></p>
              </div>
            @empty
              <p class="text-muted tx-13">No recent activity.</p>
            @endforelse

          </div>
        </div>
      </div>
    </div>
  </div>"""

    new_right_col = """  <!-- SECTION 3: CONVERSATIONS -->
  <div class="col-md-6 col-lg-5 d-flex flex-column">
    <div class="card w-100 grid-margin">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-4">
          <h6 class="card-title text-muted mb-0">SECTION 3: CONVERSATIONS</h6>
        </div>

        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead class="bg-light">
              <tr>
                <th class="pt-2 pb-2">CONVERSATION ID</th>
                <th class="pt-2 pb-2">STATUS</th>
                <th class="pt-2 pb-2 text-center">ACTION</th>
              </tr>
            </thead>
            <tbody>
              @forelse($contact->conversations as $conv)
                <tr>
                  <td class="fw-bold text-dark">#{{ $conv->formatted_id }}</td>
                  <td>
                    @if($conv->status === 'OPEN')
                      <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3">Open</span>
                    @elseif($conv->status === 'RESOLVED')
                      <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3">Resolved</span>
                    @elseif($conv->status === 'CLOSED')
                      <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle rounded-pill px-3">Closed</span>
                    @else
                      <span class="badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill px-3">{{ $conv->status }}</span>
                    @endif
                  </td>
                  <td class="text-center">
                    <a href="{{ route('conversations.show', $conv->id) }}" class="btn btn-sm btn-light btn-icon border" title="View Conversation">
                      <i data-lucide="eye" class="icon-sm"></i>
                    </a>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="3" class="text-center text-muted">No conversations found.</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>

""" + sec6_str + "\n  </div>"

    content = content.replace(timeline_str, new_right_col)
    
    # Also fix the #CONV- padding at the bottom of the page in SECTION 4
    content = content.replace("#CONV-{{ str_pad($conv->id, 5, '0', STR_PAD_LEFT) }}", "#{{ $conv->formatted_id }}")

    with open(fpath, 'w', encoding='utf-8') as f:
        f.write(content)
    print(f'Patched {fpath}')
else:
    print('File not found')
