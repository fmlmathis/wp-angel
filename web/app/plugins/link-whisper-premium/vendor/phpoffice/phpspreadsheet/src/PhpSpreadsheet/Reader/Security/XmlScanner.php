<?php

namespace LWVendor\PhpOffice\PhpSpreadsheet\Reader\Security;

use LWVendor\PhpOffice\PhpSpreadsheet\Reader;
class XmlScanner
{
    /**
     * String used to identify risky xml elements.
     *
     * @var string
     */
    private $pattern;
    /** @var ?callable */
    private $callback;
    /** @var ?bool */
    private static $libxmlDisableEntityLoaderValue;
    /**
     * @var bool
     */
    private static $shutdownRegistered = \false;
    public function __construct(string $pattern = '<!DOCTYPE')
    {
        $this->pattern = $pattern;
        $this->disableEntityLoaderCheck();
        // A fatal error will bypass the destructor, so we register a shutdown here
        if (!self::$shutdownRegistered) {
            self::$shutdownRegistered = \true;
            \register_shutdown_function([__CLASS__, 'shutdown']);
        }
    }
    public static function getInstance(Reader\IReader $reader) : self
    {
        $pattern = $reader instanceof Reader\Html ? '<!ENTITY' : '<!DOCTYPE';
        return new self($pattern);
    }
    /**
     * @codeCoverageIgnore
     */
    public static function threadSafeLibxmlDisableEntityLoaderAvailability() : bool
    {
        if (\PHP_MAJOR_VERSION === 7) {
            switch (\PHP_MINOR_VERSION) {
                case 2:
                    return \PHP_RELEASE_VERSION >= 1;
                case 1:
                    return \PHP_RELEASE_VERSION >= 13;
                case 0:
                    return \PHP_RELEASE_VERSION >= 27;
            }
            return \true;
        }
        return \false;
    }
    /**
     * @codeCoverageIgnore
     */
    private function disableEntityLoaderCheck() : void
    {
        if (\PHP_VERSION_ID < 80000) {
            $libxmlDisableEntityLoaderValue = \libxml_disable_entity_loader(\true);
            if (self::$libxmlDisableEntityLoaderValue === null) {
                self::$libxmlDisableEntityLoaderValue = $libxmlDisableEntityLoaderValue;
            }
        }
    }
    /**
     * @codeCoverageIgnore
     */
    public static function shutdown() : void
    {
        if (self::$libxmlDisableEntityLoaderValue !== null && \PHP_VERSION_ID < 80000) {
            \libxml_disable_entity_loader(self::$libxmlDisableEntityLoaderValue);
            self::$libxmlDisableEntityLoaderValue = null;
        }
    }
    public function __destruct()
    {
        self::shutdown();
    }
    public function setAdditionalCallback(callable $callback) : void
    {
        $this->callback = $callback;
    }
    /** @param mixed $arg */
    private static function forceString($arg) : string
    {
        return \is_string($arg) ? $arg : '';
    }
    /**
     * @param string $xml
     *
     * @return string
     */
    private function toUtf8($xml)
    {
        $charset = $this->findCharSet($xml);
        if ($charset !== 'UTF-8') {
            $xml = self::forceString(\mb_convert_encoding($xml, 'UTF-8', $charset));
            $charset = $this->findCharSet($xml);
            if ($charset !== 'UTF-8') {
                throw new Reader\Exception('Suspicious Double-encoded XML, spreadsheet file load() aborted to prevent XXE/XEE attacks');
            }
        }
        return $xml;
    }
    private function findCharSet(string $xml) : string
    {
        $patterns = ['/encoding\\s*=\\s*"([^"]*]?)"/', "/encoding\\s*=\\s*'([^']*?)'/"];
        foreach ($patterns as $pattern) {
            if (\preg_match($pattern, $xml, $matches)) {
                return \strtoupper($matches[1]);
            }
        }
        return 'UTF-8';
    }
    /**
     * Scan the XML for use of <!ENTITY to prevent XXE/XEE attacks.
     *
     * @param false|string $xml
     *
     * @return string
     */
    public function scan($xml)
    {
        $xml = "{$xml}";
        $this->disableEntityLoaderCheck();
        $xml = $this->toUtf8($xml);
        // Don't rely purely on libxml_disable_entity_loader()
        $pattern = '/\\0?' . \implode(
            '\\0?',
            /** @scrutinizer ignore-type */
            \str_split($this->pattern)
        ) . '\\0?/';
        if (\preg_match($pattern, $xml)) {
            throw new Reader\Exception('Detected use of ENTITY in XML, spreadsheet file load() aborted to prevent XXE/XEE attacks');
        }
        if ($this->callback !== null) {
            $xml = \call_user_func($this->callback, $xml);
        }
        return $xml;
    }
    /**
     * Scan theXML for use of <!ENTITY to prevent XXE/XEE attacks.
     *
     * @param string $filestream
     *
     * @return string
     */
    public function scanFile($filestream)
    {
        return $this->scan(\file_get_contents($filestream));
    }
}
