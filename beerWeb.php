<?php


print getHeader();

print getFooter();

function getHeader(string $sTitle): string {
    return ""
            . "<html>"
            . "\t<head>\n"
            . "\\tt<title>$sTitle</title>\n"
            . "\\tt<link rel='stylesheet' type='text/css' href='https://cdn.datatables.net/1.10.20/css/jquery.dataTables.css'>\n"
            . "\t<script type='text/javascript' charset='utf8' src='https://cdn.datatables.net/1.10.20/js/jquery.dataTables.js'></script>\n"
            . "\t</head>\n";
    
}

function getFooter(): string {
    return "</html>\n";
}


function dataTable() {
 return "$(document).ready(function() {" 
     . "$('#example').DataTable( {"
     .   " \"ajax\": '../ajax/data/arrays.txt'"
     . "} );\n"
     . "} );\n";
}


function getBeerLog(): string {
    
}