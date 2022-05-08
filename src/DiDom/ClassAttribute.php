<?php

declare(strict_types=1);

namespace DiDom;

use InvalidArgumentException;

class ClassAttribute
{
    /**
     * The DOM element instance.
     *
     * @var Element
     */
    protected $element;

    /**
     * @var string
     */
    protected $classesString = '';

    /**
     * @var string[]
     */
    protected $classes = [];

    /**
     * @param Element $element
     *
     * @throws InvalidArgumentException if parameter 1 is not an element node
     */
    public function __construct(Element $element)
    {
        if ( ! $element->isElementNode()) {
            throw new InvalidArgumentException(sprintf('The element must contain DOMElement node.'));
        }

        $this->element = $element;

        $this->parseClassAttribute();
    }

    /**
     * Parses class attribute of the element.
     */
    protected function parseClassAttribute()
    {
        if ( ! $this->element->hasAttribute('class')) {
            // possible if class attribute has been removed
            if ($this->classesString !== '') {
                $this->classesString = '';
                $this->classes = [];
            }

            return;
        }

        // if class attribute is not changed
        if ($this->element->getAttribute('class') === $this->classesString) {
            return;
        }

        // save class attribute as is (without trimming)
        $this->classesString = $this->element->getAttribute('class');

        $classesString = trim($this->classesString);

        if ($classesString === '') {
            $this->classes = [];

            return;
        }

        $classes = explode(' ', $classesString);

        $classes = array_map('trim', $classes);
        $classes = array_filter($classes);
        $classes = array_unique($classes);

        $this->classes = array_values($classes);
    }

    /**
     * Updates class attribute of the element.
     */
    protected function updateClassAttribute()
    {
        $this->classesString = implode(' ', $this->classes);

        $this->element->setAttribute('class', $this->classesString);
    }

    /**
     * @param string $className
     *
     * @return ClassAttribute
     *
     * @throws InvalidArgumentException if class name is not a string
     */
    public function add(string $className): self
    {
        $this->parseClassAttribute();

        if (in_array($className, $this->classes, true)) {
            return $this;
        }

        $this->classes[] = $className;

        $this->updateClassAttribute();

        return $this;
    }

    /**
     * @param array $classNames
     *
     * @return ClassAttribute
     *
     * @throws InvalidArgumentException if class name is not a string
     */
    public function addMultiple(array $classNames): self
    {
        $this->parseClassAttribute();

        foreach ($classNames as $className) {
            if ( ! is_string($className)) {
                throw new InvalidArgumentException(sprintf('Class name must be a string, %s given.', (is_object($className) ? get_class($className) : gettype($className))));
            }

            if (in_array($className, $this->classes, true)) {
                continue;
            }

            $this->classes[] = $className;
        }

        $this->updateClassAttribute();

        return $this;
    }

    /**
     * @return string[]
     */
    public function getAll(): array
    {
        $this->parseClassAttribute();

        return $this->classes;
    }

    /**
     * @param string $className
     *
     * @return bool
     */
    public function contains(string $className): bool
    {
        $this->parseClassAttribute();

        return in_array($className, $this->classes, true);
    }

    /**
     * @param string $className
     *
     * @return ClassAttribute
     *
     * @throws InvalidArgumentException if class name is not a string
     */
    public function remove(string $className): self
    {
        $this->parseClassAttribute();

        $classIndex = array_search($className, $this->classes);

        if ($classIndex === false) {
            return $this;
        }

        unset($this->classes[$classIndex]);

        $this->updateClassAttribute();

        return $this;
    }

    /**
     * @param array $classNames
     *
     * @return ClassAttribute
     *
     * @throws InvalidArgumentException if class name is not a string
     */
    public function removeMultiple(array $classNames): self
    {
        $this->parseClassAttribute();

        foreach ($classNames as $className) {
            if ( ! is_string($className)) {
                throw new InvalidArgumentException(sprintf('Class name must be a string, %s given.', (is_object($className) ? get_class($className) : gettype($className))));
            }

            $classIndex = array_search($className, $this->classes);

            if ($classIndex === false) {
                continue;
            }

            unset($this->classes[$classIndex]);
        }

        $this->updateClassAttribute();

        return $this;
    }

    /**
     * @param string[] $preserved
     *
     * @return ClassAttribute
     */
    public function removeAll(array $preserved = []): self
    {
        $this->parseClassAttribute();

        $preservedClasses = [];

        foreach ($preserved as $className) {
            if ( ! is_string($className)) {
                throw new InvalidArgumentException(sprintf('Class name must be a string, %s given.', (is_object($className) ? get_class($className) : gettype($className))));
            }

            if ( ! in_array($className, $this->classes, true)) {
                continue;
            }

            $preservedClasses[] = $className;
        }

        $this->classes = $preservedClasses;

        $this->updateClassAttribute();

        return $this;
    }

    /**
     * @return Element
     */
    public function getElement(): Element
    {
        return $this->element;
    }
}
