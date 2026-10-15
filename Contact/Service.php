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

namespace Box\Mod\Contact;

use FOSSBilling\InformationException;

class Service
{
    protected $di;

    public function setDi(\Pimple\Container|null $di): void
    {
        $this->di = $di;
    }

    public function install(): bool
    {
        $db = $this->di['db'];
        $db->exec('SELECT NOW()');

        return true;
    }

    public function uninstall(): bool
    {
        return true;
    }

    public function update(array $manifest): bool
    {
        return true;
    }

    public function toApiArray(array $row, string $role = 'guest', bool $deep = true): array
    {
        return $row;
    }
}