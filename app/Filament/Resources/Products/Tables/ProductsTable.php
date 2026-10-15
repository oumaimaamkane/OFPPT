<?php

namespace App\Filament\Resources\Products\Tables;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Filters\QueryBuilder;
use Filament\Tables\Filters\QueryBuilder\Constraints\TextConstraint;
use Filament\Tables\Filters\QueryBuilder\Constraints\NumberConstraint;
use Filament\Tables\Filters\QueryBuilder\Constraints\BooleanConstraint;
use Filament\Tables\Filters\QueryBuilder\Constraints\DateConstraint;
use Filament\Tables\Filters\FiltersLayout;
use Filament\Actions\ActionGroup;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Support\Enums\Width;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Collection;
use Filament\Support\Icons\Heroicon;
use Filament\Actions\DeleteAction;
use App\Models\Product;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use App\Models\Variant;
use App\Models\VariantOption;
use App\Models\Warehouse;
use App\Models\WarehouseProduct;
use Filament\Facades\Filament;
class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                SpatieMediaLibraryImageColumn::make('media')
                ->collection('product-images')
                ->conversion('thumb')
                ->circular() // optional
                ->stacked()  // shows multiple images stacked
                ->limit(3), 
                ImageColumn::make('barcode')
                ->size('50%')
                ->disk('public'),

                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Medium),

                TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('warehouse_qty')
                    ->label('Quantity')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('price')
                    ->label('Price')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

        
                    TextColumn::make('variants_count')
                    ->label('Variants')
                    ->counts('variants')
                    ->sortable()
                    ,
                    TextColumn::make('warehouseProducts.warehouse.name')
                    ->label('Entrepôts')
                    ->badge()
                    ->sortable()
                    ->listWithLineBreaks()
                    ,
        
            ])
            ->filters([
                ])
                ->recordActions([
                    ViewAction::make(),
                    EditAction::make(),
                    ])
                    ->toolbarActions([
                        BulkActionGroup::make([
                            DeleteBulkAction::make(),
                            ForceDeleteBulkAction::make(),
                            RestoreBulkAction::make(),
                        ]),
                        ])
                        ->filters([
                 TrashedFilter::make(),
                QueryBuilder::make()
                    ->constraints([
                        TextConstraint::make('name'),
                        TextConstraint::make('slug'),
                        TextConstraint::make('sku')
                            ->label('SKU (Stock Keeping Unit)'),
                        TextConstraint::make('barcode')
                            ->label('Barcode (ISBN, UPC, GTIN, etc.)'),
                        TextConstraint::make('description'),
                    
                        NumberConstraint::make('price')
                            ->icon(Heroicon::CurrencyDollar),
                        NumberConstraint::make('cost')
                            ->label('Cost per item')
                            ->icon(Heroicon::CurrencyDollar),
                        NumberConstraint::make('qty')
                            ->label('Quantity'),
                        NumberConstraint::make('security_stock'),
                        BooleanConstraint::make('is_visible')
                            ->label('Visibility'),
                        BooleanConstraint::make('featured'),
                        BooleanConstraint::make('backorder'),
                        BooleanConstraint::make('requires_shipping')
                            ->icon(Heroicon::Truck),
                        DateConstraint::make('published_at')
                            ->label('Publishing date'),
                    ])
                    ->constraintPickerColumns(2),
            ])
            ->deferFilters()
            ->recordActions([
                ActionGroup::make([
                    EditAction::make()
                    ->visible(fn() => Filament::auth()->user()->can('Update:Product')),
                    Action::make('print_ticket')
                    ->icon(Heroicon::OutlinedPrinter)
                    ->label('Imprimer le codebar')
                    ->extraAttributes(fn (Product $product) => [
                        'onclick' => "
                            event.preventDefault();
                            const iframe = document.createElement('iframe');
                            iframe.style.display = 'none';
                            iframe.src = '" . route('products.print_ticket', $product) . "';
                            document.body.appendChild(iframe);
                            iframe.onload = function() {
                                iframe.contentWindow.focus();
                                iframe.contentWindow.print();
                                setTimeout(() => document.body.removeChild(iframe), 3000);
                            };
                        "
                    ])
                    ->visible(fn() => Filament::auth()->user()->can('Update:Product')),
                    Action::make('scan_qr_code')
    ->icon(Heroicon::OutlinedQrCode)
    ->label('Scanner le QR code')
    ->extraAttributes(fn (Product $product) => [
        'onclick' => "
            event.preventDefault();

            // ── helpers ──────────────────────────────────────────────
            const style = (el, css) => Object.assign(el.style, css);

            const makeModal = (inner) => {
                const overlay = document.createElement('div');
                style(overlay, {
                    position:'fixed', inset:'0',
                    background:'rgba(0,0,0,0.7)',
                    zIndex:'9999', display:'flex',
                    alignItems:'center', justifyContent:'center'
                });
                const box = document.createElement('div');
                style(box, {
                    background:'#fff', borderRadius:'12px',
                    padding:'24px', width:'360px', boxShadow:'0 8px 32px rgba(0,0,0,.2)'
                });
                box.appendChild(inner);
                overlay.appendChild(box);
                document.body.appendChild(overlay);
                return overlay;
            };

            // ── QR SCANNER MODAL ─────────────────────────────────────
            const scanWrap = document.createElement('div');
            scanWrap.style.textAlign = 'center';

            const scanTitle = document.createElement('p');
            scanTitle.textContent = 'Scanner le QR code';
            style(scanTitle, { fontWeight:'600', fontSize:'16px', marginBottom:'16px' });

            const video = document.createElement('video');
            style(video, { width:'100%', borderRadius:'8px' });
            video.setAttribute('playsinline', '');

            const scanResult = document.createElement('p');
            style(scanResult, { marginTop:'12px', fontSize:'13px', color:'#555', minHeight:'20px' });

            const closeBtn = document.createElement('button');
            closeBtn.textContent = 'Fermer';
            style(closeBtn, {
                marginTop:'16px', padding:'8px 20px',
                background:'#ef4444', color:'#fff',
                border:'none', borderRadius:'6px', cursor:'pointer'
            });

            scanWrap.appendChild(scanTitle);
            scanWrap.appendChild(video);
            scanWrap.appendChild(scanResult);
            scanWrap.appendChild(closeBtn);

            const scanModal = makeModal(scanWrap);

            let stream, animFrame;

            const stopCamera = () => {
                cancelAnimationFrame(animFrame);
                if (stream) stream.getTracks().forEach(t => t.stop());
            };

            const closeScan = () => {
                stopCamera();
                document.body.removeChild(scanModal);
            };

            closeBtn.addEventListener('click', closeScan);
            scanModal.addEventListener('click', e => { if (e.target === scanModal) closeScan(); });

            // ── QUANTITY MODAL ───────────────────────────────────────
            const showQuantityModal = (scannedData) => {
                closeScan();

                const qtyWrap = document.createElement('div');

                const qtyTitle = document.createElement('p');
                qtyTitle.textContent = 'Ajuster la quantité';
                style(qtyTitle, { fontWeight:'700', fontSize:'17px', marginBottom:'4px', color:'#111' });

                const qtySubtitle = document.createElement('p');
                qtySubtitle.textContent = 'Code : ' + scannedData;
                style(qtySubtitle, { fontSize:'12px', color:'#888', marginBottom:'20px' });

                // Warehouse select
                const warehouseLabel = document.createElement('label');
                warehouseLabel.textContent = 'Entrepôt';
                style(warehouseLabel, { display:'block', fontSize:'13px', fontWeight:'600', marginBottom:'4px', color:'#374151' });

                const warehouseSelect = document.createElement('select');
                style(warehouseSelect, {
                    width:'100%', padding:'8px 10px', border:'1px solid #d1d5db',
                    borderRadius:'6px', fontSize:'14px', marginBottom:'16px',
                    background:'#f9fafb', cursor:'pointer'
                });
                [['', 'Sélectionner un entrepôt'], ['wh1', 'Entrepôt Principal'], ['wh2', 'Entrepôt Secondaire'], ['wh3', 'Dépôt Externe']]
                    .forEach(([val, txt]) => {
                        const opt = document.createElement('option');
                        opt.value = val; opt.textContent = txt;
                        if (!val) opt.disabled = true, opt.selected = true;
                        warehouseSelect.appendChild(opt);
                    });

                // Operation type
                const opLabel = document.createElement('label');
                opLabel.textContent = 'Opération';
                style(opLabel, { display:'block', fontSize:'13px', fontWeight:'600', marginBottom:'8px', color:'#374151' });

                const opRow = document.createElement('div');
                style(opRow, { display:'flex', gap:'8px', marginBottom:'16px' });

                let selectedOp = 'add';
                const ops = [['add', '+ Ajouter', '#16a34a'], ['sub', '− Retirer', '#dc2626'], ['set', '= Définir', '#2563eb']];
                const opBtns = {};
                ops.forEach(([val, txt, color]) => {
                    const btn = document.createElement('button');
                    btn.textContent = txt;
                    style(btn, {
                        flex:'1', padding:'8px 0', border:'2px solid ' + color,
                        borderRadius:'6px', cursor:'pointer', fontSize:'13px',
                        fontWeight:'600', color: val === 'add' ? '#fff' : color,
                        background: val === 'add' ? color : '#fff',
                        transition:'all .15s'
                    });
                    btn.addEventListener('click', () => {
                        selectedOp = val;
                        ops.forEach(([v,,c]) => {
                            style(opBtns[v], { background: v === val ? c : '#fff', color: v === val ? '#fff' : c });
                        });
                    });
                    opBtns[val] = btn;
                    opRow.appendChild(btn);
                });

                // Quantity input
                const qtyLabel = document.createElement('label');
                qtyLabel.textContent = 'Quantité';
                style(qtyLabel, { display:'block', fontSize:'13px', fontWeight:'600', marginBottom:'4px', color:'#374151' });

                const qtyRow = document.createElement('div');
                style(qtyRow, { display:'flex', gap:'8px', marginBottom:'20px' });

                const minusBtn = document.createElement('button');
                minusBtn.textContent = '−';
                style(minusBtn, {
                    width:'38px', height:'38px', border:'1px solid #d1d5db',
                    borderRadius:'6px', fontSize:'18px', cursor:'pointer', background:'#f3f4f6'
                });

                const qtyInput = document.createElement('input');
                qtyInput.type = 'number';
                qtyInput.value = '1';
                qtyInput.min = '0';
                style(qtyInput, {
                    flex:'1', textAlign:'center', border:'1px solid #d1d5db',
                    borderRadius:'6px', fontSize:'16px', fontWeight:'600', padding:'0 8px'
                });

                const plusBtn = document.createElement('button');
                plusBtn.textContent = '+';
                style(plusBtn, {
                    width:'38px', height:'38px', border:'1px solid #d1d5db',
                    borderRadius:'6px', fontSize:'18px', cursor:'pointer', background:'#f3f4f6'
                });

                minusBtn.addEventListener('click', () => { qtyInput.value = Math.max(0, parseInt(qtyInput.value || 0) - 1); });
                plusBtn.addEventListener('click', () => { qtyInput.value = parseInt(qtyInput.value || 0) + 1; });

                qtyRow.appendChild(minusBtn);
                qtyRow.appendChild(qtyInput);
                qtyRow.appendChild(plusBtn);

                // Note input
                const noteLabel = document.createElement('label');
                noteLabel.textContent = 'Note (optionnel)';
                style(noteLabel, { display:'block', fontSize:'13px', fontWeight:'600', marginBottom:'4px', color:'#374151' });

                const noteInput = document.createElement('textarea');
                noteInput.rows = 2;
                noteInput.placeholder = 'Raison de l\'ajustement...';
                style(noteInput, {
                    width:'100%', padding:'8px 10px', border:'1px solid #d1d5db',
                    borderRadius:'6px', fontSize:'13px', marginBottom:'20px',
                    resize:'none', boxSizing:'border-box', fontFamily:'inherit'
                });

                // Feedback
                const feedback = document.createElement('p');
                style(feedback, { fontSize:'13px', minHeight:'18px', marginBottom:'12px', textAlign:'center' });

                // Action buttons
                const actionRow = document.createElement('div');
                style(actionRow, { display:'flex', gap:'10px' });

                const cancelBtn = document.createElement('button');
                cancelBtn.textContent = 'Annuler';
                style(cancelBtn, {
                    flex:'1', padding:'10px', border:'1px solid #d1d5db',
                    borderRadius:'6px', cursor:'pointer', fontSize:'14px',
                    background:'#f9fafb', color:'#374151'
                });

                const confirmBtn = document.createElement('button');
                confirmBtn.textContent = 'Confirmer';
                style(confirmBtn, {
                    flex:'1', padding:'10px', border:'none',
                    borderRadius:'6px', cursor:'pointer', fontSize:'14px',
                    fontWeight:'600', background:'#2563eb', color:'#fff'
                });

                confirmBtn.addEventListener('click', () => {
                    const warehouse = warehouseSelect.value;
                    const qty = parseInt(qtyInput.value);
                    if (!warehouse) {
                        feedback.textContent = '⚠ Veuillez sélectionner un entrepôt.';
                        style(feedback, { color: '#dc2626' });
                        return;
                    }
                    if (isNaN(qty) || qty < 0) {
                        feedback.textContent = '⚠ Quantité invalide.';
                        style(feedback, { color: '#dc2626' });
                        return;
                    }

                    confirmBtn.textContent = 'Enregistrement...';
                    confirmBtn.disabled = true;

                    fetch('/api/warehouse/adjust', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                        body: JSON.stringify({
                            product_id: " . $product->id . ",
                            scanned_code: scannedData,
                            warehouse: warehouse,
                            operation: selectedOp,
                            quantity: qty,
                            note: noteInput.value
                        })
                    })
                    .then(r => r.json())
                    .then(data => {
                        feedback.textContent = '✓ Quantité mise à jour avec succès!';
                        style(feedback, { color: '#16a34a' });
                        setTimeout(() => document.body.removeChild(qtyModal), 1500);
                    })
                    .catch(() => {
                        feedback.textContent = '✗ Erreur lors de la mise à jour.';
                        style(feedback, { color: '#dc2626' });
                        confirmBtn.textContent = 'Confirmer';
                        confirmBtn.disabled = false;
                    });
                });

                cancelBtn.addEventListener('click', () => document.body.removeChild(qtyModal));
                qtyModal.addEventListener('click', e => { if (e.target === qtyModal) document.body.removeChild(qtyModal); });

                actionRow.appendChild(cancelBtn);
                actionRow.appendChild(confirmBtn);

                [qtyTitle, qtySubtitle, warehouseLabel, warehouseSelect, opLabel, opRow, qtyLabel, qtyRow, noteLabel, noteInput, feedback, actionRow]
                    .forEach(el => qtyWrap.appendChild(el));

                const qtyModal = makeModal(qtyWrap);
            };

            // ── START CAMERA ─────────────────────────────────────────
            navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } })
                .then(s => {
                    stream = s;
                    video.srcObject = stream;
                    video.play();

                    const canvas = document.createElement('canvas');
                    const ctx = canvas.getContext('2d');
                    let detected = false;

                    const scan = () => {
                        if (detected) return;
                        if (video.readyState === video.HAVE_ENOUGH_DATA) {
                            canvas.width = video.videoWidth;
                            canvas.height = video.videoHeight;
                            ctx.drawImage(video, 0, 0);
                            const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
                            const code = jsQR(imageData.data, imageData.width, imageData.height);
                            if (code) {
                                detected = true;
                                scanResult.textContent = '✓ Code détecté !';
                                scanResult.style.color = '#16a34a';
                                setTimeout(() => showQuantityModal(code.data), 500);
                                return;
                            }
                        }
                        animFrame = requestAnimationFrame(scan);
                    };
                    animFrame = requestAnimationFrame(scan);
                })
                .catch(() => {
                    scanResult.textContent = 'Impossible d\'accéder à la caméra.';
                    scanResult.style.color = '#ef4444';
                });
        "
    ])
    ->visible(fn() => Filament::auth()->user()->can('View:ScanBarcode')),
                    
                    Action::make('assign_to_warehouse')
                    ->icon(Heroicon::CubeTransparent)
                    ->color('primary')
                    ->modalWidth(Width::Medium)
                    ->modalSubmitActionLabel('Enregistrer')
                    ->fillForm(function ($record) {
                        // Pre-load existing warehouse stock
                        if ($record->warehouseProducts && $record->warehouseProducts->isNotEmpty()) {
                            $warehouseStock = $record->warehouseProducts
                                ->mapWithKeys(fn ($wp) => [
                                    $wp->warehouse_id => [
                                        'warehouse_id' => $wp->warehouse_id,
                                        'quantity'     => $wp->quantity,
                                    ],
                                ])
                                ->toArray();

                            return [
                                'warehouse_stock' => $warehouseStock,
                            ];
                        }
                    })
                    ->schema([
                        Repeater::make('warehouse_stock')
                            ->label('Stock par entrepôt')
                            ->schema([
                                Select::make('warehouse_id')
                                    ->label('Entrepôt')
                                    ->options(Warehouse::all()->pluck('name', 'id'))
                                    ->required()
                                    ->live()
                                    ,

                                TextInput::make('quantity')
                                    ->label('Quantité')
                                    ->numeric()
                                    ->minValue(0)
                                    ->default(function ($record , Get $get) {
                                        $warehouseId = $get('warehouse_id');
                                        if (!$warehouseId || !$record) return $record->warehouse_qty;
                                        
                                        $wp = $record->warehouseProducts
                                            ->firstWhere('warehouse_id', $warehouseId);
                                        
                                        return $wp?->quantity ?? $record->warehouse_qty;
                                    })
                                    ->disabled(fn (Get $get) => blank($get('warehouse_id'))),
                            ])
                            ->columnSpanFull()
                            ->defaultItems(1),
                    ])
                    ->action(function (Product $record, array $data) {
                        // Update warehouse stock
                       $record->warehouseProducts && $record->warehouseProducts->isNotEmpty() ? $record->warehouseProducts()->delete() : null;

                        foreach ($data['warehouse_stock'] as $item) {
                            if (!blank($item['warehouse_id'])) {
                                WarehouseProduct::create([
                                    'product_id' => $record->id,
                                    'warehouse_id' => $item['warehouse_id'],
                                    'quantity' => $item['quantity'],
                                ]);
                            }
                        }
                        Notification::make()
                        ->title('Produit à été assigné à un entrepôt')
                        ->success()
                        ->send();
                    })
                    ->visible(fn() => Filament::auth()->user()->can('Update:Product')),
                 
                    DeleteAction::make()
                    ->visible(fn() => Filament::auth()->user()->can('Delete:Product')),
                ]),
            ])->groupedBulkActions([
                DeleteBulkAction::make()
                ->visible(fn() => Filament::auth()->user()->can('Delete:Product')),
                ExportBulkAction::make(),
            ]);
    }
}
