<?php
//grab all open timestamps which have MORE than >pauseAfterHours< hours.

//accumulate all the break bookings he has and see if the sum of them are bigger or equal to >hoursOfRest<

//if they are less: if he doesnt have any bookings at all, add the time for now()

//if he has bookings already: does he have a booking which is up into the future (bigger than now())? if he doesnt have that, add the time for now()

//but if he does, it gets complicated. put in a booking for now() and MOVE all the conflicting bookings right after now(+hoursOfRest). I guess we ought to do that.
?>