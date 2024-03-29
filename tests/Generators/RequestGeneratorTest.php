<?php

namespace LaravelOpenapi\Codegen\Tests\Generators;

use cebe\openapi\Reader;
use Illuminate\Support\Facades\Config;
use LaravelOpenapi\Codegen\Factories\DefaultGeneratorFactory;
use LaravelOpenapi\Codegen\Generators\RequestGenerator;
use LaravelOpenapi\Codegen\Tests\TestCase;
use LaravelOpenapi\Codegen\Utils\RouteControllerResolver;
use LaravelOpenapi\Codegen\Utils\Stub;

class RequestGeneratorTest extends TestCase
{
    protected RequestGenerator $requestGenerator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->requestGenerator = DefaultGeneratorFactory::createGenerator('request');
    }

    public function test_can_create_request_file_if_not_exists()
    {
        $method = $this->getMethod(RequestGenerator::class, 'createRequestFileIfNotExists');

        $this->requestGenerator->setExtractedRouteController(RouteControllerResolver::extract('App\Http\Controllers\TestController@create'));

        $filePath = $method->invokeArgs($this->requestGenerator, []);

        $this->assertFileExists($filePath);
    }

    public function test_make_correct_request_namespace()
    {
        $method = $this->getMethod(RequestGenerator::class, 'makeNamespace');
        $this->requestGenerator->setExtractedRouteController(RouteControllerResolver::extract('App\Http\Controllers\TestController@create'));

        $namespace = $method->invokeArgs($this->requestGenerator, []);

        $this->assertSame('App\Http\Requests\CreateTestRequest', $namespace);
    }

    public function test_can_generate_requests_from_path_item()
    {
        $methodForTest = $this->getMethod(RequestGenerator::class, 'generateRequests');
        $spec = Reader::readFromYamlFile(Config::get('openapi-codegen.api_docs_url'));

        $pathItem = $spec->paths->getPath('/users');

        $methodForTest->invokeArgs($this->requestGenerator, [$pathItem]);

        $this->assertTrue(true);
    }

    public function test_replace_namespace_correct()
    {
        $methodForCall = $this->getMethod(RequestGenerator::class, 'replaceNamespace');
        $this->requestGenerator->setExtractedRouteController(RouteControllerResolver::extract('App\Http\Controllers\TestController@create'));
        $requestStub = $methodForCall->invokeArgs($this->requestGenerator, [Stub::getStubContent('request.stub')]);

        $this->assertStringContainsString('namespace App\Http\Requests;', $requestStub);
        $this->assertStringContainsString('class CreateTestRequest', $requestStub);
    }

    //    public function test_can_generate_request_file_from_operation()
    //    {
    //        $method = $this->getMethod(RequestGenerator::class, '');
    //    }
}
