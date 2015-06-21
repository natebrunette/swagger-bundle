<?php
/**
 * File JsonResponse.php
 *
 * @author Douglas Linsmeyer <douglas.linsmeyer@nerdery.com>
 * @author Edward Pfremmer <epfremme@nerdery.com>
 */
namespace RantSports\Bundle\StatsBundle\Response;

use Symfony\Component\HttpFoundation\JsonResponse as BaseJsonResponse;

/**
 * Class JsonResponse
 *
 * @package ERP\SwaggerBundle
 * @subpackage Response
 */
class JsonResponse extends BaseJsonResponse
{
    const KEY_DATA = 'data';
    const KEY_STATUS = 'status';
    const KEY_SUCCESS = 'success';
    const KEY_ERROR_CODE = 'errorCode';
    const KEY_ERROR_MESSAGE = 'errorMessage';

    /**
     * @var array
     */
    protected $defaultData = [
        self::KEY_STATUS => [
            self::KEY_SUCCESS => true,
            self::KEY_ERROR_CODE => null,
            self::KEY_ERROR_MESSAGE => null,
        ],
    ];

    /**
     * @var array
     */
    private $pristineData;

    /**
     * Constructor
     *
     * @param array $data
     * @param int $code
     * @param array $headers
     *
     * @return self
     */
    public function __construct(array $data = [], $code = 200, array $headers = [])
    {
        $data = [self::KEY_DATA => $data];

        parent::__construct($data, $code, $headers);

        $this->pristineData = $data;
        $this->resolveData();

        return $this;
    }

    /**
     * Set success for the response
     *
     * @return self
     */
    public function setSuccess()
    {
        $this->pristineData[self::KEY_STATUS][self::KEY_SUCCESS] = true;
        $this->resolveData();

        return $this;
    }

    /**
     * Set success for the response
     *
     * @return self
     */
    public function setFailure()
    {
        $this->pristineData[self::KEY_STATUS][self::KEY_SUCCESS] = false;
        $this->resolveData();

        return $this;
    }

    /**
     * Set the error code for the response
     *
     * @param int $code
     *
     * @return $this
     */
    public function setErrorCode($code)
    {
        $this->pristineData[self::KEY_STATUS][self::KEY_ERROR_CODE] = $code;
        $this->resolveData();

        return $this;
    }

    /**
     * Set the error message for the response
     *
     * @param string $message
     *
     * @return $this
     */
    public function setErrorMessage($message)
    {
        $this->pristineData[self::KEY_STATUS][self::KEY_ERROR_MESSAGE] = $message;
        $this->resolveData();

        return $this;
    }

    /**
     * Resolve data.
     *
     * @return void
     */
    private function resolveData()
    {
        $data = array_merge($this->defaultData, $this->pristineData);
        $this->setData($data);
    }
}