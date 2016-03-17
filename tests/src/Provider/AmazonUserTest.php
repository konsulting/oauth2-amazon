<?php

namespace Konsulting\OAuth2\Client\Provider;

class AmazonUserTest extends \PHPUnit_Framework_TestCase
{
    protected $user;

    public function setUp()
    {
        parent::setUp();

        $this->user = new AmazonUser([
            'user_id' => '4',
            'name' => 'Keoghan Litchfield',
            'email' => 'keoghan@klever.co.uk',
        ]);
    }

    /**
     * @test
     */
    public function GettersReturnNullWhenNoKeyExists()
    {
        $this->assertEquals('4', $this->user->getId());
        $this->assertNull($this->user->getPostcode());
    }

    /**
     * @test
     */
    public function CanGetAllDataBackAsAnArray()
    {
        $data = $this->user->toArray();
        $expectedData = [
            'user_id' => '4',
            'name' => 'Keoghan Litchfield',
            'email' => 'keoghan@klever.co.uk',
        ];
        $this->assertEquals($expectedData, $data);
    }
}
