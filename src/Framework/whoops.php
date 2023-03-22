<?php

declare(strict_types=1);

use MetaRush\LogOnce\LogOnce;
use MetaRush\LogOnce\Pdo\Adapter as PdoLogger;
use MetaRush\LogOnce\FileSystem\Adapter as FileSystemLogger;
use MetaRush\Notifier\Pushover\Builder as PushoverNotifier;
use MetaRush\Notifier\Email\Builder as EmailNotifier;
use MetaRush\EmailFallback\Builder as EmailBuilder;
use MetaRush\EmailFallback\Server as SmtpServers;
use Zkwbbr\Utils\AdjustedDateTimeByTimeZone;

// ------------------------------------------------
// We need this custom function because the default
// getTraceAsString() truncates relevant info
//
// Credit: https://stackoverflow.com/a/6076667/748789
//
// Note: You might not need this anymore in PHP 8
// https://php.watch/versions/8.0/throwable-stacktrace-param-max-length
// ------------------------------------------------
function getTraceAsStringUntruncated(\Throwable $ex): string
{
    $count = 0;
    $trace = $ex->getTrace();
    $s = '';

    foreach ($trace as $v) {

        $args = '';

        if (isset($v['args'])) {

            $args = [];

            foreach ($v['args'] as $arg) {

                if (\is_string($arg)) {

                    $args[] = "'" . $arg . "'";

                } elseif (\is_array($arg)) {

                    $args[] = 'Array';

                } elseif (\is_null($arg)) {

                    $args[] = 'NULL';

                } elseif (\is_bool($arg)) {

                    $args[] = ($arg) ? 'true' : 'false';

                } elseif (\is_object($arg)) {

                    $args[] = \get_class($arg);

                } elseif (\is_resource($arg)) {

                    $args[] = \get_resource_type($arg);

                } else {

                    $args[] = $arg;
                }
            }

            $args = \implode(', ', $args);
        }

        // there are errors in which 'file' and 'line' is not set so let's put a dummy value
        $file = $v['file'] ?? 'NONE';
        $line = $v['line'] ?? 'NONE';

        $s .= \sprintf("#%s %s (%s): %s(%s)" . PHP_EOL,
            $count,
            $file,
            $line,
            $v['function'],
            $args);

        $count++;
    }

    return $s;
}

// ------------------------------------------------
//  setup custom error handler using Whoops
//  note: we use Whoops because it also handles
//  fatal errors
// ------------------------------------------------

$whoops = new \Whoops\Run;

