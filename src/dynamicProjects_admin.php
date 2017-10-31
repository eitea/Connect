<?php include 'header.php';
isDynamicProjectAdmin($userID); ?>
<!-- BODY -->
<?php
function stripSymbols($s)
{
    $result = "";
    foreach (str_split($s) as $char) {
        if (ctype_alnum($char)) {
            $result = $result . $char;
        }
    }
    return $result;
}
echo time();
class ProjectSeries
{
    public $once;
    public $daily_every_nth;
    public $daily_days;
    public $daily_every_weekday;
    public $weekly;
    public $weekly_weeks;
    public $weekly_day;
    public $monthly_day_of_month;
    public $monthly_day_of_month_day;
    public $monthly_day_of_month_month;
    public $monthly_nth_day_of_week;
    public $monthly_nth_day_of_week_nth;
    public $monthly_nth_day_of_week_day;
    public $monthly_nth_day_of_week_month;
    public $yearly_nth_day_of_month;
    public $yearly_nth_day_of_month_nth;
    public $yearly_nth_day_of_month_month;
    public $yearly_nth_day_of_week;
    public $yearly_nth_day_of_week_nth;
    public $yearly_nth_day_of_week_day;
    public $yearly_nth_day_of_week_month;
    public $start;
    public $end;
    public $last_date;
    function get_next_date()
    {
        $now = new DateTime();
        //$date->format('Y-m-d')
        // "" indicates end of series
        switch (true) {
            case ($this->once) :
                return "";
                break;
            case ($this->daily_every_nth) :
                return "";
                break;
            case ($this->$daily_every_weekday) :
                return "";
                break;
            case ($this->weekly) :
                return "";
                break;
            case ($this->monthly_day_of_month) :
                return "";
                break;
            case ($this->monthly_nth_day_of_week) :
                return "";
                break;
            case ($this->yearly_nth_day_of_month) :
                return "";
                break;
            case ($this->yearly_nth_day_of_week) :
                return "";
                break;
            default :
                return "";
                break;
        }
    }
    function __construct($series /*eg once, daily_every_nth, ...*/, $start, $end)
    {
        // $start and $end are both strings like "2018-01-01" end can also be ""/"no" for no end or "3" for 3 repetions
        $this->once = $series == "once";
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
            echo $end;
            $this->end = new DateTime($end);
        }
        $this->last_date = $this->start;
    }
    function __sleep(){
        echo $this->end->getTimestamp();
        $this->start = $this->start->getTimestamp();
        if(!is_numeric($this->end)&&$this->end != false){
            //end is a DateTime which can't be serialized
            $this->end = $this->end->getTimestamp(); 
        }
        return array_keys(get_object_vars($this));
    }
    function __wakeup(){
        $startTimestamp = $this->start;
        $this->start = new DateTime();
        $this->start->setTimestamp($startTimestamp);
        if($this->end > 100000000){ //probably a timestamp
            $endTimestamp = $this->end;
            $this->end = new DateTime();
            $this->end->setTimestamp($endTimestamp);
        }
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["dynamicProject"])) {
    $connectIdentification = $conn->query("SELECT id FROM identification")->fetch_assoc()["id"];
    $id = $_POST["id"] ?? "";
    $name = $_POST["name"] ?? "missing name";
    $description = $_POST["description"] ?? "missing description";
    $company = $_POST["company"] ?? false;
    $color = $_POST["color"] ?? "#FFFFFF";
    $start = $_POST["start"] ?? date("Y-m-d");
    $end = $_POST["endradio"] ?? "";
    $status = $_POST["status"] ?? 'DRAFT';
    $priority = intval($_POST["priority"] ?? "3") ?? 3;
    $parent = $_POST["parent"] ?? "";
    $pictures = $_POST["imagesbase64"] ?? false;
    $owner = $_POST["owner"] ?? $userID + "";
    $clients = $_POST["clients"] ?? array();
    $employees = $_POST["employees"] ?? array();
    $optional_employees = $_POST["optionalemployees"] ?? array();
    //series one of: once daily_every_nth daily_every_weekday weekly monthly_day_of_month monthly_nth_day_of_week yearly_nth_day_of_month yearly_nth_day_of_week
    $series = $_POST["series"] ?? "once";
    //var_dump($clients);
    if ($end == "no") {
        $end = "";
    }
    else if ($end == "number") {
        $end = $_POST["endnumber"] ?? "";
    }
    else if ($end == "date") {
        $end = $_POST["enddate"] ?? "";
    }
    $series = new ProjectSeries($series, $start, $end);
    $series->daily_days = $_POST["daily_days"] || 1;
    $series->weekly_weeks = $_POST["weekly_weeks"] || 1;
    $series->weekly_day = $_POST["weekly_day"] || "monday";
    $series->monthly_day_of_month_day = $_POST["monthly_day_of_month_day"] || 1;
    $series->monthly_day_of_month_month = $_POST["monthly_day_of_month_month"] || 1;
    $series->monthly_nth_day_of_week_nth = $_POST["monthly_nth_day_of_week_nth"] || 1;
    $series->monthly_nth_day_of_week_day = $_POST["monthly_nth_day_of_week_day"] || "monday";
    $series->monthly_nth_day_of_week_month = $_POST["monthly_nth_day_of_week_month"] || 1;
    $series->yearly_nth_day_of_month_nth = $_POST["yearly_nth_day_of_month_nth"] || 1;
    $series->yearly_nth_day_of_month_month = $_POST["yearly_nth_day_of_month_month"] || "JAN";
    $series->yearly_nth_day_of_week_nth = $_POST["yearly_nth_day_of_week_nth"] || 1;
    $series->yearly_nth_day_of_week_day = $_POST["yearly_nth_day_of_week_day"] || "monday";
    $series->yearly_nth_day_of_week_month = $_POST["yearly_nth_day_of_week_month"] || "JAN";

    if ($parent == "none") {
        $parent = "";
    }
    if (empty($company) || !is_numeric($company)) {
        echo "Company not set";
        goto bodyEnd;
    }
    if ($id == "") {
        $id = uniqid($connectIdentification);
        while ($conn->query("SELECT * FROM dynamicprojects WHERE projectid = 'asdf'")->num_rows != 0) {
            $id = uniqid($connectIdentification);
        }
    }
    $owner = intval($owner) ?? $userID;
    $nextDate = $series->get_next_date();
    $series = serialize($series);
    $series = base64_encode($series);

    $description = $conn->real_escape_string($description);
    $id = $conn->real_escape_string($id);
    $name = $conn->real_escape_string($name);
    $color = $conn->real_escape_string($color);
    $start = $conn->real_escape_string($start);
    $end = $conn->real_escape_string($end);
    $status = $conn->real_escape_string($status);
    $parent = $conn->real_escape_string($parent);
    $conn->query("INSERT INTO dynamicprojects (projectid,projectname,projectdescription, companyid, projectcolor, projectstart,projectend,projectstatus,projectpriority, projectparent, projectowner) VALUES ('$id','$name','$description', $company, '$color', '$start', '$end', '$status', '$priority', '$parent', '$owner')");
    echo $conn->error;
    // series
    $stmt = $conn->prepare("INSERT INTO dynamicprojectsseries (projectid,projectnextdate,projectseries) VALUES ('$id','$nextDate',?)");
    echo $conn->error;
    $null = NULL;
    $stmt->bind_param("b", $null);
    $stmt->send_long_data(0, $series);
    $stmt->execute();
    echo $stmt->error;
    // /series
    if ($pictures) {
        foreach ($pictures as $picture) {
            // $conn->query("INSERT INTO dynamicprojectspictures (projectid,picture) VALUES ('$id','$picture')");
            $stmt = $conn->prepare("INSERT INTO dynamicprojectspictures (projectid,picture) VALUES ('$id',?)");
            echo $conn->error;
            $null = NULL;
            $stmt->bind_param("b", $null);
            $stmt->send_long_data(0, $picture);
            $stmt->execute();
            echo $stmt->error;
        }
    }
    foreach ($clients as $client) {
        $client = intval($client);
        $conn->query("INSERT INTO dynamicprojectsclients (projectid, clientid) VALUES ('$id',$client)");
    }
    foreach ($employees as $employee) {
        $employee = intval($employee);
        $conn->query("INSERT INTO dynamicprojectsemployees (projectid, userid) VALUES ('$id',$employee)");
    }
    foreach ($optional_employees as $optional_employee) {
        $optional_employee = intval($optional_employee);
        $conn->query("INSERT INTO dynamicprojectsoptionalemployees (projectid, userid) VALUES ('$id',$optional_employee)");
    }
}
?>
<br>
    

