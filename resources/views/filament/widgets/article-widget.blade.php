<x-filament-widgets::widget>
        @foreach ($articles as $article)
    <x-filament::section icon="heroicon-o-information-circle">
            <x-slot name="heading">
                {{ $article->title }}
            </x-slot>
            {!! $article->content !!}
@if ($article->date)
            <x-slot name="headerEnd">
                {{ date('d.m.Y', strtotime($article->date)) }}
            </x-slot>
@endif
    </x-filament::section>
        <br>
        @endforeach
</x-filament-widgets::widget>
