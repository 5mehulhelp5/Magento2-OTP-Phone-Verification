<?php
declare(strict_types=1);

namespace IDangerous\PhoneOtpVerification\Helper;

use Magento\Framework\App\Area;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\State;

/**
 * Validates headless app version for OTP enforcement.
 * If min version config is null/empty → enforce OTP normally.
 * If min version set and client version missing/low → pull back: do NOT enforce OTP, let user through.
 * Only applies when request is from API (GraphQL/REST); frontend requests bypass check.
 */
class AppVersionChecker
{
    private const HEADER_VERSION = 'X-App-Version';
    private const PARAM_VERSION = 'mobVer'; // common mobile param
    private const PARAM_ALT = 'appVersion';

    /** @var Config */
    private $configHelper;

    /** @var RequestInterface */
    private $request;

    /** @var State */
    private $appState;

    public function __construct(
        Config $configHelper,
        RequestInterface $request,
        State $appState
    ) {
        $this->configHelper = $configHelper;
        $this->request = $request;
        $this->appState = $appState;
    }

    /** Rejection message when app version is too old or missing */
    public const REJECTION_MESSAGE = 'App update required. Please update to the latest version.';

    /**
     * Whether the current request is from API (GraphQL or REST). Frontend requests skip version check.
     */
    public function isApiRequest(): bool
    {
        try {
            $area = $this->appState->getAreaCode();
            return $area === Area::AREA_GRAPHQL
                || $area === Area::AREA_WEBAPI_REST
                || $area === Area::AREA_WEBAPI_SOAP;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * True = enforce OTP for address/checkout. False = pull back, skip OTP, let user through.
     */
    public function isAllowedForAddressCheckout(): bool
    {
        return $this->check('address_checkout');
    }

    /**
     * True = enforce OTP for registration/account. False = pull back, skip OTP, let user through.
     */
    public function isAllowedForRegistrationAccount(): bool
    {
        return $this->check('registration_account');
    }

    private function check(string $scope): bool
    {
        if (!$this->isApiRequest()) {
            return true; // frontend: no version gate
        }

        $minVersion = $scope === 'address_checkout'
            ? $this->configHelper->getMinVersionAddressCheckout()
            : $this->configHelper->getMinVersionRegistrationAccount();

        if ($minVersion === '') {
            return true; // no check, all operations allowed
        }

        $clientVersion = $this->getClientVersion();
        if ($clientVersion === '') {
            return false; // version required but not sent → pull back
        }

        return $this->isVersionAtLeast($clientVersion, $minVersion);
    }

    /**
     * Compare versions including optional build number (e.g. 4.0.18+86).
     * Client without build is treated as build 0.
     */
    private function isVersionAtLeast(string $client, string $min): bool
    {
        $minParts = $this->parseVersion($min);
        $clientParts = $this->parseVersion($client);

        $baseCmp = version_compare($clientParts['base'], $minParts['base']);
        if ($baseCmp > 0) {
            return true;
        }
        if ($baseCmp < 0) {
            return false;
        }

        return $clientParts['build'] >= $minParts['build'];
    }

    private function parseVersion(string $v): array
    {
        $v = trim($v);
        // URL query param'da + boşluğa decode edilir: mobVer=4.0.18+87 → "4.0.18 87"
        $v = preg_replace('/\s+(\d+)$/', '+$1', $v);
        $base = $v;
        $build = 0;

        if (preg_match('/^(.+)\+(\d+)$/', $v, $m)) {
            $base = trim($m[1]);
            $build = (int)$m[2];
        }

        return ['base' => $base, 'build' => $build];
    }

    private function getClientVersion(): string
    {
        $value = '';

        if (method_exists($this->request, 'getHeader')) {
            $header = $this->request->getHeader(self::HEADER_VERSION);
            if ($header !== false && $header !== null) {
                $value = is_object($header) && method_exists($header, 'getFieldValue')
                    ? $header->getFieldValue()
                    : (string)$header;
            }
        }

        if (trim($value) === '' && isset($_SERVER['HTTP_X_APP_VERSION'])) {
            $value = (string)$_SERVER['HTTP_X_APP_VERSION'];
        }

        if (trim($value) !== '') {
            return trim($value);
        }

        $param = $this->request->getParam(self::PARAM_VERSION) ?? $this->request->getParam(self::PARAM_ALT);
        if ($param !== null && trim((string)$param) !== '') {
            return trim((string)$param);
        }

        return '';
    }
}
