<?php

namespace ERP\SwaggerBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateControllerCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('swagger:generate:controller')
            ->setDescription('Generate base REST controllers from swagger documentation');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $swaggerProvider = $this->getContainer()->get('swagger_bundle.provider.swagger');

        $swagger = $swaggerProvider->getSwagger();

        var_dump($swagger);exit;
    }
}
