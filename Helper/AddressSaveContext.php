<?php
declare(strict_types=1);

namespace IDangerous\PhoneOtpVerification\Helper;

use Magento\Framework\App\Area;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\State;
use Magento\Framework\Registry;

/**
 * Determines if the current AddressRepository->save() call is from an explicit user intent
 * (shipping-information, address book save) vs internal operations (cart creation, etc).
 * OTP verification should only run for explicit address saves.
 */
class AddressSaveContext
{
    private const REGISTRY_KEY = 'idangerous_phone_otp_explicit_address_save';

    private Config $configHelper;
    private RequestInterface $request;
    private State $appState;
    private Registry $registry;
    private AppVersionChecker $appVersionChecker;

    public function __construct(
        Config $configHelper,
        RequestInterface $request,
        State $appState,
        Registry $registry,
        AppVersionChecker $appVersionChecker
    ) {
        $this->configHelper = $configHelper;
        $this->request = $request;
        $this->appState = $appState;
        $this->registry = $registry;
        $this->appVersionChecker = $appVersionChecker;
    }

    /**
     * Mark that the current request is an explicit address save (shipping-information or address book).
     * Call this before operations that should trigger OTP verification.
     */
    public function setExplicitAddressSave(): void
    {
        $this->registry->register(self::REGISTRY_KEY, true);
    }

    /**
     * Clear the explicit address save flag (call after the operation completes).
     */
    public function clearExplicitAddressSave(): void
    {
        $this->registry->unregister(self::REGISTRY_KEY);
    }

    /**
     * Whether OTP verification should run for this AddressRepository->save() call.
     * Returns true only for: shipping-information, address book save (REST/GraphQL/frontend).
     * Returns false for: cart creation, quote sync, and other internal address saves.
     */
    public function isExplicitAddressSave(): bool
    {
        // Frontend (MVC): always run - address form, checkout
        if (!$this->appVersionChecker->isApiRequest()) {
            return true;
        }

        // REST API: path-based check
        $path = $this->getRequestPath();
        if (stripos($path, 'shipping-information') !== false) {
            return true;
        }
        if (stripos($path, 'addresses') !== false && stripos($path, 'carts') === false) {
            return true;
        }
        // Cart operations (create, assign, etc.) - skip OTP
        if (stripos($path, 'carts') !== false) {
            return false;
        }

        // GraphQL: Registry flag set by CreateCustomerAddress/UpdateCustomerAddress/ShippingInformationManagement
        return (bool) $this->registry->registry(self::REGISTRY_KEY);
    }

    private function getRequestPath(): string
    {
        try {
            $path = $this->request->getPathInfo();
            return $path !== null ? (string) $path : '';
        } catch (\Exception $e) {
            return '';
        }
    }
}
