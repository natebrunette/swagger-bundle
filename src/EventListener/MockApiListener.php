<?php
/**
 * File MockApiListener.php
 *
 * @author Edward Pfremmer <epfremme@nerdery.com>
 */
namespace AppBundle\EventListener;

use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Class MockApiListener
 *
 * Listen for mock API requests and return mock API response data
 * based on the swagger documentation
 *
 * @package SwaggerBundle\EventListener
 * @author  Edward Pfremmer <epfremme@nerdery.com>
 */
class MockApiListener
{
    // node binary
    const NODE_BIN = 'node';

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
    }

    /**
     * @param GetResponseEvent $event
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

        $bundleDir = $this->kernel->getBundle('SwaggerBundle')->getPath();

        chdir($bundleDir . $this->swaggerFile);

        $request  = $event->getRequest();
        $response = shell_exec(sprintf(
            '%s index.js --url %s --method %s',
            self::NODE_BIN,
            $request->getPathInfo(),
            $request->getMethod()
        ));

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
        if (shell_exec(sprintf('%s --version & echo $?', self::NODE_BIN)) != 0) {
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
        $bundleDir = $this->kernel->getBundle('SwaggerBundle')->getPath();

        if (!is_dir($bundleDir . '/mock-api/node_modules')) {
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
}