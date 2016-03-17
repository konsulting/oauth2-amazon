<?php
namespace Konsulting\OAuth2\Client\Provider;

use League\OAuth2\Client\Provider\ResourceOwnerInterface;

class AmazonUser implements ResourceOwnerInterface
{
    /**
     * @var array
     */
    protected $data;

    /**
     * @param  array $response
     */
    public function __construct(array $response)
    {
        $this->data = $response;
    }

    /**
     * Returns the ID for the user as a string if present.
     *
     * @return string|null
     */
    public function getId()
    {
        return $this->getField('user_id');
    }

    /**
     * Returns the name for the user as a string if present.
     *
     * @return string|null
     */
    public function getName()
    {
        return $this->getField('name');
    }

    /**
     * Returns the email for the user as a string if present.
     *
     * @return string|null
     */
    public function getEmail()
    {
        return $this->getField('email');
    }

    /**
     * Returns the current postcode of the user if present.
     *
     * @return array|null
     */
    public function getPostcode()
    {
        return $this->getField('postcode');
    }

    /**
     * Returns all the data obtained about the user.
     *
     * @return array
     */
    public function toArray()
    {
        return $this->data;
    }

    /**
     * Returns a field from the Graph node data.
     *
     * @param string $key
     *
     * @return mixed|null
     */
    private function getField($key)
    {
        return isset($this->data[$key]) ? $this->data[$key] : null;
    }
}
