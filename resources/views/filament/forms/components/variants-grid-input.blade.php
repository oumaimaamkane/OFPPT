@php
    $record = isset($getRecord) && is_callable($getRecord) ? $getRecord() : (method_exists($this, 'getRecord') ? $this->getRecord() : null);
    $product = $record?->product;
    $options = collect($product?->allOptions ?? []);
    $variants = collect($product?->variants ?? []);

    $primaryValues = [];
    $secondaryValues = [];

    if ($variants->count() >= 2) {
        $var1 = $variants->get(1); 
        $var0 = $variants->get(0); 
        
        $primaryRaw = $var1->values ?? [];
        $secondaryRaw = $var0->values ?? [];
        
        $primaryValues = is_array($primaryRaw) ? $primaryRaw : explode(',', (string) $primaryRaw);
        $secondaryValues = is_array($secondaryRaw) ? $secondaryRaw : explode(',', (string) $secondaryRaw);
    } elseif ($variants->count() == 1) {
        $var0 = $variants->first();
        $primaryRaw = $var0->values ?? [];
        $primaryValues = is_array($primaryRaw) ? $primaryRaw : explode(',', (string) $primaryRaw);
    }
@endphp

@if($variants->count() >= 2)
<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div class="overflow-x-auto my-2 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900" style="min-width: max-content;">
        <table class="w-full text-xs text-center border-collapse">
            <thead class="bg-primary-50 dark:bg-primary-900/20 text-primary-600 dark:text-primary-400">
                <tr>
                    <th colspan="{{ count($primaryValues) * count($secondaryValues) }}" class="p-2 border-b border-gray-200 dark:border-gray-700 font-bold uppercase tracking-wider text-sm bg-blue-100/50 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300">
                        {{ $product?->name }} - Quantités par Variantes
                    </th>
                </tr>
                <tr>
                    @foreach($primaryValues as $pV)
                        <th colspan="{{ count($secondaryValues) }}" class="p-1.5 border-b border-gray-200 dark:border-gray-700 border-x font-semibold bg-gray-100/50 text-gray-700 dark:bg-gray-800/80 dark:text-gray-300">
                            {{ trim($pV) }}
                        </th>
                    @endforeach
                </tr>
                <tr>
                    @foreach($primaryValues as $pV)
                        @foreach($secondaryValues as $idx => $sV)
                            <th class="p-1.5 border-b border-gray-200 dark:border-gray-700 border-x font-medium bg-gray-50 text-gray-600 dark:bg-gray-800 dark:text-gray-400">
                                {{ trim($sV) }}
                            </th>
                        @endforeach
                    @endforeach
                </tr>
            </thead>
            <tbody>
                <tr>
                    @foreach($primaryValues as $pV)
                        @foreach($secondaryValues as $sV)
                            @php
                                $pT = strtolower(trim($pV));
                                $sT = strtolower(trim($sV));
                                $opt = $options->first(function ($o) use ($pT, $sT) {
                                    $name = strtolower($o->name);
                                    return str_contains($name, $pT) && str_contains($name, $sT);
                                });
                            @endphp
                            <td class="p-1.5 border-x border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900">
                                @if($opt)
                                    <input 
                                        type="number" 
                                        wire:model="data.variant_options_data.{{ $opt->id }}.quantity" 
                                        class="w-full text-center p-1 border border-gray-300 rounded focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-800 dark:border-gray-600 font-mono" 
                                        min="0"
                                    />
                                @else
                                    <span class="text-gray-400">N/A</span>
                                @endif
                            </td>
                        @endforeach
                    @endforeach
                </tr>
            </tbody>
        </table>
    </div>
</x-dynamic-component>

@elseif($variants->count() == 1)
<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div class="overflow-x-auto my-2 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900" style="min-width: max-content;">
        <table class="w-full text-xs text-center border-collapse">
            <thead class="bg-primary-50 dark:bg-primary-900/20 text-primary-600 dark:text-primary-400">
                <tr>
                    <th colspan="{{ count($primaryValues) }}" class="p-2 border-b border-gray-200 dark:border-gray-700 font-bold uppercase tracking-wider text-sm bg-blue-100/50 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300">
                        {{ $product?->name }} - Quantités par Variantes
                    </th>
                </tr>
                <tr>
                    @foreach($primaryValues as $pV)
                        <th class="p-1.5 border-b border-gray-200 dark:border-gray-700 border-x font-semibold bg-gray-100/50 text-gray-700 dark:bg-gray-800/80 dark:text-gray-300">
                            {{ trim($pV) }}
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                <tr>
                    @foreach($primaryValues as $pV)
                        @php
                            $pT = strtolower(trim($pV));
                            $opt = $options->first(function ($o) use ($pT) {
                                return str_contains(strtolower($o->name), $pT);
                            });
                        @endphp
                        <td class="p-1.5 border-x border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900">
                            @if($opt)
                                <input 
                                    type="number" 
                                    wire:model="data.variant_options_data.{{ $opt->id }}.quantity" 
                                    class="w-full text-center p-1 border border-gray-300 rounded focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-800 dark:border-gray-600 font-mono" 
                                    min="0"
                                />
                            @else
                                <span class="text-gray-400">N/A</span>
                            @endif
                        </td>
                    @endforeach
                </tr>
            </tbody>
        </table>
    </div>
</x-dynamic-component>
@endif
