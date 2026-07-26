<?php
declare(strict_types=1);

namespace App\Mail;

/**
 * Client SMTP minimaliste (sans dépendance externe), compatible
 * avec les serveurs SMTP classiques en SSL implicite (port 465) ou STARTTLS (port 587).
 */
class MailService
{
    private static function readResponse($socket): string
    {
        $response = '';
        while (($line = fgets($socket, 515)) !== false) {
            $response .= $line;
            // La ligne finale d'une réponse SMTP a un espace en 4e position (ex: "250 ")
            if (isset($line[3]) && $line[3] === ' ') {
                break;
            }
        }
        return $response;
    }

    private static function command($socket, string $command, array $expectedCodes): string
    {
        if ($command !== '') {
            fwrite($socket, $command . "\r\n");
        }
        $response = self::readResponse($socket);
        $code = (int) substr($response, 0, 3);
        if (!in_array($code, $expectedCodes, true)) {
            throw new \RuntimeException("Erreur SMTP ($code) sur la commande [$command] : " . trim($response));
        }
        return $response;
    }

    /**
     * Envoie un email HTML via SMTP. Retourne true si l'envoi a réussi.
     */
    public static function send(string $toEmail, string $toName, string $subject, string $htmlBody): bool
    {
        $host = SMTP_HOST;
        $port = SMTP_PORT;
        $username = SMTP_USERNAME;
        $password = SMTP_PASSWORD;
        $fromEmail = SMTP_FROM_EMAIL;
        $fromName = SMTP_FROM_NAME;
        $replyTo = SMTP_REPLY_TO;
        $secure = SMTP_SECURE;

        $remote = ($secure === 'ssl' ? 'ssl://' : 'tcp://') . $host . ':' . $port;

        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
            ],
        ]);

        $socket = @stream_socket_client($remote, $errno, $errstr, 15, STREAM_CLIENT_CONNECT, $context);
        if (!$socket) {
            error_log("MailService: connexion SMTP échouée ($errno): $errstr");
            return false;
        }

        stream_set_timeout($socket, 15);
        $ehloHost = parse_url(defined('APP_URL') ? APP_URL : '', PHP_URL_HOST) ?: 'localhost';

        try {
            self::readResponse($socket); // bannière 220

            self::command($socket, "EHLO $ehloHost", [250]);

            if ($secure === 'tls') {
                self::command($socket, 'STARTTLS', [220]);
                if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    throw new \RuntimeException('Impossible d’activer le chiffrement TLS.');
                }
                self::command($socket, "EHLO $ehloHost", [250]);
            }

            self::command($socket, 'AUTH LOGIN', [334]);
            self::command($socket, base64_encode($username), [334]);
            self::command($socket, base64_encode($password), [235]);

            self::command($socket, "MAIL FROM:<$fromEmail>", [250]);
            self::command($socket, "RCPT TO:<$toEmail>", [250, 251]);
            self::command($socket, 'DATA', [354]);

            $boundary = 'bnd_' . bin2hex(random_bytes(12));
            $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
            $encodedFromName = '=?UTF-8?B?' . base64_encode($fromName) . '?=';
            $encodedToName = $toName !== '' ? '=?UTF-8?B?' . base64_encode($toName) . '?= ' : '';

            $textBody = trim(html_entity_decode(strip_tags(str_replace(
                ['<br>', '<br/>', '<br />', '</p>'],
                "\n",
                $htmlBody
            ))));

            $headers = [
                "From: $encodedFromName <$fromEmail>",
                "To: {$encodedToName}<$toEmail>",
                "Reply-To: $replyTo",
                "Subject: $encodedSubject",
                'MIME-Version: 1.0',
                "Content-Type: multipart/alternative; boundary=\"$boundary\"",
                'Date: ' . date('r'),
                'X-Mailer: ' . (defined('APP_NAME') ? APP_NAME : 'PHP'),
            ];

            $body = "--$boundary\r\n";
            $body .= "Content-Type: text/plain; charset=UTF-8\r\n";
            $body .= "Content-Transfer-Encoding: base64\r\n\r\n";
            $body .= chunk_split(base64_encode($textBody));
            $body .= "--$boundary\r\n";
            $body .= "Content-Type: text/html; charset=UTF-8\r\n";
            $body .= "Content-Transfer-Encoding: base64\r\n\r\n";
            $body .= chunk_split(base64_encode($htmlBody));
            $body .= "--$boundary--\r\n";

            // Un point seul sur une ligne termine le message SMTP ; on échappe les lignes commençant par "."
            $data = implode("\r\n", $headers) . "\r\n\r\n" . $body;
            $data = str_replace("\r\n.", "\r\n..", $data);

            self::command($socket, $data . "\r\n.", [250]);
            self::command($socket, 'QUIT', [221]);
        } catch (\Throwable $e) {
            error_log('MailService: ' . $e->getMessage());
            fclose($socket);
            return false;
        }

        fclose($socket);
        return true;
    }

    public static function sendVerificationCode(string $toEmail, string $toName, string $code): bool
    {
        $subject = 'Ton code de vérification ' . (defined('APP_NAME') ? APP_NAME : '');
        return self::send($toEmail, $toName, $subject, self::verificationTemplate($toName, $code));
    }

    public static function sendWelcomeEmail(string $toEmail, string $toName): bool
    {
        $subject = 'Bienvenue sur ' . (defined('APP_NAME') ? APP_NAME : '') . ' 🎓';
        return self::send($toEmail, $toName, $subject, self::welcomeTemplate($toName));
    }

    public static function sendPasswordResetCode(string $toEmail, string $toName, string $code): bool
    {
        $subject = 'Réinitialisation de ton mot de passe ' . (defined('APP_NAME') ? APP_NAME : '');
        return self::send($toEmail, $toName, $subject, self::resetPasswordTemplate($toName, $code));
    }

    /**
     * Envoie à l'utilisateur son matricule (code d'accompagnement) juste après un paiement réussi.
     */
    public static function sendMatriculeEmail(string $toEmail, string $toName, string $matricule): bool
    {
        $subject = 'Ton matricule d’accompagnement — ' . (defined('APP_NAME') ? APP_NAME : '');
        return self::send($toEmail, $toName, $subject, self::matriculeTemplate($toName, $matricule));
    }

    /**
     * Notifie l'administrateur qu'un nouveau paiement vient d'être confirmé.
     */
    public static function sendAdminPaymentNotification(string $userEmail, string $userName, string $formuleNom, float $montant, string $reference): bool
    {
        $adminEmail = defined('ADMIN_EMAIL') ? ADMIN_EMAIL : '';
        if ($adminEmail === '') {
            error_log('MailService: ADMIN_EMAIL non défini, notification admin ignorée.');
            return false;
        }
        $subject = 'Nouveau paiement confirmé — ' . (defined('APP_NAME') ? APP_NAME : '');
        return self::send($adminEmail, 'Admin', $subject, self::adminNotificationTemplate($userEmail, $userName, $formuleNom, $montant, $reference));
    }

    private static function verificationTemplate(string $name, string $code): string
    {
        $name = htmlspecialchars($name !== '' ? $name : 'là');
        $appName = htmlspecialchars(defined('APP_NAME') ? APP_NAME : '');
        return <<<HTML
        <div style="font-family: Arial, Helvetica, sans-serif; max-width: 480px; margin: 0 auto; padding: 32px 24px; color: #2b2b2b;">
            <p style="font-size: 13px; letter-spacing: 0.08em; text-transform: uppercase; color: #E8531F; font-weight: 700; margin: 0 0 16px;">{$appName}</p>
            <h2 style="margin: 0 0 20px; font-size: 20px;">Bonjour {$name},</h2>
            <p style="font-size: 15px; line-height: 1.6; margin: 0 0 20px;">
                Merci de ton inscription. Pour confirmer ton adresse email, saisis le code ci-dessous sur la page de vérification :
            </p>
            <div style="background:#f6f1ea; border-radius: 12px; padding: 20px; text-align:center; margin: 0 0 20px;">
                <span style="font-size: 32px; font-weight: 700; letter-spacing: 0.35em; color:#1a1a1a;">{$code}</span>
            </div>
            <p style="font-size: 13px; color:#777; margin: 0;">Ce code expire dans 15 minutes. Si tu n'es pas à l'origine de cette demande, ignore cet email.</p>
        </div>
        HTML;
    }

    private static function welcomeTemplate(string $name): string
    {
        $name = htmlspecialchars($name !== '' ? $name : 'là');
        $appName = htmlspecialchars(defined('APP_NAME') ? APP_NAME : '');
        return <<<HTML
        <div style="font-family: Arial, Helvetica, sans-serif; max-width: 480px; margin: 0 auto; padding: 32px 24px; color: #2b2b2b;">
            <p style="font-size: 13px; letter-spacing: 0.08em; text-transform: uppercase; color: #E8531F; font-weight: 700; margin: 0 0 16px;">{$appName}</p>
            <h2 style="margin: 0 0 20px; font-size: 20px;">Bienvenue {$name} 🎓</h2>
            <p style="font-size: 15px; line-height: 1.6; margin: 0 0 20px;">
                Ton adresse email est confirmée et ton compte est prêt. Tu peux dès maintenant accéder à ton
                accompagnement personnalisé, qui te suit du choix de ta filière jusqu'à l'obtention de ton allocation.
            </p>
            <p style="font-size: 15px; line-height: 1.6; margin: 0;">À très vite,<br>L'équipe {$appName}</p>
        </div>
        HTML;
    }

    private static function resetPasswordTemplate(string $name, string $code): string
    {
        $name = htmlspecialchars($name !== '' ? $name : 'là');
        $appName = htmlspecialchars(defined('APP_NAME') ? APP_NAME : '');
        return <<<HTML
        <div style="font-family: Arial, Helvetica, sans-serif; max-width: 480px; margin: 0 auto; padding: 32px 24px; color: #2b2b2b;">
            <p style="font-size: 13px; letter-spacing: 0.08em; text-transform: uppercase; color: #E8531F; font-weight: 700; margin: 0 0 16px;">{$appName}</p>
            <h2 style="margin: 0 0 20px; font-size: 20px;">Bonjour {$name},</h2>
            <p style="font-size: 15px; line-height: 1.6; margin: 0 0 20px;">
                Tu as demandé à réinitialiser ton mot de passe. Saisis le code ci-dessous sur la page de réinitialisation :
            </p>
            <div style="background:#f6f1ea; border-radius: 12px; padding: 20px; text-align:center; margin: 0 0 20px;">
                <span style="font-size: 32px; font-weight: 700; letter-spacing: 0.35em; color:#1a1a1a;">{$code}</span>
            </div>
            <p style="font-size: 13px; color:#777; margin: 0;">Ce code expire dans 15 minutes. Si tu n'es pas à l'origine de cette demande, ignore cet email — ton mot de passe restera inchangé.</p>
        </div>
        HTML;
    }

    private static function matriculeTemplate(string $name, string $matricule): string
    {
        $name = htmlspecialchars($name !== '' ? $name : 'là');
        $appName = htmlspecialchars(defined('APP_NAME') ? APP_NAME : '');
        return <<<HTML
        <div style="font-family: Arial, Helvetica, sans-serif; max-width: 480px; margin: 0 auto; padding: 32px 24px; color: #2b2b2b;">
            <p style="font-size: 13px; letter-spacing: 0.08em; text-transform: uppercase; color: #E8531F; font-weight: 700; margin: 0 0 16px;">{$appName}</p>
            <h2 style="margin: 0 0 20px; font-size: 20px;">Merci {$name}, ton paiement est confirmé 🎉</h2>
            <p style="font-size: 15px; line-height: 1.6; margin: 0 0 20px;">
                Voici ton matricule d'accompagnement. Garde-le précieusement : tu devras le présenter à ton conseiller d'orientation.
            </p>
            <div style="background:#f6f1ea; border-radius: 12px; padding: 20px; text-align:center; margin: 0 0 20px;">
                <span style="font-size: 26px; font-weight: 700; letter-spacing: 0.15em; color:#1a1a1a;">{$matricule}</span>
            </div>
            <p style="font-size: 15px; line-height: 1.6; margin: 0 0 20px;">
                Il ne te reste plus qu'une étape : complète ton profil d'orientation sur ton compte pour être mis en relation avec ton conseiller.
            </p>
            <p style="font-size: 15px; line-height: 1.6; margin: 0;">À très vite,<br>L'équipe {$appName}</p>
        </div>
        HTML;
    }

    private static function adminNotificationTemplate(string $userEmail, string $userName, string $formuleNom, float $montant, string $reference): string
    {
        $userName = htmlspecialchars($userName !== '' ? $userName : '—');
        $userEmail = htmlspecialchars($userEmail);
        $formuleNom = htmlspecialchars($formuleNom);
        $reference = htmlspecialchars($reference);
        $montantFmt = number_format($montant, 0, ',', ' ');
        $appName = htmlspecialchars(defined('APP_NAME') ? APP_NAME : '');
        return <<<HTML
        <div style="font-family: Arial, Helvetica, sans-serif; max-width: 480px; margin: 0 auto; padding: 32px 24px; color: #2b2b2b;">
            <p style="font-size: 13px; letter-spacing: 0.08em; text-transform: uppercase; color: #E8531F; font-weight: 700; margin: 0 0 16px;">{$appName} — Admin</p>
            <h2 style="margin: 0 0 20px; font-size: 20px;">Nouveau paiement confirmé</h2>
            <ul style="font-size: 15px; line-height: 1.8; margin: 0 0 20px; padding-left: 18px;">
                <li><strong>Utilisateur :</strong> {$userName} ({$userEmail})</li>
                <li><strong>Formule :</strong> {$formuleNom}</li>
                <li><strong>Montant :</strong> {$montantFmt} FCFA</li>
                <li><strong>Référence :</strong> {$reference}</li>
            </ul>
        </div>
        HTML;
    }
}