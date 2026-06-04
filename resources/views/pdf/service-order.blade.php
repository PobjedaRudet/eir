<!DOCTYPE html>
<html lang="bs">
<head>
<meta charset="UTF-8" />
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<title>Servisni nalog #{{ $serviceOrder->id }}</title>
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
  .meta-box {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    padding: 12px 14px;
    margin-bottom: 16px;
  }
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
  .notes-box {
    background: #fffbeb;
    border: 1px solid #fde68a;
    border-radius: 6px;
    padding: 10px 14px;
    font-size: 11px;
    color: #78350f;
    margin-bottom: 16px;
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
  <div class="header">
    <h1>Servisni nalog #{{ $serviceOrder->id }}</h1>
    <div class="subtitle">EIR sustav servisa opreme</div>
  </div>

  <div class="meta-box">
    <div class="meta-label">Oprema</div>
    <div class="meta-value">{{ $serviceOrder->resource_name }}</div>
  </div>

  <div class="meta-box">
    <div class="meta-label">Količina</div>
    <div class="meta-value">{{ number_format($serviceOrder->quantity_sent, 2, ',', '.') }} {{ $serviceOrder->resource_unit ?? '' }}</div>
  </div>

  <div class="meta-box">
    <div class="meta-label">Projekat</div>
    <div class="meta-value">{{ $serviceOrder->project?->name ?? 'Obrisan projekat' }}</div>
  </div>

  @if($serviceOrder->project?->city?->name)
  <div class="meta-box">
    <div class="meta-label">Grad</div>
    <div class="meta-value">{{ $serviceOrder->project->city->name }}</div>
  </div>
  @endif

  @if($serviceOrder->source_label)
  <div class="meta-box">
    <div class="meta-label">Izvor</div>
    <div class="meta-value">{{ $serviceOrder->source_label }}</div>
  </div>
  @endif

  <div class="meta-box">
    <div class="meta-label">Datum prosljeđivanja</div>
    <div class="meta-value">{{ $serviceOrder->forwarded_at?->format('d.m.Y. H:i') ?? now()->format('d.m.Y. H:i') }}</div>
  </div>

  @if($serviceOrder->note)
  <div>
    <div class="meta-label">Napomena vodje</div>
    <div class="notes-box">{{ $serviceOrder->note }}</div>
  </div>
  @endif

  @if($serviceOrder->procurement_note)
  <div>
    <div class="meta-label">Napomena nabavke</div>
    <div class="notes-box">{{ $serviceOrder->procurement_note }}</div>
  </div>
  @endif

  <div class="footer">
    Generisano: {{ now()->format('d.m.Y. H:i') }} &nbsp;|&nbsp; EIR servisni sustav
  </div>
</div>
</body>
</html>
