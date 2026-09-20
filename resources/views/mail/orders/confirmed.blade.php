<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta name="viewport" content="width=device-width">
    <title>Thank you for your purchase!</title>
    <style>
        body { margin: 0; padding: 0; min-width: 100%; background-color: #e5e5e5; }
        .body { width: 100%; background-color: #e5e5e5; }
        .container { width: 560px; max-width: 560px; margin: 0 auto; }
        .shop-name__text { font-weight: 300; font-size: 30px; color: #333333; text-decoration: none; }
        .order-number__text { font-size: 14px; color: #999999; text-transform: uppercase; letter-spacing: 1px; }
        h2 { font-weight: 300; font-size: 26px; color: #333333; margin: 0 0 12px; }
        h3 { font-weight: 300; font-size: 20px; color: #555555; margin: 0 0 20px; }
        h4 { font-weight: 500; font-size: 14px; color: #555555; margin: 0 0 5px; }
        p { margin: 0 0 16px; color: #555555; line-height: 1.5; }
        .button__cell { background: #1990c6; border-radius: 4px; }
        .button__text { display: inline-block; padding: 18px 25px; color: #ffffff !important; text-decoration: none; font-size: 16px; }
        .or { color: #999999; }
        .order-list__item-title { font-weight: 600; color: #555555; }
        .order-list__item-variant { color: #999999; font-size: 14px; }
        .order-list__item-price { margin: 0; color: #555555; text-align: right; }
        .subtotal-line__title span, .subtotal-line__value strong { color: #555555; }
        .disclaimer__subtext { color: #999999; font-size: 14px; }
        @@media only screen and (max-width: 600px) {
            .container { width: 100% !important; }
            .customer-info__item { display: block !important; width: 100% !important; }
        }
    </style>
</head>
<body>
<table class="body" width="100%" cellpadding="0" cellspacing="0" role="presentation" style="width:100%;background-color:#e5e5e5;">
    <tr>
        <td align="center" style="padding: 20px 0;">
            <table class="container" width="560" cellpadding="0" cellspacing="0" role="presentation" style="width:560px;max-width:560px;background-color:#ffffff;">
                <tr>
                    <td style="padding: 28px 30px 20px; border-bottom: 1px solid #e5e5e5;">
                        <table width="100%" cellpadding="0" cellspacing="0" role="presentation">
                            <tr>
                                <td class="shop-name__cell" style="vertical-align: middle;">
                                    @if ($logoUrl)
                                        <a href="{{ $shopUrl }}" style="text-decoration: none;">
                                            <img src="{{ $logoUrl }}" alt="{{ $shopName }}" width="160" style="max-width:160px;height:auto;border:0;display:block;">
                                        </a>
                                    @else
                                        <a href="{{ $shopUrl }}" class="shop-name__text" style="font-weight:300;font-size:30px;color:#333333;text-decoration:none;">{{ $shopName }}</a>
                                    @endif
                                </td>
                                <td class="order-number__cell" align="right" style="vertical-align: middle; text-align: right;">
                                    <span class="order-number__text" style="font-size:14px;color:#999999;text-transform:uppercase;letter-spacing:1px;">Order {{ $order->order_number }}</span>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                <tr>
                    <td style="padding: 36px 30px 28px;">
                        <h2 style="font-weight:300;font-size:26px;color:#333333;margin:0 0 12px;">Thank you for your purchase!</h2>
                        <p style="margin:0 0 24px;color:#555555;line-height:1.5;font-size:16px;">
                            @if ($order->customerFirstName())
                                Hi {{ $order->customerFirstName() }}, we're getting your order ready to be shipped. We will notify you when it has been sent.
                            @else
                                We're getting your order ready to be shipped. We will notify you when it has been sent.
                            @endif
                        </p>
                        <table cellpadding="0" cellspacing="0" role="presentation">
                            <tr>
                                <td class="button__cell" style="background:#1990c6;border-radius:4px;">
                                    <a href="{{ $url }}" class="button__text" style="display:inline-block;padding:18px 25px;color:#ffffff;text-decoration:none;font-size:16px;">View your order</a>
                                </td>
                                <td style="padding-left: 16px; vertical-align: middle;">
                                    <span class="or" style="color:#999999;">or</span>
                                    <a href="{{ $shopUrl }}" style="color:#1990c6;text-decoration:none;padding-left:4px;">Visit our store</a>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                <tr>
                    <td style="padding: 28px 30px; border-top: 1px solid #e5e5e5;">
                        <h3 style="font-weight:300;font-size:20px;color:#555555;margin:0 0 20px;">Order summary</h3>
                        <table width="100%" cellpadding="0" cellspacing="0" role="presentation">
                            @foreach ($order->items as $item)
                                <tr>
                                    <td style="padding: 10px 0; vertical-align: top; width: 76px;">
                                        @if ($item->imageUrl())
                                            <img src="{{ $item->imageUrl() }}" alt="{{ $item->product_title }}" width="60" height="60" style="width:60px;height:60px;border-radius:8px;border:1px solid #e5e5e5;object-fit:cover;display:block;">
                                        @else
                                            <div style="width:60px;height:60px;border-radius:8px;border:1px solid #e5e5e5;background:#f6f6f6;"></div>
                                        @endif
                                    </td>
                                    <td style="padding: 10px 12px 10px 0; vertical-align: top;">
                                        <span class="order-list__item-title" style="font-weight:600;color:#555555;">{{ $item->product_title }} × {{ $item->quantity }}</span>
                                        @if ($item->displayVariantTitle())
                                            <br>
                                            <span class="order-list__item-variant" style="color:#999999;font-size:14px;">{{ $item->displayVariantTitle() }}</span>
                                        @endif
                                    </td>
                                    <td style="padding: 10px 0; vertical-align: top; text-align: right; white-space: nowrap;">
                                        <p class="order-list__item-price" style="margin:0;color:#555555;">{{ $item->lineTotal()->format() }}</p>
                                    </td>
                                </tr>
                            @endforeach
                        </table>

                        <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin-top: 16px;">
                            <tr>
                                <td width="40%"></td>
                                <td>
                                    <table width="100%" cellpadding="0" cellspacing="0" role="presentation">
                                        <tr>
                                            <td style="padding: 6px 0; color:#555555;">Subtotal</td>
                                            <td style="padding: 6px 0; text-align: right; color:#555555;"><strong>{{ $order->subtotal_amount->format() }}</strong></td>
                                        </tr>
                                        @if ($order->discount_amount->isPositive())
                                            <tr>
                                                <td style="padding: 6px 0; color:#555555;">
                                                    Discount
                                                    @if ($order->coupon_code)
                                                        ({{ $order->coupon_code }})
                                                    @endif
                                                </td>
                                                <td style="padding: 6px 0; text-align: right; color:#555555;"><strong>− {{ $order->discount_amount->format() }}</strong></td>
                                            </tr>
                                        @endif
                                        <tr>
                                            <td style="padding: 6px 0; color:#555555;">Shipping</td>
                                            <td style="padding: 6px 0; text-align: right; color:#555555;"><strong>{{ $order->shipping_amount->format() }}</strong></td>
                                        </tr>
                                        @if ($order->tax_amount->isPositive())
                                            <tr>
                                                <td style="padding: 6px 0; color:#555555;">Taxes</td>
                                                <td style="padding: 6px 0; text-align: right; color:#555555;"><strong>{{ $order->tax_amount->format() }}</strong></td>
                                            </tr>
                                        @endif
                                        <tr>
                                            <td colspan="2" style="padding-top: 10px; border-top: 1px solid #e5e5e5;"></td>
                                        </tr>
                                        <tr>
                                            <td style="padding: 10px 0 0; color:#555555; font-size: 16px;">Total</td>
                                            <td style="padding: 10px 0 0; text-align: right; color:#555555; font-size: 16px;"><strong>{{ $order->grandTotal()->format() }} {{ $order->currency }}</strong></td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                <tr>
                    <td style="padding: 28px 30px; border-top: 1px solid #e5e5e5;">
                        <h3 style="font-weight:300;font-size:20px;color:#555555;margin:0 0 20px;">Customer information</h3>
                        <table width="100%" cellpadding="0" cellspacing="0" role="presentation">
                            <tr>
                                @if ($order->addressLines($order->shipping_address) !== [])
                                    <td class="customer-info__item" width="50%" style="vertical-align: top; padding-right: 12px; padding-bottom: 18px;">
                                        <h4 style="font-weight:500;font-size:14px;color:#555555;margin:0 0 5px;">Shipping address</h4>
                                        <p style="margin:0;color:#555555;line-height:1.5;font-size:14px;">
                                            @foreach ($order->addressLines($order->shipping_address) as $line)
                                                {{ $line }}@if (! $loop->last)<br>@endif
                                            @endforeach
                                        </p>
                                    </td>
                                @endif
                                @if ($order->addressLines($order->billing_address) !== [])
                                    <td class="customer-info__item" width="50%" style="vertical-align: top; padding-bottom: 18px;">
                                        <h4 style="font-weight:500;font-size:14px;color:#555555;margin:0 0 5px;">Billing address</h4>
                                        <p style="margin:0;color:#555555;line-height:1.5;font-size:14px;">
                                            @foreach ($order->addressLines($order->billing_address) as $line)
                                                {{ $line }}@if (! $loop->last)<br>@endif
                                            @endforeach
                                        </p>
                                    </td>
                                @endif
                            </tr>
                            <tr>
                                @if ($order->shipping_method_name)
                                    <td class="customer-info__item" width="50%" style="vertical-align: top; padding-right: 12px;">
                                        <h4 style="font-weight:500;font-size:14px;color:#555555;margin:0 0 5px;">Shipping method</h4>
                                        <p style="margin:0;color:#555555;font-size:14px;">{{ $order->shipping_method_name }}</p>
                                    </td>
                                @endif
                                <td class="customer-info__item" width="50%" style="vertical-align: top;">
                                    <h4 style="font-weight:500;font-size:14px;color:#555555;margin:0 0 5px;">Payment method</h4>
                                    <p style="margin:0;color:#555555;font-size:14px;">{{ $paymentMethod }} — <strong>{{ $order->grandTotal()->format() }}</strong></p>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                <tr>
                    <td style="padding: 24px 30px 32px; border-top: 1px solid #e5e5e5;">
                        <p class="disclaimer__subtext" style="margin:0;color:#999999;font-size:14px;line-height:1.5;">
                            If you have any questions, reply to this email or contact us at
                            <a href="mailto:{{ $shopEmail }}" style="color:#1990c6;text-decoration:none;">{{ $shopEmail }}</a>
                        </p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
