<?php

namespace Tests\Unit;

use App\Libraries\RrOpenApiSpec;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * @internal
 */
final class CurriculumDetailApiTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    public function testOpenApiSpecExposesPublicCurriculumEndpoint(): void
    {
        $spec = RrOpenApiSpec::build();

        $this->assertSame('3.0.3', $spec['openapi']);
        $this->assertArrayHasKey('/api/curriculum-detail-by-name', $spec['paths']);
        $this->assertSame([], $spec['paths']['/api/curriculum-detail-by-name']['get']['security'] ?? null);
        $this->assertArrayNotHasKey('/api/public/publications-by-email', $spec['paths']);
    }

    public function testEndpointIsPublicAndValidatesRequiredParam(): void
    {
        $result = $this->get('api/curriculum-detail-by-name');

        $this->assertFalse($result->isRedirect(), 'Must not redirect to login');

        $json = json_decode($result->getJSON(), true);
        $this->assertIsArray($json);
        $this->assertFalse($json['success'] ?? true);
        $this->assertSame('MISSING_PARAMETER', $json['error'] ?? '');
    }
}
