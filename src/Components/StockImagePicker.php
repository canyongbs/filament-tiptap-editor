<?php

namespace FilamentTiptapEditor\Components;

use Closure;
use Filament\Forms\Components\Field;

class StockImagePicker extends Field
{
    /**
     * @var view-string
     */
    protected string $view = 'filament-tiptap-editor::components.stock-image-picker';

    protected string | Closure | null $url = null;

    public function url(string | Closure | null $url): static
    {
        $this->url = $url;

        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->evaluate($this->url);
    }
}
