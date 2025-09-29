<?php

namespace FilamentTiptapEditor;

use FilamentTiptapEditor\Exceptions\ImagesNotResolvableException;
use FilamentTiptapEditor\Extensions\Extensions\ClassExtension;
use FilamentTiptapEditor\Extensions\Extensions\Color;
use FilamentTiptapEditor\Extensions\Extensions\IdExtension;
use FilamentTiptapEditor\Extensions\Extensions\StyleExtension;
use FilamentTiptapEditor\Extensions\Extensions\TextAlign;
use FilamentTiptapEditor\Extensions\Marks\Link;
use FilamentTiptapEditor\Extensions\Marks\Small;
use FilamentTiptapEditor\Extensions\Nodes\CheckedList;
use FilamentTiptapEditor\Extensions\Nodes\Details;
use FilamentTiptapEditor\Extensions\Nodes\DetailsContent;
use FilamentTiptapEditor\Extensions\Nodes\DetailsSummary;
use FilamentTiptapEditor\Extensions\Nodes\Grid;
use FilamentTiptapEditor\Extensions\Nodes\GridBuilder;
use FilamentTiptapEditor\Extensions\Nodes\GridBuilderColumn;
use FilamentTiptapEditor\Extensions\Nodes\GridColumn;
use FilamentTiptapEditor\Extensions\Nodes\Hurdle;
use FilamentTiptapEditor\Extensions\Nodes\Image;
use FilamentTiptapEditor\Extensions\Nodes\Lead;
use FilamentTiptapEditor\Extensions\Nodes\ListItem;
use FilamentTiptapEditor\Extensions\Nodes\MergeTag;
use FilamentTiptapEditor\Extensions\Nodes\TiptapBlock;
use FilamentTiptapEditor\Extensions\Nodes\Video;
use FilamentTiptapEditor\Extensions\Nodes\Vimeo;
use FilamentTiptapEditor\Extensions\Nodes\YouTube;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\FileUploadConfiguration;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Tiptap\Editor;
use Tiptap\Extensions\StarterKit;
use Tiptap\Marks\Highlight;
use Tiptap\Marks\Subscript;
use Tiptap\Marks\Superscript;
use Tiptap\Marks\TextStyle;
use Tiptap\Marks\Underline;
use Tiptap\Nodes\CodeBlockHighlight;
use Tiptap\Nodes\Table;
use Tiptap\Nodes\TableCell;
use Tiptap\Nodes\TableHeader;
use Tiptap\Nodes\TableRow;

class TiptapConverter
{
    protected Editor $editor;

    protected ?array $blocks = null;

    protected bool $tableOfContents = false;

    protected array $mergeTagsMap = [];

    protected ?Model $record = null;

    protected ?string $recordAttribute = null;

    public function getEditor(): Editor
    {
        return $this->editor ??= new Editor([
            'extensions' => $this->getExtensions(),
        ]);
    }

    public function blocks(array $blocks): static
    {
        $this->blocks = $blocks;

        return $this;
    }

    public function getExtensions(): array
    {
        $customExtensions = collect(config('filament-tiptap-editor.extensions', []))
            ->transform(function ($ext) {
                return new $ext['parser'];
            })->toArray();

        return [
            new StarterKit([
                'listItem' => false,
            ]),
            new TextStyle,
            new TextAlign([
                'types' => ['heading', 'paragraph'],
            ]),
            new ClassExtension,
            new IdExtension,
            new StyleExtension,
            new Color,
            new CodeBlockHighlight,
            new ListItem,
            new Lead,
            new Image,
            new CheckedList,
            new Details,
            new DetailsSummary,
            new DetailsContent,
            new Grid,
            new GridColumn,
            new GridBuilder,
            new GridBuilderColumn,
            new MergeTag,
            new Vimeo,
            new YouTube,
            new Video,
            new TiptapBlock(['blocks' => $this->blocks]),
            new Hurdle,
            new Table,
            new TableHeader,
            new TableRow,
            new TableCell,
            new Highlight,
            new Underline,
            new Superscript,
            new Subscript,
            new Link,
            new Small,
            ...$customExtensions,
        ];
    }

    public function mergeTagsMap(array $mergeTagsMap): static
    {
        $this->mergeTagsMap = $mergeTagsMap;

        return $this;
    }

