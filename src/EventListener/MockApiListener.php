<?php
/**
 * File MockApiListener.php
 *
 * @author Edward Pfremmer <epfremme@nerdery.com>
 */
namespace Nerdery\SwaggerBundle\EventListener;

use Nerdery\SwaggerBundle\Command\InstallMockApiCommand;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Process\Process;

/**
 * Class MockApiListener
 *
 * Listen for mock API requests and return mock API response data
 * based on the swagger documentation
 *
 * @package    SwaggerBundle\Nerdery
 * @subpackage EventListener
 */
class MockApiListener
{
    // node binary
    const NODE_BIN = InstallMockApiCommand::NODE_BIN;

    /**
     * @var KernelInterface
     */
    private $kernel;

    /**
     * @var bool
     */
    private $active;

    /**
     * @var string
     */
    private $swaggerFile;

    /**
     * @param KernelInterface $kernel
     * @param bool $active
     */
    public function __construct(KernelInterface $kernel, $swaggerFile, $active = false)
    {
        $this->kernel = $kernel;
        $this->active = $active;

        $this->swaggerFile = $swaggerFile;
    }

    /**
     * Respond with mock API data on kernel requests containing
     * the "x-mock-api" request header
     *
     * note: This functionality will be disabled entirely in prod mode
     *
     * @param GetResponseEvent $event
     * @throws \Exception
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        if ($this->kernel->getEnvironment() === 'prod'
            || !$event->getRequest()->headers->has('x-mock-api')
            || !$this->isActive()
        ) {
            return; // do nothing
        }

        $this->assertNodeExists();
        $this->assertMockApiExists();

        $request = $event->getRequest();

        $process = new Process(sprintf(
            '%s index.js --url %s --method %s --file %s',
            self::NODE_BIN,
            $request->getPathInfo(),
            $request->getMethod(),
            realpath($this->kernel->getRootDir() . $this->swaggerFile)
        ));

        $process->setWorkingDirectory($this->getMockApiDir())
                ->run();

        if (!$process->isSuccessful()) {
            throw new \Exception("Unable to get mock API data from service");
        }

        $response = $process->getOutput();

        $response = explode(PHP_EOL, trim($response));
        $response = end($response);

        $response = new Response($response, 200, [
            'content-type' => 'application/json'
        ]);

        $event->setResponse($response);
        $event->stopPropagation();
    }

    /**
     * Asserts that the node binary is currently
     * installed on the system
     *
     * @throws \Exception
     */
    private function assertNodeExists()
    {
        $process = new Process(sprintf('%s --version > /dev/null 2>&1', self::NODE_BIN));

        $process->setWorkingDirectory($this->getMockApiDir())
                ->run();

        if (!$process->isSuccessful()) {
            throw new \Exception("Node binary not found! - did you forget to install node?");
        }
    }

    /**
     * Assert that the mock api packages have
     * been installed
     *
     * @throws \Exception
     */
    private function assertMockApiExists()
    {
        $bundle = $this->kernel->getBundle('SwaggerBundle');

        if (!is_dir($bundle->getPath() . '/../mock-api/node_modules')) {
            throw new \Exception("Mock API not installed! - run app/console swagger:install:mock-api");
        }
    }

    /**
     * Test if listener is active
     *
     * @return bool
     */
    private function isActive()
    {
        return (bool) $this->active;
    }

    /**
     * Return the mock API directory
     *
     * @return string
     */
    private function getMockApiDir()
    {
        $bundle = $this->kernel->getBundle('SwaggerBundle');

        return $bundle->getPath() . '/../mock-api';
    }
}