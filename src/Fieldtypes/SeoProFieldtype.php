<?php

namespace Statamic\SeoPro\Fieldtypes;

use Statamic\Contracts\Entries\Entry;
use Statamic\Contracts\Taxonomies\Term;
use Statamic\Facades\Blink;
use Statamic\Facades\Blueprint;
use Statamic\Facades\GraphQL;
use Statamic\Fields\Fields as BlueprintFields;
use Statamic\Fields\Fieldtype;
use Statamic\SeoPro\Fields as SeoProFields;
use Statamic\Support\Arr;

class SeoProFieldtype extends Fieldtype
{
    protected $selectable = true;
    protected $icon = 'seo-search-graph';

    public function preProcess($data)
    {
        if ($data === false) {
            $data = ['enabled' => false];
        }

        return $this->fields()->addValues($data ?? [])->preProcess()->values()->all();
    }

    public function preload()
    {
        return [
            'fields' => $this->fieldConfig(),
            'meta' => $this->fields()->addValues($this->field->value())->meta(),
        ];
    }

    public function process($data)
    {
        if (! Arr::get($data, 'enabled')) {
            return false;
        }

        $values = Arr::removeNullValues(
            $this->fields()->addValues($data)->process()->values()->all()
        );

        return Arr::except($values, 'enabled');
    }

    protected function fields()
    {
        return Blink::once('seo-pro::blueprint-fields', function () {
            return new BlueprintFields($this->fieldConfig());
        });
    }

    protected function fieldConfig()
    {
        return Blink::once('seo-pro::blueprint-fields-config', function () {
            $parent = $this->field()->parent();
    
            if (! ($parent instanceof Entry || $parent instanceof Term)) {
                $parent = null;
            }
    
            return SeoProFields::new($parent ?? null)->getConfig();
        });
    }

    public function augment($data)
    {
        if (empty($data) || ! is_iterable($data)) {
            return $data;
        }

        return Blueprint::make()
            ->setContents(['fields' => $this->fieldConfig()])
            ->fields()
            ->addValues($data)
            ->augment()
            ->values()
            ->only(array_keys($data))
            ->all();
    }

    public function toGqlType()
    {
        return GraphQL::type('SeoPro');
    }
}
