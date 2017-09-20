<?php include 'header.php';
isDynamicProjectAdmin($userID); ?>
<!-- BODY -->
<?php
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["newDynamicProject"])){
    var_dump($_POST["imagesbase64"]);
    

    
}
?>
<br>
<button class="btn btn-default" data-toggle="modal" data-target="#newDynamicProject" type="button"><i class="fa fa-plus"></i></button>
    

<?php
// variables for easy reuse for editing existing dynamic projects
$modal_title = $lang['DYNAMIC_PROJECTS_NEW'];
$modal_name = "";
$modal_company = "";
$modal_description = "No description given";
$modal_color = "#777777";
$modal_start = date("Y-m-d");
$modal_end = ""; // Possibilities: ""(none);number (repeats); Y-m-d (date)
$modal_status = "ACTIVE"; // Possibiilities: "ACTIVE","DEACTIVATED","DRAFT","COMPLETED"
$modal_priority = 3;
$modal_id = ""; // empty => generate new

//not jet implemented:
$modal_pictures = /*test image*/ array("data:image/png;base64,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nHPO0TnnnKPu3burR48eio2NPenn+wapHTp0UEpKit577z39/e9/1/79+4+bjhOKGhoa/CXox8TFxaljx47q1auXJk+eLI/Ho++++05dunTx3z0YMGCA//MbGxt14MAB7dy5U7t27dLu3bu1c+dOlZWVtcSXBAC2RQEAgP+VmJio9PR0paenq1evXjrnnHNO+qBtcXGxCgoKVFBQoD179ui777477mr0tGnTlJKSop07d1pyh06rKi8v9z88PHnyZFVUVOgXv/iFpB+mV6Wlpfk/dunSRcnJyUpOTtZll13mP05JSYl27typrVu3auvWrdqxY4eOHj1q6ssCAMuhAAAISS6XS+eee6769OmjXr16KT09XW3btj3uc7xer/bs2aNvv/1W3377rXbt2qWdO3eqsrLSUOrQ5bt7sG7dOv/vORwOdezYUT169FD37t3VrVs39ejRQwkJCUpISNCQIUMk/XAet27dqi1btmjz5s3au3evqS8FAIyjAAAICb4HSn1LX5577rmKjIw87nOKi4u1bds2bdu2TVu3btW33357xg/aovk1NjZq//792r9/vz7//HP/7yckJKhHjx7q1auXevXqpZ49e6pr167q2rWrrrrqKklSaWmpNm3apLy8PG3atEkFBQUsUwogZFAAAASlsLAw9erVSwMGDND555+vc889178RliTV1dUpLy9Pmzdv1tatW7Vt2zaVlpYaTIxAKSkpUUlJib766itJTc9ydOnSRb169VLv3r3Vp08fderUScOGDdOwYcMkNU0/+uabb7R+/XqtX79e+/fvN/gVAEDzogAACBqdO3fWgAED1L9/f51//vnHzd/3eDz+q72bNm3S9u3bVVNTYzAtWorX69V3332n7777Th9//LEkqW3bturbt68yMjLUt29fpaamaujQoRo6dKgkqbCwUOvXr9fatWu1ceNGpn0BCCoUAAC25XK5dOGFF2rw4MHKzMxUUlKS/88aGhqUl5endevWacOGDdq+fbt/IyygtLRUK1as0IoVKyQ1rUB03nnnqX///urfv7+Sk5M1atQojRo1Sl6vV9u2bdPq1av1z3/+U/n5+WbDA8BZogAAsJW2bdvK5XJJkv76178ed5X/+++/17p167R+/Xpt2LCB+fs4ZeXl5Vq1apVWrVolSerQoYP69++vgQMHql+/furdu7d69+6tO+64Q0VFRf5dkP/dBm8AYEUUAACWl5SUpEsuuURDhw5Venq6nE6npKa53WvWrNHq1auVm5uroqIiw0kRLAoLC7V48WItXrxYTqdT6enpGjx4sAYNGqS0tDT/582ePVurV6/WqlWrlJubS+kEYAsUAACW1KlTJ/+c7J49e/p/v7S0VK1bt1Z4eLhuu+02HtxFs/N6vdqyZYu2bNmi1157TUlJSZo9e7ZiY2MVFhbmf5i4pqZG69at0xdffKHVq1erqqrKdHQAOCkKAADLSEpK0vDhwzVs2DB169bN//tFRUX64osvtGrVKm3btk3vvfeewsPDeYgXRhQVFeno0aOKjY3V5MmT1a1bNw0dOlSZmZm66KKLdNFFF6murk5r167VZ599pq+++orvVQCWQgEAYFRcXJwuvfRSDR8+XH369JHD4ZAk7d271z8ne8eOHYZTAifn8Xj8DxNHRkZqwIABuuSSSzR48GANGTJEQ4YMkcfj0apVq/TZZ59p/fr1amhoMB0bQIijAABocS6XS0OGDNHIkSPVv39///r8RUVF+uyzz7RixQrt3r3bcErg9NTU1Ogf//iH/vGPf8jlcmngwIEaPny4hgwZohEjRmjEiBEqKyvT559/rk8++YRiC8AYCgCAFpOWlqZRo0YpKytLsbGxkppWX1m5cqU+++wzbd68md1YERTq6ur01Vdf6auvvlJUVJSGDh2q4cOHq3///ho7dqzGjh2rHTt26JNPPtHy5cvZZwBAi6IAAGhWrVq10uWXX64rrrjC/zBvXV2dVq5cqaVLl2r9+vWsz4+g5vF4tGzZMi1btkzx8fG67LLL/O+Hnj176q677tJXX32lpUuXau3atfJ6vaYjAwhyFAAAzeLcc8/V6NGjNWzYMEVGRkqS8vPztXjxYq54ImSVlZVpwYIFWrBggXr27KkrrrhCWVlZuvTSS3XppZf6lx9dsmSJysrKTMcFEKQoAAACJioqSsOHD9fVV1/tv9p/5MgRLVmyhDnPwP+xY8cO7dixQ3/605/8z8QMHDhQd9xxh2699VZ9+eWXWrRokTZt2mQ6KoAgQwEAcNY6deqka665RldccYXcbrckafv27Vq0aJFWrFjBEojAv+CbErdy5Up16NBBo0aN0pVXXunfX6CgoEALFy7U0qVL2WgMQEBQAACcEYfDoQsuuEDXXXedBg4cKKfTKY/Ho48//lgfffQRV/uBM1BYWKjXXntNb7zxhi6++GKNHj1affv21X333aeJEyfq448/1oIFC9j1GsBZoQAAOC2RkZHKysrSuHHj1LVrV0lNy3cuWLBAH3/8MbufAgFQV1fn318gLS1N1157rS6//HL99Kc/1XXXXacvv/xS8+fPV15enumoAGyIAgDglMTFxWns2LEaM2aM4uLiJEl5eXmaP3++vvzyS1YuAZpJfn6+ZsyYoddee01XX321rrnmGl1yySW65JJLtGPHDr377rv64osveA8COGUUAAD/UlJSkq6//npdeeWVioqKUn19vT799FPNnz+faT5ACyovL9ff/vY3vfvuu7r00kt13XXXqWfPnvqv//ov7d+/X++++64++eQT1dbWmo4KwOIoAABOKiIiQo888oiGDRumsLAweTweffDBB3rvvfeYfwwYVFdXp08//VSffvqpBgwYoPHjx+uCCy7Q/fffr1tvvVXz58+Xy+UyHROAhVEAABwnNTVVkpSSkqKUlBSVl5frww8/1Icffqjy8nLD6QAca926dVq3bp169uypCRMm6OKLL9akSZP8m+v59uAAgGNRAABIatq465ZbblFmZqYkqb6+Xn/605+0ZMkSlh4ELG7Hjh168sknlZKSogkTJmjkyJGSpNtvv12tWrXS+++/r4qKCsMpAVgFBQAIcenp6ccN/A8fPqw2bdpo7969+uCDDwynA3A69u3bp+nTp8vlcunyyy9XWFiYbrrpJo0bN04LFizQvHnzKAIAKABAqOrWrZsmTpyoQYMGSZKKi4s1Z84cbdu2TS+88IIaGxsNJwRwpqqrqyVJb7zxhtq3b6+RI0fqhhtu0LXXXqt58+Zp3rx5/s8BEHooAECI6dixo2677TYNHz5cTqdTJSUl+tvf/qa///3vqqurU1pamumIAAKksrJSf/vb3/T222/rpptu0k9+8hPdcsstGjNmjObMmaOFCxeyUzcQgigAQIho27atbrnlFv3kJz+Ry+VSRUWF5syZow8//JABABDkiouLNXPmTM2dO1e33367Lr30Ut11110aN26c3nzzTS1dulQNDQ2mYwJoIeE9e/YM6AF79Ojh/2jFtYi7d+8uSerZs6cC/bUHwrGvnxXzHcuq+Y49x2VlZYbTnMj3unXv3r1FXsOIiAiNHDlSl19+uVwul2pra7VkyRItW7ZMHo/Hv+qPj29337S0NMueY7fbLanpfWLFaQzHnuO9e/caTnMi388Zq/4cbN++vSQpMTHRkvmkpmxS02sZHx9vOM2JfK/byf4tmTdvnlavXq0xY8aoT58+evDBB3XzzTdrwYIF2rhxY4vks/q/xTExMZKaftZYMZ8k/93aHj16WHLZV6uf45b+t/hsNEc+R2VlJRN9AQAAgBARHuidPI9tKVbcJZR8Z8+X0er5JGtmbIl8brdbCQkJ/jXAjxw5opKSklO6K5eWlua/mmTF10/64TXcv3+/jhw5YjjNifgePDtJSUmKi4uTZM180g+vYXl5uSU3xjudc+xwONSqVSu1a9dOERERamxsVGVlpQ4dOqS6ujrj+Uxo1aqVkpOTJVkzn/TDa1hXV6f8/HzDaU5k9XNs9XxS8463HEOGDAnoHYDp06crIyNDU6dOVV5eXiAPHRCTJk3ShAkTlJOTozlz5piOc4KMjAxNnz5deXl5mjp1quk4J7V06VJJ8q8zbTVPPfWUMjMzlZ2drdzcXNNxTjBhwgRNmjRJc+bMUU5OTkCPnZqaqrvuuksXXnihpKYfGq+88spp3dZPS0vTyy+/rPz8fN19990BzRco8+fPl9vt1rhx41RVVWU6zgmmTZumrKwsPf3001q+fLnpOCcYM2aMpkyZooULF2rWrFmm45wgMTFRb775poqLi3XLLbeYjnNSb775phITE3XLLbeouLjYdJwTTJkyRWPGjNGsWbO0cOHCU/r/uFwujR07VjfffLPcbrdqamr03nvv6Z133gn4XiBZWVmaNm2ali9frqeffjqgxw4Et9ut+fPnq6qqSuPGjTMd56RefvllpaWl6e6777ZkAbD6eCszM1NPPfWUcnNzlZ2dbTrOSTXneIuHgIEg4Ha7dfPNN2vcuHEKCwtTcXGxcnJytGLFCpbzBHBK6urqNG/ePH3yySe6+eabNXr0aN10000aMWKE/vSnP2nlypWmIwIIEAoAYGMOh0MjRozQpEmT1LZtW9XU1GjOnDl6++23WdkHwBkpLy/X7Nmz9dFHH2ny5Mnq37+/srOztXHjRv3xj39UQUGB6YgAzhIFALCp7t27a8qUKerVq5ckafXq1XrppZe0f/9+w8kABIOCggJNmzZNQ4cO1T333KN+/frppZde0gcffKA333zTktPvAJwaCgBgM1FRUZo4caKuueYahYWFaf/+/Zo9e7Yln3cAYH+rVq3SmjVrdMMNN+inP/2prr/+eg0fPlyzZ89mWhBgUxQAwEYuuugi/fznP1f79u1VU1OjN954Q++++26zrdQBAJJUU1Ojv/zlL/r73/+u++6777iFFmbNmmXJlZAA/DgKAGADiYmJ+vnPf64hQ4ZIktavX69Zs2Zp3759hpMBCCWFhYXKzs7WZZddpsmTJyszM1OvvPKK3nzzTb333nvsJgzYBAUAsDCn06lrrrlGd9xxh6KiolRWVqaXX35Zn376qeloAELY559/rrVr1+qOO+7Q1VdfrTvvvFNZWVmaMWOGtm/fbjoegH+DAgBYVKdOnTR16lRlZGSosbFRH3/8sV555RVLbnwFIPRUVVVp1qxZ+uSTT/TAAw+oW7dumjlzpubNm6c33njjlDYeBGCG03QAAMdzOp366U9/qhdffFEZGRkqLCzUL3/5S82YMYPBPwDL2bZtm+677z698cYb8nq9mjBhgl588UX/CmUArIcCAFhIamqqZs6cqbvuuksul0sLFizQXXfddVo7+QJAS6uvr9cbb7yh++67Tzt27FDnzp01Y8YM3X333YqKijIdD8D/QQEALMB31f+FF15Qenq69u3bp4ceekgvvPCCPB6P6XgAcEry8/N1//33KycnR/X19br++uv14osvKj093XQ0AMegAACGJSUl6Xe/+53uuusuhYeH6/3339c999yjvLw809EA4LQ1NDRozpw5uvfee7V9+3alpKRoxowZuu222xQWFmY6HgBRAACjRo4cqZdffln9+vVTcXGxpk2bppdeekk1NTWmowHAWdmzZ48eeOABvfHGG5KkW265RX/4wx/UuXNnw8kAsAoQYMjFF1+sTp06SZKWLVumF154QVVVVYZTAUDgNDQ06I033lBubq4eeeQR9ezZkx2EAQvgDgDQwjp27CipaZnP8vJyPfnkk3rmmWcY/AMIWtu3b9e9996rDz/8UBEREbriiiskSS6Xy3AyIDRRAIAWEhYWpttvv11XXXWVJKmoqEh33323vvjiC8PJAKD5eTwe/fGPf1R2draqq6slSZmZmTr//PMNJwNCDwUAaAFJSUl67rnndNNNN6mxsVFS006apaWlhpMBQMtas2aNXnvtNUlSZGSknn76aU2cOJEHhIEWRAEAmtnQoUP10ksv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%E2%80%A6%E2%80%A6");

