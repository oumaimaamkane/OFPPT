<x-filament-panels::page>
<div style="display:flex;flex-direction:column;gap:24px">

    {{-- Main Scanner Card --}}
    <div style="background:white;border-radius:14px;box-shadow:0 1px 3px rgba(0,0,0,0.08);border:1px solid #e5e7eb;overflow:hidden">

        {{-- Header --}}
        <div style="background:#000000;padding:20px 24px">
            <div style="display:flex;align-items:center;gap:12px">
                <div style="flex-shrink:0;padding:8px;background:rgba(255,255,255,0.2);border-radius:10px">
                    <svg style="width:28px;height:28px;display:block;color:white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3.75 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 013.75 9.375v-4.5zM3.75 14.625c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 01-1.125-1.125v-4.5zM13.5 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0113.5 9.375v-4.5z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6.75 6.75h.75v.75h-.75v-.75zM6.75 16.5h.75v.75h-.75V16.5zM16.5 6.75h.75v.75h-.75v-.75zM13.5 13.5h.75v.75h-.75v-.75zM13.5 19.5h.75v.75h-.75v-.75zM19.5 13.5h.75v.75h-.75v-.75zM19.5 19.5h.75v.75h-.75v-.75zM16.5 16.5h.75v.75h-.75v-.75z" />
                    </svg>
                </div>
                <div>
                    <h2 style="font-size:1.2rem;font-weight:700;color:white;margin:0;line-height:1.3">Scanner le code barre</h2>
                    <p style="font-size:0.85rem;color:rgba(199,210,254,1);margin:4px 0 0;line-height:1.3">Scanner le code barre pour mettre à jour le stock</p>
                </div>
            </div>
        </div>

        {{-- Input Area --}}
        <div style="padding:24px">
            <div style="display:flex;gap:12px;align-items:stretch">
                <div style="flex:1;position:relative">
                    <div style="position:absolute;left:12px;top:50%;transform:translateY(-50%);pointer-events:none">
                        <svg style="width:20px;height:20px;color:#9ca3af;display:block" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 15.803 7.5 7.5 0 0016.803 16.803z" />
                        </svg>
                    </div>
                    <input
                        type="text"
                        wire:model="trackingNumber"
                        wire:keydown.enter="scanBarcode"
                        placeholder="Scanner le code barre..."
                        autofocus
                        style="display:block;width:100%;padding:12px 16px 12px 44px;border:1.5px solid #d1d5db;border-radius:10px;background:#f9fafb;font-size:1rem;font-family:ui-monospace,monospace;color:#111827;outline:none;box-sizing:border-box;transition:all 0.15s"
                        onfocus="this.style.borderColor='#6366f1';this.style.background='#fff';this.style.boxShadow='0 0 0 3px rgba(99,102,241,0.15)'"
                        onblur="this.style.borderColor='#d1d5db';this.style.background='#f9fafb';this.style.boxShadow='none'"
                    />
                </div>
                <button
                    wire:click="scanBarcode"
                    wire:loading.attr="disabled"
                    style="padding:12px 24px;background:#000000;color:white;font-weight:600;border-radius:10px;border:none;cursor:pointer;display:flex;align-items:center;gap:8px;white-space:nowrap;font-size:0.95rem;transition:background 0.15s;flex-shrink:0"
                    onmouseover="this.style.background='#000000'"
                    onmouseout="this.style.background='#000000'"
                >
                    <span wire:loading.remove wire:target="scanBarcode" style="display:flex;align-items:center">
                        <svg style="width:18px;height:18px;display:block" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                        </svg>
                    </span>
                   
                    Vérifier
                </button>
            </div>
            <p style="margin:8px 0 0 4px;font-size:0.78rem;color:#6b7280">
                Appuyez sur <kbd style="padding:2px 6px;background:#f3f4f6;border:1px solid #d1d5db;border-radius:4px;font-size:0.75rem;font-family:monospace">Enter</kbd> après avoir scanné ou tapé le numéro de suivi
            </p>
        </div>

        {{-- Result Alert --}}
        @if ($resultMessage)
            <div style="padding:0 24px 24px">
                @if ($resultType === 'success')
                    <div style="display:flex;align-items:flex-start;gap:12px;padding:14px 16px;background:#ecfdf5;border:1px solid #6ee7b7;border-radius:10px">
                        <svg style="width:20px;height:20px;color:#059669;flex-shrink:0;margin-top:1px;display:block" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <p style="font-size:0.9rem;font-weight:500;color:#065f46;margin:0;line-height:1.5">{{ $resultMessage }}</p>
                    </div>
                @elseif ($resultType === 'error')
                    <div style="display:flex;align-items:flex-start;gap:12px;padding:14px 16px;background:#fef2f2;border:1px solid #fca5a5;border-radius:10px">
                        <svg style="width:20px;height:20px;color:#dc2626;flex-shrink:0;margin-top:1px;display:block" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <p style="font-size:0.9rem;font-weight:500;color:#991b1b;margin:0;line-height:1.5">{{ $resultMessage }}</p>
                    </div>
                @elseif ($resultType === 'warning')
                    <div style="display:flex;align-items:flex-start;gap:12px;padding:14px 16px;background:#fffbeb;border:1px solid #fcd34d;border-radius:10px">
                        <svg style="width:20px;height:20px;color:#d97706;flex-shrink:0;margin-top:1px;display:block" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                        </svg>
                        <p style="font-size:0.9rem;font-weight:500;color:#92400e;margin:0;line-height:1.5">{{ $resultMessage }}</p>
                    </div>
                @endif
            </div>
        @endif
    </div>

    {{-- Scanned Order Details --}}
    @if ($scannedOrder)
        <div style="background:white;border-radius:14px;box-shadow:0 1px 3px rgba(0,0,0,0.08);border:1px solid #e5e7eb;overflow:hidden">
            <div style="padding:16px 24px;border-bottom:1px solid #f3f4f6;display:flex;align-items:center;justify-content:space-between">
                <h3 style="font-size:1rem;font-weight:600;color:#111827;margin:0;display:flex;align-items:center;gap:8px">
                    <svg style="width:20px;height:20px;color:#6366f1;display:block;flex-shrink:0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25z" />
                    </svg>
                    Order Details
                </h3>
                <span style="font-size:0.75rem;color:#9ca3af">Just processed</span>
            </div>
            <div style="padding:24px">
                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(130px,1fr));gap:12px;margin-bottom:20px">
                    <div style="background:#f9fafb;border-radius:10px;padding:12px">
                        <p style="font-size:0.7rem;color:#6b7280;text-transform:uppercase;letter-spacing:0.05em;margin:0 0 4px;line-height:1">Order #</p>
                        <p style="font-weight:700;color:#111827;margin:0;font-size:0.95rem">{{ $scannedOrder['order_number'] }}</p>
                    </div>
                    <div style="background:#f9fafb;border-radius:10px;padding:12px">
                        <p style="font-size:0.7rem;color:#6b7280;text-transform:uppercase;letter-spacing:0.05em;margin:0 0 4px;line-height:1">Tracking</p>
                        <p style="font-weight:600;color:#111827;margin:0;font-size:0.78rem;font-family:monospace;word-break:break-all">{{ $scannedOrder['tracking_number'] }}</p>
                    </div>
                    <div style="background:#f9fafb;border-radius:10px;padding:12px">
                        <p style="font-size:0.7rem;color:#6b7280;text-transform:uppercase;letter-spacing:0.05em;margin:0 0 4px;line-height:1">Customer</p>
                        <p style="font-weight:600;color:#111827;margin:0;font-size:0.9rem">{{ $scannedOrder['customer_name'] }}</p>
                    </div>
                    <div style="background:#f9fafb;border-radius:10px;padding:12px">
                        <p style="font-size:0.7rem;color:#6b7280;text-transform:uppercase;letter-spacing:0.05em;margin:0 0 4px;line-height:1">Status</p>
                        @php
                            $st = strtolower($scannedOrder['delivery_status']);
                            $badgeStyle = match(true) {
                                str_contains($st, 'return')  => 'background:#dbeafe;color:#1e40af',
                                str_contains($st, 'syst')    => 'background:#fef3c7;color:#92400e',
                                str_contains($st, 'deliver') => 'background:#d1fae5;color:#065f46',
                                default                      => 'background:#f3f4f6;color:#374151',
                            };
                        @endphp
                        <span style="display:inline-block;padding:3px 10px;border-radius:999px;font-size:0.75rem;font-weight:600;{{ $badgeStyle }}">
                            {{ ucfirst($scannedOrder['delivery_status']) }}
                        </span>
                    </div>
                </div>

                <p style="font-size:0.85rem;font-weight:600;color:#374151;margin:0 0 8px">Products Affected</p>
                <div style="border-radius:10px;border:1px solid #e5e7eb;overflow:hidden">
                    <table style="width:100%;border-collapse:collapse;font-size:0.875rem">
                        <thead>
                            <tr style="background:#f9fafb">
                                <th style="text-align:left;padding:10px 16px;font-size:0.72rem;font-weight:600;color:#6b7280;text-transform:uppercase;letter-spacing:0.05em;border-bottom:1px solid #e5e7eb">Product</th>
                                <th style="text-align:center;padding:10px 16px;font-size:0.72rem;font-weight:600;color:#6b7280;text-transform:uppercase;letter-spacing:0.05em;border-bottom:1px solid #e5e7eb">Order Qty</th>
                                <th style="text-align:center;padding:10px 16px;font-size:0.72rem;font-weight:600;color:#6b7280;text-transform:uppercase;letter-spacing:0.05em;border-bottom:1px solid #e5e7eb">Warehouse Stock (After)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($scannedOrder['items'] as $i => $item)
                                <tr style="{{ $i > 0 ? 'border-top:1px solid #f3f4f6' : '' }}">
                                    <td style="padding:10px 16px;font-weight:500;color:#111827">{{ $item['product_name'] }}</td>
                                    <td style="padding:10px 16px;text-align:center;color:#374151">{{ $item['quantity'] }}</td>
                                    <td style="padding:10px 16px;text-align:center">
                                        <span style="font-weight:700;font-size:1rem;{{ $item['warehouse_qty'] < 5 ? 'color:#dc2626' : 'color:#059669' }}">
                                            {{ $item['warehouse_qty'] }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    {{-- Scan History --}}
    @if (count($scanHistory) > 0)
        <div style="background:white;border-radius:14px;box-shadow:0 1px 3px rgba(0,0,0,0.08);border:1px solid #e5e7eb;overflow:hidden">
            <div style="padding:16px 24px;border-bottom:1px solid #f3f4f6;display:flex;align-items:center;justify-content:space-between">
                <h3 style="font-size:1rem;font-weight:600;color:#111827;margin:0;display:flex;align-items:center;gap:8px">
                    <svg style="width:20px;height:20px;color:#6366f1;display:block;flex-shrink:0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Recent Scans (This Session)
                </h3>
                <button
                    wire:click="clearHistory"
                    style="font-size:0.78rem;color:#9ca3af;background:none;border:none;cursor:pointer;padding:4px 8px;border-radius:6px"
                    onmouseover="this.style.color='#ef4444'"
                    onmouseout="this.style.color='#9ca3af'"
                >Clear</button>
            </div>
            @foreach ($scanHistory as $scan)
                @php
                    $iconBg = match($scan['action']) {
                        'restocked'  => 'background:#dbeafe;color:#2563eb',
                        'dispatched' => 'background:#fef3c7;color:#d97706',
                        'not_found'  => 'background:#fee2e2;color:#dc2626',
                        default      => 'background:#f3f4f6;color:#6b7280',
                    };
                    $arrowPath = match($scan['action']) {
                        'restocked'  => 'M4.5 10.5L12 3m0 0l7.5 7.5M12 3v18',
                        'dispatched' => 'M19.5 13.5L12 21m0 0l-7.5-7.5M12 21V3',
                        'not_found'  => 'M6 18L18 6M6 6l12 12',
                        default      => 'M5 12h14',
                    };
                    $labelStyle = match($scan['action']) {
                        'restocked'  => 'color:#2563eb',
                        'dispatched' => 'color:#d97706',
                        'not_found'  => 'color:#dc2626',
                        default      => 'color:#9ca3af',
                    };
                    $label = match($scan['action']) {
                        'restocked'  => '↑ Restocked',
                        'dispatched' => '↓ Dispatched',
                        'not_found'  => 'Not Found',
                        default      => 'No Action',
                    };
                @endphp
                <div style="padding:12px 24px;display:flex;align-items:center;justify-content:space-between;{{ !$loop->last ? 'border-bottom:1px solid #f9fafb' : '' }}">
                    <div style="display:flex;align-items:center;gap:12px">
                        <span style="padding:6px;border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;{{ $iconBg }}">
                            <svg style="width:16px;height:16px;display:block" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="{{ $arrowPath }}" />
                            </svg>
                        </span>
                        <div>
                            <p style="font-size:0.875rem;font-family:monospace;color:#111827;margin:0;font-weight:500">{{ $scan['tracking_number'] }}</p>
                            @if ($scan['order_number'] !== '—')
                                <p style="font-size:0.75rem;color:#9ca3af;margin:2px 0 0">Order #{{ $scan['order_number'] }}</p>
                            @endif
                        </div>
                    </div>
                    <div style="text-align:right">
                        <p style="font-size:0.8rem;font-weight:600;margin:0;{{ $labelStyle }}">{{ $label }}</p>
                        <p style="font-size:0.75rem;color:#9ca3af;margin:2px 0 0">{{ $scan['scanned_at'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Status Legend --}}
    <div style="background:#f9fafb;border:1.5px dashed #e5e7eb;border-radius:14px;padding:20px">
        <p style="font-size:0.72rem;font-weight:700;color:#9ca3af;text-transform:uppercase;letter-spacing:0.08em;margin:0 0 12px">Status Logic</p>
        <div style="display:flex;flex-wrap:wrap;gap:20px">
            <div style="display:flex;align-items:center;gap:8px;font-size:0.875rem;color:#374151">
                <span style="padding:5px;border-radius:8px;background:#dbeafe;color:#2563eb;display:flex;align-items:center">
                    <svg style="width:16px;height:16px;display:block" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4.5 10.5L12 3m0 0l7.5 7.5M12 3v18" />
                    </svg>
                </span>
                <span><strong>Retourné</strong> → Incrementer le stock</span>
            </div>
            <div style="display:flex;align-items:center;gap:8px;font-size:0.875rem;color:#374151">
                <span style="padding:5px;border-radius:8px;background:#fef3c7;color:#d97706;display:flex;align-items:center">
                    <svg style="width:16px;height:16px;display:block" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19.5 13.5L12 21m0 0l-7.5-7.5M12 21V3" />
                    </svg>
                </span>
                <span><strong>En Système</strong> → Decrementer le stock</span>
            </div>
            <div style="display:flex;align-items:center;gap:8px;font-size:0.875rem;color:#374151">
                <span style="padding:5px;border-radius:8px;background:#f3f4f6;color:#6b7280;display:flex;align-items:center">
                    <svg style="width:16px;height:16px;display:block" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 12h14" />
                    </svg>
                </span>
                <span><strong>Autre statut</strong> → Aucune action</span>
            </div>
        </div>
    </div>

</div>

<style>
@keyframes spin { to { transform: rotate(360deg); } }
</style>
</x-filament-panels::page>