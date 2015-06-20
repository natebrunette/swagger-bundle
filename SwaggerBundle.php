<?php
/**
 * File SwaggerBundle.php
 *
 * @author Edward Pfremmer <epfremme@nerdery.com>
 */
namespace ERP\SwaggerBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Class SwaggerBundle
 *
 * @package ERP\SwaggerBundle
 */
class SwaggerBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);
    }
}
