import os

fpath = r'c:\xampp\htdocs\chatbot\resources\views\contacts\show.blade.php'

if os.path.exists(fpath):
    with open(fpath, 'r', encoding='utf-8') as f:
        content = f.read()

    # Revert Header
    old_th = """@if($isCompanyAdmin || auth()->id() === 1)
                  <th class="pt-2 pb-2 text-center">ACTION</th>
                  @endif"""
    new_th = '<th class="pt-2 pb-2 text-center">ACTION</th>'
    content = content.replace(old_th, new_th)
    
    # Add employee ID fetching before the loop
    loop_start = '@forelse($contact->conversations as $conv)'
    employee_fetch = """@php
                  $currentEmployeeId = null;
                  if (!$isCompanyAdmin && auth()->id() !== 1) {
                      $emp = \App\Models\Employee::where('email', auth()->user()->email)->first();
                      if ($emp) {
                          $currentEmployeeId = $emp->id;
                      }
                  }
                @endphp
                @forelse($contact->conversations as $conv)"""
    content = content.replace(loop_start, employee_fetch)

    # Replace TD logic
    old_td = """                    @if($isCompanyAdmin || auth()->id() === 1)
                    <td class="text-center">
                      <a href="{{ route('conversations.show', $conv->id) }}" class="btn btn-sm btn-light btn-icon border" title="View Conversation">
                        <i data-lucide="eye" class="icon-sm"></i>
                      </a>
                    </td>
                    @endif"""
    
    new_td = """                    @php
                      $canViewConv = $isCompanyAdmin || auth()->id() === 1;
                      if (!$canViewConv && $currentEmployeeId) {
                          if ($conv->assigned_tenant_user_id === $currentEmployeeId) {
                              $canViewConv = true;
                          } else {
                              $history = is_array($conv->assignment_history) ? $conv->assignment_history : json_decode($conv->assignment_history, true) ?? [];
                              foreach ($history as $record) {
                                  if (isset($record['employee_id']) && $record['employee_id'] == $currentEmployeeId) {
                                      $canViewConv = true;
                                      break;
                                  }
                              }
                          }
                      }
                    @endphp
                    <td class="text-center">
                      @if($canViewConv)
                      <a href="{{ route('conversations.show', $conv->id) }}" class="btn btn-sm btn-light btn-icon border" title="View Conversation">
                        <i data-lucide="eye" class="icon-sm"></i>
                      </a>
                      @endif
                    </td>"""
    content = content.replace(old_td, new_td)
    
    # Revert colspan
    old_colspan = '<td colspan="{{ ($isCompanyAdmin || auth()->id() === 1) ? 3 : 2 }}" class="text-center text-muted">No conversations found.</td>'
    new_colspan = '<td colspan="3" class="text-center text-muted">No conversations found.</td>'
    content = content.replace(old_colspan, new_colspan)
    
    with open(fpath, 'w', encoding='utf-8') as f:
        f.write(content)
    print("Patched show.blade.php again")
else:
    print("File not found")
