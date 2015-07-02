<?php

namespace Nerdery\SwaggerBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InstallMockApiCommand extends ContainerAwareCommand
{

    /**
     * Cli Input
     * @var InputInterface
     */
    protected $input;

    /**
     * Cli Output
     * @var OutputInterface
     */
    protected $output;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('swagger:install:mock-api')
            ->setDescription('Installs a custom version of the swagger-mock-api used to mock API responses')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input  = $input;
        $this->output = $output;

        $output->writeln('<info>Changing to Mock API directory</info>');

        chdir(__DIR__ . '/../../mock-api');

        $output->writeln('<info>Running NPM install...</info>');

        $result = shell_exec('npm install');

        if (exec('echo $!') != 0) {
            $output->writeln('<error>Installation failed!!!</error>');
            $output->write($result);
            die(1);
        }

        $output->writeln('<info>Installation Complete!</info>');
    }
}