/** @var \League\Container\Container $diContainer */
$whoops->pushHandler(function ($ex) use ($diContainer, $appCfg) {

    $errorHash = (string) \crc32($ex->getMessage() . $ex->getFile() . $ex->getLine() . $ex->getTraceAsString());

    $adjustedDateTime = AdjustedDateTimeByTimeZone::x('now', APP_ERROR_LOG_TIMEZONE, 'Y-m-d H:i:s O');

    $logMessage = "[$adjustedDateTime] {$ex->getMessage()} on {$ex->getFile()} ({$ex->getLine()}) Code: {$ex->getCode()}" . PHP_EOL . PHP_EOL .
        '--' . PHP_EOL . PHP_EOL .
        'Trace:' . PHP_EOL . PHP_EOL .
        getTraceAsStringUntruncated($ex) . PHP_EOL .
        '--' . PHP_EOL . PHP_EOL .
        '$_SERVER:' . PHP_EOL . PHP_EOL;

    $serverVars = $_SERVER;
    $removeFromServerVars = ['PHP_AUTH_USER', 'PHP_AUTH_PW'];
    foreach ($removeFromServerVars as $v)
        unset($serverVars[$v]);

    foreach ($serverVars as $k => $v)
        if (!\is_array($v))
            $logMessage .= $k . ' = ' . $v . PHP_EOL;

    // ------------------------------------------------

    if (APP_DEVELOPMENT_MODE) { // @phpstan-ignore-line
        $adapter = new FileSystemLogger(APP_ERROR_LOG_DIR);

        (new LogOnce($adapter))
            ->setTimeZone('UTC')
            ->setHash($errorHash)
            ->setLogMessage($logMessage)
            ->log();

    } else {

        $subject = 'Error alert on ' . $appCfg->getAppName() . ' #' . $errorHash;

        // ------------------------------------------------

        $pushoverBody = $ex->getMessage() . ' on ' . $ex->getFile() . ' (' . $ex->getLine() . ') Code: ' . $ex->getCode();

        /** @var \MetaRush\Notifier\Notifier $pushoverNotifier */
        $pushoverNotifier = (new PushoverNotifier)
            ->addAccount($appCfg->getPushoverAppKey(), $appCfg->getPushoverUserKey())
            ->setSubject($subject)
            ->setBody($pushoverBody)
            ->build();

        // ------------------------------------------------

        $smtpProviders = $appCfg->getSmtpProviders();

        $smtpServers = [];
        foreach ($smtpProviders as $k => $v)
            $smtpServers[] = (new SmtpServers)
                ->setHost($v['host'])
                ->setUser($v['user'])
                ->setPass($v['pass'])
                ->setPort((int) $v['port'])
                ->setEncr($v['prot']);

        $emailBuilder = (new EmailBuilder)
            ->setServers($smtpServers)
            ->setTos($appCfg->getAdminEmails())
            ->setSubject($subject)
            ->setBody($logMessage)
            ->setFromEmail($appCfg->getNoReplyEmail())
            ->setAdminEmails($appCfg->getAdminEmails())
            ->setNotificationFromEmail($appCfg->getNoReplyEmail());

        /** @var \MetaRush\Notifier\Notifier $emailNotifier */
        $emailNotifier = (new EmailNotifier)
            ->setEmailFallbackBuilder($emailBuilder)
            ->build();

        // ------------------------------------------------

        $notifiers = [
            $pushoverNotifier,
            $emailNotifier
        ];

        // ------------------------------------------------

        try {

            /** @var \MetaRush\DataMapper\DataMapper $dataMapper */
            $dataMapper = $diContainer->get(\MetaRush\DataMapper\DataMapper::class);

            $adapter = new PdoLogger($dataMapper, $appCfg->getErrorLogTable());

            (new LogOnce($adapter))
                ->setTimeZone('UTC')
                ->setHash($errorHash)
                ->setLogMessage($logMessage)
                ->setNotifiers($notifiers)
                ->log();

        } catch (\Exception $ex) {

            /* use backup logger (file system) to alert us that the primary error log failed */

            $backupSubject = 'Primary error logger failed on ' . $appCfg->getAppName() . '. Using backup to log error # ' . $errorHash;

            // ------------------------------------------------

            /** @var \MetaRush\Notifier\Notifier $backupPushoverNotifier */
            $backupPushoverNotifier = (new PushoverNotifier)
                ->addAccount($appCfg->getPushoverAppKey(), $appCfg->getPushoverUserKey())
                ->setSubject($backupSubject)
                ->setBody($ex->getMessage())
                ->build();

            // ------------------------------------------------

            $backupEmailBuilder = (new EmailBuilder)
                ->setServers($smtpServers)
                ->setTos($appCfg->getAdminEmails())
                ->setSubject($backupSubject)
                ->setBody($ex->getMessage())
                ->setFromEmail($appCfg->getNoReplyEmail())
                ->setAdminEmails($appCfg->getAdminEmails())
                ->setNotificationFromEmail($appCfg->getNoReplyEmail());

            /** @var \MetaRush\Notifier\Notifier $backupEmailNotifier */
            $backupEmailNotifier = (new EmailNotifier)
                ->setEmailFallbackBuilder($backupEmailBuilder)
                ->build();

            // ------------------------------------------------

            $adapter = new FileSystemLogger(APP_ERROR_LOG_DIR);

            $notifiersWithBackupNotifiers = \array_merge($notifiers, [$backupPushoverNotifier, $backupEmailNotifier]);

            (new LogOnce($adapter))
                ->setTimeZone('UTC')
                ->setHash($errorHash)
                ->setLogMessage($logMessage)
                ->setNotifiers($notifiersWithBackupNotifiers)
                ->log();
        }
    }

    // ----------------------------------------------
    // send response to user/client
    // ----------------------------------------------

    $msg = (string) \file_get_contents(__DIR__ . '/../Views/Default/error.php');
    $msg = \str_replace('{{errorCode}}', $errorHash, $msg);

    $response = new \Laminas\Diactoros\Response;
    $response->getBody()->write($msg);
    $response = $response->withAddedHeader('content-type', 'text/html')->withStatus(500);
    (new \Laminas\HttpHandlerRunner\Emitter\SapiEmitter)->emit($response);

});

$whoops->register();
