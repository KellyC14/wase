<?php
/**
 * Created by PhpStorm.
 * User: serge
 * Date: 2/18/19
 * Time: 10:48 AM
 */

/**
 * Test various methods in WaseAppointment
 */

use PHPUnit\Framework\TestCase;

final class WaseCalendarTest extends TestCase
{
    public function testCalendarCanBeCreated()
    {
        $this->assertInstanceOf('WaseCalendar', new WaseCalendar(
            'load',
            array("calendarid" => 2)
        ));

    }


}
