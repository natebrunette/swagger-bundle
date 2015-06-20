<?php
/**
 * File SwaggerProvider.php
 *
 * @author Edward Pfremmer <epfremme@nerdery.com>
 */
namespace ERP\SwaggerBundle\Provider;

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
     * @var string
     */
    protected $swaggerFile;

    /**
     * Constructor
     *
     * @param KernelInterface $kernel
     * @param SwaggerFactory $swaggerFactory
     * @param $swaggerFile
     */
    public function __construct(KernelInterface $kernel, SwaggerFactory $swaggerFactory, $swaggerFile) {
        $swaggerFile = realpath($kernel->getRootDir() . $swaggerFile);

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
        return $this->swaggerFactory->build($this->swaggerFile);
    }
}