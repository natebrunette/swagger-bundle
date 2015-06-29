<?php
/**
 * File SwaggerProvider.php
 *
 * @author Edward Pfremmer <epfremme@nerdery.com>
 */
namespace ERP\SwaggerBundle\Provider;

use ERP\Swagger\Entity\Operation;
use ERP\Swagger\Entity\Path;
use ERP\Swagger\Entity\Swagger;
use ERP\Swagger\Factory\SwaggerFactory;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Class SwaggerProvider
 *
 * @package ERP\SwaggerBundle
 * @subpackage Provider
 */
class SwaggerProvider
{

    /**
     * @var SwaggerFactory
     */
    protected $swaggerFactory;

    /**
     * Swagger file path
     * @var string
     */
    protected $swaggerFile;

    /**
     * Parsed Swagger Doc
     * @var Swagger
     */
    protected $swagger;

    /**
     * Constructor
     *
     * @param KernelInterface $kernel
     * @param SwaggerFactory $swaggerFactory
     * @param string $swaggerFile
     */
    public function __construct(KernelInterface $kernel, SwaggerFactory $swaggerFactory, $swaggerFile) {
        $swaggerFile = realpath($kernel->getRootDir() . '/' . $swaggerFile);

        $this->swaggerFactory = $swaggerFactory;
        $this->swaggerFile    = $swaggerFile;
    }

    /**
     * Parse & return the swagger doc
     *
     * @return Swagger
     */
    public function getSwagger()
    {
        if (!$this->swagger) {
            $this->swagger = $this->swaggerFactory->build($this->swaggerFile);
        }

        return $this->swagger;
    }

    /**
     * Return the target swagger response schema
     *
     * @param string $path
     * @param string $operation
     * @param int $response
     * @return \ERP\Swagger\Entity\Schemas\SchemaInterface|null
     */
    public function getResponse($path, $operation = 'get', $response = 200)
    {
        $swagger = $this->getSwagger();
        /** @var Path $path */
        $path = $swagger->getPaths()->get($path);
        /** @var Operation $operation */
        $operation = $path->getOperations()->get($operation);

        return $operation->getResponses()->get($response);
    }

    /**
     * @param string $name
     * @return \ERP\Swagger\Entity\Schemas\SchemaInterface|null
     */
    public function getDefinition($name)
    {
        $swagger = $this->getSwagger();

        return $swagger->getDefinitions()->get($name);
    }
}