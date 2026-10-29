<?php

namespace App\View\Components;

use App\Support\Icon as IconSet;
use Illuminate\View\Component;
use Illuminate\View\View;

class Icon extends Component
{
    public string $path;

    public function __construct(
        public string $name,
        public string $class = 'size-5',
        public float $stroke = 1.75,
    ) {
        $this->path = IconSet::svg($name, $class, $stroke);
    }

    public function render(): View
    {
        return view('components.icon');
    }
}
