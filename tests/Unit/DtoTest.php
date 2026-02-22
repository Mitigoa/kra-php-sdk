<?php

declare(strict_types=1);

namespace KraPHP\Tests\Unit;

use KraPHP\DTOs\PinResult;
use KraPHP\DTOs\TccResult;
use KraPHP\DTOs\ESlipResult;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for DTO classes.
 */
class DtoTest extends TestCase
{
    /**
     * Test PinResult::fromResponse.
     */
    public function testPinResultFromResponse(): void
    {
        $data = [
            'kraPin' => 'A000000010',
            'taxpayerName' => 'ACME KENYA LIMITED',
            'pinStatus' => 'ACTIVE',
            'taxpayerType' => 'COMPANY',
            'registrationDate' => '2020-01-15',
            'email' => 'info@acme.co.ke',
            'phoneNumber' => '+254700000000',
            'address' => 'Nairobi',
            'taxObligations' => 'VAT,PAYE,CIT',
            'isValid' => true,
            'message' => 'PIN is valid',
        ];

        $result = PinResult::fromResponse($data);

        $this->assertEquals('A000000010', $result->kraPin);
        $this->assertEquals('ACME KENYA LIMITED', $result->taxpayerName);
        $this->assertEquals('ACTIVE', $result->pinStatus);
        $this->assertEquals('COMPANY', $result->taxpayerType);
        $this->assertTrue($result->isActive());
        $this->assertTrue($result->isValid);
    }

    /**
     * Test PinResult::isActive when inactive.
     */
    public function testPinResultIsActiveFalse(): void
    {
        $data = [
            'kraPin' => 'A000000010',
            'pinStatus' => 'INACTIVE',
        ];

        $result = PinResult::fromResponse($data);
        $this->assertFalse($result->isActive());
    }

    /**
     * Test PinResult::getTaxObligationsArray.
     */
    public function testPinResultGetTaxObligationsArray(): void
    {
        $data = [
            'taxObligations' => 'VAT,PAYE,CIT',
        ];

        $result = PinResult::fromResponse($data);
        $obligations = $result->getTaxObligationsArray();

        $this->assertIsArray($obligations);
        $this->assertContains('VAT', $obligations);
        $this->assertContains('PAYE', $obligations);
        $this->assertContains('CIT', $obligations);
    }

    /**
     * Test TccResult::fromResponse.
     */
    public function testTccResultFromResponse(): void
    {
        $data = [
            'kraPin' => 'A000000010',
            'taxpayerName' => 'ACME KENYA LIMITED',
            'tccNumber' => 'TCC-2024-XXXXX',
            'status' => 'VALID',
            'expiryDate' => '2024-12-31',
            'issueDate' => '2024-01-01',
            'isValid' => true,
            'message' => 'TCC is valid',
        ];

        $result = TccResult::fromResponse($data);

        $this->assertEquals('A000000010', $result->kraPin);
        $this->assertEquals('TCC-2024-XXXXX', $result->tccNumber);
        $this->assertEquals('VALID', $result->status);
        $this->assertTrue($result->isValidStatus());
        $this->assertFalse($result->isExpired());
        $this->assertFalse($result->isRevoked());
    }

    /**
     * Test TccResult::isValidStatus.
     */
    public function testTccResultIsValidStatus(): void
    {
        $result = TccResult::fromResponse(['status' => 'VALID']);
        $this->assertTrue($result->isValidStatus());

        $result = TccResult::fromResponse(['status' => 'EXPIRED']);
        $this->assertFalse($result->isValidStatus());

        $result = TccResult::fromResponse(['status' => 'REVOKED']);
        $this->assertFalse($result->isValidStatus());
    }

    /**
     * Test ESlipResult::fromResponse.
     */
    public function testESlipResultFromResponse(): void
    {
        $data = [
            'prn' => 'PRN123456789',
            'isValid' => true,
            'amount' => 25000.00,
            'paymentDate' => '2024-10-15',
            'taxType' => 'VAT',
            'payerPin' => 'A000000010',
            'payerName' => 'ACME KENYA LIMITED',
            'status' => 'PAID',
        ];

        $result = ESlipResult::fromResponse($data);

        $this->assertEquals('PRN123456789', $result->prn);
        $this->assertTrue($result->isValid);
        $this->assertEquals(25000.00, $result->amount);
        $this->assertEquals('VAT', $result->taxType);
    }

    /**
     * Test PinResult::toArray.
     */
    public function testPinResultToArray(): void
    {
        $data = [
            'kraPin' => 'A000000010',
            'taxpayerName' => 'Test Company',
            'pinStatus' => 'ACTIVE',
        ];

        $result = PinResult::fromResponse($data);
        $array = $result->toArray();

        $this->assertIsArray($array);
        $this->assertArrayHasKey('kraPin', $array);
        $this->assertArrayHasKey('taxpayerName', $array);
        $this->assertArrayHasKey('pinStatus', $array);
    }
}
