<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ZATCA Invoice</title>
</head>
<body style="font-family: Arial, sans-serif; color: #333; line-height: 1.4; padding: 20px; direction: ltr; margin: 0;">
    <div style="max-width: 800px; margin: 0 auto; background: white;">

        <!-- Row 1: Logo + Header -->
        <table style="width: 100%; margin-bottom: 30px; border-bottom: 1px solid #ddd; padding-bottom: 15px;">
            <tr>
                <td style="width: 50%; vertical-align: middle;">
                    <img src="{{ 'data:image/png;base64,' . base64_encode(file_get_contents(public_path('color-logo.png'))) }}" alt="Logo" style="height: 50px; max-width: 120px;" />
                </td>
                <td style="width: 50%; vertical-align: middle; text-align: left;">
                    <h1 style="font-size: 22px; font-weight: bold; margin: 0 0 5px 0; color: #000;">{{ __('zatca/e-invoice.tax_invoice') }}</h1>
                    <p style="font-size: 16px; color: #666; margin: 0;">{{ __('zatca/e-invoice.invoice_number', ['number' => $invoice_number]) }}</p>
                </td>
            </tr>
        </table>

        <!-- Row 2: Bill From, Bill To, Order Details - HORIZONTAL LAYOUT -->
        <table style="width: 100%; margin-bottom: 30px;">
            <tr>
                <td style="width: 33.33%; vertical-align: top; padding: 0 15px; text-align: left;">
                    <h3 style="font-size: 14px; color: #666; font-weight: bold; margin: 0 0 8px 0;">{{ __('zatca/e-invoice.bill_from') }}</h3>
                    <h4 style="font-size: 16px; font-weight: 600; margin: 0 0 8px 0; color: #000;">{{ $seller->getCompanyName(Config::get('app.locale', 'en')) }}</h4>
                    <div style="font-size: 12px; color: #666; margin-bottom: 2px;">{{ $seller->getCompanyAddress()->getAddressLineOne(Config::get('app.locale', 'en')) }}</div>
                    <div style="font-size: 12px; color: #666; margin-bottom: 2px;">{{ $seller->getCompanyAddress()->getAddressLineTwo(Config::get('app.locale', 'en')) }}</div>
                    <div style="font-size: 12px; color: #666; margin-bottom: 2px;">{{ __('zatca/e-invoice.vat_number') }}: {{ $seller->getVatId() }}</div>
                    <div style="font-size: 12px; color: #666; margin-bottom: 2px;">{{ __('zatca/e-invoice.cr_number') }}: {{ $seller->getCompanyCr() }}</div>
                </td>
                <td style="width: 33.33%; vertical-align: top; padding: 0 15px; text-align: left;">
                    <h3 style="font-size: 14px; color: #666; font-weight: bold; margin: 0 0 8px 0;">{{ __('zatca/e-invoice.bill_to') }}</h3>
                    <h4 style="font-size: 16px; font-weight: 600; margin: 0 0 8px 0; color: #000;">{{ $buyer }}</h4>
                </td>
                <td style="width: 33.33%; vertical-align: top; padding: 0 15px; text-align: left;">
                    <h3 style="font-size: 14px; color: #666; font-weight: bold; margin: 0 0 8px 0;">{{ __('zatca/e-invoice.order_details') }}</h3>
                    <div style="font-size: 12px; color: #666; margin-bottom: 2px;"><strong>{{ __('zatca/e-invoice.issue_date') }}:</strong> {{ $order->getInvoiceDate() }}</div>
                </td>
            </tr>
        </table>

        <!-- Row 3: Invoice Items Table -->
        <table style="width: 100%; border-collapse: collapse; margin-bottom: 30px;">
            <thead>
                <tr>
                    <th style="background-color: #475569; color: white; border: 1px solid #475569; padding: 12px; text-align: center; font-weight: 600; font-size: 14px;">{{ __('zatca/e-invoice.item') }}</th>
                    <th style="background-color: #475569; color: white; border: 1px solid #475569; padding: 12px; text-align: center; font-weight: 600; font-size: 14px;">{{ __('zatca/e-invoice.qty') }}</th>
                    <th style="background-color: #475569; color: white; border: 1px solid #475569; padding: 12px; text-align: center; font-weight: 600; font-size: 14px;">{{ __('zatca/e-invoice.unit_price') }}</th>
                    <th style="background-color: #475569; color: white; border: 1px solid #475569; padding: 12px; text-align: center; font-weight: 600; font-size: 14px;">{{ __('zatca/e-invoice.discount') }}</th>
                    <th style="background-color: #475569; color: white; border: 1px solid #475569; padding: 12px; text-align: center; font-weight: 600; font-size: 14px;">{{ __('zatca/e-invoice.vat') }}</th>
                    <th style="background-color: #475569; color: white; border: 1px solid #475569; padding: 12px; text-align: center; font-weight: 600; font-size: 14px;">{{ __('zatca/e-invoice.total_price') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($order->getItems() as $item)
                <tr>
                    <td style="border: 1px solid #ddd; padding: 12px; text-align: left; font-size: 14px; background-color: white;">{{ $item->getName() }}</td>
                    <td style="border: 1px solid #ddd; padding: 12px; text-align: center; font-size: 14px; background-color: white;">{{ $item->getQuantity() }}</td>
                    <td style="border: 1px solid #ddd; padding: 12px; text-align: center; font-size: 14px; background-color: white;">{{ $item->getItemPrice()->convertAndFormatByDecimal(separator: ',') }}</td>
                    <td style="border: 1px solid #ddd; padding: 12px; text-align: center; font-size: 14px; background-color: white;">{{ $item->getDiscountPercentage() }}%</td>
                    <td style="border: 1px solid #ddd; padding: 12px; text-align: center; font-size: 14px; background-color: white;">{{ $item->getVatPercentage() ? 'V' : 'N' }}</td>
                    <td style="border: 1px solid #ddd; padding: 12px; text-align: center; font-size: 14px; background-color: white;">{{ $item->getLineTotalWithoutVat()->convertAndFormatByDecimal(separator: ',') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Row 4: QR Code, Tax Rates, Totals - HORIZONTAL LAYOUT -->
        <table style="width: 100%; margin-top: 30px;">
            <tr>
                <td style="width: 30%; vertical-align: top; text-align: center;">
                    <img src="{{ $qr_code }}" alt="QR Code" style="width: 120px; height: 120px; border: 1px solid #ddd;" />
                </td>
                <td style="width: 70%; vertical-align: top; padding-left: 30px;">
                    <div style="margin-bottom: 20px; text-align: left;">
                        <h3 style="font-size: 16px; font-weight: bold; margin: 0 0 10px 0; color: #000;">{{ __('zatca/e-invoice.tax_rates') }}</h3>
                        <div style="font-size: 14px; line-height: 1.5; margin-bottom: 5px; color: #666;">"V" {{ __('zatca/e-invoice.vat_symbol_v', ['percentage' => collect($order->getItems())->filter(fn($item) => $item->getVatPercentage() !== null)->first()->getVatPercentage()]) }}</div>
                        <div style="font-size: 14px; line-height: 1.5; margin-bottom: 5px; color: #666;">"N" {{ __('zatca/e-invoice.vat_symbol_n') }}</div>
                    </div>

                    <div style="text-align: left; border-top: 1px solid #ddd; padding-top: 15px;">
                        @if ($order->getTotalDiscount()->getAmount() > 0)
                        <table style="width: 100%; margin-bottom: 8px; font-size: 14px;">
                            <tr>
                                <td style="width: 60%; text-align: left;">{{ __('zatca/e-invoice.total_discount') }}</td>
                                <td style="width: 40%; text-align: right; font-weight: 600;">{{ __('zatca/e-invoice.amount_with_currency', ['amount' => $order->getTotalDiscount()->convertAndFormatByDecimal(separator: ',')]) }}</td>
                            </tr>
                        </table>
                        @endif

                        <table style="width: 100%; margin-bottom: 8px; font-size: 14px;">
                            <tr>
                                <td style="width: 60%; text-align: left;">{{ __('zatca/e-invoice.total_before_vat') }}</td>
                                <td style="width: 40%; text-align: right; font-weight: 600;">{{ __('zatca/e-invoice.amount_with_currency', ['amount' => $order->getTotalWithoutVat()->convertAndFormatByDecimal(separator: ',')]) }}</td>
                            </tr>
                        </table>

                        <table style="width: 100%; margin-bottom: 8px; font-size: 14px;">
                            <tr>
                                <td style="width: 60%; text-align: left;">{{ __('zatca/e-invoice.vat_total') }}</td>
                                <td style="width: 40%; text-align: right; font-weight: 600;">{{ __('zatca/e-invoice.amount_with_currency', ['amount' => $order->getTotalVat()->convertAndFormatByDecimal(separator: ',')]) }}</td>
                            </tr>
                        </table>

                        <table style="width: 100%; margin-bottom: 8px; font-size: 16px; font-weight: bold; color: #000; margin-top: 10px; padding-top: 10px; border-top: 1px solid #000;">
                            <tr>
                                <td style="width: 60%; text-align: left;">{{ __('zatca/e-invoice.total') }}</td>
                                <td style="width: 40%; text-align: right;">{{ __('zatca/e-invoice.amount_with_currency', ['amount' => $order->getTotalAmount()->convertAndFormatByDecimal(separator: ',')]) }}</td>
                            </tr>
                        </table>
                    </div>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
