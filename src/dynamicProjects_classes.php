<?php

class ProjectSeries
{
    public $once = true;
    public $daily_every_nth;
    public $daily_days = 1;
    public $daily_every_weekday;
    public $weekly;
    public $weekly_weeks = 1;
    public $weekly_day = "monday";
    public $monthly_day_of_month;
    public $monthly_day_of_month_day = 1;
    public $monthly_day_of_month_month = 1;
    public $monthly_nth_day_of_week;
    public $monthly_nth_day_of_week_nth = 1;
    public $monthly_nth_day_of_week_day = "monday";
    public $monthly_nth_day_of_week_month = 1;
    public $yearly_nth_day_of_month;
    public $yearly_nth_day_of_month_nth = 1;
    public $yearly_nth_day_of_month_month = "JAN";
    public $yearly_nth_day_of_week;
    public $yearly_nth_day_of_week_nth = 1;
    public $yearly_nth_day_of_week_day = "monday";
    public $yearly_nth_day_of_week_month = "JAN";
    public $start;
    public $end;
    public $last_date;

    function get_next_date()
    {
        $now = new DateTime();
        $retDate = $this->last_date;
        // "" should indicate the end of series
        $daily_days = $this->daily_days;
        $weekly_day = $this->weekly_day;
        $weekly_weeks = $this->weekly_weeks;
        $monthly_day_of_month_day = $this->monthly_day_of_month_day;
        $monthly_day_of_month_month = $this->monthly_day_of_month_month;
        $yearly_nth_day_of_week_nth = $this->yearly_nth_day_of_week_nth;
        $yearly_nth_day_of_week_day = $this->yearly_nth_day_of_week_day;
        $monthly_nth_day_of_week_day = $this->monthly_nth_day_of_week_day;
        $monthly_nth_day_of_week_nth = $this->monthly_nth_day_of_week_nth;
        $yearly_nth_day_of_month_nth = $this->yearly_nth_day_of_month_nth;
        $yearly_nth_day_of_week_month = $this->yearly_nth_day_of_week_month;
        $monthly_nth_day_of_week_month = $this->monthly_nth_day_of_week_month;
        $yearly_nth_day_of_month_month = $this->yearly_nth_day_of_month_month;
        switch (true) {
            case ($this->once) :
                return "";
            case ($this->daily_every_nth) :
                while($retDate < $now){
                    $retDate->add(new DateInterval("P${daily_days}D"));
                }
                break;
            case ($this->daily_every_weekday) :
                while($retDate < $now){
                    $retDate->setTimestamp(strtotime("+1 weekday", $retDate->getTimestamp()));
                }
                break;
            case ($this->weekly) :
                while($retDate < $now){
                    $retDate->setTimestamp(strtotime("+${weekly_weeks} weeks ${weekly_day}", $retDate->getTimestamp()));
                }
                break;
            case ($this->monthly_day_of_month) :
                $formatter = new NumberFormatter('en_US', NumberFormatter::SPELLOUT);
                $formatter->setTextAttribute(NumberFormatter::DEFAULT_RULESET, "%spellout-ordinal");
                while($retDate < $now){
                    $ordinal = $formatter->format($monthly_day_of_month_day);
                    $retDate->setTimestamp(strtotime("+${monthly_day_of_month_month} months ${ordinal} day", $retDate->getTimestamp()));
                }
                break;
            case ($this->monthly_nth_day_of_week) :
                $formatter = new NumberFormatter('en_US', NumberFormatter::SPELLOUT);
                $formatter->setTextAttribute(NumberFormatter::DEFAULT_RULESET, "%spellout-ordinal");
                while($retDate < $now){
                    $ordinal = $formatter->format($monthly_nth_day_of_week_nth);
                    $retDate->setTimestamp(strtotime("+${monthly_nth_day_of_week_month} months ${ordinal} ${monthly_nth_day_of_week_day}", $retDate->getTimestamp()));
                }
                break;
            case ($this->yearly_nth_day_of_month) :
                $formatter = new NumberFormatter('en_US', NumberFormatter::SPELLOUT);
                $formatter->setTextAttribute(NumberFormatter::DEFAULT_RULESET, "%spellout-ordinal");
                while($retDate < $now){
                    $ordinal = $formatter->format($yearly_nth_day_of_month_nth);
                    $retDate->setTimestamp(strtotime("${ordinal} day of ${yearly_nth_day_of_month_month}", $retDate->getTimestamp()));
                }
                break;
            case ($this->yearly_nth_day_of_week) :
                $formatter = new NumberFormatter('en_US', NumberFormatter::SPELLOUT);
                $formatter->setTextAttribute(NumberFormatter::DEFAULT_RULESET, "%spellout-ordinal");
                while($retDate < $now){
                    $ordinal = $formatter->format($yearly_nth_day_of_week_nth);
                    $retDate->setTimestamp(strtotime("${ordinal} day of ${yearly_nth_day_of_week_day} ${yearly_nth_day_of_week_month}", $retDate->getTimestamp()));
                }
                break;
            default :
                return "ERROR";
        }
        $this->last_date = $retDate;
        if($retDate > $this->end){
            return "";
        }
        return $retDate->format("Y-m-d");
    }

