<?php
namespace Clivern\Monkey\API\Request;

use Clivern\Monkey\API\Contract\RequestInterface;

/**
 * @since 1.0.0
 * @package Clivern\Monkey\API\Request
 */
class PlainRequest implements RequestInterface {

    protected $method;
    protected $parameters = [];
    protected $items = [];
    protected $headers = [];
    protected $request;
    protected $type;

    /**
     * Class Constructor
     *
     * @param string $method     The Request Method
     * @param string $type       The Request Type
     * @param array  $parameters The Request URL Parameters
     * @param array  $items      The Request Body Items
     */
    public function __construct($method = null, $type = null, $parameters = [], $items = [])
    {
        $this->method = $method;
        $this->type = $type;
        $this->parameters = $parameters;
        $this->items = $items;
    }

    /**
     * Set Method
     *
     * @param string $method The Request Method
     * @return PlainRequest An instance of this class
     */
    public function setMethod($method)
    {
        $this->method = $method;

        return $this;
    }

    /**
     * Set Type
     *
     * @param string $type The Request Type
     * @return PlainRequest An instance of this class
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Add URL Parameter
     *
     * @param string $key   The Parameter Key
     * @param string $value The Parameter Value
     * @return PlainRequest An instance of this class
     */
    public function addParameter($key, $value)
    {
        $this->parameters[$key] = $value;

        return $this;
    }

    /**
     * Add Request Body Item
     *
     * @param string $key  The Body Item Key
     * @param mixed $value The Body Item Value
     * @return PlainRequest An instance of this class
     */
    public function addItem($key, $value)
    {
        $this->items[$key] = $value;

        return $this;
    }

    /**
     * Add Header Item
     *
     * @param string $key   The Header Item Key
     * @param string $value The Header Item Value
     * @return PlainRequest An instance of this class
     */
    public function addHeader($key, $value)
    {
        $this->headers[$key] = $value;

        return $this;
    }

    /**
     * Get Request Method
     *
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * Get Request Type
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Get Request URL Parameter
     *
     * @param string $key   The Parameter Key
     * @return array
     */
    public function getParameter($key)
    {
        return ($this->parameterExists($key)) ? $this->parameters[$key] : null;
    }

    /**
     * Get Request Body Item
     *
     * @param string $key  The Body Item Key
     * @return mixed
     */
    public function getItem($key)
    {
        return ($this->itemExists($key)) ? $this->items[$key] : null;
    }

    /**
     * Get Request Body Items
     *
     * @param  string $type The Items Format (Json or Array)
     * @return mixed
     */
    public function getItems($type = "json")
    {
        if( $type == "json" ){
            return json_encode($this->items);
        }else{
            return $this->items;
        }
    }

    /**
     * Get Request Body
     *
     * @param  string $type The Body Format (Json or Array)
     * @return mixed
     */
    public function getBody($type = "json")
    {
        if( $type == "json" ){
            return json_encode($this->items);
        }else{
            return $this->items;
        }
    }

    /**
     * Get Request Header Item
     *
     * @param string $key   The Header Item Key
     * @return array
     */
    public function getHeader($key)
    {
        return ($this->headerExists($key)) ? $this->headers[$key] : null;
    }

    /**
     * Get Headers
     *
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * Check if Header Item Exists
     *
     * @param  string $key The Header Item Key
     * @return boolean
     */
    public function headerExists($key)
    {
        return (isset($this->headers[$key]));
    }

    /**
     * Check if URL Parameter Exists
     *
     * @param  string $key The URL Parameter Key
     * @return boolean
     */
    public function parameterExists($key)
    {
        return (isset($this->parameters[$key]));
    }

    /**
     * Check if Body Item Exists
     *
     * @param  string $key The Body Item Key
     * @return boolean
     */
    public function itemExists($key)
    {
        return (isset($this->items[$key]));
    }

    /**
     * Debug The Request Object
     *
     * @return string
     */
    public function debug()
    {
        $body = json_encode($this->items);
        $url = "https://example.com?" . http_build_query($this->parameters);
        $headers = "";
        foreach ($this->headers as $key => $value) {
            $headers .= " -H \"{$key}: {$value}\"";
        }

        return "curl -X {$this->method} " . trim($headers) . " -d '{$body}'" . " \"{$url}\"";
    }
}