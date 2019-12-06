<?php

namespace Symsonte\JsApi\Play;

use Symsonte\Authorization\Resource\Loader;
use Symsonte\ServiceKit\Declaration;
use Symsonte\ServiceKit\Declaration\Bag\Updater;
use Symsonte\Service\ConstructorDeclaration;
use Symsonte\Service\Declaration\Call;
use Symsonte\Service\Declaration\ObjectArgument;

/**
 * @ds\service({tags: ['symsonte.service_kit.declaration.bag.updater']})
 */
class AuthorizationUpdater implements Updater
{
    /**
     * @var Loader
     */
    private $loader;

    /**
     * @param Loader $loader
     *
     * @ds\arguments({
     *     loader: "@symsonte.authorization.resource.loader"
     * })
     */
    public function __construct(
        Loader $loader
    )
    {
        $this->loader = $loader;
    }

    /**
     * @param Declaration $declaration
     *
     * @return Declaration
     */
    public function update(Declaration $declaration)
    {
        if (!$declaration->is('symsonte.authorization_checker')) {
            return $declaration;
        }

        /** @var ConstructorDeclaration $internalDeclaration */
        $internalDeclaration = $declaration->getDeclaration();

        $internalDeclaration->addCall(
            new Call(
                'merge',
                [
                    new ObjectArgument($this->loader->load([
                        'dir' => sprintf("%s/../src", __DIR__),
                        'filter' => '*.php',
                        'extra' => [
                            'type' => 'annotation',
                            'annotation' => '/^domain\\\\authorization/'
                        ]
                    ]))
                ]
            )
        );

        return new Declaration(
            $internalDeclaration,
            $declaration->isDeductible(),
            $declaration->isPrivate(),
            $declaration->isDisposable(),
            $declaration->isLazy(),
            $declaration->getTags(),
            $declaration->getAliases(),
            $declaration->getCircularCalls()
        );
    }
}