    function __construct($series /*eg once, daily_every_nth, ...*/, $start, $end)
    {
        // $start and $end are both strings like "2018-01-01" end can also be ""/"no" for no end or "3" for 3 repetions
        $this->once = $series == "once" || $series == "";
        $this->daily_every_nth = $series == "daily_every_nth";
        $this->daily_every_weekday = $series == "daily_every_weekday";
        $this->weekly = $series == "weekly";
        $this->monthly_day_of_month = $series == "monthly_day_of_month";
        $this->monthly_nth_day_of_week = $series == "monthly_nth_day_of_week";
        $this->yearly_nth_day_of_month = $series == "yearly_nth_day_of_month";
        $this->yearly_nth_day_of_week = $series == "yearly_nth_day_of_week";
        $this->start = new DateTime($start);
        if ($end == "no" || $end == "") {
            $this->end = false;
        }
        elseif (is_numeric($end)) {
            $this->end = intval($end);
        }
        else {
            $this->end = new DateTime($end);
        }
        $this->last_date = $this->start;
    }
    
    function __sleep()
    {
        $this->start = $this->start->getTimestamp();
        if (!is_numeric($this->end) && $this->end != false) {
            //end is a DateTime which can't be serialized
            $this->end = $this->end->getTimestamp();
        }
        return array_keys(get_object_vars($this));
    }
    
    function __wakeup()
    {
        $startTimestamp = $this->start;
        $this->start = new DateTime();
        $this->start->setTimestamp($startTimestamp);
        if ($this->end > 100000000) { //probably a timestamp
            $endTimestamp = $this->end;
            $this->end = new DateTime();
            $this->end->setTimestamp($endTimestamp);
        }
    }
    
    function __toString()
    {
        $text_before = "Dieses Projekt wiederholt sich ";
        $text_after = ".";
        $daily_days = $this->daily_days;
        $weekly_weeks = $this->weekly_weeks;
        $weekly_day = $this->weekly_day;
        $monthly_day_of_month_day = $this->monthly_day_of_month_day;
        $monthly_day_of_month_month = $this->monthly_day_of_month_month;
        $monthly_nth_day_of_week_day = $this->monthly_nth_day_of_week_day;
        $monthly_nth_day_of_week_nth = $this->monthly_nth_day_of_week_nth;
        $monthly_nth_day_of_week_month = $this->monthly_nth_day_of_week_month;
        $yearly_nth_day_of_month_month = $this->yearly_nth_day_of_month_month;
        $yearly_nth_day_of_month_nth = $this->yearly_nth_day_of_month_nth;
        $yearly_nth_day_of_week_nth = $this->yearly_nth_day_of_week_nth;
        $yearly_nth_day_of_week_day = $this->yearly_nth_day_of_week_day;
        $yearly_nth_day_of_week_month = $this->yearly_nth_day_of_week_month;
        switch (true) {
            case ($this->once) :
                return "${text_before}nicht${text_after}";
                break;
            case ($this->daily_every_nth) :
                return "${text_before}jeden ${daily_days}.Tag${text_after}";
                break;
            case ($this->daily_every_weekday) :
                return "${text_before}jeden Wochentag${text_after}";
                break;
            case ($this->weekly) :
                return "${text_before}jede ${weekly_weeks}. Woche am ${weekly_day}${text_after}";
                break;
            case ($this->monthly_day_of_month) :
                return "${text_before}jeden ${monthly_day_of_month_day} jedes ${monthly_day_of_month_month}. Monats${text_after}";
                break;
            case ($this->monthly_nth_day_of_week) :
                return "${text_before}jeden ${monthly_nth_day_of_week_nth}. ${monthly_nth_day_of_week_day} jedes ${monthly_nth_day_of_week_month}. Monats${text_after}";
                break;
            case ($this->yearly_nth_day_of_month) :
                return "${text_before}jeden ${yearly_nth_day_of_month_nth}. ${yearly_nth_day_of_month_month}${text_after}";
                break;
            case ($this->yearly_nth_day_of_week) :
                return "${text_before}jeden ${yearly_nth_day_of_week_nth}. ${yearly_nth_day_of_week_day} im ${yearly_nth_day_of_week_month}${text_after}";
                break;
            default :
                return "ERROR";
                break;
        }
    }
}


?>