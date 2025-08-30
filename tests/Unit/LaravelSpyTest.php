<?php

namespace Tests\Unit;

use Farayaz\LaravelSpy\LaravelSpy;
use GuzzleHttp\Psr7\Uri;
use Tests\TestCase;

class LaravelSpyTest extends TestCase
{
    /** @test */
    public function it_can_parse_json_content()
    {
        $jsonContent = '{"name": "Reza", "age": 30}';
        $contentType = 'application/json';

        $result = LaravelSpy::parseContent('request', $jsonContent, $contentType);

        $this->assertEquals(['name' => 'Reza', 'age' => 30], $result);
    }

    /** @test */
    public function it_can_parse_xml_content()
    {
        $xmlContent = '<?xml version="1.0"?><user><name>Mehrdad</name><age>30</age></user>';
        $contentType = 'application/xml';

        $result = LaravelSpy::parseContent('request', $xmlContent, $contentType);

        $this->assertEquals(['name' => 'Mehrdad', 'age' => '30'], $result);
    }

    /** @test */
    public function it_can_parse_form_urlencoded_content()
    {
        $formContent = 'name=John&age=30&email=john@example.com';
        $contentType = 'application/x-www-form-urlencoded';

        $result = LaravelSpy::parseContent('request', $formContent, $contentType);

        $this->assertEquals([
            'name' => 'John',
            'age' => '30',
            'email' => 'john@example.com',
        ], $result);
    }

    /** @test */
    public function it_returns_base64_for_multipart_form_data()
    {
        $content = 'binary-form-data-content';
        $contentType = 'multipart/form-data';

        $result = LaravelSpy::parseContent('request', $content, $contentType);

        $this->assertEquals(base64_encode($content), $result);
    }

    /** @test */
    public function it_returns_base64_for_binary_content()
    {
        $content = 'binary-image-data';
        $contentType = 'image/jpeg';

        $result = LaravelSpy::parseContent('request', $content, $contentType);

        $this->assertEquals(base64_encode($content), $result);
    }

    /** @test */
    public function it_returns_plain_text_for_unknown_content_type()
    {
        $content = 'plain text content';
        $contentType = 'text/plain';

        $result = LaravelSpy::parseContent('request', $content, $contentType);

        $this->assertEquals($content, $result);
    }

    /** @test */
    public function it_returns_null_for_empty_content()
    {
        $result = LaravelSpy::parseContent('request', '', 'application/json');

        $this->assertNull($result);
    }

    /** @test */
    public function it_can_obfuscate_array_data()
    {
        $data = [
            'username' => 'john_doe',
            'password' => 'secret123',
            'email' => 'john@example.com',
            'token' => 'abc123xyz',
        ];

        $result = LaravelSpy::obfuscate($data);

        $this->assertEquals([
            'username' => 'john_doe',
            'password' => '🫣',
            'email' => 'john@example.com',
            'token' => '🫣',
        ], $result);
    }

    /** @test */
    public function it_can_obfuscate_nested_array_data()
    {
        $data = [
            'user' => [
                'name' => 'John',
                'password' => 'secret123',
                'profile' => [
                    'token' => 'abc123',
                ],
            ],
        ];

        $result = LaravelSpy::obfuscate($data);

        $this->assertEquals([
            'user' => [
                'name' => 'John',
                'password' => '🫣',
                'profile' => [
                    'token' => '🫣',
                ],
            ],
        ], $result);
    }

    /** @test */
    public function it_can_obfuscate_string_data()
    {
        $data = 'This contains a password and a token';

        $result = LaravelSpy::obfuscate($data);

        $this->assertEquals('This contains a 🫣 and a 🫣', $result);
    }

    /** @test */
    public function it_can_obfuscate_uri_query_parameters()
    {
        $uri = new Uri('https://api.example.com/users?name=john&password=secret&token=abc123');

        $result = LaravelSpy::obfuscate($uri);

        $this->assertInstanceOf(Uri::class, $result);
        $this->assertStringContainsString('password=%F0%9F%AB%A3', $result->getQuery());
        $this->assertStringContainsString('token=%F0%9F%AB%A3', $result->getQuery());
        $this->assertStringContainsString('name=john', $result->getQuery());
    }

    /** @test */
    public function it_can_use_custom_mask_for_obfuscation()
    {
        $data = ['password' => 'secret123'];
        config()->set('spy.obfuscation_mask', '***');

        $result = LaravelSpy::obfuscate($data);

        $this->assertEquals(['password' => '***'], $result);
    }

    /** @test */
    public function it_returns_original_data_when_no_obfuscation_keys_match()
    {
        $data = ['username' => 'john', 'email' => 'john@example.com'];

        $result = LaravelSpy::obfuscate($data);

        $this->assertEquals($data, $result);
    }
}
