<?php
ini_set('memory_limit', '-1');
ini_set('display_errors', true);

require_once 'paramount.com.pk/ParamountScraper.php';
require_once 'oup.com.pk/OUPScraper.php';
require_once 'linkshop.pk/LinkShopScraper.php';

//$url_export_map = [
//    'index.asp' => 'everything',
//];
//
//foreach ($url_export_map as $url => $exportFile) {
//    $paramountScraper = new ParamountScraper('http://paramountbooks.com.pk');
//    $paramountScraper->loadISBNS("./paramount.com.pk/{$exportFile}_ISBN");
//    $productsCount    = $paramountScraper->fetchProductUrls($url);
//    echo 'PRODUCTS FOUND: ', $productsCount, "\n";
//    $paramountScraper->startScraping("./paramount.com.pk/{$exportFile}");
//}
//$paramountScraper->scrapeRemainder("./paramount.com.pk/{$exportFile}");
//$paramountScraper->exportFailedLinks("./paramount.com.pk/failed/failed_links");

//$url_export_map = [
//    '' => 'everything',
////    'school-textbooks.html' => 'test',
//];

//foreach ($url_export_map as $url => $exportFile) {
//    $oupScraper = new OUPScraper('https://oup.com.pk');
//    $oupScraper->loadISBNS('everything_ISBN');
//    $oupScraper->fetchProductUrls($url);
//    $oupScraper->startScraping($exportFile);
////    $oupScraper->testFetch($url);
//}
//$oupScraper->scrapeRemainder($exportFile, array_sum(array_values($oupScraper->toBeVisited)));
//$oupScraper->exportFailedLinks("./oup.com.pk/failed/failed_links");

$url_export_map = [
    'all-books' => 'everything',
];

foreach ($url_export_map as $url => $exportFile) {
    $LSScraper = new LinkShopScraper('https://www.linkshop.pk');
    $LSScraper->loadISBNS('everything_ISBN');
    $LSScraper->fetchProductUrls($url);
//    $LSScraper->startScraping($exportFile);
}

$LSScraper->scrapeRemainder($exportFile, array_sum(array_values($LSScraper->toBeVisited)));
$LSScraper->exportFailedLinks("./{$LSScraper->currentDIR}/failed/failed_links");