<?php
// variables for easy reuse for editing existing dynamic projects
$modal_title = $lang['DYNAMIC_PROJECTS_NEW'];
$modal_name = "";
$modal_company = ""; //id
$modal_description = "No description given";
$modal_color = "#777777";
$modal_start = date("Y-m-d");
$modal_end = ""; // Possibilities: ""(none);number (repeats); Y-m-d (date)
$modal_status = "ACTIVE"; // Possibiilities: "ACTIVE","DEACTIVATED","DRAFT","COMPLETED"
$modal_priority = 3;
$modal_id = ""; // empty => generate new
$modal_pictures = /*test image*/ array("data:image/png;base64,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nHPO0TnnnKPu3burR48eio2NPenn+wapHTp0UEpKit577z39/e9/1/79+4+bjhOKGhoa/CXox8TFxaljx47q1auXJk+eLI/Ho++++05dunTx3z0YMGCA//MbGxt14MAB7dy5U7t27dLu3bu1c+dOlZWVtcSXBAC2RQEAgP+VmJio9PR0paenq1evXjrnnHNO+qBtcXGxCgoKVFBQoD179ui777477mr0tGnTlJKSop07d1pyh06rKi8v9z88PHnyZFVUVOgXv/iFpB+mV6Wlpfk/dunSRcnJyUpOTtZll13mP05JSYl27typrVu3auvWrdqxY4eOHj1q6ssCAMuhAAAISS6XS+eee6769OmjXr16KT09XW3btj3uc7xer/bs2aNvv/1W3377rXbt2qWdO3eqsrLSUOrQ5bt7sG7dOv/vORwOdezYUT169FD37t3VrVs39ejRQwkJCUpISNCQIUMk/XAet27dqi1btmjz5s3au3evqS8FAIyjAAAICb4HSn1LX5577rmKjIw87nOKi4u1bds2bdu2TVu3btW33357xg/aovk1NjZq//792r9/vz7//HP/7yckJKhHjx7q1auXevXqpZ49e6pr167q2rWrrrrqKklSaWmpNm3apLy8PG3atEkFBQUsUwogZFAAAASlsLAw9erVSwMGDND555+vc889178RliTV1dUpLy9Pmzdv1tatW7Vt2zaVlpYaTIxAKSkpUUlJib766itJTc9ydOnSRb169VLv3r3Vp08fderUScOGDdOwYcMkNU0/+uabb7R+/XqtX79e+/fvN/gVAEDzogAACBqdO3fWgAED1L9/f51//vnHzd/3eDz+q72bNm3S9u3bVVNTYzAtWorX69V3332n7777Th9//LEkqW3bturbt68yMjLUt29fpaamaujQoRo6dKgkqbCwUOvXr9fatWu1ceNGpn0BCCoUAAC25XK5dOGFF2rw4MHKzMxUUlKS/88aGhqUl5endevWacOGDdq+fbt/IyygtLRUK1as0IoVKyQ1rUB03nnnqX///urfv7+Sk5M1atQojRo1Sl6vV9u2bdPq1av1z3/+U/n5+WbDA8BZogAAsJW2bdvK5XJJkv76178ed5X/+++/17p167R+/Xpt2LCB+fs4ZeXl5Vq1apVWrVolSerQoYP69++vgQMHql+/furdu7d69+6tO+64Q0VFRf5dkP/dBm8AYEUUAACWl5SUpEsuuURDhw5Venq6nE6npKa53WvWrNHq1auVm5uroqIiw0kRLAoLC7V48WItXrxYTqdT6enpGjx4sAYNGqS0tDT/582ePVurV6/WqlWrlJubS+kEYAsUAACW1KlTJ/+c7J49e/p/v7S0VK1bt1Z4eLhuu+02HtxFs/N6vdqyZYu2bNmi1157TUlJSZo9e7ZiY2MVFhbmf5i4pqZG69at0xdffKHVq1erqqrKdHQAOCkKAADLSEpK0vDhwzVs2DB169bN//tFRUX64osvtGrVKm3btk3vvfeewsPDeYgXRhQVFeno0aOKjY3V5MmT1a1bNw0dOlSZmZm66KKLdNFFF6murk5r167VZ599pq+++orvVQCWQgEAYFRcXJwuvfRSDR8+XH369JHD4ZAk7d271z8ne8eOHYZTAifn8Xj8DxNHRkZqwIABuuSSSzR48GANGTJEQ4YMkcfj0apVq/TZZ59p/fr1amhoMB0bQIijAABocS6XS0OGDNHIkSPVv39///r8RUVF+uyzz7RixQrt3r3bcErg9NTU1Ogf//iH/vGPf8jlcmngwIEaPny4hgwZohEjRmjEiBEqKyvT559/rk8++YRiC8AYCgCAFpOWlqZRo0YpKytLsbGxkppWX1m5cqU+++wzbd68md1YERTq6ur01Vdf6auvvlJUVJSGDh2q4cOHq3///ho7dqzGjh2rHTt26JNPPtHy5cvZZwBAi6IAAGhWrVq10uWXX64rrrjC/zBvXV2dVq5cqaVLl2r9+vWsz4+g5vF4tGzZMi1btkzx8fG67LLL/O+Hnj176q677tJXX32lpUuXau3atfJ6vaYjAwhyFAAAzeLcc8/V6NGjNWzYMEVGRkqS8vPztXjxYq54ImSVlZVpwYIFWrBggXr27KkrrrhCWVlZuvTSS3XppZf6lx9dsmSJysrKTMcFEKQoAAACJioqSsOHD9fVV1/tv9p/5MgRLVmyhDnPwP+xY8cO7dixQ3/605/8z8QMHDhQd9xxh2699VZ9+eWXWrRokTZt2mQ6KoAgQwEAcNY6deqka665RldccYXcbrckafv27Vq0aJFWrFjBEojAv+CbErdy5Up16NBBo0aN0pVXXunfX6CgoEALFy7U0qVL2WgMQEBQAACcEYfDoQsuuEDXXXedBg4cKKfTKY/Ho48//lgfffQRV/uBM1BYWKjXXntNb7zxhi6++GKNHj1affv21X333aeJEyfq448/1oIFC9j1GsBZoQAAOC2RkZHKysrSuHHj1LVrV0lNy3cuWLBAH3/8MbufAgFQV1fn318gLS1N1157rS6//HL99Kc/1XXXXacvv/xS8+fPV15enumoAGyIAgDglMTFxWns2LEaM2aM4uLiJEl5eXmaP3++vvzyS1YuAZpJfn6+ZsyYoddee01XX321rrnmGl1yySW65JJLtGPHDr377rv64osveA8COGUUAAD/UlJSkq6//npdeeWVioqKUn19vT799FPNnz+faT5ACyovL9ff/vY3vfvuu7r00kt13XXXqWfPnvqv//ov7d+/X++++64++eQT1dbWmo4KwOIoAABOKiIiQo888oiGDRumsLAweTweffDBB3rvvfeYfwwYVFdXp08//VSffvqpBgwYoPHjx+uCCy7Q/fffr1tvvVXz58+Xy+UyHROAhVEAABwnNTVVkpSSkqKUlBSVl5frww8/1Icffqjy8nLD6QAca926dVq3bp169uypCRMm6OKLL9akSZP8m+v59uAAgGNRAABIatq465ZbblFmZqYkqb6+Xn/605+0ZMkSlh4ELG7Hjh168sknlZKSogkTJmjkyJGSpNtvv12tWrXS+++/r4qKCsMpAVgFBQAIcenp6ccN/A8fPqw2bdpo7969+uCDDwynA3A69u3bp+nTp8vlcunyyy9XWFiYbrrpJo0bN04LFizQvHnzKAIAKABAqOrWrZsmTpyoQYMGSZKKi4s1Z84cbdu2TS+88IIaGxsNJwRwpqqrqyVJb7zxhtq3b6+RI0fqhhtu0LXXXqt58+Zp3rx5/s8BEHooAECI6dixo2677TYNHz5cTqdTJSUl+tvf/qa///3vqqurU1pamumIAAKksrJSf/vb3/T222/rpptu0k9+8hPdcsstGjNmjObMmaOFCxeyUzcQgigAQIho27atbrnlFv3kJz+Ry+VSRUWF5syZow8//JABABDkiouLNXPmTM2dO1e33367Lr30Ut11110aN26c3nzzTS1dulQNDQ2mYwJoIeE9e/YM6AF79Ojh/2jFtYi7d+8uSerZs6cC/bUHwrGvnxXzHcuq+Y49x2VlZYbTnMj3unXv3r1FXsOIiAiNHDlSl19+uVwul2pra7VkyRItW7ZMHo/Hv+qPj29337S0NMueY7fbLanpfWLFaQzHnuO9e/caTnMi388Zq/4cbN++vSQpMTHRkvmkpmxS02sZHx9vOM2JfK/byf4tmTdvnlavXq0xY8aoT58+evDBB3XzzTdrwYIF2rhxY4vks/q/xTExMZKaftZYMZ8k/93aHj16WHLZV6uf45b+t/hsNEc+R2VlJRN9AQAAgBARHuidPI9tKVbcJZR8Z8+X0er5JGtmbIl8brdbCQkJ/jXAjxw5opKSklO6K5eWlua/mmTF10/64TXcv3+/jhw5YjjNifgePDtJSUmKi4uTZM180g+vYXl5uSU3xjudc+xwONSqVSu1a9dOERERamxsVGVlpQ4dOqS6ujrj+Uxo1aqVkpOTJVkzn/TDa1hXV6f8/HzDaU5k9XNs9XxS8463HEOGDAnoHYDp06crIyNDU6dOVV5eXiAPHRCTJk3ShAkTlJOTozlz5piOc4KMjAxNnz5deXl5mjp1quk4J7V06VJJ8q8zbTVPPfWUMjMzlZ2drdzcXNNxTjBhwgRNmjRJc+bMUU5OTkCPnZqaqrvuuksXXnihpKYfGq+88spp3dZPS0vTyy+/rPz8fN19990BzRco8+fPl9vt1rhx41RVVWU6zgmmTZumrKwsPf3001q+fLnpOCcYM2aMpkyZooULF2rWrFmm45wgMTFRb775poqLi3XLLbeYjnNSb775phITE3XLLbeouLjYdJwTTJkyRWPGjNGsWbO0cOHCU/r/uFwujR07VjfffLPcbrdqamr03nvv6Z133gn4XiBZWVmaNm2ali9frqeffjqgxw4Et9ut+fPnq6qqSuPGjTMd56RefvllpaWl6e6777ZkAbD6eCszM1NPPfWUcnNzlZ2dbTrOSTXneIuHgIEg4Ha7dfPNN2vcuHEKCwtTcXGxcnJytGLFCpbzBHBK6urqNG/ePH3yySe6+eabNXr0aN10000aMWKE/vSnP2nlypWmIwIIEAoAYGMOh0MjRozQpEmT1LZtW9XU1GjOnDl6++23WdkHwBkpLy/X7Nmz9dFHH2ny5Mnq37+/srOztXHjRv3xj39UQUGB6YgAzhIFALCp7t27a8qUKerVq5ckafXq1XrppZe0f/9+w8kABIOCggJNmzZNQ4cO1T333KN+/frppZde0gcffKA333zTktPvAJwaCgBgM1FRUZo4caKuueYahYWFaf/+/Zo9e7Yln3cAYH+rVq3SmjVrdMMNN+inP/2prr/+eg0fPlyzZ89mWhBgUxQAwEYuuugi/fznP1f79u1VU1OjN954Q++++26zrdQBAJJUU1Ojv/zlL/r73/+u++6777iFFmbNmmXJlZAA/DgKAGADiYmJ+vnPf64hQ4ZIktavX69Zs2Zp3759hpMBCCWFhYXKzs7WZZddpsmTJyszM1OvvPKK3nzzTb333nvsJgzYBAUAsDCn06lrrrlGd9xxh6KiolRWVqaXX35Zn376qeloAELY559/rrVr1+qOO+7Q1VdfrTvvvFNZWVmaMWOGtm/fbjoegH+DAgBYVKdOnTR16lRlZGSosbFRH3/8sV555RVLbnwFIPRUVVVp1qxZ+uSTT/TAAw+oW7dumjlzpubNm6c33njjlDYeBGCG03QAAMdzOp366U9/qhdffFEZGRkqLCzUL3/5S82YMYPBPwDL2bZtm+677z698cYb8nq9mjBhgl588UX/CmUArIcCAFhIamqqZs6cqbvuuksul0sLFizQXXfddVo7+QJAS6uvr9cbb7yh++67Tzt27FDnzp01Y8YM3X333YqKijIdD8D/QQEALMB31f+FF15Qenq69u3bp4ceekgvvPCCPB6P6XgAcEry8/N1//33KycnR/X19br++uv14osvKj093XQ0AMegAACGJSUl6Xe/+53uuusuhYeH6/3339c999yjvLw809EA4LQ1NDRozpw5uvfee7V9+3alpKRoxowZuu222xQWFmY6HgBRAACjRo4cqZdffln9+vVTcXGxpk2bppdeekk1NTWmowHAWdmzZ48eeOABvfHGG5KkW265RX/4wx/UuXNnw8kAsAoQYMjFF1+sTp06SZKWLVumF154QVVVVYZTAUDgNDQ06I033lBubq4eeeQR9ezZkx2EAQvgDgDQwjp27CipaZnP8vJyPfnkk3rmmWcY/AMIWtu3b9e9996rDz/8UBEREbriiiskSS6Xy3AyIDRRAIAWEhYWpttvv11XXXWVJKmoqEh33323vvjiC8PJAKD5eTwe/fGPf1R2draqq6slSZmZmTr//PMNJwNCDwUAaAFJSUl67rnndNNNN6mxsVFS006apaWlhpMBQMtas2aNXnvtNUlSZGSknn76aU2cOJEHhIEWRAEAmtnQoUP10ksv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");
$modal_parent = ""; //default: "none" or ""



