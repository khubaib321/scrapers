<?php
ini_set('memory_limit', '-1');
ini_set('display_errors', true);

require_once 'paramount.com.pk/ParamountScraper.php';
require_once 'oup.com.pk/OUPScraper.php';
require_once 'linkshop.pk/LinkShopScraper.php';
require_once 'stationaryx.pk/StationaryXScraper.php';
require_once 'ferozsons.com.pk/FerozsonsScraper.php';

//$url_export_map = [
//    'LoginIndex.asp?cat=06&opt=4&SubCat=06&Title=MEDICAL%20BOOKS&mx2x=8' => 'medical',
//    'LoginIndex.asp?cat=01&opt=4&SubCat=01&Title=BUSINESS&mx2x=8' => 'business',
//    'LoginIndex.asp?cat=02&opt=4&SubCat=02&Title=CHILDREN%20BOOKS&mx2x=8' => 'children books',
//    'LoginIndex.asp?cat=03&opt=4&SubCat=03&Title=COMPUTER%20SCIENCE&mx2x=8' => 'computer science',
//    'LoginIndex.asp?cat=04&opt=4&SubCat=04&Title=ENGINEERING&mx2x=8' => 'engineering',
//    'LoginIndex.asp?cat=05&opt=4&SubCat=05&Title=GENERAL%20INTEREST&mx2x=8' => 'general interest',
//    'LoginIndex.asp?cat=07&opt=4&SubCat=07&Title=SCIENCE&mx2x=8' => 'science',
//    'LoginIndex.asp?cat=08&opt=4&SubCat=08&Title=SOCIAL%20SCIENCES&mx2x=8' => 'social sciences',
//    'LoginIndex.asp?opt=05' => '',
//];
//foreach ($url_export_map as $url => $exportFile) {
//    $paramountScraper = new ParamountScraper('http://paramountbooks.com.pk');
//    $paramountScraper->loadISBNS("{$exportFile}_ISBN");
//    $productsCount    = $paramountScraper->fetchProductUrls($url);
//    $paramountScraper->startScraping($exportFile);
//}
//$paramountScraper->scrapeRemainder($exportFile);
//$paramountScraper->grabProductsFromLists($exportFile);
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
//$oupScraper->scrapeRemainder($exportFile);
//$oupScraper->exportFailedLinks("./oup.com.pk/failed/failed_links");
//$url_export_map = [
//    'all-books' => 'everything',
//];
//
//foreach ($url_export_map as $url => $exportFile) {
//    $LSScraper = new LinkShopScraper('https://www.linkshop.pk');
//    $LSScraper->loadISBNS('everything_ISBN');
//    $LSScraper->fetchProductUrls($url);
////    $LSScraper->startScraping($exportFile);
//}
//
//$LSScraper->scrapeRemainder($exportFile);
//$LSScraper->exportFailedLinks("./{$LSScraper->currentDIR}/failed/failed_links");
//$url_export_map = [
//    'office-supplies?limit=100' => 'office supplies',
//    'school-supplies?limit=100' => 'school supplies',
//    'educational-toys?limit=100' => 'educational toys',
//    'art-x?limit=100' => 'art supplies',
//];
//foreach ($url_export_map as $url => $exportFile) {
//    $sxScraper = new StationaryXScraper('https://stationeryx.pk');
//    $sxScraper->loadISBNS("{$exportFile}_ISBN");
//    $sxScraper->loadSubCategories($url);
//    echo print_r($sxScraper->toBeVisited, 1), $sxScraper->newLine;
//    $sxScraper->scrapeRemainder($exportFile);
//}

$url_export_map = [
    '' => 'ferozsons',
];

foreach ($url_export_map as $url => $exportFile) {
    $fzScraper = new FerozsonsScraper('https://ferozsons.com.pk');
    $fzScraper->loadISBNS("{$exportFile}_ISBN");
    $fzScraper->getCategoryPages($url);
//    echo print_r($fzScraper->toBeVisited, 1), $fzScraper->newLine;
    $fzScraper->scrapeRemainder($exportFile);
}
