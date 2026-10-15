<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Filament\Support\Facades\FilamentView;
use Illuminate\Support\Facades\Blade;
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        FilamentView::registerRenderHook(
        'panels::body.end',
        fn () => Blade::render("
            <script>
                document.addEventListener('livewire:initialized', () => {
                    Livewire.on('print-ticket', ({ url }) => {
                        const iframe = document.createElement('iframe');
                        iframe.style.display = 'none';
                        iframe.src = url;
                        document.body.appendChild(iframe);
                        iframe.onload = function() {
                            iframe.contentWindow.focus();
                            iframe.contentWindow.print();
                            setTimeout(() => document.body.removeChild(iframe), 3000);
                        };
                    });
                });
            </script>
        "),
    );
    }
}
