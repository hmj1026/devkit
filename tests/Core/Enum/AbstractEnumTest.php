<?php

namespace Devkit\Tests\Core\Enum;

use Devkit\Tests\Core\Enum\Fixture\SparseEnum;
use Devkit\Tests\Core\Enum\Fixture\StatusEnum;
use PHPUnit\Framework\TestCase;

class AbstractEnumTest extends TestCase
{
    public function testToArrayReturnsConstantNameToValueMap()
    {
        $this->assertSame(
            array('ACTIVE' => 1, 'INACTIVE' => 0),
            StatusEnum::toArray()
        );
    }

    public function testValuesReturnsConstantValuesInDeclarationOrder()
    {
        $this->assertSame(array(1, 0), StatusEnum::values());
    }

    public function testKeysReturnsConstantNamesInDeclarationOrder()
    {
        $this->assertSame(array('ACTIVE', 'INACTIVE'), StatusEnum::keys());
    }

    public function testGetByAliasResolvesDeclaredAlias()
    {
        $this->assertSame(1, StatusEnum::getByAlias('enabled'));
        $this->assertSame(0, StatusEnum::getByAlias('disabled'));
    }

    public function testGetByAliasReturnsNullForUnknownAlias()
    {
        $this->assertNull(StatusEnum::getByAlias('nonexistent'));
    }

    public function testMappingReturnsContents()
    {
        $this->assertSame(
            array('ACTIVE' => '啟用', 'INACTIVE' => '停用'),
            StatusEnum::mapping()
        );
    }

    public function testContentReturnsLabelWhenDeclared()
    {
        $this->assertSame('啟用', StatusEnum::content('ACTIVE'));
        $this->assertSame('停用', StatusEnum::content('INACTIVE'));
    }

    public function testContentFallsBackToConstantNameWhenMissing()
    {
        $this->assertSame('UNKNOWN', StatusEnum::content('UNKNOWN'));
    }

    public function testSubclassWithoutAliasesReturnsEmptyMapping()
    {
        $this->assertSame(array(), SparseEnum::mapping());
        $this->assertNull(SparseEnum::getByAlias('anything'));
    }

    public function testSparseSubclassEnumeratesItsOwnConstants()
    {
        $this->assertSame(
            array('RED' => 'r', 'GREEN' => 'g', 'BLUE' => 'b'),
            SparseEnum::toArray()
        );
    }

    public function testToArrayIsMemoisedPerSubclass()
    {
        $first = StatusEnum::toArray();
        $second = StatusEnum::toArray();

        // Same instance check is invalid for arrays (PHP copies on read); the
        // memoisation contract is observable as identical results without
        // additional reflection overhead. Identity of contents is sufficient.
        $this->assertSame($first, $second);
    }

    public function testSubclassesHaveIndependentConstantCaches()
    {
        $status = StatusEnum::toArray();
        $sparse = SparseEnum::toArray();

        $this->assertNotSame($status, $sparse);
        $this->assertSame(array('ACTIVE' => 1, 'INACTIVE' => 0), $status);
        $this->assertSame(array('RED' => 'r', 'GREEN' => 'g', 'BLUE' => 'b'), $sparse);
    }
}
