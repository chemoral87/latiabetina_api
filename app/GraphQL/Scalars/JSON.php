<?php

declare(strict_types=1);

namespace App\GraphQL\Scalars;

use GraphQL\Error\Error;
use GraphQL\Language\AST\Node;
use GraphQL\Type\Definition\ScalarType;
use GraphQL\Utils\AST;

/**
 * Arbitrary JSON value.
 *
 * `ChurchMemberMedal.description` is stored as a JSON string in the database and
 * decoded to an array by `ChurchMemberMedalController`; this scalar keeps the
 * same object/array shape so the Nuxt side does not have to decode again.
 */
final class JSON extends ScalarType
{
    public string $name = 'JSON';

    public function serialize($value): mixed
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);

            return json_last_error() === JSON_ERROR_NONE ? $decoded : $value;
        }

        return $value;
    }

    public function parseValue($value): mixed
    {
        return $value;
    }

    public function parseLiteral(Node $valueNode, ?array $variables = null): mixed
    {
        try {
            return AST::valueFromASTUntyped($valueNode, $variables);
        } catch (\Throwable $e) {
            throw new Error('Cannot parse JSON literal: '.$e->getMessage());
        }
    }
}
