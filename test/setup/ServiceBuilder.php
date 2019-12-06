<?php

namespace Symsonte\JsApi\Test;

use Symsonte\Resource\DelegatorBuilder;
use Symsonte\ServiceKit\Declaration\Bag;
use Symsonte\ServiceKit\Declaration\Bag\Builder;
use Symsonte\ServiceKit\Resource\Loader;

/**
 * @ds\service({tags: [{key: 'last', name: 'symsonte.service_kit.declaration.bag.builder'}]})
 */
class ServiceBuilder implements Builder
{
    /**
     * @var Builder
     */
    private $builder;

    /**
     * @var Loader
     */
    private $loader;

    /**
     * @param Builder[] $builders
     * @param Loader    $loader
     *
     * @ds\arguments({
     *     builders: '#symsonte.resource.builder',
     *     loader:   "@symsonte.service_kit.resource.loader"
     * })
     */
    public function __construct(
        array $builders,
        Loader $loader
    ) {
        $this->builder = new DelegatorBuilder($builders);
        $this->loader = $loader;
    }

    /**
     * {@inheritdoc}
     */
    public function build(Bag $bag)
    {
        $bag = $this->loader->load(
            $this->builder->build([
                'dir' => sprintf('%s/../src', __DIR__),
                'filter' => '*.php',
                'extra' => [
                    'type' => 'annotation',
                    'annotation' => '/^di\\\\/'
                ]
            ]),
            $bag
        );

        $bag = $this->loader->load(
            $this->builder->build([
                'file' => sprintf('%s/services.yml', __DIR__),
            ]),
            $bag
        );

        return $bag;
    }
}