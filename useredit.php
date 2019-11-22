<?php

$sCardId = filter_input(INPUT_GET, 'cardId');

printHeader($sCardId);
printEditor($sCardId);
printFooter();

function printHeader(string $sCardId): void {
    print "<html>"
            . "  <head>\n"
            . "    <title>Properties for card id $sCardId</title>\n"
            . "  </head>\n"
            . "  <body>\n";
}

function printEditor(string $sCardId): void {
    print "    <form type='post' action='beerService.php'>\n"
            . "      <table>\n"
            . "        <tr><th>Card Id</th><td><input type='text' name='cardId' value='$sCardId' readonly='true'/></td></tr>\n"
            . "        <tr><th>Name</th><td><input type='text' name='name' size='40'/></td></tr>\n"
            . "        <tr><th>Department</th><td><input type='text' name='department' size='20'/></td></tr>\n"
            . "      </table>\n"
            . "      <input type='hidden' name='action' value='updateUser'/>\n"
            . "      <input type='Submit' value='Submit'/>\n"
            . "    </form>";
}

function printBeers() {
    
}

function printFooter(): void {
    print "  </body>\n" . 
            "</html>";
}
