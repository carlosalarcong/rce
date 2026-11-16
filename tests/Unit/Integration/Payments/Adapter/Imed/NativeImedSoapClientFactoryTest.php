<?php

declare(strict_types=1);

namespace App\Tests\Unit\Integration\Payments\Adapter\Imed;

use App\Integration\Payments\Adapter\Imed\NativeImedSoapClientFactory;
use App\Integration\Payments\Contract\ProviderUnavailableException;
use PHPUnit\Framework\TestCase;

final class NativeImedSoapClientFactoryTest extends TestCase
{
    public function testUnreachableWsdlIsProviderUnavailable(): void
    {
        $client = new NativeImedSoapClientFactory(1)->create(sys_get_temp_dir().'/rce-no-existe-'.bin2hex(random_bytes(4)).'.wsdl');

        $this->expectException(ProviderUnavailableException::class);
        $this->expectExceptionMessage('WSDL not reachable');
        $client->call('VerResulEmi', []);
    }

    public function testTimeoutMustBePositive(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new NativeImedSoapClientFactory(0);
    }
}
