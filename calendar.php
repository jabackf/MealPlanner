
<link href="css/meal.css" rel="stylesheet" type="text/css">

<?php
function days_in_month($month, $year)
{
   return $month == 2 ? ($year % 4 ? 28 : ($year % 100 ? 29 : ($year % 400 ? 28 : 29))) : (($month - 1) % 7 % 2 ? 30 : 31);
}

function day_of_the_week($m, $d, $y)
{
   $h = mktime(0, 0, 0, $m, $d, $y);
   return date("N", $h) ;
}

function day_of_the_week_name($m, $d, $y)
{
   $h = mktime(0, 0, 0, $m, $d, $y);
   return date("l", $h) ;
}

function remove_variable_url($varToRemove, $var2)
{
      $newurl="";
      foreach($_GET as $variable => $value){
        if($variable != $varToRemove && $variable != $var2){
           $newurl .= $variable.'='.$value.'&';
        }
      }

      $newurl = rtrim($newurl,'&');

         if ($newurl=="")
         {
            return parse_url($_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'], PHP_URL_PATH);
         }
         else
         {
             return parse_url($_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'], PHP_URL_PATH)."?".$newurl;
         }

}

function GetMonthString($n)
{
    $timestamp = mktime(0, 0, 0, $n, 1, 2005);

    return date("F", $timestamp);
}

function show_printable_calendar_link()
{
    if (empty($_GET['m']))
    {
        echo "<a href='print_cal.php?m=".str_pad(date('m'), 2, "0", STR_PAD_LEFT)."&y=".date('Y')."'>Click here for a printable list of events for this month</a>";
    }
    else
    {
         echo "<a href='print_cal.php?m=".str_pad($_GET['m'], 2, "0", STR_PAD_LEFT)."&y=".$_GET['y']."'>Click here for a printable list of events for this month</a>";
    }
}

//arguments:
//$jscallback - optional argument that specifies the name of a javascript callback function
//to be called when a date is clicked. The callback is passed three arguments: month, day, and year.
function show_calendar($edit, $hoffset, $jscallback="")
{
  echo "<!--- BEGIN CALENDAR -->\n";

  $width=165;  //horizontal space between calendar numbers
  $height=203;  //vertical space between calendar numbers
  $m= date('m');
  $y= date('Y');
  echo "<div name='calendar'>";
  if (!empty($_GET['m']))
  {
     $m=$_GET['m'];
  }
  if (!empty($_GET['y']))
  {
     $y=$_GET['y'];
  }
  $m=str_pad($m, 2, "0", STR_PAD_LEFT);
  $nm=$m+1;
  $ny=$y;
  if ($nm>=13)
  {
     $nm=1;
     $ny=$ny+1;
  }
  $pm=$m-1;
  $py=$y;
  if ($pm<=0)
  {
     $pm=12;
     $py=$py-1;
     if ($py<=0)
     {
        $py=1;
     }
  }
  echo "\n\t<div align=center style='position:relative; left:".$hoffset."'>";

  $url=remove_variable_url("y","m");

  if (strpos($url,"?",0))
  {
     $nurl=$url."&m=".$nm."&y=".$ny;
     $purl=$url."&m=".$pm."&y=".$py;
  }
  else
  {
     $nurl=$url."?m=".$nm."&y=".$ny;
     $purl=$url."?m=".$pm."&y=".$py;
  }
  
  echo "\n\t\t<table width='".$width."' height='".$height."' background='img/calendar.gif' >";
  echo "\n\t\t\t<th colspan=7><font size=4 color=black><b>".GetMonthString($m)." ".$y."</font></th >\n\t\t<tr height=10>\n\t\t\t<td></td>\n\t\t\t</tr><tr>";
  
  $first = day_of_the_week($m, 1, $y);
  if ($first==7)
  {
     $first=0;
  }
  $c=$first;

  for ($i=1; $i<=$first; $i+=1)
  {
      echo "\n\t\t<td></td>";
  }
  for ($i=1; $i<=days_in_month($m, $y); $i+=1)
  {
  
      $col='black';
	  $data_exists=false;
      if (file_exists("cal/".$m.".".str_pad($i, 2, "0", STR_PAD_LEFT).".".$y.".cal"))
      {
         $col="blue";
		 $data_exists=true;
      }
      if ($m==date('m') && $y==date('Y') && $i==date('d'))
      {
          $col="red";
      }
      if ($c>6)
      {
           $c=0;
           echo "\n\t\t\t</tr><tr>";
      }
	  
	  //Show the date on the calendar and the data associated with it
	  echo "\n\t\t\t\t<td style='color:".$col."' class='calendar_date'>";
	  if ($jscallback){
		  echo "<a style='color:".$col."' href='javascript:".$jscallback."(".$m.",".$i.",",$y.");'>";
	  }
      echo $i;
	  if ($jscallback){
		  echo "</a>";
	  }
	  if ($data_exists){
			echo "\n\t\t\t\t\t<div class='date_data'>PlaceHolder Info #1<br>PlaceHolder Info #2<br>PlaceHolder Info #3</div>";
	  }
	  echo "</td>";
      $c=($c+1);
  }

  if ((days_in_month($m, $y)+$first)<=28)
  {
   echo "\n\t\t\t</tr><tr height=24><td></td></tr>";
  }
  if ((days_in_month($m, $y)+$first)<=35)
  {
   echo "\n\t\t\t</tr><tr height=24><td></td></tr>";
  }
  echo "\n\t\t</table>";

  echo "\n\t<a href='http://".$purl."#calendar'>prev</a> - "."<a href='http://".$nurl."#calendar'>next</a>\n</div></div><br>\n";
  echo "<!--- END CALENDAR -->\n";
}

?>
