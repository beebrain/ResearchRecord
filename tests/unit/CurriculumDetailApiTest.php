<?php

namespace Tests\Unit;

use App\Filters\CurriculumApiTokenFilter;
use App\Libraries\RrOpenApiSpec;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * @internal
 */
final class CurriculumDetailApiTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    public function testOpenApiSpecUsesCurriculumToken(): void
    {
        $spec = RrOpenApiSpec::build();

        $this->assertSame('3.0.3', $spec['openapi']);
        $this->assertArrayHasKey('/api/curriculum-detail-by-name', $spec['paths']);
        $this->assertArrayHasKey('curriculumApiToken', $spec['components']['securitySchemes']);
        $this->assertArrayNotHasKey('apiKeyAuth', $spec['components']['securitySchemes']);
        $this->assertArrayNotHasKey('/api/public/publications-by-email', $spec['paths']);
    }

    public function testEndpointRequiresTokenWithoutLogin(): void
    {
        $result = $this->get('api/curriculum-detail-by-name?curriculum_name=test');

        $this->assertFalse($result->isRedirect(), 'Must not redirect to login');

        $json = json_decode($result->getJSON(), true);
        $this->assertIsArray($json);
        $this->assertFalse($json['success'] ?? true);
        $this->assertContains($json['error'] ?? '', ['UNAUTHORIZED', 'API_NOT_CONFIGURED']);
    }

    public function testEndpointRejectsInvalidTokenWhenConfigured(): void
    {
        if (! CurriculumApiTokenFilter::isConfigured()) {
            $this->markTestSkipped('CURRICULUM_API_TOKEN not set in .env');
        }

        $result = $this->withHeaders(['X-Curriculum-Api-Token' => 'invalid-token-for-test'])
            ->get('api/curriculum-detail-by-name?curriculum_name=test');

        $result->assertStatus(401);
    }
}
