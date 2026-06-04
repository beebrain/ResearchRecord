<?php

namespace App\Libraries;

/**
 * OpenAPI 3 spec for Research Record interactive docs (/api/docs).
 */
final class RrOpenApiSpec
{
    /**
     * @return array<string,mixed>
     */
    public static function build(): array
    {
        $root = rtrim(site_url(), '/');

        return [
            'openapi' => '3.0.3',
            'info'    => [
                'title'       => 'Research Record API',
                'description' => 'Interactive API documentation (Swagger UI). Public endpoints — ไม่ต้องใช้ token.',
                'version'     => '1.0.0',
            ],
            'servers' => [
                ['url' => $root, 'description' => 'Current Research Record server'],
            ],
            'tags' => [
                ['name' => 'Curriculum', 'description' => 'หลักสูตร (public)'],
            ],
            'paths' => self::paths(),
            'components' => self::components(),
        ];
    }

    /**
     * @return array<string,mixed>
     */
    private static function paths(): array
    {
        return [
            '/api/curriculum-detail-by-name' => [
                'get' => [
                    'tags'        => ['Curriculum'],
                    'summary'     => 'รายละเอียดหลักสูตร + ผู้รับผิดชอบ + ผลงาน',
                    'description' => 'ค้นหาหลักสูตรจากชื่อ (partial ได้) แล้วส่งกลับ '
                        . 'อาจารย์ผู้รับผิดชอบสูงสุด 5 คน พร้อมผลงานที่ **approve = 1** เท่านั้น.',
                    'operationId' => 'getCurriculumDetailByName',
                    'security'    => [],
                    'parameters'  => [
                        [
                            'name'        => 'curriculum_name',
                            'in'          => 'query',
                            'required'    => true,
                            'description' => 'ชื่อหลักสูตร (exact ก่อน แล้ว partial LIKE)',
                            'schema'      => ['type' => 'string', 'example' => 'วิทยาการคอมพิวเตอร์'],
                        ],
                        [
                            'name'        => 'faculty_id',
                            'in'          => 'query',
                            'required'    => false,
                            'description' => 'กรองคณะเมื่อชื่อหลักสูตรซ้ำ (ใช้หลังได้ 409)',
                            'schema'      => ['type' => 'integer', 'example' => 3],
                        ],
                    ],
                    'responses' => [
                        '200' => [
                            'description' => 'สำเร็จ',
                            'content'     => [
                                'application/json' => [
                                    'schema' => ['$ref' => '#/components/schemas/CurriculumDetailResponse'],
                                ],
                            ],
                        ],
                        '400' => ['$ref' => '#/components/responses/BadRequest'],
                        '404' => ['$ref' => '#/components/responses/CurriculumNotFound'],
                        '409' => [
                            'description' => 'ชื่อตรงหลายหลักสูตร',
                            'content'     => [
                                'application/json' => [
                                    'schema' => ['$ref' => '#/components/schemas/AmbiguousCurriculumError'],
                                ],
                            ],
                        ],
                        '500' => ['$ref' => '#/components/responses/ServerError'],
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array<string,mixed>
     */
    private static function components(): array
    {
        return [
            'securitySchemes' => [
                'curriculumApiToken' => [
                    'type'        => 'apiKey',
                    'in'          => 'header',
                    'name'        => 'X-Curriculum-Api-Token',
                    'description' => 'ค่าจาก CURRICULUM_API_TOKEN ใน .env (หรือ Authorization: Bearer <token>)',
                ],
            ],
            'schemas' => [
                'CurriculumDetailResponse' => [
                    'type'       => 'object',
                    'properties' => [
                        'success'              => ['type' => 'boolean', 'example' => true],
                        'curriculum'           => ['$ref' => '#/components/schemas/CurriculumInfo'],
                        'responsible_teachers' => [
                            'type'  => 'array',
                            'items' => ['$ref' => '#/components/schemas/ResponsibleTeacher'],
                        ],
                        'summary'              => ['$ref' => '#/components/schemas/CurriculumSummary'],
                        'retrieved_at'         => ['type' => 'string', 'format' => 'date-time'],
                    ],
                ],
                'CurriculumInfo' => [
                    'type'       => 'object',
                    'properties' => [
                        'id'           => ['type' => 'integer'],
                        'name'         => ['type' => 'string'],
                        'code'         => ['type' => 'string'],
                        'degree_level' => ['type' => 'string', 'enum' => ['bachelor', 'master', 'doctoral']],
                        'faculty'      => ['$ref' => '#/components/schemas/FacultyRef'],
                        'chair'        => ['$ref' => '#/components/schemas/TeacherRef', 'nullable' => true],
                    ],
                ],
                'FacultyRef' => [
                    'type'       => 'object',
                    'properties' => [
                        'id'   => ['type' => 'integer'],
                        'name' => ['type' => 'string'],
                        'code' => ['type' => 'string'],
                    ],
                ],
                'TeacherRef' => [
                    'type'       => 'object',
                    'properties' => [
                        'uid'          => ['type' => 'integer'],
                        'email'        => ['type' => 'string', 'format' => 'email'],
                        'name_thai'    => ['type' => 'string'],
                        'name_english' => ['type' => 'string'],
                    ],
                ],
                'ResponsibleTeacher' => [
                    'type'       => 'object',
                    'properties' => [
                        'order'             => ['type' => 'integer', 'minimum' => 1, 'maximum' => 5],
                        'uid'               => ['type' => 'integer'],
                        'email'             => ['type' => 'string'],
                        'name_thai'         => ['type' => 'string'],
                        'name_english'      => ['type' => 'string'],
                        'role'              => ['type' => 'string'],
                        'position'          => ['type' => 'string'],
                        'is_chair'          => ['type' => 'boolean'],
                        'publication_count' => ['type' => 'integer'],
                        'publications'      => [
                            'type'  => 'array',
                            'items' => ['$ref' => '#/components/schemas/Publication'],
                        ],
                    ],
                ],
                'Publication' => [
                    'type'       => 'object',
                    'properties' => [
                        'id'                  => ['type' => 'integer'],
                        'title'               => ['type' => 'string'],
                        'abstract'            => ['type' => 'string', 'nullable' => true],
                        'publication_type'    => ['type' => 'string'],
                        'source'              => ['type' => 'string'],
                        'publication_year'    => ['type' => 'string'],
                        'publication_year_be' => ['type' => 'integer', 'nullable' => true],
                        'doi'                 => ['type' => 'string', 'nullable' => true],
                        'authors'             => ['type' => 'string', 'nullable' => true],
                        'approve'             => ['type' => 'integer', 'example' => 1],
                        'created_at'          => ['type' => 'string', 'format' => 'date-time'],
                    ],
                ],
                'CurriculumSummary' => [
                    'type'       => 'object',
                    'properties' => [
                        'responsible_count'   => ['type' => 'integer'],
                        'total_publications'  => ['type' => 'integer'],
                        'unique_publications' => ['type' => 'integer'],
                        'publications_filter' => ['type' => 'string', 'example' => 'approved_only'],
                    ],
                ],
                'ErrorBody' => [
                    'type'       => 'object',
                    'properties' => [
                        'success' => ['type' => 'boolean', 'example' => false],
                        'error'   => ['type' => 'string'],
                        'message' => ['type' => 'string'],
                    ],
                ],
                'AmbiguousCurriculumError' => [
                    'allOf' => [
                        ['$ref' => '#/components/schemas/ErrorBody'],
                        [
                            'type'       => 'object',
                            'properties' => [
                                'error'      => ['type' => 'string', 'example' => 'AMBIGUOUS_CURRICULUM'],
                                'candidates' => [
                                    'type'  => 'array',
                                    'items' => [
                                        'type'       => 'object',
                                        'properties' => [
                                            'id'           => ['type' => 'integer'],
                                            'name'         => ['type' => 'string'],
                                            'code'         => ['type' => 'string'],
                                            'faculty_id'   => ['type' => 'integer'],
                                            'faculty_name' => ['type' => 'string'],
                                            'faculty_code' => ['type' => 'string'],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'responses' => [
                'BadRequest' => [
                    'description' => 'พารามิเตอร์ไม่ครบหรือไม่ถูกต้อง',
                    'content'     => [
                        'application/json' => ['schema' => ['$ref' => '#/components/schemas/ErrorBody']],
                    ],
                ],
                'CurriculumNotFound' => [
                    'description' => 'ไม่พบหลักสูตร',
                    'content'     => [
                        'application/json' => ['schema' => ['$ref' => '#/components/schemas/ErrorBody']],
                    ],
                ],
                'UnauthorizedCurriculumToken' => [
                    'description' => 'Token ไม่ถูกต้องหรือไม่ได้ส่ง',
                    'content'     => [
                        'application/json' => ['schema' => ['$ref' => '#/components/schemas/ErrorBody']],
                    ],
                ],
                'ServerError' => [
                    'description' => 'ข้อผิดพลาดฝั่งเซิร์ฟเวอร์',
                    'content'     => [
                        'application/json' => ['schema' => ['$ref' => '#/components/schemas/ErrorBody']],
                    ],
                ],
            ],
        ];
    }
}
