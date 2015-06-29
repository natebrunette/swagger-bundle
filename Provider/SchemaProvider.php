<?php
/**
 * Created by IntelliJ IDEA.
 * User: epfremme
 * Date: 6/29/15
 * Time: 2:36 AM
 */

namespace ERP\SwaggerBundle\Provider;

use ERP\Swagger\Listener\SerializationSubscriber;
use JMS\Serializer\EventDispatcher\EventDispatcher;
use JMS\Serializer\SerializerBuilder;
use JsonSchema\Uri\UriRetriever;

class SchemaProvider extends UriRetriever
{

    /**
     * @var \JMS\Serializer\Serializer
     */
    protected $serializer;

    /**
     * @var SwaggerProvider
     */
    protected $swaggerProvider;

    /**
     * Constructor
     *
     * @param SwaggerProvider $swaggerProvider
     */
    public function __construct(SwaggerProvider $swaggerProvider)
    {
        $this->swaggerProvider = $swaggerProvider;

        $this->serializer = $this->getSerializer();
    }

    /**
     * Return a raw json-schema object
     *
     * Used by the json-schema validation bundle to resolve schema references
     *
     * @param string $name
     * @param null $baseUri
     * @return object|\stdClass
     */
    public function retrieve($name, $baseUri = null)
    {
        $schema = $this->swaggerProvider->getDefinition($name);

        return json_decode($this->serializer->serialize($schema, 'json'));
    }

    /**
     * Return new JMS Serializer configured with
     * the swagger serialization listener
     *
     * @return \JMS\Serializer\Serializer
     */
    protected function getSerializer()
    {
        $serializerBuilder = new SerializerBuilder();

        $serializerBuilder->configureListeners(function(EventDispatcher $eventDispatcher) {
            $eventDispatcher->addSubscriber(new SerializationSubscriber());
        });

        return $serializerBuilder->build();
    }
}