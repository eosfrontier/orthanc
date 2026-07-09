<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

/**
 * Base OpenAPI specification for the Virtual Storage API.
 *
 * Contains the API info, server configuration, security scheme,
 * all reusable schemas, and common error responses.
 */
#[OA\Info(
    version: '3.0',
    title: 'Virtual Storage API',
    description: 'REST API for the EOS Orthanc Virtual Storage system. Manages categories, item types, inventory, transfers, labels, and audit logs.',
)]
#[OA\Server(
    url: '/',
    description: 'Storage API v3',
)]
#[OA\SecurityScheme(
    securityScheme: 'bearerAuth',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'Sanctum',
    description: 'Enter your Sanctum bearer token',
)]

// ── Reusable Schemas ────────────────────────────────────────────────

#[OA\Schema(
    schema: 'StorageCategory',
    required: ['id', 'name', 'created_at', 'created_by', 'is_system'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'Currency'),
        new OA\Property(property: 'created_at', type: 'integer', description: 'Unix timestamp', example: 1711641600),
        new OA\Property(property: 'created_by', type: 'integer', example: 42),
        new OA\Property(property: 'deleted_at', type: 'integer', nullable: true, description: 'Unix timestamp, null if active', example: null),
        new OA\Property(property: 'is_system', type: 'integer', enum: [0, 1], example: 0),
    ],
)]

#[OA\Schema(
    schema: 'StorageItemType',
    required: ['id', 'name', 'category_id', 'stackable', 'is_system', 'created_at', 'created_by'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'Sonuren'),
        new OA\Property(property: 'description', type: 'string', nullable: true, example: 'Standard currency'),
        new OA\Property(property: 'icon', type: 'string', nullable: true, example: 'coin'),
        new OA\Property(property: 'category_id', type: 'integer', example: 1),
        new OA\Property(property: 'stackable', type: 'integer', enum: [0, 1], example: 1),
        new OA\Property(property: 'max_quantity', type: 'integer', nullable: true, example: 999),
        new OA\Property(property: 'is_system', type: 'integer', enum: [0, 1], example: 0),
        new OA\Property(property: 'created_at', type: 'integer', description: 'Unix timestamp', example: 1711641600),
        new OA\Property(property: 'created_by', type: 'integer', example: 42),
        new OA\Property(property: 'updated_at', type: 'integer', nullable: true, description: 'Unix timestamp', example: 1711641600),
        new OA\Property(property: 'deleted_at', type: 'integer', nullable: true, example: null),
        new OA\Property(property: 'category_name', type: 'string', example: 'Currency'),
    ],
)]

#[OA\Schema(
    schema: 'StorageInventory',
    required: ['id', 'character_id', 'item_type_id', 'quantity', 'updated_at'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'character_id', type: 'integer', example: 100),
        new OA\Property(property: 'item_type_id', type: 'integer', example: 1),
        new OA\Property(property: 'quantity', type: 'integer', example: 50),
        new OA\Property(property: 'updated_at', type: 'integer', description: 'Unix timestamp', example: 1711641600),
        new OA\Property(property: 'item_type', ref: '#/components/schemas/StorageItemType'),
    ],
)]

#[OA\Schema(
    schema: 'StorageLog',
    required: ['id', 'item_type_id', 'quantity', 'actor_id', 'action', 'brokered', 'created_at'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'item_type_id', type: 'integer', example: 1),
        new OA\Property(property: 'quantity', type: 'integer', example: 10),
        new OA\Property(property: 'source_char_id', type: 'integer', nullable: true, example: 100),
        new OA\Property(property: 'target_char_id', type: 'integer', nullable: true, example: 200),
        new OA\Property(property: 'actor_id', type: 'integer', example: 42),
        new OA\Property(property: 'action', type: 'string', example: 'mint'),
        new OA\Property(property: 'brokered', type: 'integer', enum: [0, 1], example: 0),
        new OA\Property(property: 'note', type: 'string', nullable: true, example: 'Quest reward'),
        new OA\Property(property: 'created_at', type: 'integer', description: 'Unix timestamp', example: 1711641600),
    ],
)]

