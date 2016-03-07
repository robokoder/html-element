<?php

namespace Spatie\HtmlElement;

class Html
{
    /** @var string */
    protected $tag;

    /** @var \Spatie\HtmlElement\Attributes */
    protected $attributes;

    /** @var array */
    protected $contents;

    public static function el(...$arguments) : string
    {
        $tag = $arguments[0];
        $attributes = isset($arguments[2]) ? $arguments[1] : [];
        $contents = $arguments[2] ?? $arguments[1] ?? [];

        if (! is_array($contents)) {
            $contents = [$contents];
        }

        return (new static($tag, $attributes, $contents))->render();
    }

    protected function __construct(string $tag, array $attributes = [], array $contents = [])
    {
        $this->attributes = new Attributes($attributes);
        $this->contents = $contents;

        $this->parseAndSetTag($tag);
    }

    protected function parseAndSetTag($tag)
    {
        $elements = explode('>', $tag, 2);

        if (isset($elements[1])) {
            $this->contents = [(new static($elements[1], [], $this->contents))->render()];
        }

        $parts = preg_split('/(?=[.#])/', $elements[0]);

        list($tag, $id, $classes) = array_reduce($parts, function ($parts, $part) {

            switch ($part[0]) {
                case '.':
                    $parts[2][] = ltrim($part, '.');
                    break;
                case '#':
                    $parts[1] = ltrim($part, '#');
                    break;
                default:
                    $parts[0] = $part;
                    break;
            }

            return $parts;

        }, ['div', '', []]);

        $this->tag = $tag;

        if (! empty($id)) {
            $this->attributes->setAttribute('id', $id);
        }

        $this->attributes->addClass($classes);
    }

    protected function isSelfClosingElement() : bool
    {
        return in_array(strtolower($this->tag), [
            'area', 'base', 'br', 'col', 'embed', 'hr',
            'img', 'input', 'keygen', 'link', 'menuitem',
            'meta', 'param', 'source', 'track', 'wbr',
        ]);
    }

    protected function renderOpeningTag() : string
    {
        return $this->attributes->isEmpty() ?
            "<{$this->tag}>" :
            "<{$this->tag} {$this->attributes}>";
    }

    protected function renderContents() : string
    {
        return implode('', $this->contents);
    }

    protected function renderClosingTag() : string
    {
        return "</{$this->tag}>";
    }

    protected function render() : string
    {
        if ($this->isSelfClosingElement()) {
            return $this->renderOpeningTag();
        }

        return "{$this->renderOpeningTag()}{$this->renderContents()}{$this->renderClosingTag()}";
    }
}
