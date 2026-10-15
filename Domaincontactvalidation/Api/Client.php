<?php

/*
 * Namingo Registrar
 *
 * Copyright (c) 2023-2026 Taras Kondratyuk
 * Copyright (c) 2025-2026 Namingo contributors
 * Copyright (c) 2026 Terbora Ltd.
 *
 * Licensed under the Apache License, Version 2.0.
 * May contain portions of FOSSBilling sample modules, also licensed under Apache-2.0.
 * See the LICENSE file for the full license text.
 *
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Box\Mod\Domaincontactvalidation\Api;

class Client extends \FOSSBilling\Api\AbstractApi
{
    private const TABLE = 'domain_contact_validation';

    public function get_status(): array
    {
        // Never accept client_id from the request.
        $identity = $this->getIdentity();
        $clientId = method_exists($identity, 'getId')
            ? (int) $identity->getId()
            : (int) $identity->id;

        $row = $this->getDi()['db']->getRow(
            'SELECT
                is_validated,
                validation_checked_at,
                validation_method,
                CASE
                    WHEN validation_token IS NOT NULL
                     AND validation_token <> ""
                    THEN 1
                    ELSE 0
                END AS has_open_token
             FROM `' . self::TABLE . '`
             WHERE client_id = :client_id',
            [':client_id' => $clientId]
        );

        if (!$row) {
            return [
                'status' => 'not_started',
                'is_validated' => false,
                'verified_by' => null,
                'verified_at' => null,
                'verification_method' => null,
                'action_required' => true,
            ];
        }

        $isValidated = (int) $row['is_validated'] === 1;
        $hasOpenToken = (int) $row['has_open_token'] === 1;

        return [
            'status' => $isValidated
                ? 'validated'
                : ($hasOpenToken ? 'pending' : 'unvalidated'),
            'is_validated' => $isValidated,
            'verified_by' => $isValidated ? 'Staff' : null,
            'verified_at' => $isValidated
                ? $row['validation_checked_at']
                : null,
            'verification_method' => $isValidated
                ? $row['validation_method']
                : null,
            'action_required' => !$isValidated,
        ];
    }
}