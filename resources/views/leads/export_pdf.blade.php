<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Leads Export — {{ now()->format('d M Y') }}</title>
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: Arial, sans-serif; font-size: 11px; color: #1f2937; background: #fff; padding: 20px; }

    .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 2px solid #3b82f6; padding-bottom: 12px; }
    .header h1 { font-size: 20px; font-weight: 700; color: #1e3a5f; }
    .header .meta { text-align: right; color: #6b7280; font-size: 10px; }
    .header .meta span { display: block; }

    table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    thead tr { background: #1e3a5f; color: #fff; }
    thead th { padding: 7px 8px; text-align: left; font-size: 10px; font-weight: 600; white-space: nowrap; }
    tbody tr { border-bottom: 1px solid #e5e7eb; }
    tbody tr:nth-child(even) { background: #f8fafc; }
    tbody td { padding: 6px 8px; vertical-align: top; font-size: 10px; }

    .badge { display: inline-block; padding: 2px 7px; border-radius: 3px; font-size: 9px; font-weight: 700; color: #fff; }
    .badge-new        { background: #3b82f6; }
    .badge-contacted  { background: #6b7280; }
    .badge-interested { background: #f97316; }
    .badge-won        { background: #22c55e; }
    .badge-lost       { background: #ef4444; }
    .badge-default    { background: #94a3b8; }

    .footer { margin-top: 16px; text-align: center; color: #9ca3af; font-size: 9px; }

    @media print {
      body { padding: 0; }
      @page { margin: 15mm; size: A4 landscape; }
    }
  </style>
</head>
<body>

<div class="header">
  <div>
    <h1>📋 Leads Report</h1>
    <p style="color:#6b7280; font-size:10px; margin-top:4px;">Total: {{ count($rows) }} leads</p>
  </div>
  <div class="meta">
    <span>Exported by: {{ auth()->user()->name }}</span>
    <span>Date: {{ now()->format('d M Y, h:i A') }}</span>
  </div>
</div>

<table>
  <thead>
    <tr>
      <th>#</th>
      @foreach($headers as $header)
        <th>{{ $header }}</th>
      @endforeach
    </tr>
  </thead>
  <tbody>
    @foreach($leads as $i => $lead)
    @php
      $statusName = strtolower($lead->leadStatus->name ?? '');
      $badgeClass = match(true) {
        str_contains($statusName, 'new')        => 'badge-new',
        str_contains($statusName, 'contacted')  => 'badge-contacted',
        str_contains($statusName, 'interested') => 'badge-interested',
        str_contains($statusName, 'won')        => 'badge-won',
        str_contains($statusName, 'lost')       => 'badge-lost',
        default                                 => 'badge-default',
      };
    @endphp
    <tr>
      <td style="color:#9ca3af;">{{ $i + 1 }}</td>
      <td><strong>{{ $lead->lead_id }}</strong></td>
      <td>{{ $lead->lead_name }}</td>
      <td>{{ $lead->company_name ?: '—' }}</td>
      <td>{{ $lead->phone_number }}</td>
      <td>{{ $lead->whatsapp_number ?: '—' }}</td>
      <td>{{ $lead->email ?: '—' }}</td>
      <td>{{ $lead->leadSource->name ?? '—' }}</td>
      <td>
        @if($lead->leadStatus)
          <span class="badge {{ $badgeClass }}">{{ $lead->leadStatus->name }}</span>
        @else
          —
        @endif
      </td>
      <td>{{ $lead->follow_up_date ? $lead->follow_up_date->format('d M Y') : '—' }}</td>
      <td style="max-width:150px;">{{ Str::limit($lead->requirement, 60) }}</td>
      <td>{{ $lead->created_at->format('d M Y') }}</td>
    </tr>
    @endforeach
  </tbody>
</table>

<!-- <div class="footer">
  Generated on {{ now()->format('d M Y \a\t h:i A') }} — Confidential
</div> -->

<script>
  window.onload = function() { window.print(); };
</script>
</body>
</html>
