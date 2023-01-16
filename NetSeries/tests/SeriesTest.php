<?php

use App\Entity\User;
use App\Entity\Series;
use App\Entity\Rating;


class SeriesTest extends PHPUnit\Framework\TestCase
{
    public function testEntityRelations()
    {
       
        $series = new Series();
        $user1 = new User();
        $user2 = new User();

        $rating1 = new Rating();
        $rating2 = new Rating();

        $rating1->setUser($user1);
        $rating1->setDate(new DateTime());
        $rating1->setSeries($series);
        $rating1->setEstModere(true);
        $rating1->setValue(10);

        $rating2->setUser($user2);
        $rating2->setDate(new DateTime());
        $rating2->setSeries($series);
        $rating2->setEstModere(true);
        $rating2->setValue(4);

        $series->addRating($rating1);
        $series->addRating($rating2);

        $this->assertEquals(3.5, $series->getAverageRating());
    }
}