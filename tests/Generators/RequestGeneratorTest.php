<?php

namespace LaravelOpenapi\Codegen\Tests\Generators;

use cebe\openapi\Reader;
use cebe\openapi\spec\PathItem;
use cebe\openapi\SpecObjectInterface;
use Illuminate\Support\Facades\Config;
use LaravelOpenapi\Codegen\DTO\OpenapiProperty;
use LaravelOpenapi\Codegen\Factories\DefaultGeneratorFactory;
use LaravelOpenapi\Codegen\Generators\RequestGenerator;
use LaravelOpenapi\Codegen\Tests\TestCase;
use LaravelOpenapi\Codegen\Utils\RouteControllerResolver;
use LaravelOpenapi\Codegen\Utils\Stub;

class RequestGeneratorTest extends TestCase
{
    protected RequestGenerator $requestGenerator;

    protected SpecObjectInterface $spec;

    protected function setUp(): void
    {
        parent::setUp();

        $this->requestGenerator = DefaultGeneratorFactory::createGenerator('request');

        $this->spec = Reader::readFromYamlFile(Config::get('openapi-codegen.api_docs_url'));
    }

    public function test_can_generate_request_from_path()
    {
        $pathItem = $this->spec->paths->getPath('/request-generation');
        $this->requestGenerator->generateRequests(new PathItem(['post' => $pathItem->post]));

        $file = base_path('app/Http/Requests/CreateSomeRequest.php');
        $requestFileContent = file_get_contents($file);

        $this->assertFileExists($file);
        $this->assertStringContainsString("'name' =>", $requestFileContent);
        $this->assertStringContainsString("'email' =>", $requestFileContent);

        unlink($file);
    }

    public function test_skip_request_generation_if_it_is_true()
    {
        $pathItem = $this->spec->paths->getPath('/request-skip-generation');
        $this->requestGenerator->generateRequests($pathItem);

        $file = base_path('app/Http/Requests/SkipSomeRequest.php');
        $this->assertFileDoesNotExist($file);
    }

    public function test_can_make_correct_namespace()
    {
        $this->requestGenerator->setExtractedRouteController(RouteControllerResolver::extract('App\Http\Controllers\SomeController@create'));
        $namespace = $this->requestGenerator->makeNamespace();

        $this->assertSame('App\Http\Requests\CreateSomeRequest', $namespace);
    }

    public function test_generate_request_for_get_method_with_request_skip_false()
    {
        $pathItem = $this->spec->paths->getPath('/request-generation-for-get');
        $this->requestGenerator->generateRequests($pathItem);

        $file = base_path('app/Http/Requests/GetNotSkipSomeRequest.php');
        $requestFileContent = file_get_contents($file);

        $this->assertFileExists($file);
        $this->assertStringContainsString('GetNotSkipSomeRequest', $requestFileContent);

        unlink($file);
    }

    public function test_can_replace_namespace_correctly()
    {
        $this->requestGenerator->setExtractedRouteController(RouteControllerResolver::extract('App\Http\Controllers\SomeController@create'));
        $requestStub = $this->requestGenerator->replaceNamespace(Stub::getStubContent('request.stub'));

        $this->assertStringContainsString('namespace App\Http\Requests;', $requestStub);
        $this->assertStringContainsString('class CreateSomeRequest', $requestStub);
    }

    public function test_can_replace_rules()
    {
        $pathItem = $this->spec->paths->getPath('/request-generation');
        $operation = $pathItem->post;

        $requestStub = Stub::getStubContent('request.stub');
        $stub = $this->requestGenerator->replaceRules($operation, $requestStub);

        $this->assertMatchesRegularExpression("/'name' => \[|'email' => \[|'id' =>/", $stub);
        $this->assertStringNotContainsString('{{ rules }}', $stub);
    }

    public function test_if_request_body_is_empty_replaces_rules_with_empty_space()
    {
        $pathItem = $this->spec->paths->getPath('/request-generation');
        $operation = $pathItem->post;
        unset($operation->requestBody);

        $requestStub = Stub::getStubContent('request.stub');
        $stub = $this->requestGenerator->replaceRules($operation, $requestStub);

        $this->assertStringNotContainsString('{{ rules }}', $stub);
    }

    public function test_get_all_rules_from_request_body()
    {
        $pathItem = $this->spec->paths->getPath('/request-generation');
        $operation = $pathItem->post;

        $rules = $this->requestGenerator->getAllRules($operation);
        $this->assertStringContainsString(
            "'name' => ['required', 'string']",
            $rules
        );
    }

    public function test_generate_request_from_operation()
    {
        $pathItem = $this->spec->paths->getPath('/request-generation');
        $operation = $pathItem->post;

        $extractedRC = RouteControllerResolver::extract($operation->{'l-og-controller'});
        $this->requestGenerator->setExtractedRouteController($extractedRC);

        $this->requestGenerator->generateRequestForOperation($operation);

        $file = base_path('app/Http/Requests/CreateSomeRequest.php');
        $requestFileContent = file_get_contents($file);

        $this->assertFileExists($file);
        $this->assertStringContainsString(
            "'name' => ['required', 'string']",
            $requestFileContent
        );
        unlink($file);
    }

    public function test_make_route_for_property_correctly()
    {
        $property = new OpenapiProperty('name');
        $property->addValidationRule('required');
        $property->addValidationRule('string');

        $rules = $this->requestGenerator->makeRulesForProperty($property);

        $this->assertSame("['required', 'string'],\n", $rules);
    }

    public function test_can_create_request_file_if_not_exists()
    {
        $pathItem = $this->spec->paths->getPath('/request-generation');
        $operation = $pathItem->post;
        $operation->{'l-og-controller'} = "App\Http\Controllers\NotExistsFolder\SomeController@create";
        $extractedRC = RouteControllerResolver::extract($operation->{'l-og-controller'});
        $this->requestGenerator->setExtractedRouteController($extractedRC);
        $file = base_path('app/Http/Requests/NotExistsFolder/CreateSomeRequest.php');

        $this->assertFileDoesNotExist($file);

        $this->requestGenerator->createRequestFileIfNotExists();

        $this->assertFileExists($file);
        unlink($file);
    }

    public function test_get_file_path_with_php_extension()
    {
        $pathItem = $this->spec->paths->getPath('/request-generation');
        $operation = $pathItem->post;
        $extractedRC = RouteControllerResolver::extract($operation->{'l-og-controller'});
        $this->requestGenerator->setExtractedRouteController($extractedRC);

        $expectedFile = base_path('app/Http/Requests/CreateSomeRequest.php');
        $actual = $this->requestGenerator->getFilePath();
        $this->assertSame($expectedFile, $actual);
    }

    public function test_can_create_complexity_request()
    {
        $pathItem = $this->spec->paths->getPath('/for-validation');
        $operation = $pathItem->post;

        $extractedRC = RouteControllerResolver::extract($operation->{'l-og-controller'});
        $this->requestGenerator->setExtractedRouteController($extractedRC);

        $this->requestGenerator->generateRequestForOperation($operation);

        $file = base_path('app/Http/Requests/CreateComplexRequest.php');
        $requestFileContent = file_get_contents($file);

        $this->assertFileExists($file);
        $this->assertStringContainsString(
            "'details.name' => ['string', 'required']",
            $requestFileContent
        );
        unlink($file);
    }
}
