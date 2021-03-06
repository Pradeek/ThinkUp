<?php
/**
 *
 * ThinkUp/tests/TestOfInsightBaselineMySQLDAO.php
 *
 * Copyright (c) 2012-2015 Gina Trapani
 *
 * LICENSE:
 *
 * This file is part of ThinkUp (http://thinkup.com).
 *
 * ThinkUp is free software: you can redistribute it and/or modify it under the terms of the GNU General Public
 * License as published by the Free Software Foundation, either version 2 of the License, or (at your option) any
 * later version.
 *
 * ThinkUp is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied
 * warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more
 * details.
 *
 * You should have received a copy of the GNU General Public License along with ThinkUp.  If not, see
 * <http://www.gnu.org/licenses/>.
 *
 * @license http://www.gnu.org/licenses/gpl.html
 * @copyright 2012-2015 Gina Trapani
 * @author Gina Trapani
 */
require_once dirname(__FILE__).'/init.tests.php';
require_once THINKUP_WEBAPP_PATH.'_lib/extlib/simpletest/autorun.php';
require_once THINKUP_WEBAPP_PATH.'config.inc.php';

class TestOfInsightBaselineMySQLDAO extends ThinkUpUnitTestCase {
    public function setUp() {
        parent::setUp();
        $this->builders = self::buildData();
    }

    protected function buildData() {
        $builders = array();
        $builders[] = FixtureBuilder::build('insight_baselines', array('date'=>'2012-05-01',
        'slug'=>'avg_replies_per_week', 'instance_id'=>1, 'value'=>51));
        return $builders;
    }

    public function tearDown() {
        $this->builders = null;
        parent::tearDown();
    }

    public function testDoesInsightBaselineExistBefore() {
        $dao = new InsightBaselineMySQLDAO();
        $result = $dao->doesInsightBaselineExistBefore("avg_replies_per_week", 1, '2013-06-01');
        $this->assertTrue($result);

        $result = $dao->doesInsightBaselineExistBefore("avg_replies_per_week", 1, '2011-06-01');
        $this->assertFalse($result);

        $result = $dao->doesInsightBaselineExistBefore("some_fake_thing", 1, '2014-06-01');
        $this->assertFalse($result);
    }

    public function testDoesInsightBaselineExist() {
        $dao = new InsightBaselineMySQLDAO();
        $result = $dao->doesInsightBaselineExist("avg_replies_per_week", 1);
        $this->assertTrue($result);
        $result = $dao->doesInsightBaselineExist("avg_replies_per_week", 10);
        $this->assertFalse($result);
        $result = $dao->doesInsightBaselineExist("yo_yo_yooo", 1);
        $this->assertFalse($result);
    }

    public function testInsertInsightBaseline() {
        $dao = new InsightBaselineMySQLDAO();
        //date specified
        $result = $dao->insertInsightBaseline('avg_replies_per_week', 1, 51, '2012-05-05');
        $this->assertTrue($result);

        $result = $dao->getInsightBaseline('avg_replies_per_week', 1, '2012-05-05');
        $this->assertEqual($result->value, 51);

        //inserting existing baseline should update
        $result = $dao->insertInsightBaseline('avg_replies_per_week', 1, 50, '2012-05-05');
        $this->assertTrue($result);

        //assert update was successful
        $result = $dao->getInsightBaseline('avg_replies_per_week', 1, '2012-05-05');
        $this->assertEqual($result->value, 50);

        //no date specified
        $result = $dao->insertInsightBaseline('avg_replies_per_week', 1, 4551);
        $this->assertTrue($result);
        $result = $dao->getInsightBaseline('avg_replies_per_week', 1);
        $this->assertEqual($result->value, 4551);
    }

    public function testGetInsightBaseline() {
        $dao = new InsightBaselineMySQLDAO();
        $result = $dao->getInsightBaseline('avg_replies_per_week', 1, '2012-05-01');
        $this->assertIsA($result, 'InsightBaseline');
        $this->assertEqual($result->slug, 'avg_replies_per_week');
        $this->assertEqual($result->date, '2012-05-01');
        $this->assertEqual($result->instance_id, 1);
        $this->assertEqual($result->value, 51);

        $result = $dao->getInsightBaseline('avg_replies_per_week', 1, '2012-05-02');
        $this->assertNull($result);
    }

    public function testGetMostRecentInsightBaseline() {
        $dao = new InsightBaselineMySQLDAO();
        $result = $dao->getMostRecentInsightBaseline('avg_replies_per_week', 1);
        $this->assertIsA($result, 'InsightBaseline');
        $this->assertEqual($result->slug, 'avg_replies_per_week');
        $this->assertEqual($result->date, '2012-05-01');
        $this->assertEqual($result->instance_id, 1);
        $this->assertEqual($result->value, 51);

        $builders[] = FixtureBuilder::build('insight_baselines', array('date'=>'2012-04-30',
        'slug'=>'avg_replies_per_week', 'instance_id'=>1, 'value'=>99));
        $result = $dao->getMostRecentInsightBaseline('avg_replies_per_week', 1);
        $this->assertEqual($result->date, '2012-05-01');
        $this->assertEqual($result->value, 51);

        $builders[] = FixtureBuilder::build('insight_baselines', array('date'=>'2014-04-01',
        'slug'=>'avg_replies_per_week', 'instance_id'=>1, 'value'=>12));
        $result = $dao->getMostRecentInsightBaseline('avg_replies_per_week', 1);
        $this->assertEqual($result->date, '2014-04-01');
        $this->assertEqual($result->value, 12);

        $builders[] = FixtureBuilder::build('insight_baselines', array('date'=>'2014-04-02',
        'slug'=>'avg_replies_per_week', 'instance_id'=>11, 'value'=>12));
        $result = $dao->getMostRecentInsightBaseline('avg_replies_per_week', 1);
        $this->assertEqual($result->date, '2014-04-01');
        $this->assertEqual($result->value, 12);
        $this->assertNotEqual($result->instance_id, 11);

        $builders[] = FixtureBuilder::build('insight_baselines', array('date'=>'2014-04-02',
        'slug'=>'nope', 'instance_id'=>1, 'value'=>22));
        $result = $dao->getMostRecentInsightBaseline('avg_replies_per_week', 1);
        $this->assertEqual($result->date, '2014-04-01');
        $this->assertEqual($result->value, 12);
        $this->assertNotEqual($result->slug, 'nope');

        $result = $dao->getMostRecentInsightBaseline('new_baseline', 1);
        $this->assertNull($result);
        $builders[] = FixtureBuilder::build('insight_baselines', array('date'=>'2014-04-02',
        'slug'=>'new_baseline', 'instance_id'=>1, 'value'=>22));
        $result = $dao->getMostRecentInsightBaseline('new_baseline', 1);
        $this->assertNotNull($result);
    }

    public function testUpdateInsightBaseline() {
        $dao = new InsightBaselineMySQLDAO();

        //update existing baseline
        $result = $dao->updateInsightBaseline('avg_replies_per_week', 1, 101, '2012-05-01');
        $this->assertTrue($result);
        //check that value was updated
        $result = $dao->getInsightBaseline('avg_replies_per_week', 1, '2012-05-01');
        $this->assertEqual($result->value, 101);

        //update nonexistent baseline
        $result = $dao->updateInsightBaseline('avg_replies_per_week', 1, 101, '2012-05-10');
        $this->assertFalse($result);

        //no date specified
        $result = $dao->updateInsightBaseline('avg_replies_per_week', 1, 101);
        $this->assertFalse($result);
    }
}
