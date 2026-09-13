<?php
namespace App\Tests\Service;

use App\Service\RutService;
use PHPUnit\Framework\TestCase;

class RutServiceTest extends TestCase
{
    private RutService $service;

    protected function setUp(): void
    {
        $this->service = new RutService();
    }

    /**
     * @dataProvider validRutsProvider
     */
    public function testValidateAcceptsValidRuts(string $rut): void
    {
        $this->assertTrue($this->service->validate($rut), "RUT '$rut' debería ser válido");
    }

    public static function validRutsProvider(): array
    {
        return [
            'RUT con DV 0' => ['12.345.670-0'],
            'RUT con DV K' => ['11.111.111-K'],
            'RUT sin formato' => ['123456700'],
            'RUT con guion' => ['12345670-0'],
            'RUT conocido válido' => ['76.123.456-7'],
        ];
    }

    /**
     * @dataProvider invalidRutsProvider
     */
    public function testValidateRejectsInvalidRuts(string $rut): void
    {
        $this->assertFalse($this->service->validate($rut), "RUT '$rut' debería ser inválido");
    }

    public static function invalidRutsProvider(): array
    {
        return [
            'DV incorrecto' => ['12.345.678-0'],
            'Muy corto' => ['123-4'],
            'Letras en cuerpo' => ['AB123456-7'],
            'Vacío' => [''],
            'Solo guion' => ['-'],
        ];
    }

    public function testFormatStandardizesRut(): void
    {
        $this->assertEquals('12.345.670-0', $this->service->format('123456700'));
        $this->assertEquals('11.111.111-K', $this->service->format('11111111k'));
        $this->assertEquals('76.123.456-7', $this->service->format('761234567'));
    }

    public function testCleanRemovesFormatting(): void
    {
        $this->assertEquals('123456700', $this->service->clean('12.345.670-0'));
        $this->assertEquals('11111111K', $this->service->clean('11.111.111-k'));
    }

    public function testCalculateDvReturnsCorrectDigit(): void
    {
        $this->assertEquals('0', $this->service->calculateDv('12345670'));
        $this->assertEquals('K', $this->service->calculateDv('11111111'));
    }
}