//not jet implemented:
$modal_clients = array(); //array of ids
$modal_owner = "";
$modal_employees = array();
$modal_optional_employees = array();
$modal_series = "";



require "dynamicProjects_template.php";


?>


<!--  
*************************************************************************************
                              List all dynamic projects
*************************************************************************************
-->

<table class="table">
<thead>
    <tr>
        <th>Name</th>
        <th>Description</th>
        <th>Company</th>
        <th>Start</th>
        <th>End</th>
        <th>Status</th>
        <th>Priority</th>
        <th>Parent</th>
        <th>Pictures</th>
        <th>Clients</th>
        <th>Employees</th>
        <th>Optional Employees</th>
    </tr>
</thead>
<tbody>
    <?php
    $result = $conn->query("SELECT * FROM dynamicprojects");
    while ($row = $result->fetch_assoc()) {
        $id = $row["projectid"];
        $name = $row["projectname"];
        $description = $row["projectdescription"];
        $company = $row["companyid"];
        $companyName = $conn->query("SELECT name FROM companyData where id = $company")->fetch_assoc()["name"];
        $color = $row["projectcolor"];
        $start = $row["projectstart"];
        $end = $row["projectend"];
        $status = $row["projectstatus"];
        $priority = $row["projectpriority"];
        $pictureResult = $conn->query("SELECT picture FROM dynamicprojectspictures WHERE projectid='$id'");
        $seriesResult = $conn->query("SELECT projectseries FROM dynamicprojectsseries WHERE projectid='$id'");
        echo $conn->error;
        $parent = $row["projectparent"];
        $ownerId = $row["projectowner"];
        $owner = $conn->query("SELECT * FROM UserData WHERE id='$ownerId'")->fetch_assoc();
        $owner = "${owner['firstname']} ${owner['lastname']}";
        $clientsResult = $conn->query("SELECT * FROM dynamicprojectsclients INNER JOIN  $clientTable ON  $clientTable.id = dynamicprojectsclients.clientid  WHERE projectid='$id'");
        $employeesResult = $conn->query("SELECT * FROM dynamicprojectsemployees INNER JOIN UserData ON UserData.id = dynamicprojectsemployees.userid WHERE projectid='$id'");
        $optional_employeesResult = $conn->query("SELECT * FROM dynamicprojectsoptionalemployees INNER JOIN UserData ON UserData.id = dynamicprojectsoptionalemployees.userid WHERE projectid='$id'");
        $pictures = array();
        $clients = array();
        $employees = array();
        $optional_employees = array();
        if (!empty($parent)) {
            $parent = $conn->real_escape_string($parent);
            $parent = $conn->query("SELECT * FROM dynamicprojects WHERE projectid='$parent'")->fetch_assoc()["projectname"];
        }
        $series = null;
        if ($seriesResult) {
            $series = $seriesResult->fetch_assoc()["projectseries"];
            $series = base64_decode($series);         
            $series = unserialize($series, array("allowed_classes"=>array("ProjectSeries")));
        }else{
            echo "series couldn't be unserialized";
        }
       // echo $series->start;

        echo "<tr>";
        echo "<td style='background-color:$color;'>$name</td>";
        echo "<td>$description</td>";
        echo "<td>$companyName</td>";
        echo "<td>$start</td>";
        echo "<td>$end</td>";
        echo "<td>$status</td>";
        echo "<td>$priority</td>";
        echo "<td>$parent</td>";
        echo "<td>$owner</td>";
        echo "<td>";
        while ($pictureRow = $pictureResult->fetch_assoc()) {
            array_push($pictures, $picture);
            $picture = $pictureRow["picture"];
            echo "<img  height='50' src='$picture'>";
        }
        echo "</td>";
        echo "<td>";
        while ($clientRow = $clientsResult->fetch_assoc()) {
            array_push($clients, $clientRow["id"]);
            $client = $clientRow["name"];
            echo "$client, ";
        }
        echo "</td>";
        echo "<td>";
        while ($employeeRow = $employeesResult->fetch_assoc()) {
            array_push($employees, $employeeRow["id"]);
            $employee = "${employeeRow['firstname']} ${employeeRow['lastname']}";
            echo "$employee, ";
        }
        echo "</td>";
        echo "<td>";
        while ($optional_employeeRow = $optional_employeesResult->fetch_assoc()) {
            array_push($optional_employees, $optional_employeeRow["id"]);
            $optional_employee = "${optional_employeeRow['firstname']} ${optional_employeeRow['lastname']}";
            echo "$optional_employee, ";
        }
        echo "</td>";

        echo "<td>";
        $modal_title = "Edit Dynamic Project";
        $modal_name = $name;
        $modal_company = $company;
        $modal_description = $description;
        $modal_color = $color;
        $modal_start = $start;
        $modal_end = $end; // Possibilities: ""(none);number (repeats); Y-m-d (date)
        $modal_status = $status; // Possibiilities: "ACTIVE","DEACTIVATED","DRAFT","COMPLETED"
        $modal_priority = $priority;
        $modal_id = $id;
        $modal_pictures = $pictures;
        $modal_parent = $parent; //default: "none" or ""
        $modal_clients = $clients; //array of ids
        $modal_owner = $ownerId;
        $modal_employees = $employees;
        $modal_optional_employees = $optional_employees;
        $modal_series = "";
        require "dynamicProjects_template.php";
        echo "</td>";
        echo "</tr>";
    }
    ?>
</tbody>
</table>





<!-- /BODY -->
<?php
bodyEnd : //to stop loader when displaying an error
include 'footer.php'; ?>