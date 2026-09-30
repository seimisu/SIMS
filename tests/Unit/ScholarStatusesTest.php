<?php

namespace Tests\Unit;

use App\Support\ScholarStatuses;
use PHPUnit\Framework\TestCase;

class ScholarStatusesTest extends TestCase
{
    public function test_legacy_status_aliases_are_normalized(): void
    {
        $this->assertSame('LEAVE OF ABSENCE', ScholarStatuses::normalize('LOA'));
        $this->assertSame('WITHDRAWN', ScholarStatuses::normalize('withdrew'));
        $this->assertSame('ONGOING', ScholarStatuses::normalize(' ongoing '));
    }

    public function test_restricted_scholar_statuses_block_services(): void
    {
        foreach (ScholarStatuses::BLOCKED_FROM_SERVICES as $status) {
            $this->assertTrue(ScholarStatuses::blocksServices($status));
        }

        $this->assertFalse(ScholarStatuses::blocksServices('NEW'));
        $this->assertFalse(ScholarStatuses::blocksServices('ONGOING'));
        $this->assertFalse(ScholarStatuses::blocksServices('GRADUATING'));
        $this->assertFalse(ScholarStatuses::blocksServices('GRADUATED'));
    }
}
