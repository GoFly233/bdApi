<?php

namespace Xfrocks\Api\Transform;

use XF\Mvc\Entity\ArrayCollection;
use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Finder;
use Xfrocks\Api\Controller\AbstractController;
use Xfrocks\Api\Transformer;

class LazyTransformer implements \JsonSerializable
{
    const SOURCE_TYPE_ENTITY = 'entity';
    const SOURCE_TYPE_FINDER = 'finder';

    /**
     * @var AbstractController
     */
    protected $controller;

    /**
     * @var array|null
     */
    protected $finderSortByList = null;

    /**
     * @var string|null
     */
    protected $key = null;

    /**
     * @var mixed|null
     */
    protected $source = null;

    /**
     * @var string
     */
    protected $sourceType = '';

    /**
     * @param AbstractController $controller
     */
    public function __construct($controller)
    {
        $this->controller = $controller;
    }

    /**
     * @return array|null
     */
    public function jsonSerialize()
    {
        return $this->transform();
    }

    /**
     * @param Entity $entity
     */
    public function setEntity(Entity $entity)
    {
        if ($this->source !== null) {
            throw new \LogicException('Source has already been set: ' . $this->sourceType);
        }

        $this->source = $entity;
        $this->sourceType = self::SOURCE_TYPE_ENTITY;
    }

    /**
     * @param Finder $finder
     */
    public function setFinder(Finder $finder)
    {
        if ($this->source !== null) {
            throw new \LogicException('Source has already been set: ' . $this->sourceType);
        }

        $this->source = $finder;
        $this->sourceType = self::SOURCE_TYPE_FINDER;
    }

    /**
     * @param string $key
     */
    public function setKey($key)
    {
        $this->key = $key;
    }

    /**
     * @param array $keys
     * @return LazyTransformer
     */
    public function sortByList(array $keys)
    {
        if ($this->sourceType !== self::SOURCE_TYPE_FINDER) {
            throw new \LogicException('Source is not a Finder: ' . $this->sourceType);
        }

        $this->finderSortByList = $keys;
        return $this;
    }

    /**
     * @return array|null
     */
    public function transform()
    {
        $controller = $this->controller;
        /** @var Transformer $transformer */
        $transformer = $controller->app()->container('api.transformer');
        $context = $controller->params()->getTransformContext();

        if ($this->key !== null) {
            if ($context->selectorShouldExcludeField($this->key)) {
                return null;
            }

            $context = $context->getSubContext($this->key, null, null);
        }

        switch ($this->sourceType) {
            case self::SOURCE_TYPE_ENTITY:
                /** @var Entity $entity */
                $entity = $this->source;
                return $transformer->transformEntity($context, null, $entity);
            case self::SOURCE_TYPE_FINDER:
                /** @var Finder $finder */
                $finder = $this->source;
                return $transformer->transformFinder($context, null, $finder, function ($result) {
                    /** @var ArrayCollection $entities */
                    $entities = $result;

                    if ($this->finderSortByList !== null) {
                        $entities = $entities->sortByList($this->finderSortByList);
                    }

                    return $entities;
                });
        }

        throw new \LogicException('Unrecognized source type ' . $this->sourceType);
    }
}
