<?php
/**
 *  Copyright 2017 Justin Dane D. Vallar
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

namespace Vallarj\JsonApi\Factory\Service;


use Interop\Container\ContainerInterface;
use Vallarj\JsonApi\Service\DecoderService;
use Vallarj\JsonApi\Service\Options\DecoderServiceOptions;
use Vallarj\JsonApi\Service\Options\SchemaOptions;
use Zend\ServiceManager\Factory\FactoryInterface;

class DecoderServiceFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        // JSON API config variable
        $jsonapiConfig = $container->get('config')['jsonapi'] ?? [];

        // Decoder configuration
        $decoderOptions = new DecoderServiceOptions($jsonapiConfig['decoder'] ?? []);

        return new DecoderService($decoderOptions);
    }
}