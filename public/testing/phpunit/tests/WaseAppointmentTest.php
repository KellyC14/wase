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

final class WaseAppointmentTest extends TestCase
{
    public function testAppointmentCanBeCreated()
    {
        $this->assertInstanceOf('WaseAppointment', new WaseAppointment(
            'create',
            array("name" => "serge")
        ));

    }


}
