<!DOCTYPE html>
<html lang="bs">
<head>
<meta charset="UTF-8" />
<title>Narudžbenica #{{ $purchaseOrder->id }}</title>
<style>
  body { font-family: Arial, sans-serif; font-size: 14px; color: #333; background: #f9fafb; margin: 0; padding: 20px; }
  .container { max-width: 580px; margin: 0 auto; background: #fff; border-radius: 8px; overflow: hidden; border: 1px solid #e5e7eb; }
  .top-bar { background: #2563eb; padding: 24px 32px; }
  .top-bar h1 { color: #fff; margin: 0; font-size: 20px; }
  .top-bar p { color: #bfdbfe; margin: 4px 0 0; font-size: 13px; }
  .body { padding: 24px 32px; }
  .greeting { font-size: 15px; margin-bottom: 16px; }
  .info-box { background: #f1f5f9; border-radius: 6px; padding: 14px 18px; margin-bottom: 20px; }
  .info-box p { margin: 0 0 6px; font-size: 13px; }
  .info-box p:last-child { margin: 0; }
  .info-box strong { color: #1e40af; }
  .summary { margin-bottom: 20px; }
  .summary-title { font-size: 12px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.05em; color: #6b7280; margin-bottom: 8px; }
  table.items { width: 100%; border-collapse: collapse; font-size: 13px; }
  table.items th { background: #e0e7ff; color: #1e40af; padding: 7px 10px; text-align: left; font-size: 11px; text-transform: uppercase; }
  table.items td { padding: 6px 10px; border-bottom: 1px solid #f1f5f9; }
  table.items tr:last-child td { border-bottom: none; }
  .footer { padding: 16px 32px; background: #f8fafc; border-top: 1px solid #e5e7eb; font-size: 11px; color: #9ca3af; text-align: center; }
  .note-box { background: #fffbeb; border-left: 3px solid #f59e0b; padding: 10px 14px; border-radius: 4px; font-size: 13px; color: #78350f; margin-bottom: 20px; }
</style>
</head>
<body>
<div class="container">
  <div class="top-bar">
    <h1>Narudžbenica #{{ $purchaseOrder->id }}</h1>
    <p>EIR &ndash; Sustav upravljanja nabavkom</p>
  </div>
  <div class="body">
    <p class="greeting">
      Poštovani{{ $purchaseOrder->supplier_name ? ' ' . $purchaseOrder->supplier_name : '' }},
    </p>
    <p style="margin-bottom:20px;">
      U prilogu se nalazi narudžbenica br. <strong>#{{ $purchaseOrder->id }}</strong> od {{ $purchaseOrder->created_at->format('d.m.Y.') }}.
      Molimo vas da potvrdite prijem narudžbe i dostupnost navedene robe.
    </p>

    <div class="summary">
      <div class="summary-title">Stavke narudžbenice</div>
      <table class="items">
        <thead>
          <tr>
            <th>#</th>
            <th>Naziv</th>
            <th>Količina</th>
            <th>Jed.</th>
          </tr>
        </thead>
        <tbody>
          @foreach($purchaseOrder->items as $idx => $item)
          <tr>
            <td>{{ $idx + 1 }}</td>
            <td>{{ $item->resource_name }}</td>
            <td>{{ number_format($item->quantity, 2, ',', '.') }}</td>
            <td>{{ $item->unit ?? '—' }}</td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>

    @if($purchaseOrder->notes)
    <div class="note-box">
      <strong>Napomena:</strong> {{ $purchaseOrder->notes }}
    </div>
    @endif

    <p style="font-size:13px; color:#6b7280;">
      Detaljan popis stavki dostupan je u priloženom PDF dokumentu.
    </p>
  </div>
  <div class="footer">
    Ovaj e-mail poslan je automatski putem EIR sustava &ndash; {{ now()->format('d.m.Y. H:i') }}
  </div>
</div>
</body>
</html>
