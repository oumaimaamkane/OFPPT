@php
    $product = $getRecord()->product;
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
<div class="overflow-x-auto my-2 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900" style="min-width: max-content;">
    <table class="w-full text-xs text-center border-collapse">
        <thead class="bg-primary-50 dark:bg-primary-900/20 text-primary-600 dark:text-primary-400">
            <tr style="border: 1px solid #e5e7eb; padding:5px;">
                @foreach($primaryValues as $pV)
                    <th style="border: 1px solid #e5e7eb; padding:5px;" colspan="{{ count($secondaryValues) }}" class="p-1.5 border-b border-gray-200 dark:border-gray-700 border-x font-semibold bg-green-100/50 text-green-700 dark:bg-green-900/20 dark:text-green-400">
                        {{ trim($pV) }}
                    </th>
                @endforeach
            </tr>
            <tr>
                @foreach($primaryValues as $pV)
                    @foreach($secondaryValues as $idx => $sV)
                        <th style="border: 1px solid #e5e7eb; padding:5px;" class="p-1.5 border-b border-gray-200 dark:border-gray-700 border-x font-medium bg-green-50 text-green-600 dark:bg-gray-800 dark:text-green-500">
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
                        <td style="border: 1px solid #e5e7eb; padding:5px; vertical-align: top; min-width: 105px;" class="p-1.5 border-x border-gray-200 dark:border-gray-700 font-mono {{ ($opt && $opt->quantity > 0) ? 'font-bold' : 'text-gray-400' }}">
                            <div  style="display: flex; flex-direction: column; align-items: center; gap: 1px; width: 100%; flex-wrap: nowrap;">
                                <span class="leading-tight">{{ $opt ? $opt->quantity : '0' }}</span>
                                @if($opt)
                                <div  style="display: flex; align-items: center; justify-content: center; gap: 1px; width: 100%;" x-data="{ amount: '' }" x-on:click.stop>
                                    <div class="fi-input-wrp flex shrink-0 rounded-lg shadow-sm ring-1 transition duration-75 bg-white focus-within:ring-2 dark:bg-white/5 ring-gray-950/10 focus-within:ring-primary-600 dark:ring-white/20 dark:focus-within:ring-primary-500" style="width: 4rem;">
                                        <input
                                            type="number"
                                            x-model="amount"
                                            x-on:keydown.enter.prevent="$wire.updateVariantQuantity({{ $opt->id }}, amount); amount = '';"
                                            class="fi-input block w-full border-none py-0.5 text-xs text-center text-gray-950 transition duration-75 placeholder:text-gray-400 focus:ring-0 dark:text-white dark:placeholder:text-gray-500 bg-transparent px-1 [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none"
                                            placeholder="quantité"
                                        />
                                    </div>
                                    <button
                                        type="button"
                                        style="background-color: #000000; color: #ffffff; border:1px solid #000000; padding: 5px;"
                                        x-on:click="$wire.updateVariantQuantity({{ $opt->id }}, amount); amount = '';"
                                        class="fi-icon-btn flex shrink-0 items-center justify-center"
                                        title="Ajouter"
                                    >
                                        <svg class="w-3.5 h-3.5" width="10" height="10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
</svg>
                                    </button>
                                </div>
                                @endif
                            </div>
                        </td>
                    @endforeach
                @endforeach
            </tr>
        </tbody>
    </table>
</div>

@elseif($variants->count() == 1)
<div class="overflow-x-auto my-2 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900" style="min-width: max-content;">
    <table class="w-full text-xs text-center border-collapse">
        <thead class="bg-primary-50 dark:bg-primary-900/20 text-primary-600 dark:text-primary-400">
            <tr>
                @foreach($primaryValues as $pV)
                    <th style="border: 1px solid #e5e7eb; padding:5px;" class="p-1.5 border-b border-gray-200 dark:border-gray-700 border-x font-semibold bg-green-100/50 text-green-700 dark:bg-green-900/20 dark:text-green-400">
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
                    <td style="border: 1px solid #e5e7eb; padding:5px; vertical-align: top; min-width: 105px;" class="p-1.5 border-x border-gray-200 dark:border-gray-700 font-mono {{ ($opt && $opt->quantity > 0) ? 'font-bold' : 'text-gray-400' }}">
                        <div style="display: flex; flex-direction: column; align-items: center; gap: 1px; width: 100%; flex-wrap: nowrap;">
                            <span class="leading-tight">{{ $opt ? $opt->quantity : '0' }}</span>
                            @if($opt)
                            <div style="display: flex; align-items: center; justify-content: center; gap: 1px; width: 100%;" x-data="{ amount: '' }" x-on:click.stop>
                                <div class="fi-input-wrp flex shrink-0 rounded-lg shadow-sm ring-1 transition duration-75 bg-white focus-within:ring-2 dark:bg-white/5 ring-gray-950/10 focus-within:ring-primary-600 dark:ring-white/20 dark:focus-within:ring-primary-500" style="width: 4rem;">
                                    <input
                                        type="number"
                                        x-model="amount"
                                        x-on:keydown.enter.prevent="$wire.updateVariantQuantity({{ $opt->id }}, amount); amount = '';"
                                        class="fi-input block w-full border-none py-0.5 text-xs text-center text-gray-950 transition duration-75 placeholder:text-gray-400 focus:ring-0 dark:text-white dark:placeholder:text-gray-500 bg-transparent px-1 [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none"
                                        placeholder="quantité"
                                    />
                                </div>
                                <button
                                    type="button"
                                    x-on:click="$wire.updateVariantQuantity({{ $opt->id }}, amount); amount = '';"
                                    style="background-color: #000000; color: #ffffff; border:1px solid #000000; padding: 5px;"
                                    class="fi-icon-btn flex shrink-0 items-center justify-center"
                                    title="Ajouter"
                                >
                                     <svg class="w-3.5 h-3.5" width="10" height="10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
</svg>
                                </button>
                            </div>
                            @endif
                        </div>
                    </td>
                @endforeach
            </tr>
        </tbody>
    </table>
</div>

@else
<span class="text-xs text-gray-500">Pas de variantes</span>
@endif