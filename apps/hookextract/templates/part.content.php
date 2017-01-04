<div id="page3">
<form id="jobSettings" action="/timers" method="post">
  <?php 
  $params = $_;
   if($params['everyday']){
   	?>
   	<h3>Already defined jobs:</h3>
    <table>
    <tr><td colspan="1">Label:</td><td colspan="2"><p><?php echo $params['label']?></p></td>
    <td colspan="1"><button id="setActive" <?php if($params['active'] != "-"){echo "disabled";}?>>Activate</button>
    <input type="button" id="setDeactive" value="Deactivate"  <?php if($params['active'] == "-"){echo "disabled";}?>></td>
    </tr>
    <tr><td style="width: 100px;">
    <span>Recurrency:</span>
    </td><td style="width: 100px;">
      <span><?php echo $params['everyday'] ?></span>
    </td>
    <td style="width: 100px;"><span>Begin at <?php echo $params['begin']?></span></td>
    <td style="width: 100px;"><span><?php if($params["plushour"] == 'on'){ 	?>
    	  Plus 1 try 
       <?php   }  	?>
     </span></td>
    </tr>
    <tr><td colspan="4">Selection Interval: <?php  echo $params["begin_selection"] ?> - 
    	<?php  echo $params["end_selection"] ?>	
    	</td>
    </tr>
    </table>
    <br><br>
   <?php }?>
 <p>Please modify job settings:</p>
 <p>Label:<input type="text" name="label" id="label"></input></p>
 <p>Please select from and to date:</p>
<p>Begin:<input type="text" name="begin_selection" id="begin_selection"></input></p>
<p>End:<input type="text" name="end_selection" id="end_selection"></input></p>
    <table>
    <tr><td><p>Recurrency:</p>
    </td><td>
    </td><td>
    </td></tr>
    
    <tr><td></td>
    <td><p>Every day:</p></td>
    <td><input name="everyday" value="1" type="radio"></input></td>
    <td colspan="4"><span>Start time:</span><select name="begin" id="begin">
    <option>0:00</option>
    <option>1:00</option>
    <option>2:00</option>
    <option>3:00</option>
    <option>4:00</option>
    <option>5:00</option>
    <option>6:00</option>
    <option>7:00</option>
    <option>8:00</option>
    <option>9:00</option>
    <option>10:00</option>
    <option>11:00</option>
    <option>12:00</option>
    <option>13:00</option>
    <option>14:00</option>
    <option>15:00</option>
    <option>16:00</option>
    <option>17:00</option>
    <option>18:00</option>
    <option>19:00</option>
    <option>20:00</option>
    <option>21:00</option>
    <option>22:00</option>
    <option>23:00</option>
    </select>
    </td>
    <td colspan="3">
    <span>After 1hr try</span><input name="plushour" id="plushour" type="checkbox"></input>
    </td>
    </tr>
    <tr><td></td>
    <td>Every week:</td>
    <td><input name="everyday" value="2" type="radio"></input></td>
    <td>Sun<input type="checkbox" name="week1" id="week1"></td>
    <td>Mon<input type="checkbox" name="week2" id="week2"></td>
    <td>Tue<input type="checkbox" name="week3" id="week3"></td>
    <td>Wed<input type="checkbox" name="week4" id="week4"></td>
    <td>Thu<input type="checkbox" name="week5" id="week5"></td>
    <td>Fri<input type="checkbox" name="week6" id="week6"></td>
    <td>Sat<input type="checkbox" name="week7" id="week7"></td>
    </tr>
    
    </table>

    <button name="submit" id="submit_timers">Save</button>
</form>
</div>
<div id="page2">
    <form id="fileUpload" action="/owncloud/index.php/apps/hookextract/upload" method="post" enctype="multipart/form-data" >
        <label for="filepath">Upload Excel file:</label>
        <input type="file" name="filepath" id="filepath" />
        <button type="submit" name="submit" id="submit" >Submit</button>
    </form>
</div>
<div id="page1">
<p>Please select from and to date:</p>
<?php 
$startdate = strtotime("today");
$enddate = strtotime("-1 month", $startdate);

$start_string = date("Y-m-d", $startdate);
$end_string = date("Y-m-d", $enddate);
?>
<p><input type="text" name="from" id="from" value="<?php echo $end_string?>"></input></p>
<p><input type="text" name="to" id="to" value="<?php echo $start_string?>"></input></p>
<p><button id="preselect">Make preselection</button></p>

<div id="echo-result"></div>

<div id="echo-selection"></div>

<div id="iframe">
    <iframe name="iframebox" id="iframebox" ></iframe>
</div>

</div>