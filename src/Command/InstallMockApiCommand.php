<?php
/**
 * InstallMockApiCommand.php
 *
 * @author Edward Pfremmer <epfremme@nerdery.com>
 */
namespace Nerdery\SwaggerBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class InstallMockApiCommand
 *
 * @package    SwaggerBundle\Nerdery
 * @subpackage Command
 */
class InstallMockApiCommand extends ContainerAwareCommand
{
    // node binaries
    const NODE_BIN  = 'tools/node/bin/node';
    const NPM_BIN   = 'tools/node/bin/npm';
    const GRUNT_BIN = 'tools/node/bin/grunt';
    const BOWER_BIN = 'tools/node/bin/bower';

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

        $bundleDir = $this->getContainer()->get('kernel')->getBundle('SwaggerBundle')->getPath();

        chdir($bundleDir . '/../mock-api');

        if (shell_exec(sprintf('%s --version > /dev/null 2>&1; echo $?', self::NODE_BIN)) != 0) {
            $this->installNode();
        }

        $output->writeln('<info>Running NPM install...</info>');

        if (shell_exec(sprintf('%s update; echo $?', self::NPM_BIN)) != 0) {
            $output->writeln('<error>Installation failed!!!</error>');
            die(1);
        }

        $output->writeln('<info>Installation Complete!</info>');
    }

    /**
     * Install a standalone version of the node executable
     * used to install mock API packages
     *
     * @return void
     */
    private function installNode()
    {
        $installScript = stristr(PHP_OS, 'WIN')
            ? 'node-standalone-install.cmd'
            : 'node-standalone-install.sh'
        ;

        $this->output->writeln('<info>Installing Node Standalone...</info>');

        shell_exec(sprintf('cd tools && ./%s && chmod 755 node/bin/*', $installScript));

        $this->output->writeln('<info>Node Standalone Installed!</info>');
    }
}
