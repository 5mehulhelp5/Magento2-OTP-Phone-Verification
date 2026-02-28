<?php
declare(strict_types=1);

namespace IDangerous\PhoneOtpVerification\Plugin\GraphQl;

use Magento\CustomerGraphQl\Model\Customer\Address\CreateCustomerAddress;
use IDangerous\PhoneOtpVerification\Helper\AddressSaveContext;

/**
 * Sets explicit address save context before GraphQL createCustomerAddress,
 * so AddressRepositoryPlugin runs OTP verification.
 */
class SetExplicitAddressContextPlugin
{
    private AddressSaveContext $addressSaveContext;

    public function __construct(AddressSaveContext $addressSaveContext)
    {
        $this->addressSaveContext = $addressSaveContext;
    }

    /**
     * @param CreateCustomerAddress $subject
     * @param int $customerId
     * @param array $data
     * @return array
     */
    public function beforeExecute(CreateCustomerAddress $subject, int $customerId, array $data): array
    {
        $this->addressSaveContext->setExplicitAddressSave();
        return [$customerId, $data];
    }
}