$modal_clients = array();
$modal_owner = "";
$modal_employees = array();
$modal_optional_employees = array();
$modal_pictures = array();

$modal_parent = "";

$modal_series = "";
?>
<!-- new dynamic project modal -->
<form method="post" autocomplete="off" id="newProjectForm">
<input type="hidden" name="id" value="<?php echo $modal_id ?>">
    <div class="modal fade" id="newDynamicProject" tabindex="-1" role="dialog" aria-labelledby="newDynamicProjectLabel">
        <div class="modal-dialog" role="form">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title" id="newDynamicProjectLabel">
                        <?php echo $modal_title; ?>
                    </h4>
                </div>
                    <!-- modal body -->
                    <!-- tab buttons -->
                    <ul class="nav nav-tabs">
                        <li class="active"><a data-toggle="tab" href="#newProjectBasics">Grundlagen*</a></li>
                        <li><a data-toggle="tab" href="#newProjectDescription">Projektbeschreibung*</a></li>
                        <li><a data-toggle="tab" href="#newProjectAdvanced">Erweitert</a></li>
                        <li><a data-toggle="tab" href="#newProjectSeries">Serie</a></li>
                    </ul>
                    <!-- /tab buttons -->
                    <div class="tab-content">
                        <div id="newProjectBasics" class="tab-pane fade in active">
                            <div class="modal-body">
                            <!-- basics -->
                            <div class="well">
                                <label>Projektname*:</label>
                                <input class="form-control" type="text" name="name" required value="<?php echo $modal_name; ?>">
                                <label>Mandant*:</label>
                                <select class="form-control js-example-basic-single" name="company" required onchange="showClients(this.value, 0,'#newDynamicProjectClients')">
                                    <option value="">...</option>
                                    <?php 
                                    $result = $conn->query("SELECT * FROM $companyTable WHERE id IN (".implode(', ', $available_companies).") ");
                                    while ($row = $result->fetch_assoc()) {
                                        $companyID = $row["id"];
                                        $companyName = $row["name"];
                                        $selected = $companyID == $modal_company && $modal_company != "" ? "selected":"";
                                        echo "<option $selected value='$companyID'>$companyName</option>";
                                    }
                                    ?>
                                </select>
                                <label>Kunde*:</label>
                                <select id="newDynamicProjectClients" class="form-control js-example-basic-single" name="client" multiple="multiple" required>
                                    <option>Zuerst Mandant auswählen</option>
                                    <?php 
                                        foreach ($modal_clients as $client) {
                                            echo "<option>$client</option>";
                                        }
                                    ?>
                                </select>
                            </div>
                            <div class="well">
                                <label>Besitzer*:</label>
                                <select class="form-control js-example-basic-single" name="owner" required>
                                    <?php
                                    $result = $conn->query("SELECT * FROM UserData");
                                    while ($row = $result->fetch_assoc()) {
                                        $x = $row['id'];
                                        $selected = $x == $userID ? "selected":"";
                                        $name = "${row['firstname']} ${row['lastname']}";
                                        echo "<option value='$x' $selected >$name</option>";
                                    }
                                    ?>
                                </select>
                                <label>Mitarbeiter*:</label>
                                <select class="form-control js-example-basic-single" name="employees" multiple="multiple" required>
                                    <?php
                                    $result = $conn->query("SELECT * FROM UserData");
                                    while ($row = $result->fetch_assoc()) {
                                        $x = $row['id'];
                                        $selected = $x == $userID ? "selected":"";
                                        $name = "${row['firstname']} ${row['lastname']}";
                                        echo "<option value='$x' $selected >$name</option>";
                                    }
                                    ?>
                                </select>
                                <label>Optionale Mitarbeiter:</label>
                                <select class="form-control js-example-basic-single" name="optionalemployees" multiple="multiple">
                                    <?php
                                    $result = $conn->query("SELECT * FROM UserData");
                                    while ($row = $result->fetch_assoc()) {
                                        $x = $row['id'];
                                        $name = "${row['firstname']} ${row['lastname']}";
                                        echo "<option value='$x' $selected >$name</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <!-- /basics -->
                            </div>
                        </div>
                        <div id="newProjectDescription" class="tab-pane fade">
                            <div class="modal-body">
                            <!-- description -->
                            <div class="well">
                                <label>Projektbeschreibung*:</label>
                                <textarea class="form-control" style="max-width: 100%" rows="10" name="description" required><?php echo $modal_description; ?></textarea>
                                <label>Bilder auswählen:</label><br>
                                <label class="btn btn-default" role="button">Durchsuchen...
                                <input type="file" name="images" multiple class="form-control" style="display:none;" id="newProjectImageUpload" accept=".jpg,.jpeg,.png"></label>
                                <div id="newProjectPreview"></div>
                            </div>
                            <!-- /description -->
                            </div>
                        </div>
                        <div id="newProjectAdvanced" class="tab-pane fade">
                            <div class="modal-body">
                            <!-- advanced -->
                            <div class="well">
                                <label>Priorität*:</label>
                                <select class="form-control js-example-basic-single" name="priority" required>
                                    <option value="example">Sehr niedrig</option>
                                    <option value="example">Niedrig</option>
                                    <option value="example" selected>Normal</option>
                                    <option value="example">Hoch</option>
                                    <option value="example">Sehr hoch</option>
                                </select>
                                <label>Status*:</label>
                                <div class="input-group">
                                <select class="form-control" name="status" required>
                                        <option value="example" <?php echo $modal_status == 'DEACTIVATED' ? "selected":"" ?> >Deaktiviert</option>
                                        <option value="example" <?php echo $modal_status == 'DRAFT' ? "selected":"" ?> >Entwurf</option>
                                        <option value="example" <?php echo $modal_status == 'ACTIVE' ? "selected":"" ?> >Aktiv</option>
                                        <option value="example" <?php echo $modal_status == 'COMPLETED' ? "selected":"" ?> >Abgeschlossen</option>
                                    </select>
                                    <span class="input-group-addon text-warning"> % abgeschlossen </span>
                                    <input type='number' class="form-control" name='completed' value="0" min="0" max="100" step="10" required/>
                                </div>
                                <label>Farbe:</label>
                                <input type="color" class="form-control" value="<?php echo $modal_color; ?>" name="color">
                                <label>Überprojekt:</label>
                                <select class="form-control js-example-basic-single" name="parent" required>
                                    <option value="none" selected>Keines</option>
                                </select>
                            </div>
                            <!-- /advanced -->
                            </div>
                        </div>
                        <div id="newProjectSeries" class="tab-pane fade">
                            <div class="modal-body">
                            <!-- series -->

                                <div class="well">
                                    <label>Start:</label>
                                    <input type='text' class="form-control datepicker" name='localPart' placeholder='Startdatum' value="<?php echo $modal_start; ?>" />
                                    <label>Ende:</label><br>
                                    <?php if($modal_end == ""): ?>
                                        <label><input type="radio" name="endradio" value="no" checked> Ohne</label><br>
                                    <?php else: ?>
                                        <label><input type="radio" name="endradio" value="no"> Ohne</label><br>
                                    <?php endif; ?>
                                    <?php if(preg_match("/\d{4}-\d{2}-\d{2}/",$modal_end)): ?>
                                        <input type="radio" name="endradio" value="date" checked><label><input type='text' class="form-control datepicker" name='enddate' placeholder="Enddatum" value="<?php echo $modal_end; ?>" /></label><br>
                                    <?php else: ?>
                                        <input type="radio" name="endradio" value="date"><label><input type='text' class="form-control datepicker" name='enddate' placeholder="Enddatum" value="<?php echo date('Y-m-d');?>" /></label><br>
                                    <?php endif; ?>
                                    <?php if(preg_match("/^\d+$/",$modal_end)): ?>
                                        <input type="radio" name="endradio" value="number" checked><label><input type='number' class="form-control" name='endnumber' placeholder="Wiederholungen" value="<?php echo $modal_end ?>"></label>
                                    <?php else: ?>
                                        <input type="radio" name="endradio" value="number"><label><input type='number' class="form-control" name='endnumber' placeholder="Wiederholungen"></label>
                                    <?php endif; ?>
                                </div>

                                <div class="well">
                                    
                                        <label>Einmalig</label><br>
                                        <input type="radio" checked>Keine Wiederholungen<br>

                                        <label>Täglich</label><br>
                                        <input type="radio">Alle <label><input class="form-control" type="number"></label> Tage<br>
                                        <input type="radio">Montag bis Freitag<br>


                                        <label>Wöchentlich</label><br>
                                        <input type="radio">Alle <label><input class="form-control" type="number"></label> Wochen am <label><select class="form-control" name="day" required>
                                        <option value="monday">Montag</option>
                                        <option value="tuesday">Dienstag</option>
                                        <option value="wednesday">Mittwoch</option>
                                        <option value="thursday">Donnerstag</option>
                                        <option value="Friday">Freitag</option>
                                        <option value="Saturday">Samstag</option>
                                        <option value="Sunday">Sonntag</option>
                                </select></label> <br>


                                        <label>Monatlich</label><br>
                                        <input type="radio">am <label><input class="form-control" type="number"></label> Tag jedes <label><input class="form-control" type="number"></label>. Monats<br>
                                        <input type="radio">am <label><input class="form-control" type="number"></label> <label><select class="form-control" name="day" required>
                                        <option value="monday">Montag</option>
                                        <option value="tuesday">Dienstag</option>
                                        <option value="wednesday">Mittwoch</option>
                                        <option value="thursday">Donnerstag</option>
                                        <option value="Friday">Freitag</option>
                                        <option value="Saturday">Samstag</option>
                                        <option value="Sunday">Sonntag</option>
                                </select></label> jedes <label><input class="form-control" type="number"></label> monats<br>

                                        
                                        <label>Jährlich</label><br>
                                        <input type="radio">jeden <input value="1">. <input value="Jänner"><br>
                                        <input type="radio">am <input value="1">. <input value="Montag"> im september<br>

                                    
                                </div>


                            <!-- /series -->
                            </div>
                        </div>
                    </div>
                    <!-- /modal body -->
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo $lang['CANCEL']; ?></button>
                    <button type="submit" class="btn btn-warning" name="newDynamicProject"><?php echo $lang['SAVE']; ?></button>
                </div>
            </div>
        </div>
    </div>
</form>
<!-- /new dynamic project modal -->
<script>
function showClients(company, client, targetSelector){
    $.ajax({
        url:'ajaxQuery/AJAX_getClient.php',
        data:{companyID:company, clientID:client},
        type: 'get',
        success : function(resp){
        $(targetSelector).html(resp);
        },
        error : function(resp){},
    });
}

$("#newProjectImageUpload").change(function(event){
    var files = event.target.files;
    //$("#newProjectPreview").html(""); //delete old pictures
    // Loop through the FileList and render image files as thumbnails.
    for (var i = 0, f; f = files[i]; i++) {
      // Only process image files.
      if (!f.type.match('image.*')) {
        continue;
      }
      var reader = new FileReader();
      // Closure to capture the file information.
      reader.onload = (function(theFile) {
        return function(e) {
          // Render thumbnail.
          var span = document.createElement('span');
          span.innerHTML = 
          [
            '<img class="img-thumbnail" style="width:49%;margin:0.5%" src="', 
            e.target.result,
            '" title="', escape(theFile.name), 
            '"/>'
          ].join('');
          $("#newProjectPreview").append(span);
          $("#newProjectPreview img").unbind("click").click(removeImg)
        };
      })(f);
      // Read in the image file as a data URL.
      reader.readAsDataURL(f);
    }
    
  });

$(function(){
    $("#newProjectPreview img").click(removeImg)
})

function removeImg(event){
   $(event.target).remove()
}

$("#newProjectForm").submit(function(event){
    $("#newProjectPreview").find("input").remove()
    $("#newProjectPreview").find("img").each(function(index,elem){
        $("#newProjectPreview").append("<input type='hidden' value='" + getImageSrc(elem) + "' name='imagesbase64[]'>")
    })
})

function getImageSrc(img){
    var c = document.createElement("canvas");
    c.width = img.width;
    c.height = img.height;
    var ctx = c.getContext("2d");
    ctx.drawImage(img, 10, 10);
    return c.toDataURL();
}

</script>






<!-- /BODY -->
<?php include 'footer.php'; ?>