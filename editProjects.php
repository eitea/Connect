<!DOCTPYE html>
<header>
</header>

<body>
  <form method='post'>
    <p><?php echo $lang['PROJECT']; ?></p>
    <br><br>

    <table>
      <tr>
        <th>Delete</th>
        <th>Name</th>
        <th>Hours</th>
        <th>Price</th>
      </tr>

      <?php
      $clientID = "0";
      if(isset($_GET['customerID'])){
        $clientID = $_GET['customerID'];
      }
      $query = "SELECT * FROM $projectTable WHERE clientID = $clientID";
      $result = mysqli_query($conn, $query);
      if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
          $i = $row['id'];
          echo "<tr>";
          echo "<td><input type='checkbox' name='indexProject[]' value= ".$i."></td>";
          echo "<td>".$row['name']."</td>";
          echo "<td><input name='editHoursProject[]' type='number' step='any' value='".$row['hours']."'></td>";
          echo "<td><input name='editPriceProject[]' type='number' step='any' value='". $row['hourlyPrice'] ."'></td>";
          echo "</tr>";

          echo "<input name='editingIndexProject[]' type='text' value='$i' style='display:none;'>";
        }
      }
    ?>
    </table><br><br>

    <div class="createEntry">
      Options:
      <input type="submit" class="button" name="deleteProject" value="Delete">
      <input type="submit" class="button" name="saveProject" value="Save">
      <br><br>
      Create:
      <input type="text" name="nameProject" placeholder="name" onkeydown='if(event.keyCode == 13) return false;'>
      <input type="text" name="hoursProject" size=4 placeholder="hours" onkeydown='if(event.keyCode == 13) return false;'>
      <input type="number" step="any" type="text" name="hourlyPriceProject" placeholder = "€" style="width:50px;">
      <input type="submit" class="button" name="createProject" value="+"/>

    </div><br>
  </form>
</body>
y>
