<?php

namespace App\Filament\Widgets;

use App\Models\Article;
use Illuminate\Database\Eloquent\Collection;
use Filament\Widgets\Widget;

class ArticleWidget extends Widget
{
    protected string $view = 'filament.widgets.article-widget';
    protected int | string | array $columnSpan = 2;

    public Collection $articles;

    public function mount(): void
    {
        $this->articles = Article::all();
    }
}
