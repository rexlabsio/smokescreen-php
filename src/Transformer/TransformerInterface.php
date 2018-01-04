<?php
namespace RexSoftware\Smokescreen\Transformer;

interface TransformerInterface
{
    // Would be great if we could actually define this method as part of our interface
    // but "thanks php", we can't indicate a more specific type in our implementation so we
    // can't type hint our model definition.
    // public function transform($data): array;

    public function getIncludeMap(): array;

    /**
     * Getter for available includes.
     *
     * @return array
     */
    public function getAvailableIncludes(): array;

    /**
     * Getter for default includes.
     *
     * @return array
     */
    public function getDefaultIncludes(): array;

    /**
     * Getter for default properties.
     *
     * @return array
     */
    public function getDefaultProps(): array;


    /**
     * Get the relationships for this transformer.
     * They can be used to eager-load in advance.
     *
     * @return array
     */
    public function getRelationships(): array;

//    /**
//     * Getter for current scope.
//     *
//     * @return Scope
//     */
//    public function getScope(): Scope;
}