<x-filament-widgets::widget>
        @foreach ($articles as $article)
    <x-filament::section icon="heroicon-o-information-circle">
            <x-slot name="heading">
                {{ $article->title }}
            </x-slot>
@if ($article->date)
            <x-slot name="afterHeader">
                {{ date('d.m.Y', strtotime($article->date)) }}
            </x-slot>
@endif
            <x-slot name="description">
                {!! $article->content !!}
            </x-slot>
    </x-filament::section>
        <br>
        @endforeach
</x-filament-widgets::widget>
