<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Code-barres - {{ $product->name }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        @page {
            size: 100mm 100mm;
            margin: 0;
        }

        body {
            width: 100mm;
            height: 100mm;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            gap: 4mm;
            font-family: Arial, sans-serif;
            overflow: hidden;
        }

        .product-name {
            font-size: 14pt;
            font-weight: bold;
            text-align: center;
            width: 90mm;
            word-break: break-word;
            line-height: 1.2;
        }

        .barcode img {
            width: 85mm;
            height: auto;
            display: block;
        }

        /* Hide everything on screen, show only on print */
        @media screen {
            body {
                background: #f0f0f0;
                justify-content: center;
                align-items: center;
                height: 100vh;
            }

            .label {
                background: white;
                width: 100mm;
                height: 100mm;
                display: flex;
                flex-direction: column;
                justify-content: center;
                align-items: center;
                gap: 4mm;
                box-shadow: 0 0 10px rgba(0,0,0,0.2);
                padding: 5mm;
            }
        }

        @media print {
            body {
                background: white;
                width: 100mm;
                height: 100mm;
            }

            .label {
                width: 100mm;
                height: 100mm;
                padding: 5mm;
                display: flex;
                flex-direction: column;
                justify-content: center;
                align-items: center;
                gap: 4mm;
            }
        }
    </style>
</head>
<body>
    <div class="label">
        <div class="product-name">{{ $product->name }}</div>
        <div class="barcode">
            <img src="{{asset('storage/'.$product->barcode) }}" alt="{{ $product->name }}" />
        </div>
    </div>
</body>
</html>