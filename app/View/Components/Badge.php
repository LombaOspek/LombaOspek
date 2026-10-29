<?php

namespace App\View\Components;

use App\Support\Badge as BadgeArt;
use Illuminate\View\Component;
use Illuminate\View\View;

class Badge extends Component
{
    public string $svg;

    public function __construct(
        public string $icon,
        public string $tone = 'teal',
        public bool $unlocked = true,
        public string $class = 'size-12',
        public ?string $label = null,
    ) {
        $this->svg = BadgeArt::crest($icon, $tone, $unlocked, $class, $label);
    }

    public function render(): View
    {
        return view('components.badge');
    }
}