#[OA\Schema(
    schema: 'StorageSetting',
    required: ['key_name', 'value', 'updated_at', 'updated_by'],
    properties: [
        new OA\Property(property: 'key_name', type: 'string', example: 'transfers_enabled'),
        new OA\Property(property: 'value', type: 'string', example: '1'),
        new OA\Property(property: 'updated_at', type: 'integer', description: 'Unix timestamp', example: 1711641600),
        new OA\Property(property: 'updated_by', type: 'integer', example: 42),
    ],
)]

#[OA\Schema(
    schema: 'StorageLabelToken',
    required: ['id', 'token', 'items', 'source', 'created_by', 'created_at'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'token', type: 'string', format: 'uuid', example: '550e8400-e29b-41d4-a716-446655440000'),
        new OA\Property(property: 'items', type: 'array', items: new OA\Items(ref: '#/components/schemas/StorageLabelTokenItem')),
        new OA\Property(property: 'note', type: 'string', nullable: true, example: 'Starter pack'),
        new OA\Property(property: 'source', type: 'string', enum: ['mint', 'burn'], example: 'mint'),
        new OA\Property(property: 'source_char_id', type: 'integer', nullable: true, example: null),
        new OA\Property(property: 'created_by', type: 'integer', example: 42),
        new OA\Property(property: 'created_at', type: 'integer', description: 'Unix timestamp', example: 1711641600),
        new OA\Property(property: 'claimed_by', type: 'integer', nullable: true, example: null),
        new OA\Property(property: 'claimed_at', type: 'integer', nullable: true, example: null),
        new OA\Property(property: 'expires_at', type: 'integer', nullable: true, description: 'Unix timestamp', example: null),
    ],
)]

#[OA\Schema(
    schema: 'StorageLabelTokenItem',
    required: ['id', 'item_type_id', 'quantity'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'item_type_id', type: 'integer', example: 1),
        new OA\Property(property: 'quantity', type: 'integer', example: 10),
        new OA\Property(property: 'item_type', ref: '#/components/schemas/StorageItemType'),
    ],
)]

#[OA\Schema(
    schema: 'ValidationError',
    required: ['message', 'errors'],
    properties: [
        new OA\Property(property: 'message', type: 'string', example: 'The given data was invalid.'),
        new OA\Property(
            property: 'errors',
            type: 'object',
            additionalProperties: new OA\AdditionalProperties(
                type: 'array',
                items: new OA\Items(type: 'string'),
            ),
            example: ['name' => ['The name field is required.']],
        ),
    ],
)]

#[OA\Schema(
    schema: 'ErrorMessage',
    required: ['message'],
    properties: [
        new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.'),
    ],
)]

// ── Reusable Responses ──────────────────────────────────────────────

#[OA\Response(
    response: 'Unauthenticated',
    description: 'Unauthenticated — missing or invalid bearer token',
    content: new OA\JsonContent(ref: '#/components/schemas/ErrorMessage'),
)]
#[OA\Response(
    response: 'Forbidden',
    description: 'Forbidden — token lacks the required ability',
    content: new OA\JsonContent(ref: '#/components/schemas/ErrorMessage'),
)]
#[OA\Response(
    response: 'NotFound',
    description: 'Resource not found',
    content: new OA\JsonContent(ref: '#/components/schemas/ErrorMessage'),
)]
#[OA\Response(
    response: 'Conflict',
    description: 'Conflict — resource has dependent records',
    content: new OA\JsonContent(ref: '#/components/schemas/ErrorMessage'),
)]
#[OA\Response(
    response: 'ValidationError',
    description: 'Validation error',
    content: new OA\JsonContent(ref: '#/components/schemas/ValidationError'),
)]
#[OA\Response(
    response: 'Locked',
    description: 'Locked — the requested operation is currently disabled',
    content: new OA\JsonContent(ref: '#/components/schemas/ErrorMessage'),
)]

class OpenApiSpec
{
}
