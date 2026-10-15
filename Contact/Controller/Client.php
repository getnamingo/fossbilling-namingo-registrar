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

namespace Box\Mod\Contact\Controller;

class Client implements \FOSSBilling\InjectionAwareInterface
{
    protected $di;

    public function setDi(\Pimple\Container|null $di): void
    {
        $this->di = $di;
    }

    public function getDi(): ?\Pimple\Container
    {
        return $this->di;
    }

    public function register(\Box_App &$app): void
    {
        $app->get('/contact', 'get_index', [], static::class);
        $app->post('/contact', 'get_index', [], static::class);
    }

    public function get_index(\Box_App $app)
    {
        $error = '';
        $success = '';

        $domain = filter_input(INPUT_GET, 'domain', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
            $message = filter_input(INPUT_POST, 'message', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

            if ($name && $email && $message) {
                $parts = explode('.', $domain);
                $sld = '';
                $tld = '';

                if (count($parts) >= 3) {
                    $tld = implode('.', array_slice($parts, -2));
                    $sld = $parts[count($parts) - 3];
                } elseif (count($parts) === 2) {
                    $sld = $parts[0];
                    $tld = $parts[1];
                } else {
                    $error = "Error: Invalid domain format.";
                }

                if ($sld && $tld) {
                    $contact = $this->di['db']->getRow(
                        'SELECT * FROM service_domain WHERE sld = :sld AND tld = :tld',
                        ['sld' => $sld, 'tld' => '.' . $tld]
                    );
                } else {
                    $error = "Error: Unable to extract SLD and TLD from the domain.";
                }

                $sender = [
                    'email' => $email,
                    'name' => $name,
                ];
                $recipient = [
                    'email' => $contact['contact_email'],
                    'name' => $contact['contact_first_name'] . ' ' . $contact['contact_last_name'],
                ];

                $mod = $this->di['mod']('email');
                $settings = $mod->getConfig();
                $logEnabled = isset($settings['log_enabled']) && $settings['log_enabled'];
                $transport = $settings['mailer'] ?? 'sendmail';

                $mail = new \FOSSBilling\Mail($sender, $recipient, "Contact Domain Registrant: " . $domain, $message, $transport, $settings['custom_dsn'] ?? null);

                try {
                    $mail->send($settings);
                        
                    if ($logEnabled) {
                        $activityService = $this->di['mod_service']('activity');
                        $activityService->logEmail("Contact Domain Registrant: " . $domain, null, $email, $contact['contact_email'], $message);
                    }
                    $success = 'Your message has been sent successfully.';
                } catch (\Exception $e) {
                    $error = 'Failed to send the message. Please try again later.';
                    $this->di['logger']->setChannel('email')->err($e->getMessage());
                }
            } else {
                $error = 'Please fill in all fields with valid information.';
            }
        } else {
            if ($domain) {
                $parts = explode('.', $domain);
                $sld = '';
                $tld = '';

                if (count($parts) >= 3) {
                    $tld = implode('.', array_slice($parts, -2));
                    $sld = $parts[count($parts) - 3];
                } elseif (count($parts) === 2) {
                    $sld = $parts[0];
                    $tld = $parts[1];
                } else {
                    $error = "Error: Invalid domain format.";
                }

                if ($sld && $tld) {
                    $domainExists = $this->di['db']->getAll(
                        'SELECT * FROM service_domain WHERE sld = :sld AND tld = :tld',
                        ['sld' => $sld, 'tld' => '.' . $tld]
                    );

                    if (!$domainExists) {
                        $error = "Error: The specified domain does not exist.";
                    }
                } else {
                    $error = "Error: Unable to extract SLD and TLD from the domain.";
                }
            } else {
                $error = "Error: You must specify a domain.";
            }
        }

        return $app->render('mod_contact_index', [
            'domain' => $domain,
            'error' => $error,
            'success' => $success,
        ]);
    }

}