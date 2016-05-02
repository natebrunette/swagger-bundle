<?php
/**
 * SwaggerContext.php
 *
 * @author Edward Pfremmer <epfremme@nerdery.com>
 */
namespace Nerdery\SwaggerBundle\Behat;

use Behat\Behat\Context\SnippetAcceptingContext;
use Behat\Gherkin\Node\TableNode;
use Behat\Mink\Driver\BrowserKitDriver;
use Behat\MinkExtension\Context\MinkAwareContext;
use Behat\MinkExtension\Context\MinkContext;
use Behat\Symfony2Extension\Context\KernelDictionary;
use JsonSchema\Uri\UriResolver;
use JsonSchema\Uri\UriRetriever;
use JsonSchema\RefResolver;
use JsonSchema\Validator;
use LogicException;
use Nerdery\SwaggerBundle\Response\JsonResponse;
use PHPUnit_Framework_Assert;
use stdClass;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Tebru\Realtype\Realtype;

/**
 * Class SwaggerContext
 *
 * Defines contexts to test API responses
 *
 * @package    Nerdery\SwaggerBundle
 * @subpackage Behat
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
     * Request server params
     *
     * @var array
     */
    protected $server;

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

        $this->resolver = new RefResolver(new UriRetriever(), new UriResolver());
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
        $rows = $payload->getRowsHash();
        foreach ($rows as $key => $value) {
            $rows[$key] = Realtype::get($value);
        }

        $this->payload = $rows;
    }

    /**
     * Store headers to be used for scenario requests
     *
     * Format:
     * | key | value |
     *
     * @Given I have the request headers:
     * @param TableNode $payload
     */
    public function iHaveTheHeaders(TableNode $payload)
    {
        $headers = [];
        $rows = $payload->getRowsHash();
        foreach ($rows as $key => $value) {
            $header = 'HTTP_' . strtoupper($key);
            $headers[$header] = Realtype::get($value);
        }

        $this->server = $headers;
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
        $data = $this->payload ?: [];
        $server = $this->server ?: [];

        $this->getDriver()->getClient()->request($method, $path, $data, [], $server);
    }

    /**
     * @When I use the :schema schema
     * @When I am using the :schema schema
     *
     * @param string $schema - Entity/Model or full schema '$ref' name
     * @throws InvalidArgumentException
     * @throws LogicException
     */
    public function iUseTheSchema($schema)
    {
        $fullSchema = $this->getFullSchema();
        $definitions = $this->getPropertyFromSchema($fullSchema, 'definitions');

        if (!property_exists($definitions, $schema)) {
            throw new LogicException('Schema "%s" not found in definitions', $schema);
        }

        $this->schema = $definitions->$schema;
    }

    /**
     * @When I test swagger path :path
     * @When I test swagger path :path with operation :operation
     * @When I test swagger path :path with operation :operation and response :response
     *
     * @param string $path
     * @param string $operation
     * @param int|string $response
     * @throws LogicException
     * @throws InvalidArgumentException
     */
    public function iTestTheSwaggerPath($path, $operation = 'get', $response = 200)
    {
        $fullSchema = $this->getFullSchema();
        $paths = $this->getPropertyFromSchema($fullSchema, 'paths');
        $pathObject = $this->getPropertyFromSchema($paths, $path);
        $operationObject = $this->getPropertyFromSchema($pathObject, $operation);
        $responsesObjects = $this->getPropertyFromSchema($operationObject, 'responses');

        if (!property_exists($responsesObjects, $response)) {
            $response = 'default';
        }

        $responseObject = $this->getPropertyFromSchema($responsesObjects, $response);

        if (!property_exists($responseObject, 'schema')) {
            $this->schema = new stdClass();

            return;
        }

        $this->schema = $responseObject->schema;

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
     * @Then the json response data should be valid
     * @Then the json response key :key should be valid
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
     * Test the response against the schema
     *
     * @Then the json response should be valid
     *
     * @throws \Exception
     */
    public function theJsonResponseShouldBeValid()
    {
        $data = $this->getJsonContent(false);
        $this->assertJsonIsValid($data);
    }

    /**
     * Validate the json data again target schema
     *
     * @param stdClass $data
     * @throws \Exception
     */
    public function assertJsonIsValid($data)
    {
        if (!$this->schema) {
            throw new \Exception("Missing json schema - did you forget to specify one in the scenario?");
        }

        $validator = new Validator();

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

    /**
     * Load full schema from configured file
     *
     * @return stdClass
     * @throws LogicException
     * @throws InvalidArgumentException
     */
    private function getFullSchema()
    {
        $file = sprintf(
            '%s/%s',
            $this->getKernel()->getRootDir(),
            $this->getContainer()->getParameter('swagger_bundle.swagger_file')
        );

        return $this->resolver->resolve(sprintf('file://%s', $file));
    }

    /**
     * Get schema paths
     *
     * @param stdClass $fullSchema
     * @param string $property
     * @return stdClass
     * @throws LogicException
     */
    private function getPropertyFromSchema(stdClass $fullSchema, $property)
    {
        if (!property_exists($fullSchema, $property)) {
            throw new LogicException(sprintf('Schema is missing "%s"', $property));
        }

        return $fullSchema->$property;
    }
}