    public function asHTML(string | array $content, bool $toc = false, int $maxDepth = 3, array $newImages = []): string
    {
        $editor = $this->getEditor()->setContent($content);

        if ($toc) {
            $this->parseHeadings($editor, $maxDepth);
        }

        if (filled($this->mergeTagsMap)) {
            $this->parseMergeTags($editor);
        }

        $this->generateImageUrls($editor, $newImages);

        return $editor->getHTML();
    }

    public function asJSON(string | array $content, bool $decoded = false, bool $toc = false, int $maxDepth = 3): string | array
    {
        $editor = $this->getEditor()->setContent($content);

        if ($toc) {
            $this->parseHeadings($editor, $maxDepth);
        }

        if (filled($this->mergeTagsMap)) {
            $this->parseMergeTags($editor);
        }

        return $decoded ? json_decode($editor->getJSON(), true) : $editor->getJSON();
    }

    public function saveImages(array $document, string $disk, HasMedia $record, string $recordAttribute, array $newImages, ?Collection $existingImages = null, array $unusedImageKeys = []): array
    {
        $existingImages ??= collect([]);

        $document['content'] ??= [];

        return [json_decode($this->getEditor()->setContent($document)->descendants(function (&$node) use ($disk, $existingImages, $newImages, $record, $recordAttribute, &$unusedImageKeys) {
            if ($node->type !== 'image') {
                return;
            }

            $id = $node->attrs->id ?? null;

            if (blank($id)) {
                return;
            }

            if (($unusedImageIndex = array_search($id, $unusedImageKeys)) !== false) {
                unset($unusedImageKeys[$unusedImageIndex]);
            }

            if ($existingImages->has($id)) {
                return;
            }

            if (array_key_exists($id, $newImages)) {
                $newImage = $newImages[$id];

                $content = ($newImage instanceof TemporaryUploadedFile) ?
                    $newImage->get() :
                    FileUploadConfiguration::storage()->get($newImage['path']);

                $extension = ($newImage instanceof TemporaryUploadedFile) ?
                    $newImage->getClientOriginalExtension() :
                    $newImage['extension'];

                $image = $record
                    ->addMediaFromString($content)
                    ->usingFileName(((string) Str::ulid()) . '.' . $extension)
                    ->toMediaCollection($recordAttribute, diskName: $disk);

                $existingImages->put($image->uuid, $image);

                $node->attrs->id = $image->uuid;

                return;
            }

            $existingImage = Media::findByUuid($id);

            if (! $existingImage) {
                return;
            }

            $newImage = $existingImage->copy($record, collectionName: $recordAttribute, diskName: $disk);

            $existingImages->put($newImage->uuid, $newImage);

            $node->attrs->id = $newImage->uuid;
        })->getJSON(), associative: true), $unusedImageKeys];
    }

    public function copyImagesToNewRecord(array $content, Model $replica, string $disk): array
    {
        $editor = $this->getEditor()->setContent($content);

        $record = $this->getRecord();

        $recordAttribute = $this->getRecordAttribute();

        $images = $record instanceof HasMedia ?
            $record->getMedia(collectionName: $recordAttribute)->keyBy('uuid') :
            collect([]);

        $editor->descendants(function (&$node) use ($disk, $images, $record, $recordAttribute, $replica) {
            if ($node->type !== 'image') {
                return;
            }

            $id = $node->attrs?->id ?? null;

            if (blank($id)) {
                return;
            }

            if (
                (! ($record instanceof HasMedia)) ||
                blank($recordAttribute)
            ) {
                throw new ImagesNotResolvableException("Image [{$id}] attempted to be replicated, but the TipTap converter was not configured with the media record and attribute.");
            }

            if (! $images->has($id)) {
                return;
            }

            $newImage = $images->get($id)->copy($replica, collectionName: $recordAttribute, diskName: $disk);

            $node->attrs->id = $newImage->uuid;
        });

        return json_decode($editor->getJSON(), associative: true);
    }

    public function asText(string | array $content): string
    {
        $editor = $this->getEditor()->setContent($content);

        if (filled($this->mergeTagsMap)) {
            $this->parseMergeTags($editor);
        }

        return $editor->getText();
    }

