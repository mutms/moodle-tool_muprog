<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace enrol_programs\local\commerce;

use advanced_testcase;
use enrol_programs\local\content\top;
use enrol_programs\local\program;
use enrol_programs\local\source\ecommerce;
use enrol_programs\local\source\manual;
use local_commerce\local\benefit;
use local_commerce\local\holdkey;
use local_commerce\local\price;
use local_commerce\local\product;
use local_vouchers\local\voucher;
use local_commerce\local\beneficiary;
use moodle_url;

/**
 * Handler tests.
 *
 * @group openlms
 * @group local_commerce
 * @group opensourcelearning
 *
 * @package enrol_programs
 * @author Andrew Hancox <andrewdchancox@googlemail.com>
 * @author Open Source Learning <enquiries@opensourcelearning.co.uk>
 * @link https://opensourcelearning.co.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright 2021, Andrew Hancox
 */
class benefithandler_test extends advanced_testcase {

    protected function setUp(): void {
        global $CFG;
        $CFG->enablecompletion = true;
        $this->resetAfterTest();

        if (!\enrol_programs\local\source\ecommerce::is_commerce_available()) {
            $this->markTestSkipped('Commerce not available');
        }

        \local_commerce\local\util::enable_commerce();
    }

    /**
     * Check benefit becomes unavailable when maxenrolled limit is hit.
     */
    public function test_benefitcurrentlyavailable() {
        $user = static::getDataGenerator()->create_user();
        $user2 = static::getDataGenerator()->create_user();

        $now = time();

        /** @var \enrol_programs_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('enrol_programs');
        $program = $generator->create_program();

        ecommerce::update_source((object)[
            'programid' => $program->id,
            'type' => 'ecommerce',
            'enable' => 1,
            'ecommerce_maxusers' => 2,
        ]);

        $product = new product();
        $product->set('name', 'test product');
        $product->create();
        $benefit = benefit::get_record(['pluginname' => 'enrol_programs', 'instance' => $program->id]);
        $product->set_benefits([$benefit->get('id')]);

        $price = new price();
        $price->set('productid', $product->get('id'));
        $price->create();

        $holdkey = \local_commerce\local\holdkey::generateholdkey('paymentprovider_nullprovider', '1', $product->get('id'),
            $user->id, time() + WEEKSECS, json_encode(['quantity' => 1]));
        $holdkey->holdbenefits();
        $product->grantbenefitstouser($now - 1, $now + 1000, $holdkey);

        static::assertTrue($product->allbenefitscurrentlyavailable(1));

        $holdkey2 = holdkey::generateholdkey('paymentprovider_nullprovider', '2', $product->get('id'), $user2->id, time() + WEEKSECS, json_encode(['quantity' => 1]));
        $holdkey2->holdbenefits();
        static::assertFalse($product->allbenefitscurrentlyavailable(1));
        $product->grantbenefitstouser($now - 1, $now + 1000, $holdkey2);

        static::assertFalse($product->allbenefitscurrentlyavailable(1));
    }

    /**
     * Check benefit becomes unavailable when maxenrolled limit is hit when using a voucher.
     */
    public function test_benefitcurrentlyavailable_voucher() {
        $user = static::getDataGenerator()->create_user();
        $user2 = static::getDataGenerator()->create_user();
        $user3 = static::getDataGenerator()->create_user();

        $now = time();

        /** @var \enrol_programs_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('enrol_programs');
        $program = $generator->create_program();

        ecommerce::update_source((object)[
            'programid' => $program->id,
            'type' => 'ecommerce',
            'enable' => 1,
            'ecommerce_maxusers' => 3,
        ]);

        $product = new product();
        $product->set('name', 'test product');
        $product->create();
        $benefit = benefit::get_record(['pluginname' => 'enrol_programs', 'instance' => $program->id]);
        $product->set_benefits([$benefit->get('id')]);

        $price = new price();
        $price->set('productid', $product->get('id'));
        $price->create();

        $holdkey = holdkey::generateholdkey('paymentprovider_nullprovider', '1', $product->get('id'), $user2->id, time() + WEEKSECS, json_encode(['quantity' => 1]));
        $holdkey->holdbenefits();
        $product->grantbenefitstovoucher($now - 1, $now + 1000, $holdkey);

        static::assertTrue($product->allbenefitscurrentlyavailable(1));
        static::assertFalse($product->allbenefitscurrentlyavailable(3));

        $holdkey2 = holdkey::generateholdkey('paymentprovider_nullprovider', '2', $product->get('id'), $user2->id, time() + WEEKSECS, json_encode(['quantity' => 1]));
        $holdkey2->holdbenefits();
        static::assertTrue($product->allbenefitscurrentlyavailable(1));
        static::assertFalse($product->allbenefitscurrentlyavailable(2));

        $beneficiary = $product->grantbenefitstovoucher($now - 1, $now + 1000, $holdkey2);
        static::assertTrue($product->allbenefitscurrentlyavailable(1));
        static::assertFalse($product->allbenefitscurrentlyavailable(2));

        $voucher = new voucher(['id' => $beneficiary->get('beneficiaryinstanceid')]);
        $voucher->redeem($user3);
        static::assertTrue($product->allbenefitscurrentlyavailable(1));
        static::assertFalse($product->allbenefitscurrentlyavailable(2));
    }

    /**
     * Check basic benefit handler functiinality.
     */
    public function test_benefithandlerbasics() {
        global $DB;

        $user = static::getDataGenerator()->create_user();
        $enrolleduser = static::getDataGenerator()->create_user();

        /** @var \enrol_programs_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('enrol_programs');
        $program = $generator->create_program(['sources' => ['manual' => []]]);

        ecommerce::update_source((object)[
            'programid' => $program->id,
            'type' => 'ecommerce',
            'enable' => 1,
            'ecommerce_maxusers' => 2,
        ]);


        $manualsource = $DB->get_record('enrol_programs_sources', ['programid' => $program->id, 'type' => 'manual'], '*', MUST_EXIST);
        manual::allocate_users($program->id, $manualsource->id, [$enrolleduser->id]);

        $product = new product();
        $product->set('name', 'test product');
        $product->create();
        $benefit = benefit::get_record(['pluginname' => 'enrol_programs', 'instance' => $program->id]);
        $product->set_benefits([$benefit->get('id')]);
        $handler = $benefit->getbenefithandler();

        $alert = $product->get_prerequisitewarnings();
        static::assertCount(0, $alert);

        static::assertEquals('Program 1: Program allocation', $handler->getbenefitname());

        static::assertFalse($handler->benefitcurrentlypossessed($user->id));
        static::assertTrue($handler->benefitcurrentlypossessed($enrolleduser->id));

        static::assertEquals(
            [10, new moodle_url('/enrol/programs/my/program.php', ['id' => $program->id])],
            $handler->getredirecturl(0)
        );
    }

    public function test_releasehold() {
        /** @var \enrol_programs_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('enrol_programs');
        $program = $generator->create_program();

        ecommerce::update_source((object)[
            'programid' => $program->id,
            'type' => 'ecommerce',
            'enable' => 1,
            'ecommerce_maxusers' => 2,
        ]);

        $user = static::getDataGenerator()->create_user();

        $product = new product();
        $product->set('name', 'test product');
        $product->create();
        $benefit = benefit::get_record(['pluginname' => 'enrol_programs', 'instance' => $program->id]);
        $product->set_benefits([$benefit->get('id')]);

        $price = new price();
        $price->set('productid', $product->get('id'));
        $price->create();

        $holdkey = holdkey::generateholdkey('paymentprovider_nullprovider', '1', $product->get('id'), $user->id, time() + WEEKSECS, json_encode(['quantity' => 2]));
        $holdkey->holdbenefits();

        static::assertFalse($product->allbenefitscurrentlyavailable(2));

        $holdkey->releasebenefits();
        /*
        Test needs updating once functionality to reduce quantity held by key is implemented.

        static::assertFalse($product->allbenefitscurrentlyavailable(2));
        */
        static::assertTrue($product->allbenefitscurrentlyavailable(1));

        $holdkey->releasebenefits(1);

        $holdkey = holdkey::generateholdkey('paymentprovider_nullprovider', '12', $product->get('id'), $user->id, time() + WEEKSECS, json_encode(['quantity' => 2]));
        $holdkey->holdbenefits();

        static::assertFalse($product->allbenefitscurrentlyavailable(2));

        $holdkey->releasebenefits();

        static::assertTrue($product->allbenefitscurrentlyavailable(2));
    }

    public function test_includedcourseenrolments() {
        /** @var \enrol_programs_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('enrol_programs');
        $program = $generator->create_program();

        ecommerce::update_source((object)[
            'programid' => $program->id,
            'type' => 'ecommerce',
            'enable' => 1,
        ]);

        $benefit = benefit::get_record(['pluginname' => 'enrol_programs', 'instance' => $program->id]);
        $handler = $benefit->getbenefithandler();

        $this->assertCount(0, $handler->includedcourseenrolments());

        $course1 = static::getDataGenerator()->create_course(['enablecompletion' => true]);
        $course2 = static::getDataGenerator()->create_course(['enablecompletion' => true]);

        $top = top::load($program->id);
        $top->append_course($top, $course1->id);
        $top->append_course($top, $course2->id);

        $this->assertCount(2, $handler->includedcourseenrolments());
    }

    public function test_prerequisites() {
        /** @var \enrol_programs_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('enrol_programs');
        $program = $generator->create_program();

        ecommerce::update_source((object)[
            'programid' => $program->id,
            'type' => 'ecommerce',
            'enable' => 1,
        ]);

        $cohort1 = $this->getDataGenerator()->create_cohort();
        $cohort2 = $this->getDataGenerator()->create_cohort();

        $benefit = benefit::get_record(['pluginname' => 'enrol_programs', 'instance' => $program->id]);
        $handler = $benefit->getbenefithandler();

        $this->assertEmpty($handler->get_prerequisitewarnings());

        program::update_program_visibility(
            (object)['id' => $program->id, 'public' => 1, 'cohorts' => [$cohort1->id, $cohort2->id]]);

        $this->assertEquals(['Users must be a member of one of the following cohorts: Cohort 1, Cohort 2'], $handler->get_prerequisitewarnings());

        $user = static::getDataGenerator()->create_user();

        $this->assertFalse($handler->prerequisitesmet($user->id, []));

        cohort_add_member($cohort1->id, $user->id);

        $this->assertTrue($handler->prerequisitesmet($user->id, []));
    }
}
