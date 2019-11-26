<?php

include('beer.php');
$sCardId = filter_input(INPUT_GET, 'cardId');

if(!isset($sCardId)) {
    $sCardId = filter_input(INPUT_POST, 'cardId');
}

$sName = filter_input(INPUT_GET, 'name');
$sDepartment = filter_input(INPUT_GET, 'department');

$oDb = new Database();

if(isset($sCardId) && isset($sDepartment) && isset($sName)) {
    $oDb->updateUser($sCardId, $sName, $sDepartment);
}
$oUser = $oDb->getUser($sCardId);

printHeader($oUser->cardId);
printEditor($oUser);
printBeers($oUser->beers);
printFooter();

function printHeader(string $sCardId): void {
    print "<html>"
            . "  <head>\n"
            . "    <title>Properties for card id $sCardId</title>\n"
            . "    <link rel=\"stylesheet\" type=\"text/css\" href=\"//cdn.datatables.net/1.10.20/css/jquery.dataTables.min.css\">\n"
            . "    <link rel=\"stylesheet\" type=\"text/css\" href=\"style.css\">\n"
            . "    <script type=\"text/javascript\" src=\"https://code.jquery.com/jquery-3.3.1.js\"></script>\n"
            .  "   <script type=\"text/javascript\" src=\"https://cdn.datatables.net/1.10.20/js/jquery.dataTables.min.js\"></script>\n"
            . "    <script type=\"text/javascript\">\n"
            . "      $(document).ready(function () {\n"
            . "        $('#userlog').DataTable({\n"
            . "          \"ordering\": false,\n"
            . "          \"searching\": false,\n"
            . "          \"bPaginate\": false,\n"
            . "          \"bInfo\": false,\n"
            . "          'columns': [\n"
            . "            null,\n"
            . "            null,\n"
            . "            null,\n"
            . "            null\n"
            . "          ],\n"
            . "        });\n"
            . "      });\n"
            . "     </script>\n"
            . "  </head>\n"
            . "  <body>\n"
            . "    <div class='topnav'>\n"
            . "      <a href='log.html'>Beer log</a>\n"
            . "      <a href='users.html'>Top user list</a>\n"
            . "      <a href='charts.html'>Top user chart</a>\n"
            . "      <a href='charts.html'>Tap distribution</a>\n"
            . "      <a class='active' href=''>User edit</a>\n"
            . "   </div>\n";
}

function printEditor(ExtendedUser $oUser): void {
    print "    <form type='POST' class='center' action='useredit.php'>\n"
            . "        <table class='center'>\n"
            . "          <tr><th style='text-align:left'>Card Id</th><td><input type='text' name='cardId' value='" . $oUser->cardId . "' readonly='true' size='25'/></td></tr>\n"
            . "          <tr><th style='text-align:left'>Name</th><td><input type='text' name='name' size='25' value='" . $oUser->name . "'/></td></tr>\n"
            . "          <tr><th style='text-align:left'>Department</th><td><input type='text' name='department' size='25' value='" . $oUser->department . "'/></td></tr>\n"
            . "          <tr><td><input type='Submit' value='Save changes'/></td><td>&nbsp;</td></tr>\n"
            . "        </table>\n"
            . "    </form>";
}

function printBeers(array $aBeers): void {
    $str =  "    <table id='userlog' class=\"display\" style=\"width:50%\">\n";
    $str .= "      <thead><tr><th>Beer id</th><th>Tap</th><th>Volume</th><th>Timestamp</th></tr></thead>\n";
    foreach($aBeers as $oBeer) {
        $str .= "      <tr><td>" . $oBeer->id . "</td><td>" . $oBeer->tap . "</td><td>" . $oBeer->volume . "</td><td>" . $oBeer->getFormattedTimestamp() . "</td></tr>\n";
    }
    $str .= "    </table>\n";
    print $str;        
}

function printFooter(): void {
    print "  </body>\n" .
            "</html>";
}