    public function asTOC(string | array $content, int $maxDepth = 3): string
    {
        if (is_string($content)) {
            $content = $this->asJSON($content, decoded: true);
        }

        $headings = $this->parseTocHeadings($content['content'], $maxDepth);

        return $this->generateNestedTOC($headings, $headings[0]['level']);
    }

    public function parseHeadings(Editor $editor, int $maxDepth = 3): Editor
    {
        $editor->descendants(function (&$node) use ($maxDepth) {
            if ($node->type !== 'heading') {
                return;
            }

            if ($node->attrs->level > $maxDepth) {
                return;
            }

            if (! property_exists($node->attrs, 'id') || $node->attrs->id === null) {
                $node->attrs->id = str(collect($node->content)->map(function ($node) {
                    return $node?->text ?? null;
                })->implode(' '))->kebab()->toString();
            }

            array_unshift($node->content, (object) [
                'type' => 'text',
                'text' => '#',
                'marks' => [
                    [
                        'type' => 'link',
                        'attrs' => [
                            'href' => '#' . $node->attrs->id,
                        ],
                    ],
                ],
            ]);
        });

        return $editor;
    }

    public function parseTocHeadings(array $content, int $maxDepth = 3): array
    {
        $headings = [];

        foreach ($content as $node) {
            if ($node['type'] === 'heading') {
                if ($node['attrs']['level'] <= $maxDepth) {
                    $text = collect($node['content'])->map(function ($node) {
                        return $node['text'] ?? null;
                    })->implode(' ');

                    if (! isset($node['attrs']['id'])) {
                        $node['attrs']['id'] = str($text)->kebab()->toString();
                    }

                    $headings[] = [
                        'level' => $node['attrs']['level'],
                        'id' => $node['attrs']['id'],
                        'text' => $text,
                    ];
                }
            } elseif (array_key_exists('content', $content)) {
                $this->parseTocHeadings($content, $maxDepth);
            }
        }

        return $headings;
    }

    public function parseMergeTags(Editor $editor): Editor
    {
        $editor->descendants(function (&$node) {
            if ($node->type !== 'mergeTag') {
                return;
            }

            if (filled($this->mergeTagsMap)) {
                $node->content = [
                    (object) [
                        'type' => 'text',
                        'text' => $this->mergeTagsMap[$node->attrs->id] ?? null,
                    ],
                ];
            }
        });

        return $editor;
    }

    public function generateImageUrls(Editor $editor, array $newImages = []): Editor
    {
        $record = $this->getRecord();

        $recordAttribute = $this->getRecordAttribute();

        $images = $record instanceof HasMedia ? $record->getMedia(collectionName: $recordAttribute)->keyBy('uuid') : collect([]);

        $editor->descendants(function (&$node) use ($images, $newImages) {
            if ($node->type !== 'image') {
                return;
            }

            $id = $node->attrs?->id ?? null;

            if (blank($id)) {
                return;
            }

            unset($node->attrs->id);

            if ($newImage = ($newImages[$id] ?? null)) {
                $node->attrs->src = $newImage->temporaryUrl();

                return;
            }

            if (! $images->has($id)) {
                return;
            }

            $image = $images->get($id);

            if (config("filesystems.disks.{$image->disk}.media_library_visibility") === 'public') {
                $node->attrs->src = $image->getUrl();

                return;
            }

            $node->attrs->src = $image->getTemporaryUrl(now()->addDay());
        });

        return $editor;
    }

    public function generateNestedTOC(array $headings, int $parentLevel = 0): string
    {
        $result = '<ul>';
        $prev = $parentLevel;

        foreach ($headings as $item) {
            $prev <= $item['level'] ?: $result .= str_repeat('</ul>', $prev - $item['level']);
            $prev >= $item['level'] ?: $result .= '<ul>';

            $result .= '<li><a href="#' . $item['id'] . '">' . $item['text'] . '</a></li>';

            $prev = $item['level'];
        }

        $result .= '</ul>';

        return $result;
    }

    public function record(?Model $record, ?string $attribute): static
    {
        $this->record = $record;
        $this->recordAttribute = $attribute;

        return $this;
    }

    public function getRecord(): ?Model
    {
        return $this->record;
    }

    public function getRecordAttribute(): ?string
    {
        return $this->recordAttribute;
    }
}
