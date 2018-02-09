<?php
/**
 *  Copyright 2017-2018 Justin Dane D. Vallar
 *
 *  Licensed under the Apache License, Version 2.0 (the "License");
 *  you may not use this file except in compliance with the License.
 *  You may obtain a copy of the License at
 *
 *      http://www.apache.org/licenses/LICENSE-2.0
 *
 *  Unless required by applicable law or agreed to in writing, software
 *  distributed under the License is distributed on an "AS IS" BASIS,
 *  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 *  See the License for the specific language governing permissions and
 *  limitations under the License.
 *
 */

namespace Vallarj\JsonApi;


use Vallarj\JsonApi\Exception\InvalidArgumentException;
use Vallarj\JsonApi\Schema\ResourceSchemaInterface;
use Vallarj\JsonApi\Schema\ToManyRelationshipInterface;
use Vallarj\JsonApi\Schema\ToOneRelationshipInterface;

class Encoder implements EncoderInterface
{
    /** @var SchemaManagerInterface Handles resource schema instances */
    private $schemaManager;

    /** @var array Keys of relationships to include in the current operation */
    private $includedKeys;

    /** @var array Holds the keys of the current relationship being extracted and its parents */
    private $includedWalker;

    /** @var array Holds the data for the current operation */
    private $data;

    /** @var array Holds the included resources of the current operation */
    private $included;

    /** @var bool Indicates if the last operation was successful */
    private $success;

    /**
     * Encoder constructor.
     * @param SchemaManagerInterface $schemaManager
     */
    function __construct(SchemaManagerInterface $schemaManager)
    {
        $this->schemaManager = $schemaManager;

        $this->initializeService();
    }
    /**
     * @inheritdoc
     */
    public function encode($resource, array $schemaClasses, array $includedKeys = []): string
    {
        $this->initializeService($includedKeys);


        if (is_object($resource)) {
            $this->encodeSingleResource($resource, $schemaClasses);
        } else if (is_array($resource)) {
            $this->encodeResourceCollection($resource, $schemaClasses);
        } else {
            throw new InvalidArgumentException('Resource must be an object or an array of objects.');
        }

        // Disassemble included
        $included = [];
        foreach($this->included as $byType) {
            foreach($byType as $byId) {
                $included[] = $byId;
            }
        }

        $root = [
            "data" => $this->data
        ];

        if(!empty($included)) {
            $root['included'] = $included;
        }

        // Encode the data
        return json_encode($root, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    private function initializeService(array $includedKeys = [])
    {
        $this->data = [];
        $this->included = [];
        $this->includedWalker = [];
        $this->includedKeys = $includedKeys;
        $this->success = false;
    }

    private function encodeSingleResource($resource, array $schemaClasses): void
    {
        $resourceClass = is_null($resource) ? null : get_class($resource);

        foreach($schemaClasses as $schemaClass) {
            $schema = $this->schemaManager->get($schemaClass);
            if($schema->getMappingClass() == $resourceClass) {
                // Extract resource data
                $this->data = $this->extractResource($resource, $schema);
                return;
            }
        }

        throw new InvalidArgumentException("No compatible schema found for the given resource object.");
    }

    private function encodeResourceCollection(array $resources, array $schemaClasses): void
    {
        foreach($resources as $resource) {
            $resourceClass = is_null($resource) ? null : get_class($resource);
            $compatibleSchema = null;

            foreach($schemaClasses as $schemaClass) {
                $schema = $this->schemaManager->get($schemaClass);
                if($schema->getMappingClass() == $resourceClass) {
                    $compatibleSchema = $schema;
                }
            }

            if(!$compatibleSchema) {
                throw new InvalidArgumentException("No compatible schema found for a given resource object.");
            }

            // Extract resource data
            $this->data[] = $this->extractResource($resource, $compatibleSchema);
        }
    }

    private function extractResource($object, ResourceSchemaInterface $schema): array
    {
        // Extract attributes
        $attributes = [];
        $schemaAttributes = $schema->getAttributes();
        foreach($schemaAttributes as $schemaAttribute) {
            if($schemaAttribute->isReadable()) {
                $key = $schemaAttribute->getKey();
                $attributes[$key] = $schemaAttribute->getValue($object);
            }
        }

        // Extract relationships
        $relationships = [];
        $schemaRelationships = $schema->getRelationships();
        foreach($schemaRelationships as $schemaRelationship) {
            if($schemaRelationship instanceof ToOneRelationshipInterface &&
                ($schemaRelationship->isReadable())) {
                $expectedSchemas = $schemaRelationship->getExpectedSchemas();
                $mappedObject = $schemaRelationship->getObject($object);
                $key = $schemaRelationship->getKey();

                $relationship = $this->extractRelationship($mappedObject, $key, $expectedSchemas);
                if($relationship) {
                    $relationships[$key]['data'] = $relationship;
                }
            } else if($schemaRelationship instanceof ToManyRelationshipInterface &&
                ($schemaRelationship->isReadable())) {
                $expectedSchemas = $schemaRelationship->getExpectedSchemas();
                $collection = $schemaRelationship->getCollection($object);
                $key = $schemaRelationship->getKey();

                foreach($collection as $mappedObject) {
                    $relationship = $this->extractRelationship($mappedObject, $key, $expectedSchemas);
                    if($relationship) {
                        $relationships[$key]['data'][] = $relationship;
                    }
                }
            }
        }

        // Extract meta
        $meta = [];
        $schemaMeta = $schema->getMeta();
        foreach($schemaMeta as $metaItem) {
            $key = $metaItem->getKey();
            $meta[$key] = $metaItem->getValue($object);
        }

        // Build the return data
        $data = [
            'type' => $schema->getResourceType(),
            'id' => (string)$schema->getIdentifier()->getResourceId($object),
        ];

        // Include attributes if not empty
        if(!empty($attributes)) {
            $data['attributes'] = $attributes;
        }

        // Include relationships if not empty
        if(!empty($relationships)) {
            $data['relationships'] = $relationships;
        }

        // Include meta if not empty
        if(!empty($meta)) {
            $data['meta'] = $meta;
        }

        return $data;
    }

    private function extractRelationship($mappedObject, string $key, array $expectedSchemas): ?array
    {
        $schema = null;
        foreach($expectedSchemas as $schemaClass) {
            $testSchema = $this->schemaManager->get($schemaClass);
            $mappedObjectClass = is_null($mappedObject) ? null : get_class($mappedObject);
            if($testSchema->getMappingClass() == $mappedObjectClass) {
                // Extract resource data
                $schema = $testSchema;
                break;
            }
        }

        if(!$schema) {
            return null;
        }

        // Get the resource type
        $resourceType = $schema->getResourceType();

        // Get the ID
        $resourceId = $schema->getIdentifier()->getResourceId($mappedObject);

        // Push key to the walker array
        array_push($this->includedWalker, $key);

        // If included, add included resource only once
        if(in_array(implode('.', $this->includedWalker), $this->includedKeys) &&
            !isset($this->included[$resourceType][$resourceId])) {
            // Indexing by type and ID ensures a unique resource is included only once
            $this->included[$resourceType][$resourceId] = $this->extractResource($mappedObject, $schema);
        }

        // Pop key from walker array
        array_pop($this->includedWalker);

        return [
            'type' => $resourceType,
            'id' => (string)$resourceId
        ];
    }
}