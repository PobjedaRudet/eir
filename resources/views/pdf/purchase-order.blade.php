<!DOCTYPE html>
<html lang="bs">
<head>
<meta charset="UTF-8" />
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<title>Narudžbenica #{{ $po->id }}</title>
<style>
  body {
    font-family: DejaVu Sans, sans-serif;
    font-size: 12px;
    color: #1a1a1a;
    margin: 0;
    padding: 0;
  }
  .page {
    padding: 32px 40px;
  }
  .header {
    border-bottom: 2px solid #2563eb;
    padding-bottom: 16px;
    margin-bottom: 24px;
  }
  .header h1 {
    font-size: 20px;
    font-weight: bold;
    color: #1e40af;
    margin: 0 0 4px 0;
  }
  .header .subtitle {
    font-size: 11px;
    color: #6b7280;
  }
  .meta-grid {
    width: 100%;
    margin-bottom: 24px;
    border-collapse: collapse;
  }
  .meta-grid td {
    width: 50%;
    vertical-align: top;
    padding: 0;
  }
  .meta-box {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    padding: 12px 14px;
    margin-right: 8px;
  }
  .meta-box.right { margin-right: 0; margin-left: 8px; }
  .meta-label {
    font-size: 9px;
    font-weight: bold;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #6b7280;
    margin-bottom: 4px;
  }
  .meta-value {
    font-size: 12px;
    color: #1a1a1a;
    font-weight: 600;
  }
  .meta-value.small {
    font-weight: normal;
    font-size: 11px;
  }
  .section-title {
    font-size: 10px;
    font-weight: bold;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: #6b7280;
    margin-bottom: 8px;
  }
  table.items {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 24px;
  }
  table.items thead tr {
    background: #2563eb;
    color: #fff;
  }
  table.items thead th {
    padding: 8px 10px;
    font-size: 10px;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    text-align: left;
    font-weight: 600;
  }
  table.items thead th.right { text-align: right; }
  table.items tbody tr:nth-child(even) {
    background: #f1f5f9;
  }
  table.items tbody td {
    padding: 7px 10px;
    border-bottom: 1px solid #e2e8f0;
    font-size: 11px;
    color: #1a1a1a;
  }
  table.items tbody td.right { text-align: right; }
  .type-badge {
    display: inline-block;
    padding: 1px 6px;
    border-radius: 4px;
    font-size: 9px;
    font-weight: bold;
    text-transform: uppercase;
  }
  .type-equipment { background: #dbeafe; color: #1d4ed8; }
  .type-material  { background: #d1fae5; color: #065f46; }
  .work-orders-list {
    margin-bottom: 24px;
  }
  .work-order-item {
    display: inline-block;
    background: #eff6ff;
    border: 1px solid #bfdbfe;
    border-radius: 4px;
    padding: 3px 8px;
    margin: 2px 4px 2px 0;
    font-size: 11px;
    color: #1e40af;
  }
  .notes-box {
    background: #fffbeb;
    border: 1px solid #fde68a;
    border-radius: 6px;
    padding: 10px 14px;
    font-size: 11px;
    color: #78350f;
    margin-bottom: 24px;
  }
  .footer {
    border-top: 1px solid #e2e8f0;
    padding-top: 12px;
    font-size: 9px;
    color: #9ca3af;
    text-align: center;
  }
</style>
</head>
<body>
<div class="page">

  <!-- Header -->
  <div class="header">
    <h1>Narudžbenica #{{ $po->id }}</h1>
    <div class="subtitle">EIR sustav upravljanja nabavkom</div>
  </div>

  <!-- Meta info -->
  <table class="meta-grid">
    <tr>
      <td>
        <div class="meta-box">
          <div class="meta-label">Status</div>
          <div class="meta-value">
            @if($po->status === 'kreirana') Kreirana
            @elseif($po->status === 'narucena') Naručena
            @else Isporučena
            @endif
          </div>
        </div>
      </td>
      <td>
        <div class="meta-box right">
          <div class="meta-label">Datum kreiranja</div>
          <div class="meta-value small">{{ $po->created_at->format('d.m.Y. H:i') }}</div>
        </div>
      </td>
    </tr>
  </table>

  @if($po->supplier_name || $po->supplier_email)
  <table class="meta-grid" style="margin-bottom: 24px;">
    <tr>
      <td colspan="2">
        <div class="meta-box" style="margin-right: 0;">
          <div class="meta-label">Dobavljač</div>
          @if($po->supplier_name)
            <div class="meta-value">{{ $po->supplier_name }}</div>
          @endif
          @if($po->supplier_email)
            <div class="meta-value small">{{ $po->supplier_email }}</div>
          @endif
        </div>
      </td>
    </tr>
  </table>
  @endif

  <!-- Linked work orders -->
  @php
    $linkedWorkOrders = $po->items->map(fn($i) => $i->workOrderItem?->workOrder)->filter()->unique('id');
  @endphp
  @if($linkedWorkOrders->isNotEmpty())
  <div class="work-orders-list">
    <div class="section-title">Radni nalozi</div>
    @foreach($linkedWorkOrders as $wo)
      <span class="work-order-item">
        {{ $wo->order_label }} &mdash; {{ $wo->project->name }}@if($wo->project->city) ({{ $wo->project->city->name }})@endif
      </span>
    @endforeach
  </div>
  @endif

  <!-- Items table -->
  <div class="section-title">Stavke narudžbenice</div>
  <table class="items">
    <thead>
      <tr>
        <th>#</th>
        <th>Tip</th>
        <th>Naziv</th>
        <th class="right">Količina</th>
        <th>Jed.</th>
      </tr>
    </thead>
    <tbody>
      @foreach($po->items as $idx => $item)
      <tr>
        <td>{{ $idx + 1 }}</td>
        <td>
          <span class="type-badge {{ $item->resource_type === 'equipment' ? 'type-equipment' : 'type-material' }}">
            {{ $item->resource_type === 'equipment' ? 'Oprema' : 'Materijal' }}
          </span>
        </td>
        <td>{{ $item->resource_name }}</td>
        <td class="right">{{ number_format($item->quantity, 2, ',', '.') }}</td>
        <td>{{ $item->unit ?? '—' }}</td>
      </tr>
      @endforeach
    </tbody>
  </table>

  @if($po->notes)
  <div>
    <div class="section-title">Napomena</div>
    <div class="notes-box">{{ $po->notes }}</div>
  </div>
  @endif

  <!-- Footer -->
  <div class="footer">
    Generisano: {{ now()->format('d.m.Y. H:i') }} &nbsp;|&nbsp; EIR nabavka sustav
  </div>

</div>
</body>
</html>
