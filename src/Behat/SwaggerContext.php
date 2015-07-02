<?php
/**
 * FeatureContext.php
 *
 * @package    AppBundle
 * @subpackage Tests
 */
namespace AppBundle\Tests\Features\Context;

use Behat\Behat\Context\SnippetAcceptingContext;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use Behat\Mink\Driver\BrowserKitDriver;
use Behat\MinkExtension\Context\MinkAwareContext;
use Behat\MinkExtension\Context\MinkContext;
use Behat\Symfony2Extension\Context\KernelDictionary;
use Nerdery\SwaggerBundle\Provider\SchemaProvider;
use JsonSchema\RefResolver;
use JsonSchema\Validator;
use NerderyBundle\Response\JsonResponse;
use PHPUnit_Framework_Assert;

/**
 * Defines contexts to test App responses
 *
 * @author Edward Pfremmer <epfremme@nerdery.com>
 */
class SwaggerContext extends MinkContext implements MinkAwareContext, SnippetAcceptingContext
{
    // symfony2 kernal access
    use KernelDictionary;

    // test environment
    const ENV = 'test';

    // request methods
    const GET     = 'GET';
    const PUT     = 'PUT';
    const POST    = 'POST';
    const PATCH   = 'PATCH';
    const HEAD    = 'HEAD';
    const OPTIONS = 'OPTIONS';

    /**
     * Request Payload
     * @var array
     */
    protected $payload;

    /**
     * Decoded Json Data
     * @var mixed
     */
    protected $data;

    /**
     * Json Schema
     * @var object
     */
    protected $schema;

    /**
     * @var SchemaProvider
     */
    protected $schemaProvider;

    /**
     * @var RefResolver
     */
    protected $resolver;

    /**
     * Initialize the scenario test context
     *
     * Every scenario gets its own context instance. You can also pass arbitrary
     * arguments to the context constructor through behat.yml.
     */
    public function __construct() {}

    /**
     * @BeforeScenario
     */
    public function setup()
    {
        $kernel = $this->getKernel();

        PHPUnit_Framework_Assert::assertEquals(self::ENV, $kernel->getEnvironment(), sprintf(
            'Attempted to run tests on "%s" environment, expected "%s" [ABORTING]',
            $kernel->getEnvironment(),
            self::ENV
        ));

        $this->schemaProvider = $this->getContainer()->get('swagger_bundle.provider.schema');
        $this->resolver       = new RefResolver($this->schemaProvider);
    }

    /**
     * Return the current session driver
     *
     * @return BrowserKitDriver|\Behat\Mink\Driver\DriverInterface
     */
    protected function getDriver()
    {
        return $this->getSession()->getDriver();
    }

    /**
     * Get page content
     *
     * @return string
     */
    protected function getPageContent()
    {
        return $this->getSession()->getPage()->getContent();
    }

    /**
     * Return json decoded page content
     *
     * @param int|bool $format
     * @return mixed|false|array
     */
    protected function getJsonContent($format = JSON_OBJECT_AS_ARRAY)
    {
        return json_decode($this->getPageContent(), $format) ?: [];
    }

    /**
     * Store payload body to be used for scenario requests
     *
     * Format:
     * | key | value |
     *
     * @Given I have the request payload:
     * @param TableNode $payload
     */
    public function iHaveThePayload(TableNode $payload)
    {
        $this->payload = $payload->getRowsHash();
    }

    /**
     * Make a new request and store the response & history to be accessed
     * during future test assertions in the current scenario
     *
     * @When I request :path
     * @When I request :path with method :method
     *
     * @param string $path
     * @param string $method
     */
    public function iRequestWithMethod($path, $method = self::GET)
    {
        $method = strtoupper($method);
        $data   = $this->payload ?: [];

        $this->getDriver()->getClient()->request($method, $path, $data);
    }

    /**
     * @When I use the :schema schema
     * @When I am using the :schema schema
     *
     * @param string $schema - Entity/Model or full schema '$ref' name
     */
    public function iUseTheSchema($schema)
    {
        $this->schema = $this->schemaProvider->retrieve($schema);
    }

    /**
     * @When I test swagger path :path
     * @When I test swagger path :path with operation :operation
     * @When I test swagger path :path with operation :operation and response :response
     *
     * @param string $path
     * @param string $operation
     * @param int $response
     */
    public function iTestTheSwaggerPath($path, $operation = 'get', $response = 200)
    {
        $swaggerProvider = $this->getContainer()->get('swagger_bundle.provider.swagger');
        $this->schema = $swaggerProvider->getResponse($path, $operation, $response);

        $this->iRequestWithMethod($path, $operation);
    }

    /**
     * @Then the response should be json
     */
    public function theResponseShouldBeJson()
    {
        PHPUnit_Framework_Assert::assertJson($this->getPageContent());
    }

    /**
     * @Then the response json should contain key :key
     *
     * @param string $key
     */
    public function theResponseJsonShouldContain($key)
    {
        PHPUnit_Framework_Assert::assertArrayHasKey($key, $this->getJsonContent());
    }

    /**
     * @Then the response json key :key should equal :value
     *
     * @param string $key
     * @param mixed $value
     */
    public function theResponseJsonKeyShouldEqual($key, $value)
    {
        $data = $this->getJsonContent();

        PHPUnit_Framework_Assert::assertEquals($value, $data[$key]);
    }

    /**
     * @Then The json response data should be valid
     * @Then The json response key :key should be valid
     *
     * @param null|string $key
     */
    public function theJsonResponseDataShouldBeValid($key = JsonResponse::KEY_DATA)
    {
        $data = $this->getJsonContent(false);

        if (is_string($key)) {
            $this->theResponseJsonShouldContain($key);

            $data = is_array($data) ? $data[$key] : $data->{$key};
        }

        $this->assertJsonIsValid($data);
    }

    /**
     * Validate the
     * @param \stdClass $data
     * @throws \Exception
     */
    public function assertJsonIsValid($data)
    {
        if (!$this->schema) {
            throw new \Exception("Missing json schema - did you forget to specify one in the scenario?");
        }

        $validator = new Validator();

        $this->resolver->resolve($this->schema);
        $validator->check($data, $this->schema);

        if (!$validator->isValid()) {
            $errors = $validator->getErrors();
            $errors = array_map(function($error) {
                return sprintf('%s: %s', $error['property'], $error['message']);
            }, $errors);

            $errors[] = sprintf('%1$sProvided Data: %1$s%2$s', PHP_EOL, json_encode($data, JSON_PRETTY_PRINT));

            throw new \Exception(implode(PHP_EOL, $errors));
        }
    }
}