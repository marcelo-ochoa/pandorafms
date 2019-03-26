<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Models\VisualConsole\items\Group;

/**
 * Test class
 */
class GroupTest extends TestCase
{


    public function testCanBeCreatedFromValidUserStructure(): void
    {
        $this->assertInstanceOf(
            Group::class,
            Group::fromArray(
                [
                    'id'       => 13,
                    'type'     => GROUP_ITEM,
                    'width'    => '600',
                    'height'   => '500',
                    'imageSrc' => 'image.jpg',
                    'groupId'  => 12,
                ]
            )
        );

        $this->assertInstanceOf(
            Group::class,
            Group::fromArray(
                [
                    'id'       => 1004,
                    'type'     => GROUP_ITEM,
                    'width'    => '600',
                    'height'   => '500',
                    'image'    => 'test_image.png',
                    'id_group' => 0,
                ]
            )
        );
    }


    public function testCannotBeCreatedWithInvalidImageSrc(): void
    {
        $this->expectException(InvalidArgumentException::class);
        // Invalid imageSrc.
        Group::fromArray(
            [
                'id'            => 7,
                'type'          => GROUP_ITEM,
                'label'         => null,
                'labelPosition' => 'up',
                'isLinkEnabled' => true,
                'isOnTop'       => false,
                'parentId'      => null,
                'width'         => '0',
                'height'        => '0',
                'x'             => -666,
                'y'             => 76,
                'imageSrc'      => '',
                'id_group'      => 0,
            ]
        );
        // Missing imageSrc.
        Group::fromArray(
            [
                'id'            => 7,
                'type'          => GROUP_ITEM,
                'label'         => null,
                'labelPosition' => 'up',
                'isLinkEnabled' => true,
                'isOnTop'       => false,
                'parentId'      => null,
                'width'         => '0',
                'height'        => '0',
                'x'             => -666,
                'y'             => 76,
                'id_group'      => 0,
            ]
        );
    }


    public function testCannotBeCreatedWithInvalidGroupId(): void
    {
        $this->expectException(InvalidArgumentException::class);
        // Invalid groupId.
        Group::fromArray(
            [
                'id'            => 7,
                'type'          => GROUP_ITEM,
                'label'         => null,
                'labelPosition' => 'up',
                'isLinkEnabled' => true,
                'isOnTop'       => false,
                'parentId'      => null,
                'width'         => '0',
                'height'        => '0',
                'x'             => -666,
                'y'             => 76,
                'imageSrc'      => 'test.jpg',
                'groupId'       => 'bar',
            ]
        );
        // Missing groupId.
        Group::fromArray(
            [
                'id'            => 7,
                'type'          => GROUP_ITEM,
                'label'         => null,
                'labelPosition' => 'up',
                'isLinkEnabled' => true,
                'isOnTop'       => false,
                'parentId'      => null,
                'width'         => '0',
                'height'        => '0',
                'x'             => -666,
                'y'             => 76,
                'imageSrc'      => 'test.jpg',
            ]
        );
    }


    public function testContainerIsRepresentedAsJson(): void
    {
        $this->assertEquals(
            '{"id":1,"type"11,"label":null,"labelPosition":"down","isLinkEnabled":false,"isOnTop":false,"parentId":null,"aclGroupId":null,"width":0,"height":0,"x":0,"y":0,"imageSrc":"image.jpg","groupId":0}',
            Group::fromArray(
                [
                    'id'       => 1,
                    'type'     => GROUP_ITEM,
                    'width'    => '0',
                    'height'   => '0',
                    'imageSrc' => 'image.jpg',
                    'groupId'  => 0,
                ]
            )
        );
    }


}
