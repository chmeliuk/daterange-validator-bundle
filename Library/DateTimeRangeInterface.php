<?php
namespace BestModules\DateTimeIntersectBundle\Library;

interface DateTimeRangeInterface
{
    /**
     * @return \DateTime
     */
    public function getStartDate();
    
    /**
     * @return \DateTime
     */
    public function getEndDate();
}
