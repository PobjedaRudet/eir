<!DOCTYPE html>
<html lang="bs">
<head>
<meta charset="UTF-8" />
<title>Servisni nalog #{{ $serviceOrder->id }}</title>
<style>
  body { font-family: Arial, sans-serif; font-size: 14px; color: #333; background: #f9fafb; margin: 0; padding: 20px; }
  .container { max-width: 580px; margin: 0 auto; background: #fff; border-radius: 8px; overflow: hidden; border: 1px solid #e5e7eb; }
  .top-bar { background: #2563eb; padding: 24px 32px; }
  .top-bar h1 { color: #fff; margin: 0; font-size: 20px; }
  .top-bar p { color: #bfdbfe; margin: 4px 0 0; font-size: 13px; }
  .body { padding: 24px 32px; }
  .summary { background: #f1f5f9; border-radius: 6px; padding: 14px 18px; margin-bottom: 20px; }
  .summary p { margin: 0 0 6px; font-size: 13px; }
  .summary p:last-child { margin: 0; }
  .summary strong { color: #1e40af; }
  .note-box { background: #fffbeb; border-left: 3px solid #f59e0b; padding: 10px 14px; border-radius: 4px; font-size: 13px; color: #78350f; margin-bottom: 20px; }
  .footer { padding: 16px 32px; background: #f8fafc; border-top: 1px solid #e5e7eb; font-size: 11px; color: #9ca3af; text-align: center; }
</style>
</head>
<body>
<div class="container">
  <div class="top-bar">
    <h1>Servisni nalog #{{ $serviceOrder->id }}</h1>
    <p>EIR &ndash; Servis opreme</p>
  </div>
  <div class="body">
    <p style="margin-bottom:20px;">
      Poštovani{{ $serviceOrder->supplier_name ? ' ' . $serviceOrder->supplier_name : '' }},
    </p>
    <p style="margin-bottom:20px;">
      šaljemo vam zahtjev za servis opreme <strong>{{ $serviceOrder->resource_name }}</strong> za projekat
      <strong>{{ $serviceOrder->project?->name ?? 'Obrisan projekat' }}</strong>.
      U prilogu se nalazi servisni nalog sa osnovnim podacima.
    </p>

    <div class="summary">
      <p><strong>Oprema:</strong> {{ $serviceOrder->resource_name }}</p>
      <p><strong>Količina:</strong> {{ number_format($serviceOrder->quantity_sent, 2, ',', '.') }} {{ $serviceOrder->resource_unit ?? '' }}</p>
      <p><strong>Projekat:</strong> {{ $serviceOrder->project?->name ?? 'Obrisan projekat' }}</p>
      @if($serviceOrder->project?->city?->name)
      <p><strong>Grad:</strong> {{ $serviceOrder->project->city->name }}</p>
      @endif
      <p><strong>Datum slanja:</strong> {{ $serviceOrder->forwarded_at?->format('d.m.Y. H:i') ?? now()->format('d.m.Y. H:i') }}</p>
    </div>

    @if($serviceOrder->note)
    <div class="note-box">
      <strong>Napomena vodje:</strong> {{ $serviceOrder->note }}
    </div>
    @endif

    @if($serviceOrder->procurement_note)
    <div class="note-box">
      <strong>Napomena nabavke:</strong> {{ $serviceOrder->procurement_note }}
    </div>
    @endif

    <p style="font-size:13px; color:#6b7280;">
      Molimo potvrdite prijem servisnog naloga i planirani termin povrata.
    </p>
  </div>
  <div class="footer">
    Ovaj e-mail poslan je automatski putem EIR sustava &ndash; {{ now()->format('d.m.Y. H:i') }}
  </div>
</div>
</body>
</html>
