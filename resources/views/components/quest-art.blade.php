@props([
    'quest' => null,
    'action' => null,
    'method' => null,
    'seed' => 1,
    'size' => 'md',
])

@php
    use App\Support\QuestArt;

    // Bila diberi model Quest, jenis ilustrasi diturunkan dari aksi & metode
    // verifikasinya. Bila tidak, pemanggil boleh memberi enum langsung.
    if ($quest) {
        $svg = QuestArt::forQuest($quest);
    } else {
        $svg = QuestArt::for($action, $method, (int) $seed);
    }

    $box = match ($size) {
        'sm' => 'size-14',
        'lg' => 'size-28',
        default => 'size-20',
    };
@endphp

<span {{ $attributes->merge(['class' => "quest-art grid shrink-0 place-items-center {$box}"]) }}>
    {!! $svg !!}
</span>