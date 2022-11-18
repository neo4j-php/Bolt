<?php


namespace Bolt\connection;

use Bolt\Bolt;
use Bolt\error\ConnectException;
use Bolt\error\ConnectionTimeoutException;

/**
 * Stream socket class
 *
 * @author Michal Stefanak
 * @link https://github.com/neo4j-php/Bolt
 * @package Bolt\connection
 */
class StreamSocket extends AConnection
{
    private array $sslContextOptions = [];
    private bool $autoSSL = false;

    /**
     * @var resource
     */
    private $stream;

    /**
     * Set SSL Context options
     * It will disable auto resolving SSL context options
     * @link https://www.php.net/manual/en/context.ssl.php
     * @param array $options If you pass empty array it won't use SSL
     */
    public function setSslContextOptions(array $options = [])
    {
        $this->sslContextOptions = $options;
        $this->setAutoSslContextOptions(false);
    }

    /**
     * Enable or disable auto resolving SSL context options
     */
    public function setAutoSslContextOptions(bool $auto = true)
    {
        $this->autoSSL = $auto;
    }

    /**
     * @return bool
     * @throws ConnectException
     */
    public function connect(): bool
    {
        $context = stream_context_create([
            'socket' => [
                'tcp_nodelay' => true,
            ],
            'ssl' => $this->autoSSL ? [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'SNI_enabled' => false,
                'allow_self_signed' => true,
                'capture_peer_cert' => true,
                'capture_peer_cert_chain' => true
            ] : $this->sslContextOptions
        ]);

        $this->createStreamSocketClient($context);
        $this->enableSSL();
        $this->configureTimeout();

        return true;
    }

    /**
     * @param resource $context
     * @throws ConnectException
     */
    private function createStreamSocketClient($context)
    {
        $this->stream = @stream_socket_client('tcp://' . $this->ip . ':' . $this->port, $errno, $errstr, $this->timeout, STREAM_CLIENT_CONNECT, $context);
        if ($this->stream === false) {
            throw new ConnectException($errstr, $errno);
        }

        if (!stream_set_blocking($this->stream, true)) {
            throw new ConnectException('Cannot set socket into blocking mode');
        }
    }

    /**
     * @throws ConnectException
     */
    private function enableSSL()
    {
        if (!empty($this->sslContextOptions)) {
            $enableCrypto = @stream_socket_enable_crypto($this->stream, true, STREAM_CRYPTO_METHOD_ANY_CLIENT);
            if (!$enableCrypto === false) {
                throw new ConnectException('Enable encryption error');
            }
        }

        if ($this->autoSSL) {
            $enableCrypto = @stream_socket_enable_crypto($this->stream, true, STREAM_CRYPTO_METHOD_ANY_CLIENT);

            if ($enableCrypto === true) {
                $this->autoSSL();
            } elseif (feof($this->stream)) {
                //if you try to enable crypto on stream where is not supported you are forcefully disconnected
                //because we used autoSSL we just reconnect without SSL
                $this->createStreamSocketClient(stream_context_create([
                    'socket' => [
                        'tcp_nodelay' => true,
                    ]
                ]));
            }
        }
    }

    /**
     * Set stream socket ssl parameters by received certificate
     */
    private function autoSSL()
    {
        $params = stream_context_get_params($this->stream);
        if (isset($params['options']['ssl']['peer_certificate']) && is_resource($params['options']['ssl']['peer_certificate'])) {
            $cert = openssl_x509_parse($params['options']['ssl']['peer_certificate']);
            stream_context_set_params($this->stream, [
                'ssl' => [
                    'verify_peer' => true,
                    'verify_peer_name' => true,
                    'SNI_enabled' => true,
                    'peer_name' => $cert['subject']['CN'],
                    'allow_self_signed' => count($params['options']['ssl']['peer_certificate_chain']) == 1 && $cert['subject'] == $cert['issuer']
                ]
            ]);
        }
    }

    /**
     * Write to connection
     * @param string $buffer
     * @throws ConnectException
     */
    public function write(string $buffer)
    {
        if (Bolt::$debug)
            $this->printHex($buffer);

        $size = mb_strlen($buffer, '8bit');

        $time = microtime(true);
        while (0 < $size) {
            $sent = fwrite($this->stream, $buffer);

            if ($sent === false) {
                if (microtime(true) - $time >= $this->timeout)
                    throw new ConnectionTimeoutException('Connection timeout reached after ' . $this->timeout . ' seconds.');
                else
                    throw new ConnectException('Write error');
            }

            $buffer = mb_strcut($buffer, $sent, null, '8bit');
            $size -= $sent;
        }
    }

    /**
     * Read from connection
     * @param int $length
     * @return string
     * @throws ConnectException
     */
    public function read(int $length = 2048): string
    {
        $output = '';
        do {
            $readed = stream_get_contents($this->stream, $length - mb_strlen($output, '8bit'));

            if (stream_get_meta_data($this->stream)['timed_out'] ?? false)
                throw new ConnectionTimeoutException('Connection timeout reached after ' . $this->timeout . ' seconds.');
            if ($readed === false)
                throw new ConnectException('Read error');

            $output .= $readed;
        } while (mb_strlen($output, '8bit') < $length);

        if (Bolt::$debug)
            $this->printHex($output, 'S: ');

        return $output;
    }

    /**
     * Close connection
     */
    public function disconnect()
    {
        if (is_resource($this->stream))
            stream_socket_shutdown($this->stream, STREAM_SHUT_RDWR);
    }

    public function setTimeout(float $timeout)
    {
        parent::setTimeout($timeout);
        $this->configureTimeout();
    }

    /**
     * @return void
     * @throws ConnectException
     */
    private function configureTimeout(): void
    {
        if (is_resource($this->stream)) {
            $timeout = (int)floor($this->timeout);
            if (!stream_set_timeout($this->stream, $timeout, (int)floor(($this->timeout - $timeout) * 1000000))) {
                throw new ConnectException('Cannot set timeout on stream');
            }
        }
    }
}
